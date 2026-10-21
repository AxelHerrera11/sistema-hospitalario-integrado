<?php

declare(strict_types=1);

/*
 * ASII-21 · Semana 2 · SRP. Ejecutar: php docs/alertas-criticas/semana-02/evidencia/demo-srp.php
 * "Antes" (CriticalAlertManager hace todo) vs. "después" (una clase por responsabilidad): cambiar el
 * canal (TI) o el plazo (calidad) no toca el detector ni el registrador. Datos ficticios.
 */

final readonly class ResultValidated
{
    public function __construct(public string $resultVersionId, public string $labTestId, public float $value, public string $episodeId) {}
}

final class CriticalAlert
{
    public string $status = 'notified';
    public function __construct(public readonly string $resultVersionId, public readonly string $direction, public int $level, public string $recipient, public DateTimeImmutable $dueAt) {}
}

const THRESHOLDS = ['GLU-v1' => [40.0, 400.0], 'K-v1' => [2.5, 6.5]];
const ATTENDING = ['EP-1' => 'dra.lopez', 'EP-2' => null];          // care_assignments
const CHAIN = [1 => 'jefatura.medicina', 2 => 'direccion.medica'];     // organigrama

// ANTES: cuatro razones para cambiar (política clínica, organigrama, canal y plazos).
final class CriticalAlertManager
{
    /** @var array<string, CriticalAlert> */
    public array $alerts = [];
    /** @var list<string> */
    public array $audit = [];
    public function onResultValidated(ResultValidated $e, DateTimeImmutable $now): ?CriticalAlert
    {
        [$low, $high] = THRESHOLDS[$e->labTestId];                                         // política clínica
        $direction = $e->value < $low ? 'low' : ($e->value > $high ? 'high' : null);
        if ($direction === null || isset($this->alerts[$e->resultVersionId])) {
            return null;
        }
        $recipient = ATTENDING[$e->episodeId] ?? null;                                     // organigrama
        $alert = new CriticalAlert($e->resultVersionId, $direction, $recipient === null ? 1 : 0, $recipient ?? CHAIN[1], $now->modify('+15 minutes')); // plazo
        $this->alerts[$e->resultVersionId] = $alert;                                       // persistencia + canal + auditoría
        $this->audit[] = "alerta.generada {$e->resultVersionId}";
        return $alert;
    }
    public function escalate(DateTimeImmutable $now): void
    {
        foreach ($this->alerts as $a) {
            if ($a->dueAt <= $now && $a->level < 2) {
                [$a->level, $a->status, $a->dueAt] = [$a->level + 1, 'escalated', $now->modify('+15 minutes')];
                $a->recipient = CHAIN[$a->level];
                $this->audit[] = "alerta.escalada {$a->resultVersionId}";
            }
        }
    }
}

// DESPUÉS: una razón para cambiar por clase.
/** Laboratorio: la política clínica de valores críticos. */
final class CriticalValueDetector
{
    public function direction(float $value, array $t): ?string { return $value < $t[0] ? 'low' : ($value > $t[1] ? 'high' : null); }
}

/** Dirección médica: quién responde en cada nivel. */
final class RecipientResolver
{
    public function initial(string $episodeId): array { return ($a = ATTENDING[$episodeId] ?? null) === null ? [1, CHAIN[1]] : [0, $a]; }
    public function forLevel(int $level): string { return CHAIN[$level]; }
}

/** Persistencia (en la Semana 3 pasa a ser una interfaz con adaptador PDO). */
final class AlertRepository
{
    /** @var array<string, CriticalAlert> */
    private array $rows = [];
    public function findByResult(string $id): ?CriticalAlert { return $this->rows[$id] ?? null; }
    public function save(CriticalAlert $alert): void { $this->rows[$alert->resultVersionId] = $alert; }
    public function pendingDue(DateTimeImmutable $now): array { return array_values(array_filter($this->rows, fn ($a) => $a->dueAt <= $now)); }
}

/** TI: el canal de entrega. */
interface AlertNotifier
{
    public function notify(string $recipient, string $message): void;
}

final class InboxNotifier implements AlertNotifier
{
    public array $sent = [];
    public function notify(string $recipient, string $message): void { $this->sent[] = "{$recipient}: {$message}"; }
}

/** Calidad: plazos y niveles. */
final readonly class EscalationPolicy
{
    public function __construct(public int $minutesPerLevel = 15, public int $maxLevel = 2) {}
    public function dueFrom(DateTimeImmutable $from): DateTimeImmutable { return $from->modify("+{$this->minutesPerLevel} minutes"); }
}

/** Gobernanza: la evidencia. */
final class AuditLogger
{
    public array $events = [];
    public function record(string $event, string $id): void { $this->events[] = "{$event} {$id}"; }
}

/** Cómo nace una alerta: idempotencia, destinatario y plazo inicial. */
final class CriticalAlertRegistrar
{
    public function __construct(private readonly AlertRepository $repo, private readonly RecipientResolver $recipients, private readonly EscalationPolicy $policy, private readonly AuditLogger $audit) {}
    public function register(ResultValidated $e, string $direction, DateTimeImmutable $now): ?CriticalAlert
    {
        if ($this->repo->findByResult($e->resultVersionId) !== null) {
            return null;
        }
        [$level, $recipient] = $this->recipients->initial($e->episodeId);
        $this->repo->save($alert = new CriticalAlert($e->resultVersionId, $direction, $level, $recipient, $this->policy->dueFrom($now)));
        $this->audit->record('alerta.generada', $e->resultVersionId);
        return $alert;
    }
}

/** Cuándo y a quién se escala. */
final class AlertEscalator
{
    public function __construct(private readonly AlertRepository $repo, private readonly RecipientResolver $recipients, private readonly AlertNotifier $notifier, private readonly EscalationPolicy $policy, private readonly AuditLogger $audit) {}
    public function escalateDue(DateTimeImmutable $now): void
    {
        foreach ($this->repo->pendingDue($now) as $a) {
            if ($a->level < $this->policy->maxLevel) {
                [$a->level, $a->status, $a->dueAt] = [$a->level + 1, 'escalated', $this->policy->dueFrom($now)];
                $a->recipient = $this->recipients->forLevel($a->level);
                $this->repo->save($a);
                $this->notifier->notify($a->recipient, 'escalada');
                $this->audit->record('alerta.escalada', $a->resultVersionId);
            }
        }
    }
}

/** Orquestador delgado: coordina, no decide reglas. */
final class CriticalAlertFlow
{
    public function __construct(private readonly CriticalValueDetector $detector, private readonly CriticalAlertRegistrar $registrar, private readonly AlertNotifier $notifier) {}
    public function onResultValidated(ResultValidated $e, DateTimeImmutable $now): ?CriticalAlert
    {
        $direction = $this->detector->direction($e->value, THRESHOLDS[$e->labTestId]);
        $alert = $direction === null ? null : $this->registrar->register($e, $direction, $now);
        $alert && $this->notifier->notify($alert->recipient, "{$e->labTestId} {$e->value}");
        return $alert;
    }
}

$failures = 0;
function check(string $label, bool $ok): void { global $failures; echo ($ok ? 'PASS' : 'FAIL') . "  {$label}\n"; $failures += $ok ? 0 : 1; }
function build(AlertNotifier $n, EscalationPolicy $p): array
{
    [$repo, $audit, $rr] = [new AlertRepository(), new AuditLogger(), new RecipientResolver()];
    return [new CriticalAlertFlow(new CriticalValueDetector(), new CriticalAlertRegistrar($repo, $rr, $p, $audit), $n), new AlertEscalator($repo, $rr, $n, $p, $audit), $repo, $audit];
}

$t0 = new DateTimeImmutable('2026-10-12 08:00:00');
$glucosa = new ResultValidated('RV-1', 'GLU-v1', 32.0, 'EP-1');
$antes = new CriticalAlertManager();
[$flow, $escalator, $repo, $audit] = build(new InboxNotifier(), new EscalationPolicy());

echo "== Diseño después reproduce el comportamiento del diseño antes ==\n";
foreach ([[$glucosa, 'glucosa 32 → alerta baja para el médico tratante (nivel 0)'], [new ResultValidated('RV-2', 'K-v1', 7.1, 'EP-2'), 'potasio 7.1 sin médico asignado → jefatura (nivel 1)'], [new ResultValidated('RV-3', 'GLU-v1', 95.0, 'EP-1'), 'glucosa 95 → sin alerta']] as [$e, $label]) {
    [$a, $d] = [$antes->onResultValidated($e, $t0), $flow->onResultValidated($e, $t0)];
    check($label, ($a === null && $d === null) || ($a && $d && [$a->direction, $a->level, $a->recipient] === [$d->direction, $d->level, $d->recipient]));
}
check('evento repetido → no se duplica la alerta (idempotencia)', $antes->onResultValidated($glucosa, $t0) === null && $flow->onResultValidated($glucosa, $t0) === null);
$antes->escalate($t0->modify('+16 minutes'));
$escalator->escalateDue($t0->modify('+16 minutes'));
check('a los 16 min sin acuse sube a jefatura (nivel 1) en ambos diseños', $repo->findByResult('RV-1')->recipient === 'jefatura.medicina' && $antes->alerts['RV-1']->level === 1);
check('los mismos eventos quedan auditados', $antes->audit === $audit->events);

echo "\n== Cambio de TI: canal de notificación (InboxNotifier → OutboxNotifier) ==\n";
final class OutboxNotifier implements AlertNotifier
{
    public array $outbox = [];
    public function notify(string $recipient, string $message): void { $this->outbox[] = [$recipient, $message]; } // outbox_events
}
$outbox = new OutboxNotifier();
$alerta = build($outbox, new EscalationPolicy())[0]->onResultValidated($glucosa, $t0);
check('la alerta se detecta y registra igual que antes', $alerta?->direction === 'low' && $alerta->recipient === 'dra.lopez');
check('la notificación sale por el outbox y no por la bandeja', $outbox->outbox === [['dra.lopez', 'GLU-v1 32']]);

echo "\n== Cambio de calidad: plazo de escalamiento 15 → 10 min ==\n";
foreach ([10 => 1, 15 => 0] as $minutos => $nivel) {
    [$f, $esc, $r] = build(new InboxNotifier(), new EscalationPolicy(minutesPerLevel: $minutos));
    $f->onResultValidated($glucosa, $t0);
    $esc->escalateDue($t0->modify('+11 minutes'));
    check("con {$minutos} min, a los 11 min queda en nivel {$nivel}", $r->findByResult('RV-1')->level === $nivel);
}

echo "\n== Dependencias por clase (reflexión) ==\n";
$deps = [];
foreach ([CriticalValueDetector::class, RecipientResolver::class, CriticalAlertRegistrar::class] as $class) {
    $ctor = (new ReflectionClass($class))->getConstructor();
    $deps[$class] = $ctor ? array_map(fn ($p) => (string) $p->getType(), $ctor->getParameters()) : [];
    echo "      {$class} → " . ($deps[$class] ? implode(', ', $deps[$class]) : '(sin dependencias)') . "\n";
}
check('el detector y el resolvedor no dependen de persistencia, canal ni plazos', $deps[CriticalValueDetector::class] === [] && $deps[RecipientResolver::class] === []);
check('el registrador no depende del canal de notificación', !in_array('AlertNotifier', $deps[CriticalAlertRegistrar::class], true));

echo "\n" . ($failures === 0 ? 'OK: todas las aserciones pasaron' : "ERROR: {$failures} aserciones fallaron") . "\n";
exit($failures === 0 ? 0 : 1);
