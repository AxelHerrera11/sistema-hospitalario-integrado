<?php

declare(strict_types=1);

namespace MicroHis\OrdenesLab\Presentation;

use MicroHis\OrdenesLab\Domain\LabOrder;
use MicroHis\OrdenesLab\Domain\LabOrderEvent;

/** View (MVC): representa una orden y su historial en HTML. Escapa todo texto dinámico. */
final class LabOrderView
{
    public function render(LabOrder $order): string
    {
        $rows = '';
        foreach ($order->items as $item) {
            $rows .= '<tr><td>' . $this->escape($item->code) . '</td><td>' . $this->escape($item->name) . '</td></tr>';
        }
        $events = '';
        foreach ($order->events as $event) {
            $events .= '<li>' . $this->escape($this->describe($event)) . '</li>';
        }

        return '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Orden de laboratorio</title></head><body>'
            . '<h1>Orden ' . $this->escape($order->id) . '</h1>'
            . '<p>Estado: <strong>' . $this->escape($order->status) . '</strong> · Prioridad: ' . $this->escape($order->priority) . '</p>'
            . '<p>Paciente: ' . $this->escape($order->patientId) . ' · Episodio: ' . $this->escape($order->careEpisodeId) . '</p>'
            . '<table><thead><tr><th>Código</th><th>Prueba</th></tr></thead><tbody>' . $rows . '</tbody></table>'
            . '<h2>Historial</h2><ol>' . $events . '</ol>'
            . '</body></html>';
    }

    private function describe(LabOrderEvent $event): string
    {
        return ($event->fromStatus ?? 'inicio') . ' → ' . $event->toStatus
            . ' · ' . $event->actor . ' · ' . $event->occurredAt
            . ($event->reason === null ? '' : ' · ' . $event->reason);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
