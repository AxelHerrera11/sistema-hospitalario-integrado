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
        if (!PostgresIds::usable($this->pdo, $noteId)) {
            return null;
        }
        $note = $this->rows('SELECT care_episode_id, author_user_ref_id, status, content FROM clinical_notes WHERE clinical_note_id = ?', [$noteId])[0] ?? null;
        $diagnoses = $this->rows("SELECT code, type FROM diagnoses WHERE clinical_note_id = ? AND status = 'active'", [$noteId]);
        return $note === null ? null : new ClinicalNote($noteId, $note['care_episode_id'], $note['author_user_ref_id'], $note['status'],
            array_map(static fn (array $d) => new Diagnosis($d['code'], $d['type']), $diagnoses), SoapSections::fromJson($note['content']));
    }

    public function add(string $episodeId, string $authorId, SoapSections $sections): string
    {
        $id = Uuid::v4();
        $recordId = $this->openMedicalRecord($episodeId);
        $this->transaction(function () use ($id, $recordId, $episodeId, $authorId, $sections): void {
            $this->pdo->prepare("INSERT INTO clinical_notes (clinical_note_id, medical_record_id, care_episode_id, author_user_ref_id, note_type, content, version_no) VALUES (?, ?, ?, ?, 'soap', ?, 1)")
                ->execute([$id, $recordId, $episodeId, $authorId, $sections->toJson()]);
            $this->audit->record($authorId, 'nota_soap.registrada', 'clinical_note', $id, $episodeId, ['version_no' => 1]);
        });
        return $id;
    }

    public function addDiagnosis(ClinicalNote $note, Diagnosis $diagnosis, string $description): void
    {
        $id = Uuid::v4();
        $this->transaction(function () use ($id, $note, $diagnosis, $description): void {
            $this->assertStillDraft($note->id);
            $this->pdo->prepare('INSERT INTO diagnoses (diagnosis_id, clinical_note_id, care_episode_id, author_user_ref_id, code, description, type, onset_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$id, $note->id, $note->episodeId, $note->authorId, $diagnosis->code, $description, $diagnosis->type, date('Y-m-d')]);
            $this->audit->record($note->authorId, 'diagnostico.asociado', 'diagnosis', $id, $note->episodeId, ['clinical_note_id' => $note->id, 'code' => $diagnosis->code, 'type' => $diagnosis->type]);
        });
    }

    /** Otra sesión pudo firmar la nota tras leerla (D-02): se vuelve a comprobar dentro de la transacción, con la fila bloqueada contra la firma concurrente. Mitigación del módulo; la regla en la base (trigger) es de ASII-09. */
    private function assertStillDraft(string $noteId): void
    {
        $lock = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql' ? ' FOR SHARE' : '';
        $status = $this->rows('SELECT status FROM clinical_notes WHERE clinical_note_id = ?' . $lock, [$noteId])[0]['status'] ?? null;
        $status === 'draft' || throw new SoapRuleViolation('NOTE_SIGNED', 409);
    }

    /** El expediente abierto del paciente del episodio. Crearlo es de Expediente: sin él no se escribe (409) en lugar de inventarlo. */
    private function openMedicalRecord(string $episodeId): string
    {
        PostgresIds::usable($this->pdo, $episodeId) || throw new SoapRuleViolation('MEDICAL_RECORD_NOT_FOUND', 409);
        $record = $this->rows("SELECT r.medical_record_id FROM care_episodes e JOIN medical_records r ON r.local_patient_id = e.local_patient_id AND r.status = 'open' WHERE e.care_episode_id = ?", [$episodeId])[0] ?? null;
        return $record['medical_record_id'] ?? throw new SoapRuleViolation('MEDICAL_RECORD_NOT_FOUND', 409);
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
        } catch (SoapRuleViolation $e) { // regla de dominio comprobada dentro de la transacción: se revierte y se propaga tal cual
            $this->pdo->inTransaction() && $this->pdo->rollBack();
            throw $e;
        }
    }

    private function rows(string $sql, array $params): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
