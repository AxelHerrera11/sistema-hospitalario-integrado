<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Persistence;

use DateTimeImmutable;
use DateTimeZone;
use MicroHis\Medicos\Application\AppointmentReader;
use MicroHis\Medicos\Application\DoctorRepository;
use MicroHis\Medicos\Domain\Period;
use MicroHis\Medicos\Domain\SpecialtyLink;
use PDO;

/**
 * Adaptador PDO del Repository (y lector de Citas); cada caso de uso solo ve su puerto (ISP). Siempre con sentencias preparadas.
 * Sirve para PostgreSQL (esquema HOSPITAL) y para el subconjunto SQLite de las pruebas: las diferencias de dialecto se resuelven aquí.
 */
final class PdoDoctorStore implements AppointmentReader, DoctorRepository
{
    private readonly DateTimeZone $zone;

    /** @param string $timezone zona en que se leen las horas de las citas (`starts_at` es timestamptz en PostgreSQL). */
    public function __construct(private readonly PDO $pdo, string $timezone = 'UTC')
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->zone = new DateTimeZone($timezone);
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
        $id = self::uuid();
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
            AND p.valid_from <= ? AND (p.valid_to IS NULL OR p.valid_to > ?)
            ORDER BY p.doctor_profile_id", [$specialtyId, $date, $date, $date, $date], PDO::FETCH_COLUMN);
    }

    /** Citas que solapan el día de la zona del hospital, recortadas a [00:00, 24:00]: una cita que cruza la medianoche ocupa su parte del día. */
    public function busyIntervals(string $doctorId, string $date): array
    {
        $dayStart = new DateTimeImmutable($date . ' 00:00:00', $this->zone);
        $dayEnd = $dayStart->modify('+1 day');
        $rows = $this->rows("SELECT starts_at, ends_at FROM appointments WHERE doctor_profile_id = ? AND starts_at < ? AND ends_at > ? AND status IN ('scheduled', 'confirmed') ORDER BY starts_at",
            [$doctorId, $this->instant($dayEnd), $this->instant($dayStart)]);
        $local = fn (string $at): DateTimeImmutable => (new DateTimeImmutable($at, new DateTimeZone('UTC')))->setTimezone($this->zone);
        return array_map(static function (array $row) use ($local, $dayStart, $dayEnd): array {
            [$from, $to] = [$local($row['starts_at']), $local($row['ends_at'])];
            return [$from <= $dayStart ? '00:00' : $from->format('H:i'), $to >= $dayEnd ? '24:00' : $to->format('H:i')];
        }, $rows);
    }

    /** Instante comparable con `starts_at`: ISO con zona en PostgreSQL (timestamptz); UTC sin zona en SQLite, donde se guarda como texto. */
    private function instant(DateTimeImmutable $moment): string
    {
        $utc = $moment->setTimezone(new DateTimeZone('UTC'));

        return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql' ? $utc->format('Y-m-d H:i:sP') : $utc->format('Y-m-d H:i:s');
    }

    /** UUID v4: `doctor_profile_id` es uuid en HOSPITAL y en SQLite se guarda como texto. */
    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0F | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3F | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    private function insertLink(string $doctorId, SpecialtyLink $link, string $actorId, string $event): void
    {
        $insert = $this->pdo->prepare('INSERT INTO doctor_specialties (doctor_profile_id, specialty_id, is_primary, valid_from, valid_to) VALUES (?, ?, ?, ?, ?)');
        $insert->bindValue(1, $doctorId);
        $insert->bindValue(2, $link->specialtyId);
        $insert->bindValue(3, $link->isPrimary, PDO::PARAM_BOOL);
        $insert->bindValue(4, $link->period->from);
        $insert->bindValue(5, $link->period->to);
        $insert->execute();
        $this->pdo->prepare("INSERT INTO local_access_audits (correlation_id, actor_user_ref_id, module, action, resource_type, resource_id) VALUES (?, ?, 'medicos', ?, 'doctor_profile', ?)")
            ->execute([self::uuid(), $actorId, 'medicos.'.$event, $doctorId]);
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
