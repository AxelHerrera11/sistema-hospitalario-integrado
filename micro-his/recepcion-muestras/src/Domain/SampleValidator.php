<?php
declare(strict_types=1);
namespace Reception\Domain;

final class SampleValidator implements RequirementsValidator
{
    public function __construct(private string $type) {}

    public function reason(array $observed, array $requirements, \DateTimeImmutable $now): ?string
    {
        if ($observed['type'] !== $this->type) return 'tipo_incorrecto';
        if ($observed['container'] !== $requirements['container']) return 'contenedor_incorrecto';
        if ($observed['volume'] < $requirements['min_volume']) return 'volumen_insuficiente';
        $age = $now->getTimestamp() - (new \DateTimeImmutable($observed['collected_at']))->getTimestamp();
        if ($age < 0 || $age > $requirements['max_minutes'] * 60) return 'fuera_de_estabilidad';
        if ($observed['temperature'] < $requirements['min_temperature']
            || $observed['temperature'] > $requirements['max_temperature']) return 'temperatura_inadecuada';
        foreach (['hemolizada', 'coagulada', 'derramada'] as $flag) {
            if ($observed[$flag] ?? false) return $flag;
        }
        return null;
    }
}
