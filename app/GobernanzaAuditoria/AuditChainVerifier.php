<?php

declare(strict_types=1);

namespace App\GobernanzaAuditoria;

use DateTimeImmutable;
use DateTimeZone;
use MicroHis\Gobernanza\Persistence\AuditChainHasher;
use PDO;

/**
 * Verifica la cadena SHA-256 de audit_log: desde el génesis sigue previous_hash, recalcula cada event_hash con
 * AuditChainHasher (el mismo formato de los dos escritores, Tenants y Gobernanza) y termina en la cabeza leída al
 * empezar, así lo que se agregue durante la verificación no cuenta. La cadena es única para CENTRAL; al hospital de la
 * sesión solo se le revela el event_id roto si ese evento es suyo.
 */
final class AuditChainVerifier
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly AuditChainHasher $hasher = new AuditChainHasher(),
    ) {}

    /** @return array{valid: bool, checked: int, total: int, broken: null|array{position: int, event_id: ?string, cause: string}} */
    public function verify(string $hospitalId): array
    {
        $head = trim((string) $this->pdo->query('SELECT head_hash FROM audit_chain_state WHERE chain_id = 1')->fetchColumn());
        $rows = $this->pdo->query('SELECT event_id, occurred_at, event, actor_id, hospital_id, detail, trace_id, result, previous_hash, event_hash FROM audit_log')
            ->fetchAll(PDO::FETCH_ASSOC);
        $next = [];
        foreach ($rows as $row) {
            $next[trim((string) $row['previous_hash'])][] = $row;
        }

        $hash = AuditChainHasher::GENESIS;
        $checked = 0;
        while ($hash !== $head) {
            $candidates = $next[$hash] ?? [];
            if (count($candidates) !== 1) {
                return $this->broken($checked, count($rows), null, $hospitalId, $candidates === [] ? 'eslabon_faltante' : 'bifurcacion');
            }
            $row = $candidates[0];
            $expected = $this->hasher->hash(
                $hash,
                (string) $row['event_id'],
                (new DateTimeImmutable((string) $row['occurred_at']))->setTimezone(new DateTimeZone('UTC'))->format(AuditChainHasher::TIME_FORMAT),
                (string) $row['event'],
                (string) $row['actor_id'],
                (string) $row['hospital_id'],
                json_decode((string) $row['detail'], true) ?? [],
                $row['trace_id'],
                $row['result'],
            );
            if (! hash_equals($expected, trim((string) $row['event_hash']))) {
                return $this->broken($checked, count($rows), $row, $hospitalId, 'hash_no_coincide');
            }
            $hash = $expected;
            $checked++;
        }

        return ['valid' => true, 'checked' => $checked, 'total' => count($rows), 'broken' => null];
    }

    private function broken(int $position, int $total, ?array $row, string $hospitalId, string $cause): array
    {
        $own = $row !== null && (string) $row['hospital_id'] === $hospitalId;

        return [
            'valid' => false,
            'checked' => $position,
            'total' => $total,
            'broken' => ['position' => $position + 1, 'event_id' => $own ? (string) $row['event_id'] : null, 'cause' => $cause],
        ];
    }
}
