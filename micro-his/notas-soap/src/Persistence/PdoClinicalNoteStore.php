<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Persistence;

use MicroHis\NotasSoap\Application\AuditTrail;
use MicroHis\NotasSoap\Application\ClinicalNoteRepository;
use MicroHis\NotasSoap\Domain\ClinicalNote;
use MicroHis\NotasSoap\Domain\Diagnosis;
use MicroHis\NotasSoap\Domain\SoapSections;
use PDO;

/** Repository de notas y diagnósticos con PDO. Siempre con sentencias preparadas; la auditoría entra en la misma transacción. */
final class PdoClinicalNoteStore implements ClinicalNoteRepository
{
    public function __construct(private readonly PDO $pdo, private readonly AuditTrail $audit)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function find(string $noteId): ?ClinicalNote
    {
        $note = $this->rows('SELECT care_episode_id, author_user_ref_id, status FROM clinical_notes WHERE clinical_note_id = ?', [$noteId])[0] ?? null;
        $diagnoses = $this->rows("SELECT code, type FROM diagnoses WHERE clinical_note_id = ? AND status = 'active'", [$noteId]);
        return $note === null ? null : new ClinicalNote($noteId, $note['care_episode_id'], $note['author_user_ref_id'], $note['status'],
            array_map(static fn (array $d) => new Diagnosis($d['code'], $d['type']), $diagnoses));
    }

    public function add(string $episodeId, string $authorId, SoapSections $sections): string
    {
        $id = 'nota-' . bin2hex(random_bytes(4));
        $this->transaction(function () use ($id, $episodeId, $authorId, $sections): void {
            $this->pdo->prepare("INSERT INTO clinical_notes (clinical_note_id, care_episode_id, author_user_ref_id, note_type, content) VALUES (?, ?, ?, 'soap', ?)")
                ->execute([$id, $episodeId, $authorId, $sections->toJson()]);
            $this->audit->record('nota_soap_registrada', $authorId, $id);
        });
        return $id;
    }

    public function addDiagnosis(ClinicalNote $note, Diagnosis $diagnosis, string $description): void
    {
        $this->transaction(function () use ($note, $diagnosis, $description): void {
            $this->pdo->prepare('INSERT INTO diagnoses (clinical_note_id, care_episode_id, author_user_ref_id, code, description, type, onset_date) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$note->id, $note->episodeId, $note->authorId, $diagnosis->code, $description, $diagnosis->type, date('Y-m-d')]);
            $this->audit->record('diagnostico_asociado', $note->authorId, $note->id . ':' . $diagnosis->code);
        });
    }

    /** Todo o nada: si falla cualquier INSERT (incluida la auditoría) se revierte y se informa un error de persistencia. */
    private function transaction(callable $work): void
    {
        try {
            $this->pdo->beginTransaction();
            $work();
            $this->pdo->commit();
        } catch (\RuntimeException $e) { // PDOException o la bitácora caída de cualquier adaptador
            $this->pdo->inTransaction() && $this->pdo->rollBack();
            throw new \RuntimeException('persistencia_no_disponible', 0, $e);
        }
    }

    private function rows(string $sql, array $params): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
