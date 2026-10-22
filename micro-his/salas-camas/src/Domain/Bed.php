<?php
declare(strict_types=1);

namespace MicroHis\SalasCamas\Domain;

final readonly class Bed
{
    private const STATUSES = ['available', 'occupied', 'maintenance', 'inactive'];

    public function __construct(
        public ?string $id,
        public string $wardId,
        public string $code,
        public string $type,
        public string $status = 'available'
    ) {
        if ($wardId === '' || $code === '' || $type === '') {
            throw new DomainRuleViolation('BED_REQUIRED_FIELDS', 'Sala, código y tipo de cama son obligatorios.');
        }
        if (!in_array($status, self::STATUSES, true)) {
            throw new DomainRuleViolation('BED_STATUS_INVALID', 'Estado operativo de cama no válido.');
        }
    }

    public static function create(string $wardId, array $data): self
    {
        return new self(null, $wardId, trim((string)($data['code'] ?? '')),
            trim((string)($data['type'] ?? '')), (string)($data['status'] ?? 'available'));
    }
}
