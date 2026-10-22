<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Presentation;

use MicroHis\NotasSoap\Application\AssociateDiagnosis;
use MicroHis\NotasSoap\Application\RegisterSoapNote;
use MicroHis\NotasSoap\Domain\Diagnosis;
use MicroHis\NotasSoap\Domain\SoapRuleViolation;
use MicroHis\NotasSoap\Domain\SoapSections;

/** Traduce la petición HTTP al caso de uso y el resultado a estado + cuerpo JSON. Sin SQL ni reglas de negocio. */
final class SoapNoteHttpHandler
{
    public function __construct(private readonly RegisterSoapNote $register, private readonly AssociateDiagnosis $associate)
    {
    }

    public function registerNote(array $headers, string $episodeId, array $body): array
    {
        return $this->respond(201, function () use ($headers, $episodeId, $body): array {
            $sections = new SoapSections(...array_map(static fn (string $k): string => self::text($body, $k), ['subjective', 'objective', 'assessment', 'plan']));
            return ['clinical_note_id' => $this->register->execute(self::text($headers, 'X-User-ID'), $episodeId, $sections), 'status' => 'draft'];
        });
    }

    public function associateDiagnosis(array $headers, string $noteId, array $body): array
    {
        return $this->respond(201, function () use ($headers, $noteId, $body): array {
            $diagnosis = new Diagnosis(self::text($body, 'code'), self::text($body, 'type'));
            $this->associate->execute(self::text($headers, 'X-User-ID'), $noteId, $diagnosis);
            return ['clinical_note_id' => $noteId, 'code' => $diagnosis->code, 'type' => $diagnosis->type];
        });
    }

    /** Campo de texto opcional: ausente equivale a cadena vacía; cualquier otro tipo es 422 antes de llegar al caso de uso. */
    private static function text(array $source, string $key): string
    {
        $value = $source[$key] ?? '';
        return is_string($value) ? $value : throw new SoapRuleViolation('PAYLOAD_INVALID', 422);
    }

    private function respond(int $status, callable $action): array
    {
        try {
            return ['status' => $status, 'body' => $action()];
        } catch (SoapRuleViolation $e) {
            return ['status' => $e->status, 'body' => ['code' => $e->getMessage()]];
        } catch (\RuntimeException) {
            return ['status' => 500, 'body' => ['code' => 'SOAP_PERSISTENCE_ERROR']];
        }
    }
}
