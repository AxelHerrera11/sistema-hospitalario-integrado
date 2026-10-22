<?php

declare(strict_types=1);

namespace MicroHis\Occupancy\Domain;

final class OccupancyCalculator
{
    /** @param list<BedSnapshot> $beds */
    public function calculate(array $beds, \DateTimeImmutable $cutoff): array
    {
        $wards = [];
        foreach ($beds as $bed) {
            $wards[$bed->wardId] ??= $this->emptyCounts($bed->wardId, $bed->wardCode);
            $category = $bed->hasActiveStay
                ? 'occupied'
                : match ($bed->operationalStatus) {
                    'available' => 'available',
                    'maintenance' => 'maintenance',
                    'inactive' => 'inactive',
                    'occupied' => 'unverified_occupied',
                };
            $wards[$bed->wardId][$category]++;
            $wards[$bed->wardId]['total_beds']++;
            if (($bed->hasActiveStay && $bed->operationalStatus !== 'occupied')
                || (!$bed->hasActiveStay && $bed->operationalStatus === 'occupied')) {
                $wards[$bed->wardId]['inconsistencies']++;
            }
        }

        $global = $this->emptyCounts(null, null);
        foreach ($wards as &$ward) {
            $ward['occupancy_rate'] = $this->rate($ward);
            $ward['distribution_percentages'] = $this->percentages($ward);
            foreach (['occupied', 'available', 'maintenance', 'inactive', 'unverified_occupied', 'total_beds', 'inconsistencies'] as $field) {
                $global[$field] += $ward[$field];
            }
        }
        unset($ward);
        $global['occupancy_rate'] = $this->rate($global);
        $global['distribution_percentages'] = $this->percentages($global);

        return ['cutoff_at' => $cutoff->format(DATE_ATOM), 'global' => $global, 'wards' => array_values($wards)];
    }

    private function emptyCounts(?string $wardId, ?string $wardCode): array
    {
        return ['ward_id' => $wardId, 'ward_code' => $wardCode] + [
            'occupied' => 0, 'available' => 0, 'maintenance' => 0, 'inactive' => 0,
            'unverified_occupied' => 0, 'total_beds' => 0, 'inconsistencies' => 0,
        ];
    }

    private function rate(array $counts): ?float
    {
        // Tasa sobre camas operativas: el inventario menos las camas en
        // mantenimiento y las inactivas (contrato de ocupacion vigente).
        $operational = $counts['total_beds'] - $counts['maintenance'] - $counts['inactive'];

        return $operational <= 0 ? null : round($counts['occupied'] / $operational * 100, 2);
    }

    private function percentages(array $counts): array
    {
        foreach (['occupied', 'available', 'maintenance', 'inactive', 'unverified_occupied'] as $category) {
            $result[$category] = $counts['total_beds'] === 0 ? 0.0 : round($counts[$category] / $counts['total_beds'] * 100, 2);
        }

        return $result;
    }
}
