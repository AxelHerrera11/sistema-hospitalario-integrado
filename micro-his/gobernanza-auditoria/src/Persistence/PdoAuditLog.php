<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Persistence;

use MicroHis\Gobernanza\Domain\AuditEvent;
use MicroHis\Gobernanza\Domain\AuditEventReader;
use MicroHis\Gobernanza\Domain\AuditFilter;
use MicroHis\Gobernanza\Domain\AuditLogger;
use MicroHis\Gobernanza\Domain\AuditPage;
use MicroHis\Gobernanza\Domain\AuditStorageFailure;
use PDO;

/**
 * Adaptador PDO sobre audit_log + audit_chain_state (CENTRAL).
 *
 * Esquema: migraciones 2026_10_19_000200_create_audit_log y
 * 2026_10_20_000200_enrich_audit_log de la rama de Tenants, pendientes de
 * fusión en develop y de validación por el responsable del esquema.
 * Drivers soportados: pgsql (destino) y sqlite (pruebas locales).
 * Todas las sentencias son preparadas; ningún valor del filtro se concatena.
 */
final class PdoAuditLog implements AuditLogger, AuditEventReader
{
    private const COLUMNS = 'event_id, occurred_at, event, actor_id, hospital_id, detail, trace_id, result';

    private readonly string $driver;

    public function __construct(
        private readonly PDO $pdo,
        private readonly AuditChainHasher $hasher = new AuditChainHasher(),
        private readonly AuditRowMapper $mapper = new AuditRowMapper(),
    ) {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->driver = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if (!in_array($this->driver, ['pgsql', 'sqlite'], true)) {
            throw new AuditStorageFailure('audit_driver_unsupported');
        }
    }

    public function append(AuditEvent $event): void
    {
        $owned = !$this->pdo->inTransaction();
        try {
            $owned && $this->pdo->beginTransaction();
            $this->insertChained($event);
            $owned && $this->pdo->commit();
        } catch (AuditStorageFailure $failure) {
            $this->rollback($owned);
            throw $failure;
        } catch (\Throwable) {
            // Sin detalle del driver: puede contener SQL o datos de conexión.
            $this->rollback($owned);
            throw new AuditStorageFailure('audit_write_failed');
        }
    }

    public function search(string $tenantId, AuditFilter $filter): AuditPage
    {
        [$where, $params] = $this->where($tenantId, $filter);
        try {
            $count = $this->pdo->prepare("SELECT COUNT(*) FROM audit_log WHERE {$where}");
            $this->bind($count, $params);
            $count->execute();
            $total = (int) $count->fetchColumn();

            $select = $this->pdo->prepare(
                'SELECT ' . self::COLUMNS . " FROM audit_log WHERE {$where}"
                . ' ORDER BY occurred_at DESC, event_id DESC LIMIT :limit OFFSET :offset',
            );
            $this->bind($select, $params);
            $select->bindValue(':limit', $filter->perPage, PDO::PARAM_INT);
            $select->bindValue(':offset', $filter->offset(), PDO::PARAM_INT);
            $select->execute();
            $events = array_map([$this->mapper, 'toEvent'], $select->fetchAll(PDO::FETCH_ASSOC));
        } catch (\Throwable) {
            throw new AuditStorageFailure('audit_read_failed');
        }

        return new AuditPage($events, $total, $filter->page, $filter->perPage);
    }

    private function insertChained(AuditEvent $event): void
    {
        $lock = $this->driver === 'pgsql' ? ' FOR UPDATE' : '';
        $state = $this->pdo->prepare("SELECT head_hash FROM audit_chain_state WHERE chain_id = 1{$lock}");
        $state->execute();
        $previous = $state->fetchColumn();
        if (!is_string($previous) || strlen(trim($previous)) !== 64) {
            throw new AuditStorageFailure('audit_chain_missing');
        }
        $previous = trim($previous);

        $occurredAt = $event->occurredAt->format(AuditChainHasher::TIME_FORMAT);
        $detail = $this->mapper->detail($event);
        $hash = $this->hasher->hash(
            $previous,
            $event->eventId,
            $occurredAt,
            $event->eventName(),
            $event->actorId,
            $event->tenantId,
            $detail,
            $event->correlationId,
            $event->outcome->value,
        );

        $insert = $this->pdo->prepare(
            'INSERT INTO audit_log (event_id, occurred_at, event, actor_id, hospital_id, detail, trace_id, result, previous_hash, event_hash)'
            . ' VALUES (:event_id, :occurred_at, :event, :actor_id, :hospital_id, :detail, :trace_id, :result, :previous_hash, :event_hash)',
        );
        $insert->execute([
            ':event_id' => $event->eventId,
            ':occurred_at' => $occurredAt,
            ':event' => $event->eventName(),
            ':actor_id' => $event->actorId,
            ':hospital_id' => $event->tenantId,
            ':detail' => $this->hasher->encode($detail),
            ':trace_id' => $event->correlationId,
            ':result' => $event->outcome->value,
            ':previous_hash' => $previous,
            ':event_hash' => $hash,
        ]);

        $advance = $this->pdo->prepare('UPDATE audit_chain_state SET head_hash = :next WHERE chain_id = 1 AND head_hash = :previous');
        $advance->execute([':next' => $hash, ':previous' => $previous]);
        if ($advance->rowCount() !== 1) {
            throw new AuditStorageFailure('audit_chain_conflict');
        }
    }

    /** @return array{0: string, 1: array<string, string>} */
    private function where(string $tenantId, AuditFilter $filter): array
    {
        $clauses = ['hospital_id = :tenant'];
        $params = [':tenant' => $tenantId];
        if ($filter->actorId !== null) {
            $clauses[] = 'actor_id = :actor';
            $params[':actor'] = $filter->actorId;
        }
        if ($filter->module !== null) {
            // "_" es comodín en LIKE: se escapa para que el módulo sea literal.
            $clauses[] = "(event = :module_exact OR event LIKE :module_prefix ESCAPE '!')";
            $params[':module_exact'] = $filter->module;
            $params[':module_prefix'] = str_replace(['!', '_', '%'], ['!!', '!_', '!%'], $filter->module) . '.%';
        }
        if ($filter->action !== null) {
            $clauses[] = $this->actionExpression() . ' = :action';
            $params[':action'] = $filter->action;
        }
        if ($filter->from !== null) {
            $clauses[] = 'occurred_at >= :from';
            $params[':from'] = $filter->from->format(AuditChainHasher::TIME_FORMAT);
        }
        if ($filter->until !== null) {
            $clauses[] = 'occurred_at < :until';
            $params[':until'] = $filter->until->format(AuditChainHasher::TIME_FORMAT);
        }
        if ($filter->outcome !== null) {
            $clauses[] = 'result = :outcome';
            $params[':outcome'] = $filter->outcome->value;
        }
        if ($filter->correlationId !== null) {
            $clauses[] = 'trace_id = :correlation';
            $params[':correlation'] = $filter->correlationId;
        }

        return [implode(' AND ', $clauses), $params];
    }

    /** Acción = texto tras el primer punto de event (el módulo no contiene puntos). */
    private function actionExpression(): string
    {
        return $this->driver === 'pgsql'
            ? "substring(event from position('.' in event) + 1)"
            : "substr(event, instr(event, '.') + 1)";
    }

    /** @param array<string, string> $params */
    private function bind(\PDOStatement $statement, array $params): void
    {
        foreach ($params as $name => $value) {
            $statement->bindValue($name, $value, PDO::PARAM_STR);
        }
    }

    private function rollback(bool $owned): void
    {
        if ($owned && $this->pdo->inTransaction()) {
            try {
                $this->pdo->rollBack();
            } catch (\Throwable) {
                // La conexión ya está inutilizable; el fallo se reporta arriba.
            }
        }
    }
}
