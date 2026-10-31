<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Client;

/**
 * Cliente del API v1 de notas SOAP para los módulos que lo consumen (Expediente, Prescripciones, Órdenes).
 * Solo la lectura se reintenta, una vez, porque es idempotente; registrar o asociar nunca, para no duplicar notas ni diagnósticos.
 */
final class SoapNoteApiClient
{
    public function __construct(private readonly HttpTransport $transport, private readonly string $actorId)
    {
    }

    public function registerNote(string $episodeId, string $subjective, string $objective, string $assessment, string $plan): string
    {
        $body = ['subjective' => $subjective, 'objective' => $objective, 'assessment' => $assessment, 'plan' => $plan];
        return $this->expect(201, $this->transport->send('POST', '/api/v1/care-episodes/' . rawurlencode($episodeId) . '/soap-notes', $this->actorId, $body))['clinical_note_id'];
    }

    public function associateDiagnosis(string $noteId, string $code, string $type): void
    {
        $this->expect(201, $this->transport->send('POST', '/api/v1/soap-notes/' . rawurlencode($noteId) . '/diagnoses', $this->actorId, ['code' => $code, 'type' => $type]));
    }

    /** @return array{clinical_note_id: string, status: string, sections: array<string, string>, diagnoses: list<array{code: string, type: string}>} */
    public function note(string $noteId): array
    {
        $path = '/api/v1/soap-notes/' . rawurlencode($noteId);
        try {
            $response = $this->transport->send('GET', $path, $this->actorId);
        } catch (SoapNoteApiUnavailable) {
            $response = $this->transport->send('GET', $path, $this->actorId); // único reintento; si falla, se propaga
        }
        $data = $this->expect(200, $response);
        unset($data['trace_id']);
        return $data;
    }

    /** Cualquier estado distinto del esperado termina en SoapNoteApiError con el código y el trace_id del servidor. */
    private function expect(int $status, array $response): array
    {
        if ($response['status'] !== $status) {
            throw new SoapNoteApiError($response['status'], (string) ($response['body']['code'] ?? 'UNEXPECTED_RESPONSE'), (string) ($response['body']['trace_id'] ?? ''));
        }
        return $response['body'];
    }
}
