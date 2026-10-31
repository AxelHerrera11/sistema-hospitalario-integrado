<?php

declare(strict_types=1);

namespace MicroHis\Alertas\Presentation;

use MicroHis\Alertas\Domain\CriticalAlert;

/** View: bandeja de alertas críticas del médico (UC-ALR-06). Solo presenta; todo texto se escapa. */
final class AlertInboxView
{
    /** @param list<CriticalAlert> $alerts */
    public function render(array $alerts): string
    {
        $e = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $items = array_map(static fn (CriticalAlert $a): string => "<li role=\"alert\"><strong>{$e($a->labTestId)} {$e($a->value)} ("
            . ($a->direction === 'low' ? 'bajo' : 'alto') . " el umbral crítico)</strong> · paciente {$e($a->patientId)} · vence {$e($a->dueAt)}</li>", $alerts);

        return '<!doctype html><html lang="es"><meta charset="utf-8"><title>Alertas críticas</title><h1>Alertas críticas pendientes (' . count($alerts) . ')</h1>'
            . ($items === [] ? '<p>No hay alertas pendientes.</p>' : '<ul>' . implode('', $items) . '</ul>') . '</html>';
    }
}
