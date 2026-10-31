<?php

declare(strict_types=1);

namespace App\ExpedienteMedico;

use Closure;
use PDO;

/**
 * Vínculo asistencial médico ↔ episodio (care_assignments de la base HOSPITAL): es lo que habilita a un médico a
 * consultar el expediente (PdoCareRelationship). Solo episodios abiertos y médicos con especialidad vigente, que es
 * lo mismo que exige el trigger enforce_care_assignment_integrity. Sentencias preparadas; sin migraciones.
 */
final class CareAssignments
{
    private const PATIENT = 'SELECT local_patient_id, code, given_name, family_name FROM local_patients WHERE code = ?';
    private const EPISODES = "SELECT care_episode_id, type, started_at, service_code FROM care_episodes
        WHERE local_patient_id = ? AND status = 'open' ORDER BY started_at DESC";
    private const ACTIVE = "SELECT a.care_assignment_id, a.care_episode_id, a.doctor_profile_id, r.display_name, s.name AS specialty, a.valid_from
        FROM care_assignments a JOIN care_episodes e ON e.care_episode_id = a.care_episode_id
        JOIN doctor_profiles p ON p.doctor_profile_id = a.doctor_profile_id
        JOIN local_user_refs r ON r.local_user_ref_id = p.local_user_ref_id
        LEFT JOIN specialties s ON s.specialty_id = a.specialty_id
        WHERE e.local_patient_id = ? AND e.status = 'open' AND a.status = 'active' AND a.valid_to IS NULL ORDER BY a.valid_from";
    private const DOCTORS = "SELECT p.doctor_profile_id, r.display_name, s.specialty_id, s.name AS specialty
        FROM doctor_profiles p JOIN local_user_refs r ON r.local_user_ref_id = p.local_user_ref_id AND r.status = 'active'
        JOIN doctor_specialties ds ON ds.doctor_profile_id = p.doctor_profile_id AND ds.status = 'active'
            AND ds.valid_from <= CURRENT_DATE AND (ds.valid_to IS NULL OR ds.valid_to > CURRENT_DATE)
        JOIN specialties s ON s.specialty_id = ds.specialty_id AND s.status = 'active'
        WHERE p.status = 'active' AND p.valid_from <= CURRENT_DATE AND (p.valid_to IS NULL OR p.valid_to > CURRENT_DATE)";
    private const INSERT = "INSERT INTO care_assignments (care_assignment_id, care_episode_id, doctor_profile_id, specialty_id,
        assigned_by_user_ref_id, scope, reason, source, status, valid_from) VALUES (?, ?, ?, ?, ?, '{\"care\":\"attending\"}', ?, 'manual', 'active', CURRENT_TIMESTAMP)";
    private const END = "UPDATE care_assignments SET status = 'closed', valid_to = ? WHERE care_assignment_id = ? AND status = 'active'
        AND care_episode_id IN (SELECT care_episode_id FROM care_episodes WHERE local_patient_id = ?)";

    /** @param Closure(): PDO $pdo */
    public function __construct(private readonly Closure $pdo) {}

    /** Episodios abiertos, médicos tratantes vigentes y médicos asignables; null si el paciente no existe. */
    public function overview(string $patientCode): ?array
    {
        $patient = $this->patient($patientCode);
        if ($patient === null) {
            return null;
        }

        return [
            'patient' => ['code' => $patient['code'], 'display_name' => trim($patient['given_name'].' '.$patient['family_name'])],
            'episodes' => $this->rows(self::EPISODES, [$patient['local_patient_id']]),
            'assignments' => $this->rows(self::ACTIVE, [$patient['local_patient_id']]),
            'doctors' => $this->rows(self::DOCTORS.' ORDER BY r.display_name, s.name', []),
        ];
    }

    public function assign(string $patientCode, string $episodeId, string $doctorId, string $specialtyId, string $reason, string $actorRef, Closure $audit): array
    {
        $patient = $this->patient($patientCode) ?? throw new CareAssignmentFailure('PACIENTE_NO_ENCONTRADO', 404);
        $open = array_column($this->rows(self::EPISODES, [$patient['local_patient_id']]), 'care_episode_id');
        if (! in_array($episodeId, $open, true)) {
            throw new CareAssignmentFailure('EPISODIO_NO_DISPONIBLE', 422);
        }
        if ($this->rows(self::DOCTORS.' AND p.doctor_profile_id = ? AND s.specialty_id = ?', [$doctorId, $specialtyId]) === []) {
            throw new CareAssignmentFailure('MEDICO_NO_ELEGIBLE', 422);
        }
        foreach ($this->rows(self::ACTIVE, [$patient['local_patient_id']]) as $active) {
            if ($active['care_episode_id'] === $episodeId && $active['doctor_profile_id'] === $doctorId) {
                throw new CareAssignmentFailure('ASIGNACION_DUPLICADA', 409);
            }
        }

        $id = self::uuid();
        $this->atomically(function (PDO $pdo) use ($id, $episodeId, $doctorId, $specialtyId, $actorRef, $reason, $audit): void {
            // valid_from con el reloj de la base: el mismo que usa PdoCareRelationship al comprobar el vínculo.
            $pdo->prepare(self::INSERT)->execute([$id, $episodeId, $doctorId, $specialtyId, $actorRef, $reason]);
            $audit($id);
        });

        return ['care_assignment_id' => $id, 'care_episode_id' => $episodeId, 'doctor_profile_id' => $doctorId, 'specialty_id' => $specialtyId];
    }

    /** Termina el vínculo (status closed + valid_to): la fila se conserva como evidencia. */
    public function end(string $patientCode, string $assignmentId, Closure $audit): void
    {
        $patient = $this->patient($patientCode) ?? throw new CareAssignmentFailure('PACIENTE_NO_ENCONTRADO', 404);
        $this->atomically(function (PDO $pdo) use ($assignmentId, $patient, $audit): void {
            $statement = $pdo->prepare(self::END);
            $statement->execute([self::now(), $assignmentId, $patient['local_patient_id']]);
            if ($statement->rowCount() === 0) {
                throw new CareAssignmentFailure('ASIGNACION_NO_ENCONTRADA', 404);
            }
            $audit();
        });
    }

    /** Escritura y auditoría juntas: si la bitácora falla, el cambio se revierte (falla cerrada). */
    private function atomically(Closure $work): void
    {
        $pdo = ($this->pdo)();
        $pdo->beginTransaction();
        try {
            $work($pdo);
            $pdo->commit();
        } catch (\Throwable $failure) {
            $pdo->rollBack();
            throw $failure;
        }
    }

    private function patient(string $code): ?array
    {
        return $this->rows(self::PATIENT, [$code])[0] ?? null;
    }

    private function rows(string $sql, array $params): array
    {
        $statement = ($this->pdo)()->prepare($sql);
        $statement->execute($params);

        return array_map(static fn (array $row): array => array_map(
            static fn ($value) => $value === null ? null : (string) $value,
            $row,
        ), $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    private static function now(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s.uP');
    }

    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
