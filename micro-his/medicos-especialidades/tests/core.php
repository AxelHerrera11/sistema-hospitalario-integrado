<?php

declare(strict_types=1);

use MicroHis\Medicos\Application\DoctorProfileStore;
use MicroHis\Medicos\Application\RegisterDoctor;
use MicroHis\Medicos\Application\SpecialtyCatalog;
use MicroHis\Medicos\Domain\DoctorRuleViolation;
use MicroHis\Medicos\Domain\SlotPlanner;
use MicroHis\Medicos\Domain\SpecialtyLink;

require __DIR__ . '/../bootstrap.php';

/** Pruebas del núcleo sin BD: fakes en memoria de los puertos y validaciones defensivas. */
function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": $name" . PHP_EOL;
    $ok || exit(1);
}

function fails(callable $fn, string $exception, ?string $code = null): bool
{
    try {
        $fn();
    } catch (Throwable $e) {
        return $e instanceof $exception && ($code === null || $e->getMessage() === $code);
    }
    return false;
}

$store = new class implements DoctorProfileStore {
    public int $created = 0;

    public function licenseExists(string $license): bool
    {
        return false;
    }

    public function createDoctor(string $userRef, string $license, SpecialtyLink $primary, string $actorId): string
    {
        return 'doc-' . ++$this->created;
    }
};
$catalog = new class implements SpecialtyCatalog {
    public function activeSpecialtyId(string $code): ?string
    {
        return $code === 'CARD' ? 'sp-card' : null;
    }
};
$register = new RegisterDoctor($store, $catalog);

check('validación: userRef solo con espacios -> PAYLOAD_INVALID', fails(fn () => $register->execute('admin1', '   ', 'COL-1', 'CARD', '2026-01-01'), DoctorRuleViolation::class, 'PAYLOAD_INVALID'));
check('validación: licencia solo con espacios -> PAYLOAD_INVALID', fails(fn () => $register->execute('admin1', 'u-ana', " \t ", 'CARD', '2026-01-01'), DoctorRuleViolation::class, 'PAYLOAD_INVALID'));
check('validación: los campos en blanco no llegan a createDoctor()', $store->created === 0);
check('camino feliz: alta válida llega a createDoctor()', $register->execute('admin1', 'u-ana', 'COL-1', 'CARD', '2026-01-01') === 'doc-1');

$planner = new SlotPlanner();
check('franjas: tamaño 0 se rechaza en vez de entrar en bucle', fails(fn () => $planner->freeSlots([['08:00', '09:00']], [], 0), InvalidArgumentException::class));
check('franjas: tamaño negativo se rechaza', fails(fn () => $planner->freeSlots([['08:00', '09:00']], [], -30), InvalidArgumentException::class));
check('franjas: 30 minutos sigue funcionando', $planner->freeSlots([['08:00', '09:00']], [['08:30', '09:00']]) === ['08:00']);
check('D-02: una cita 08:15-08:45 bloquea 08:00 y 08:30 aunque empiece fuera de la rejilla', $planner->freeSlots([['08:00', '09:30']], [['08:15', '08:45']]) === ['09:00']);
check('D-02: una cita larga 08:00-09:30 bloquea todos los bloques que cubre; 09:30 queda libre', $planner->freeSlots([['08:00', '10:00']], [['08:00', '09:30']]) === ['09:30']);
check('D-02: intervalo semiabierto: una cita que termina a las 08:30 no bloquea 08:30', $planner->freeSlots([['08:00', '09:00']], [['07:30', '08:30']]) === ['08:30']);
echo 'Todas las pruebas pasaron.' . PHP_EOL;
