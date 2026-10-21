<?php
declare(strict_types=1);
namespace Reception\Presentation;

use Reception\Application\ReceiveSpecimen;

final class ReceptionController
{
    public function __construct(private ReceiveSpecimen $receive) {}

    public function store(array $input, string $tenant, string $actor): array
    {
        foreach (['hemolizada', 'coagulada', 'derramada'] as $flag) {
            if (isset($input[$flag]) && !is_bool($input[$flag])) return ['status' => 422, 'message' => 'Condición inválida'];
        }
        foreach (['item_id', 'patient_code', 'patient_name', 'type', 'container', 'collected_at'] as $key) {
            if (!isset($input[$key]) || !is_string($input[$key]) || trim($input[$key]) === '') {
                return ['status' => 422, 'message' => 'Campo inválido: '.$key];
            }
        }
        if (!in_array($input['type'], ['blood', 'urine', 'swab'], true)
            || !isset($input['volume'], $input['temperature'])
            || !is_numeric($input['volume']) || !is_numeric($input['temperature'])
            || !is_finite((float) $input['volume']) || !is_finite((float) $input['temperature'])
            || $input['volume'] < 0) return ['status' => 422, 'message' => 'Observaciones inválidas'];
        try { new \DateTimeImmutable($input['collected_at']); }
        catch (\Exception) { return ['status' => 422, 'message' => 'Fecha inválida']; }
        try {
            return ['status' => 201, 'data' => $this->receive->execute($tenant, $actor, $input, new \DateTimeImmutable())];
        } catch (\DomainException $e) {
            return ['status' => $e->getCode(), 'message' => $e->getMessage()];
        } catch (\Throwable) { return ['status' => 500, 'message' => 'No se pudo guardar la muestra']; }
    }
}
