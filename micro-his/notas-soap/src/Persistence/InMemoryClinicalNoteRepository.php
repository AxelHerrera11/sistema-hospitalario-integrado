<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Persistence;

use MicroHis\NotasSoap\Application\AuditTrail;
use MicroHis\NotasSoap\Application\ClinicalNoteRepository;
use MicroHis\NotasSoap\Domain\ClinicalNote;
use MicroHis\NotasSoap\Domain\Diagnosis;
use MicroHis\NotasSoap\Domain\SoapSections;

/** Adaptador en memoria del mismo contrato que PdoClinicalNoteStore: pruebas y demo sin base de datos. */
final class InMemoryClinicalNoteRepository implements ClinicalNoteRepository
{
    /** @var array<string, array{episode: string, author: string, status: string, content: string}> públicas solo para que las pruebas lean el estado */
    public array $notes = [];
    /** @var array<string, list<array{code: string, type: string, description: string}>> */
    public array $diagnoses = [];
    private int $sequence = 0;

    public function __construct(private readonly AuditTrail $audit)
    {
    }

    public function find(string $noteId): ?ClinicalNote
    {
        $note = $this->notes[$noteId] ?? null;
        return $note === null ? null : new ClinicalNote($noteId, $note['episode'], $note['author'], $note['status'],
            array_map(static fn (array $d) => new Diagnosis($d['code'], $d['type']), $this->diagnoses[$noteId] ?? []));
    }

    public function add(string $episodeId, string $authorId, SoapSections $sections): string
    {
        $id = 'nota-' . ++$this->sequence;
        $this->atomically(function () use ($id, $episodeId, $authorId, $sections): void {
            $this->notes[$id] = ['episode' => $episodeId, 'author' => $authorId, 'status' => 'draft', 'content' => $sections->toJson()];
            $this->audit->record('nota_soap_registrada', $authorId, $id);
        });
        return $id;
    }

    public function addDiagnosis(ClinicalNote $note, Diagnosis $diagnosis, string $description): void
    {
        $this->atomically(function () use ($note, $diagnosis, $description): void {
            $this->diagnoses[$note->id][] = ['code' => $diagnosis->code, 'type' => $diagnosis->type, 'description' => $description];
            $this->audit->record('diagnostico_asociado', $note->authorId, $note->id . ':' . $diagnosis->code);
        });
    }

    /** Solo para pruebas: la firma real (UC-SOAP-05) aún no está implementada. */
    public function markSigned(string $noteId): void
    {
        $this->notes[$noteId]['status'] = 'signed';
    }

    /** Igual que la transacción PDO: si falla la auditoría se restaura el estado anterior. */
    private function atomically(callable $work): void
    {
        $before = [$this->notes, $this->diagnoses];
        try {
            $work();
        } catch (\RuntimeException $e) {
            [$this->notes, $this->diagnoses] = $before;
            throw new \RuntimeException('persistencia_no_disponible', 0, $e);
        }
    }
}
