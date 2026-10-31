<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Persistence;

use MicroHis\NotasSoap\Application\AuditTrail;
use MicroHis\NotasSoap\Application\ClinicalNoteRepository;
use MicroHis\NotasSoap\Domain\ClinicalNote;
use MicroHis\NotasSoap\Domain\Diagnosis;
use MicroHis\NotasSoap\Domain\SoapRuleViolation;
use MicroHis\NotasSoap\Domain\SoapSections;
use MicroHis\NotasSoap\Domain\Uuid;

/** Adaptador en memoria del mismo contrato que PdoClinicalNoteStore: pruebas y demo sin base de datos. */
final class InMemoryClinicalNoteRepository implements ClinicalNoteRepository
{
    /** @var array<string, array{episode: string, author: string, status: string, content: string}> públicas solo para que las pruebas lean el estado */
    public array $notes = [];
    /** @var array<string, list<array{code: string, type: string, description: string}>> */
    public array $diagnoses = [];

    public function __construct(private readonly AuditTrail $audit)
    {
    }

    public function find(string $noteId): ?ClinicalNote
    {
        $note = $this->notes[$noteId] ?? null;
        return $note === null ? null : new ClinicalNote($noteId, $note['episode'], $note['author'], $note['status'],
            array_map(static fn (array $d) => new Diagnosis($d['code'], $d['type']), $this->diagnoses[$noteId] ?? []), SoapSections::fromJson($note['content']));
    }

    public function add(string $episodeId, string $authorId, SoapSections $sections): string
    {
        $id = Uuid::v4();
        $this->atomically(function () use ($id, $episodeId, $authorId, $sections): void {
            $this->notes[$id] = ['episode' => $episodeId, 'author' => $authorId, 'status' => 'draft', 'content' => $sections->toJson()];
            $this->audit->record($authorId, 'nota_soap.registrada', 'clinical_note', $id, $episodeId, ['version_no' => 1]);
        });
        return $id;
    }

    public function addDiagnosis(ClinicalNote $note, Diagnosis $diagnosis, string $description): void
    {
        ($this->notes[$note->id]['status'] ?? null) === 'draft' || throw new SoapRuleViolation('NOTE_SIGNED', 409); // igual que el PDO: se vuelve a comprobar al escribir (D-02)
        $id = Uuid::v4();
        $this->atomically(function () use ($id, $note, $diagnosis, $description): void {
            $this->diagnoses[$note->id][] = ['code' => $diagnosis->code, 'type' => $diagnosis->type, 'description' => $description];
            $this->audit->record($note->authorId, 'diagnostico.asociado', 'diagnosis', $id, $note->episodeId, ['clinical_note_id' => $note->id, 'code' => $diagnosis->code, 'type' => $diagnosis->type]);
        });
    }

    public function sign(ClinicalNote $note): void
    {
        ($this->notes[$note->id]['status'] ?? null) === 'draft' || throw new SoapRuleViolation('NOTE_SIGNED', 409);
        $this->atomically(function () use ($note): void {
            $this->notes[$note->id]['status'] = 'signed';
            $codes = array_map(static fn (Diagnosis $d): string => $d->code, $note->diagnoses);
            $primary = array_values(array_filter($note->diagnoses, static fn (Diagnosis $d): bool => $d->type === 'primary'))[0]->code ?? '';
            $this->audit->record($note->authorId, 'nota_soap.firmada', 'clinical_note', $note->id, $note->episodeId, ['version_no' => 1, 'primary_code' => $primary, 'diagnosis_codes' => $codes]);
        });
    }

    /** Solo para pruebas: deja la nota firmada sin pasar por el caso de uso ni la auditoría. */
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
