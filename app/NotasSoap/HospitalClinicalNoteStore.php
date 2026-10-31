<?php

declare(strict_types=1);

namespace App\NotasSoap;

use MicroHis\NotasSoap\Application\AuditTrail;
use MicroHis\NotasSoap\Application\ClinicalNoteRepository;
use MicroHis\NotasSoap\Domain\ClinicalNote;
use MicroHis\NotasSoap\Domain\Diagnosis;
use MicroHis\NotasSoap\Domain\SoapRuleViolation;
use MicroHis\NotasSoap\Domain\SoapSections;
use MicroHis\NotasSoap\Domain\Uuid;
use MicroHis\NotasSoap\Persistence\PdoClinicalNoteStore;
use PDO;

/**
 * Notas SOAP sobre el esquema HOSPITAL real. El alta ancla la nota al expediente abierto del paciente del episodio, en la
 * versión 1 (`medical_record_id` y `version_no` son NOT NULL en la migración 000400); lectura y diagnósticos reutilizan el
 * adaptador del micro-HIS.
 */
final class HospitalClinicalNoteStore implements ClinicalNoteRepository
{
    public function __construct(private readonly PDO $pdo, private readonly PdoClinicalNoteStore $store, private readonly AuditTrail $audit)
    {
    }

    public static function isUuid(string $value): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
    }

    public function find(string $noteId): ?ClinicalNote
    {
        return self::isUuid($noteId) ? $this->store->find($noteId) : null;
    }

    public function add(string $episodeId, string $authorId, SoapSections $sections): string
    {
        $id = Uuid::v4();
        try {
            $this->pdo->beginTransaction();
            $insert = $this->pdo->prepare("INSERT INTO clinical_notes (clinical_note_id, medical_record_id, care_episode_id, author_user_ref_id, note_type, content, version_no)
                SELECT ?, r.medical_record_id, e.care_episode_id, ?, 'soap', ?, 1 FROM care_episodes e
                JOIN medical_records r ON r.local_patient_id = e.local_patient_id WHERE e.care_episode_id = ? AND r.status = 'open'");
            $insert->execute([$id, $authorId, $sections->toJson(), $episodeId]);
            if ($insert->rowCount() !== 1) {
                $this->pdo->rollBack(); // sin expediente abierto no hay dónde anclar la nota; crearlo es de Expediente
                throw new SoapRuleViolation('MEDICAL_RECORD_NOT_FOUND', 409);
            }
            $this->audit->record($authorId, 'nota_soap.registrada', 'clinical_note', $id, $episodeId, ['version_no' => 1]);
            $this->pdo->commit();
        } catch (\RuntimeException $e) { // PDOException o la bitácora caída: todo o nada, como PdoClinicalNoteStore
            $this->pdo->inTransaction() && $this->pdo->rollBack();
            // R-06 (contrato de #1080): la base solo admite una nota original por expediente; es un conflicto, no una caída.
            $e instanceof \PDOException && (string) $e->getCode() === '23505' && str_contains($e->getMessage(), 'clinical_notes_record_version_unique')
                && throw new SoapRuleViolation('RECORD_ALREADY_HAS_NOTE', 409);
            throw new \RuntimeException('persistencia_no_disponible', 0, $e);
        }

        return $id;
    }

    public function addDiagnosis(ClinicalNote $note, Diagnosis $diagnosis, string $description): void
    {
        $this->store->addDiagnosis($note, $diagnosis, $description);
    }

    public function sign(ClinicalNote $note): void
    {
        $this->store->sign($note);
    }
}
