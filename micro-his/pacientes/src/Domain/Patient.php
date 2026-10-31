<?php
declare(strict_types=1);
namespace MicroHis\Pacientes\Domain;

use DateTimeImmutable;

final readonly class Patient
{
    private const SEXES = ['female','male','other','unknown'];
    private const STATUSES = ['active','inactive','deceased'];

    public function __construct(
        public ?string $id, public string $globalId, public string $code,
        public string $givenName, public string $familyName,
        public string $birthDate, public string $sex,
        public ?string $contact, public string $status = 'active'
    ) {
        if ($globalId === '' || $code === '' || $givenName === '' || $familyName === '') {
            throw new DomainRuleViolation('PATIENT_REQUIRED_FIELDS','Global ID, código, nombres y apellidos son obligatorios.');
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate);
        if (!$date || $date->format('Y-m-d') !== $birthDate) {
            throw new DomainRuleViolation('BIRTH_DATE_INVALID','La fecha debe usar YYYY-MM-DD.','birth_date');
        }
        if ($date > new DateTimeImmutable('today')) {
            throw new DomainRuleViolation('BIRTH_DATE_FUTURE','La fecha de nacimiento no puede ser futura.','birth_date');
        }
        if (!in_array($sex, self::SEXES, true)) {
            throw new DomainRuleViolation('SEX_INVALID','Sexo no válido.','sex');
        }
        if (!in_array($status, self::STATUSES, true)) {
            throw new DomainRuleViolation('STATUS_INVALID','Estado no válido.','status');
        }
    }

    public static function register(array $d): self
    {
        return new self(
            null, trim((string)($d['global_id'] ?? '')), trim((string)($d['code'] ?? '')),
            trim((string)($d['given_name'] ?? '')), trim((string)($d['family_name'] ?? '')),
            (string)($d['birth_date'] ?? ''), (string)($d['sex'] ?? ''),
            isset($d['contact']) ? trim((string)$d['contact']) : null,
            trim((string)($d['status'] ?? 'active'))
        );
    }

    public function update(array $d): self
    {
        return new self(
            $this->id, $this->globalId, $this->code,
            trim((string)($d['given_name'] ?? $this->givenName)),
            trim((string)($d['family_name'] ?? $this->familyName)),
            (string)($d['birth_date'] ?? $this->birthDate),
            (string)($d['sex'] ?? $this->sex),
            array_key_exists('contact',$d) ? ($d['contact'] === null ? null : trim((string)$d['contact'])) : $this->contact,
            (string)($d['status'] ?? $this->status)
        );
    }
}