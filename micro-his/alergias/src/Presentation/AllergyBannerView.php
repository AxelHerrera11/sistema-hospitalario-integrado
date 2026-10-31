<?php

declare(strict_types=1);

namespace MicroHis\Alergias\Presentation;

use MicroHis\Alergias\Domain\AllergyAlert;

/** View: banner de alergias del expediente (ASII-10). Solo presenta; todo texto se escapa. */
final class AllergyBannerView
{
    private const STYLE = ['critical' => '#b71c1c', 'warning' => '#e65100', 'unavailable' => '#4a4a4a', 'none' => '#2e7d32'];

    public function render(AllergyAlert $alert): string
    {
        $e = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $items = implode('', array_map(static fn (array $a): string => "<li>{$e($a['allergen_name'] ?? $a['allergen_code'])} · {$e($a['severity'])}"
            . ($a['reaction'] ? " · {$e($a['reaction'])}" : '') . '</li>', $alert->allergies));

        return "<div role=\"alert\" class=\"allergy-banner {$e($alert->level)}\" style=\"border-left:6px solid " . self::STYLE[$alert->level] . ';padding:8px">'
            . "<strong>{$e($alert->message)}</strong>" . ($items === '' ? '' : "<ul>{$items}</ul>") . '</div>';
    }
}
