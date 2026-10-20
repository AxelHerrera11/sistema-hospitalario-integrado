<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Presentation;

use MicroHis\SignosVitales\Application\RecordVitalSigns;
use MicroHis\SignosVitales\Domain\VitalSigns;
use MicroHis\SignosVitales\Domain\VitalSignViolation;

/** Traduce la petición HTTP al caso de uso y el resultado a estado + cuerpo JSON. Sin SQL ni reglas de negocio. */
final class VitalSignHttpHandler
{
    public function __construct(private readonly RecordVitalSigns $record)
    {
    }

    public function record(array $headers, string $episodeId, array $body): array
    {
        try {
            $values = array_diff_key($body, ['measured_at' => true]);
            if (array_filter($values, static fn ($v): bool => !is_int($v) && !is_float($v)) !== []) {
                throw new VitalSignViolation('VITAL_SIGNS_INVALID', 422);
            }
            $vitals = new VitalSigns(array_map('floatval', $values));
            return ['status' => 201, 'body' => $this->record->execute(self::text($headers, 'X-User-ID'), $episodeId, $vitals, self::text($body, 'measured_at'))];
        } catch (VitalSignViolation $e) {
            return ['status' => $e->status, 'body' => ['code' => $e->getMessage()]];
        } catch (\RuntimeException) {
            return ['status' => 500, 'body' => ['code' => 'VITAL_SIGN_PERSISTENCE_ERROR']];
        }
    }

    /** Campo de texto opcional: ausente equivale a cadena vacía; cualquier otro tipo es 422 antes de llegar al caso de uso. */
    private static function text(array $source, string $key): string
    {
        $value = $source[$key] ?? '';
        return is_string($value) ? $value : throw new VitalSignViolation('PAYLOAD_INVALID', 422);
    }
}
