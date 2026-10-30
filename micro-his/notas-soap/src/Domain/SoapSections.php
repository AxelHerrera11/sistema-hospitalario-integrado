<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Domain;

/** Las cuatro secciones de la nota (RF-SOAP-02). Si se puede construir, es válida: es el SoapSectionsValidator de la Semana 2. */
final readonly class SoapSections
{
    public const MAX_LENGTH = 4000;

    public function __construct(public string $subjective, public string $objective, public string $assessment, public string $plan)
    {
        foreach ([$subjective, $objective, $assessment, $plan] as $text) {
            if (trim($text) === '' || mb_strlen($text) > self::MAX_LENGTH) {
                throw new SoapRuleViolation('SOAP_SECTION_INVALID', 422);
            }
        }
    }

    /** Reconstruye las secciones desde el `content` guardado por toJson(). */
    public static function fromJson(string $content): self
    {
        $s = json_decode($content, true, 2, JSON_THROW_ON_ERROR);
        return new self($s['S'], $s['O'], $s['A'], $s['P']);
    }

    public function toJson(): string
    {
        return json_encode(['S' => $this->subjective, 'O' => $this->objective, 'A' => $this->assessment, 'P' => $this->plan], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
