<?php

declare(strict_types=1);

namespace Reception\Presentation;

use Reception\Application\ReceiveSpecimen;
use Reception\Domain\CodeCollision;
use Reception\Domain\ReceptionError;

final class ReceptionController
{
    public function __construct(private ReceiveSpecimen $receive) {}

    public function store(array $input, string $tenant, string $actor): array
    {
        foreach (['hemolizada', 'coagulada', 'derramada', 'sin_etiqueta'] as $flag) {
            if (isset($input[$flag]) && ! is_bool($input[$flag])) {
                return $this->invalid('Condición inválida');
            }
        }
        foreach (['item_id', 'request_key', 'patient_code', 'patient_name', 'type', 'container', 'collected_at'] as $key) {
            if (! isset($input[$key]) || ! is_string($input[$key]) || trim($input[$key]) === '') {
                return $this->invalid('Campo inválido: '.$key);
            }
        }
        foreach (['item_id', 'request_key'] as $key) {
            if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/iD', $input[$key])) {
                return $this->invalid('UUID inválido: '.$key);
            }
        }
        foreach (['volume' => [0, 1000], 'temperature' => [-80, 100]] as $key => [$min, $max]) {
            if (! isset($input[$key]) || ! is_numeric($input[$key]) || ! is_finite((float) $input[$key]) || $input[$key] < $min || $input[$key] > $max) {
                return $this->invalid('Observación inválida: '.$key);
            }
        }
        if (! in_array($input['type'], ['blood', 'urine', 'swab'], true) || ! \DateTimeImmutable::createFromFormat(DATE_ATOM, $input['collected_at'])) {
            return $this->invalid('Tipo o fecha inválidos');
        }
        try {
            return ['status' => 201, 'data' => $this->receive->execute($tenant, $actor, $input, new \DateTimeImmutable)];
        } catch (ReceptionError $error) {
            return ['status' => $error->getCode(), 'error' => $error->error, 'message' => $error->getMessage()];
        } catch (CodeCollision) {
            return ['status' => 500, 'error' => 'CODE_GENERATION_FAILED', 'message' => 'No se pudo generar código único'];
        } catch (\Throwable) {
            return ['status' => 503, 'error' => 'STORAGE_UNAVAILABLE', 'message' => 'Persistencia no disponible'];
        }
    }

    private function invalid(string $message): array
    {
        return ['status' => 422, 'error' => 'VALIDATION_FAILED', 'message' => $message];
    }
}
