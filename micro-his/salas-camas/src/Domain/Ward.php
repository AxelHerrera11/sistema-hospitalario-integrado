<?php
declare(strict_types=1);

namespace MicroHis\SalasCamas\Domain;

final readonly class Ward
{
    private const STATUSES = ['active', 'inactive'];

    public function __construct(
        public ?string $id,
        public string $code,
        public string $name,
        public string $location,
        public string $status = 'active'
    ) {
        if ($code === '' || $name === '' || $location === '') {
            throw new DomainRuleViolation('WARD_REQUIRED_FIELDS', 'Código, nombre y ubicación son obligatorios.');
        }
        if (!in_array($status, self::STATUSES, true)) {
            throw new DomainRuleViolation('WARD_STATUS_INVALID', 'Estado de sala no válido.');
        }
    }

    public static function create(array $data): self
    {
        return new self(null, trim((string)($data['code'] ?? '')), trim((string)($data['name'] ?? '')),
            trim((string)($data['location'] ?? '')), (string)($data['status'] ?? 'active'));
    }
}
