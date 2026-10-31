<?php

declare(strict_types=1);

namespace MicroHis\ReportesOperativos\Domain;

/**
 * Filtros validados de un reporte. Un rango mayor a 366 días se rechaza antes de
 * tocar la fuente de datos para no generar consultas costosas.
 */
final readonly class ReportFilters
{
    private const MAX_DAYS = 366;

    /** @param array<string, string> $extra filtros propios del reporte (estado, médico, etc.) */
    public function __construct(
        public ?string $dateFrom,
        public ?string $dateTo,
        public int $perPage = 50,
        public string $order = 'asc',
        public array $extra = [],
    ) {
    }

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        $from = self::date($query['date_from'] ?? null, 'date_from');
        $to = self::date($query['date_to'] ?? null, 'date_to');
        if ($from === null && $to === null) {
            throw new ReportFailure(422, 'report_filter_invalid', 'Indique date_from o date_to.');
        }
        if ($from !== null && $to !== null) {
            if ($to < $from) {
                throw new ReportFailure(422, 'report_filter_invalid', 'date_to no puede ser anterior a date_from.');
            }
            if ((new \DateTimeImmutable($from))->diff(new \DateTimeImmutable($to))->days > self::MAX_DAYS) {
                throw new ReportFailure(422, 'report_range_too_long', 'El rango máximo es de 366 días.');
            }
        }
        $perPage = filter_var($query['per_page'] ?? 50, FILTER_VALIDATE_INT);
        if ($perPage === false || $perPage < 1 || $perPage > 100) {
            throw new ReportFailure(422, 'report_filter_invalid', 'per_page debe estar entre 1 y 100.');
        }
        $order = strtolower((string) ($query['order'] ?? 'asc'));
        if (!in_array($order, ['asc', 'desc'], true)) {
            throw new ReportFailure(422, 'report_filter_invalid', 'order debe ser asc o desc.');
        }

        return new self($from, $to, $perPage, $order, array_filter([
            'status' => is_string($query['status'] ?? null) ? $query['status'] : null,
            'doctor_profile_id' => is_string($query['doctor_profile_id'] ?? null) ? $query['doctor_profile_id'] : null,
        ], static fn (?string $value): bool => $value !== null));
    }

    /** @return array<string, mixed> filtros aplicados, para la cabecera X-Report-Filters */
    public function toArray(): array
    {
        return array_filter($this->extra + [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'per_page' => $this->perPage,
            'order' => $this->order,
        ], static fn (mixed $value): bool => $value !== null);
    }

    private static function date(mixed $value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $valid = is_string($value)
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1
            && checkdate((int) substr($value, 5, 2), (int) substr($value, 8, 2), (int) substr($value, 0, 4));
        if (!$valid) {
            throw new ReportFailure(422, 'report_filter_invalid', "{$field} debe usar el formato Y-m-d.");
        }

        return $value;
    }
}
