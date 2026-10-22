<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Persistence;

use MicroHis\Medicos\Application\DoctorRepository;
use MicroHis\Medicos\Domain\Period;
use MicroHis\Medicos\Domain\SpecialtyLink;

/** Adaptador en memoria del mismo contrato que PdoDoctorStore: pruebas y demo sin base de datos. */
final class InMemoryDoctorRepository implements DoctorRepository
{
    /** @var list<array{event: string, actor_id: string, detail: string}> */
    public array $audits = [];
    private int $sequence = 0;

    /**
     * @param array<string, array{0: string, 1: string}> $specialties código => [id, estado]
     * @param array<string, array{user: string, license: string, status: string, period: Period}> $profiles id => perfil
     * @param array<string, list<SpecialtyLink>> $links id del médico => vínculos activos
     * @param bool $storageDown simula la base caída: toda escritura falla sin dejar cambios
     */
    public function __construct(private array $specialties = [], private array $profiles = [], private array $links = [], private readonly bool $storageDown = false)
    {
    }

    public function activeSpecialtyId(string $code): ?string
    {
        [$id, $status] = $this->specialties[$code] ?? [null, null];
        return $status === 'active' ? $id : null;
    }

    public function licenseExists(string $license): bool
    {
        return in_array($license, array_column($this->profiles, 'license'), true);
    }

    public function createDoctor(string $userRef, string $license, SpecialtyLink $primary, string $actorId): string
    {
        $this->assertWritable();
        $id = 'doc-' . ++$this->sequence;
        $this->profiles[$id] = ['user' => $userRef, 'license' => $license, 'status' => 'active', 'period' => new Period($primary->period->from)];
        $this->addLink($id, $primary, $actorId, 'medico_registrado');
        return $id;
    }

    public function specialtiesOf(string $doctorId): ?array
    {
        $profile = $this->profiles[$doctorId] ?? null;
        return $profile === null || $profile['status'] !== 'active' ? null : [$profile['period'], $this->links[$doctorId] ?? []];
    }

    public function associate(string $doctorId, SpecialtyLink $link, string $actorId): void
    {
        $this->assertWritable();
        $this->addLink($doctorId, $link, $actorId, 'especialidad_asociada');
    }

    public function doctorsWith(string $specialtyId, string $date): array
    {
        $ids = [];
        foreach ($this->profiles as $id => $profile) {
            $vigente = $profile['status'] === 'active' && $profile['period']->from <= $date && ($profile['period']->to === null || $profile['period']->to > $date);
            foreach ($vigente ? $this->links[$id] ?? [] : [] as $link) {
                if ($link->specialtyId === $specialtyId && $link->period->from <= $date && ($link->period->to === null || $link->period->to > $date)) {
                    $ids[] = (string) $id;
                    break;
                }
            }
        }
        sort($ids);
        return $ids;
    }

    private function addLink(string $doctorId, SpecialtyLink $link, string $actorId, string $event): void
    {
        $this->links[$doctorId][] = $link;
        $this->audits[] = ['event' => $event, 'actor_id' => $actorId, 'detail' => $doctorId . ':' . $link->specialtyId];
    }

    /** Se verifica antes de modificar nada, así una falla no deja el perfil sin su auditoría. */
    private function assertWritable(): void
    {
        $this->storageDown && throw new \RuntimeException('persistencia_no_disponible');
    }
}
