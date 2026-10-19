<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Persistence;

use MicroHis\Medicos\Application\AppointmentReader;
use MicroHis\Medicos\Application\DoctorRepository;
use MicroHis\Medicos\Domain\Period;
use MicroHis\Medicos\Domain\SpecialtyLink;
use PDO;

/** Adaptador PDO del Repository (y lector de Citas); cada caso de uso solo ve su puerto (ISP). Siempre con sentencias preparadas. */
final class PdoDoctorStore implements DoctorRepository, AppointmentReader
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function activeSpecialtyId(string $code): ?string
    {
        return $this->rows("SELECT specialty_id FROM specialties WHERE code = ? AND status = 'active'", [$code], PDO::FETCH_COLUMN)[0] ?? null;
    }

    public function licenseExists(string $license): bool
    {
        return $this->rows('SELECT 1 FROM doctor_profiles WHERE license_number = ?', [$license], PDO::FETCH_COLUMN) !== [];
    }

    public function createDoctor(string $userRef, string $license, SpecialtyLink $primary, string $actorId): string
    {
        $id = 'doc-' . bin2hex(random_bytes(4));
        $this->transaction(function () use ($id, $userRef, $license, $primary, $actorId): void {
            $this->pdo->prepare('INSERT INTO doctor_profiles (doctor_profile_id, local_user_ref_id, license_number, valid_from) VALUES (?, ?, ?, ?)')
                ->execute([$id, $userRef, $license, $primary->period->from]);
            $this->insertLink($id, $primary, $actorId, 'medico_registrado');
        });
        return $id;
    }

    public function specialtiesOf(string $doctorId): ?array
    {
        $profile = $this->rows("SELECT valid_from, valid_to FROM doctor_profiles WHERE doctor_profile_id = ? AND status = 'active'", [$doctorId])[0] ?? null;
        $links = $this->rows("SELECT specialty_id, is_primary, valid_from, valid_to FROM doctor_specialties WHERE doctor_profile_id = ? AND status = 'active'", [$doctorId]);
        return $profile === null ? null : [
            new Period($profile['valid_from'], $profile['valid_to']),
            array_map(static fn (array $r) => new SpecialtyLink($r['specialty_id'], (bool) $r['is_primary'], new Period($r['valid_from'], $r['valid_to'])), $links),
        ];
    }

    public function associate(string $doctorId, SpecialtyLink $link, string $actorId): void
    {
        $this->transaction(fn () => $this->insertLink($doctorId, $link, $actorId, 'especialidad_asociada'));
    }

    public function doctorsWith(string $specialtyId, string $date): array
    {
        return $this->rows("SELECT p.doctor_profile_id FROM doctor_profiles p JOIN doctor_specialties s ON s.doctor_profile_id = p.doctor_profile_id
            WHERE s.specialty_id = ? AND p.status = 'active' AND s.status = 'active' AND s.valid_from <= ? AND (s.valid_to IS NULL OR s.valid_to > ?)
            ORDER BY p.doctor_profile_id", [$specialtyId, $date, $date], PDO::FETCH_COLUMN);
    }

    public function busySlots(string $doctorId, string $date): array
    {
        return $this->rows("SELECT substr(starts_at, 12, 5) FROM appointments WHERE doctor_profile_id = ? AND substr(starts_at, 1, 10) = ? AND status IN ('scheduled', 'confirmed')", [$doctorId, $date], PDO::FETCH_COLUMN);
    }

    private function insertLink(string $doctorId, SpecialtyLink $link, string $actorId, string $event): void
    {
        $this->pdo->prepare('INSERT INTO doctor_specialties (doctor_profile_id, specialty_id, is_primary, valid_from, valid_to) VALUES (?, ?, ?, ?, ?)')
            ->execute([$doctorId, $link->specialtyId, (int) $link->isPrimary, $link->period->from, $link->period->to]);
        $this->pdo->prepare('INSERT INTO local_access_audits (event, actor_id, detail) VALUES (?, ?, ?)')->execute([$event, $actorId, $doctorId . ':' . $link->specialtyId]);
    }

    /** Todo o nada: si falla cualquier INSERT (incluida la auditoría) se revierte y se informa un error de persistencia. */
    private function transaction(callable $work): void
    {
        try {
            $this->pdo->beginTransaction();
            $work();
            $this->pdo->commit();
        } catch (\PDOException $e) {
            $this->pdo->inTransaction() && $this->pdo->rollBack();
            throw new \RuntimeException('persistencia_no_disponible', 0, $e);
        }
    }

    private function rows(string $sql, array $params, int $mode = PDO::FETCH_ASSOC): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll($mode);
    }
}
