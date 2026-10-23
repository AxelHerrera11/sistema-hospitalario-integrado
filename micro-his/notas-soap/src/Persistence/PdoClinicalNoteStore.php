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
        $note = $this->rows('SELECT care_episode_id, author_user_ref_id, status, content, version_no, supersedes_id FROM clinical_notes WHERE clinical_note_id = ?', [$noteId])[0] ?? null;
        $diagnoses = $this->rows("SELECT code, type FROM diagnoses WHERE clinical_note_id = ? AND status = 'active'", [$noteId]);
        return $note === null ? null : new ClinicalNote($noteId, $note['care_episode_id'], $note['author_user_ref_id'], $note['status'],
            array_map(static fn (array $d) => new Diagnosis($d['code'], $d['type']), $diagnoses), SoapSections::fromJson($note['content']), (int) $note['version_no'], $note['supersedes_id']);
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

    public function correct(ClinicalNote $note, SoapSections $sections, string $reason): string
    {
        $id = Uuid::v4();
        $this->transaction(function () use ($id, $note, $sections, $reason): void {
            // La fila original se bloquea: dos correcciones simultáneas se serializan y la segunda ve a la primera (cada nota se reemplaza una sola vez).
            $lock = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql' ? ' FOR UPDATE' : '';
            $original = $this->rows('SELECT medical_record_id, version_no FROM clinical_notes WHERE clinical_note_id = ?' . $lock, [$note->id])[0] ?? throw new SoapRuleViolation('NOTE_NOT_FOUND', 404);
            $this->rows('SELECT 1 FROM clinical_notes WHERE supersedes_id = ?', [$note->id]) === [] || throw new SoapRuleViolation('NOTE_ALREADY_CORRECTED', 409);
            $version = (int) $original['version_no'] + 1;
            $this->pdo->prepare("INSERT INTO clinical_notes (clinical_note_id, medical_record_id, care_episode_id, author_user_ref_id, note_type, content, version_no, supersedes_id, correction_reason) VALUES (?, ?, ?, ?, 'soap', ?, ?, ?, ?)")
                ->execute([$id, $original['medical_record_id'], $note->episodeId, $note->authorId, $sections->toJson(), $version, $note->id, $reason]);
            $this->audit->record($note->authorId, 'nota_soap.corregida', 'clinical_note', $id, $note->episodeId, ['version_no' => $version, 'supersedes_id' => $note->id]);
        });
        return $id;
    }

    public function sign(ClinicalNote $note): void
    {
        $this->transaction(function () use ($note): void {
            $lock = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql' ? ' FOR UPDATE' : '';
            $row = $this->rows('SELECT status, version_no FROM clinical_notes WHERE clinical_note_id = ?' . $lock, [$note->id])[0] ?? null;
            ($row['status'] ?? null) === 'draft' || throw new SoapRuleViolation('NOTE_SIGNED', 409); // otra sesión pudo firmarla tras leerla
            $this->pdo->prepare("UPDATE clinical_notes SET status = 'signed', signed_at = CURRENT_TIMESTAMP WHERE clinical_note_id = ?")->execute([$note->id]);
            $codes = array_map(static fn (Diagnosis $d): string => $d->code, $note->diagnoses);
            $primary = array_values(array_filter($note->diagnoses, static fn (Diagnosis $d): bool => $d->type === 'primary'))[0]->code ?? '';
            $this->audit->record($note->authorId, 'nota_soap.firmada', 'clinical_note', $note->id, $note->episodeId, ['version_no' => (int) $row['version_no'], 'primary_code' => $primary, 'diagnosis_codes' => $codes]);
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
            // R-06 (ASII-09): la restricción única (expediente, versión) deja una sola nota por expediente; se informa como conflicto y no como caída.
            $e instanceof \PDOException && ((string) $e->getCode()) === '23505' && str_contains($e->getMessage(), 'clinical_notes_record_version_unique')
                && throw new SoapRuleViolation('RECORD_ALREADY_HAS_NOTE', 409);
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
