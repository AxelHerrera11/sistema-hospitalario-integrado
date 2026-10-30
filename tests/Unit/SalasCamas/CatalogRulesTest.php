<?php

declare(strict_types=1);

namespace Tests\Unit\SalasCamas;

use MicroHis\SalasCamas\Application\{BedRepository, CatalogOperationFailure, CatalogService, WardRepository};
use MicroHis\SalasCamas\Domain\{Bed, DomainRuleViolation, Ward};
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3).'/micro-his/salas-camas/bootstrap.php';

final class CatalogRulesTest extends TestCase
{
    public function test_u01_ward_normalizes_input_and_defaults_to_active(): void
    {
        $ward = Ward::create(['code' => ' W-1 ', 'name' => ' Sala ', 'location' => ' Piso 1 ']);
        $this->assertSame(['W-1', 'Sala', 'Piso 1', 'active', null],
            [$ward->code, $ward->name, $ward->location, $ward->status, $ward->id]);
    }

    public function test_u02_bed_preserves_relation_and_all_valid_states(): void
    {
        $default = Bed::create('ward-1', ['code' => ' B-1 ', 'type' => ' standard ']);
        $this->assertSame(['ward-1', 'B-1', 'standard', 'available'],
            [$default->wardId, $default->code, $default->type, $default->status]);
        foreach (['available', 'occupied', 'maintenance', 'inactive'] as $status) {
            $this->assertSame($status, Bed::create('ward-1', [
                'code' => 'B-1', 'type' => 'standard', 'status' => $status,
            ])->status);
        }
    }

    public function test_u03_ward_accepts_inactive_and_rejects_unknown_state(): void
    {
        $this->assertSame('inactive', (new Ward(null, 'W', 'Sala', 'Piso', 'inactive'))->status);
        try {
            new Ward(null, 'W', 'Sala', 'Piso', 'broken');
            $this->fail('Se aceptó un estado de ward inválido.');
        } catch (DomainRuleViolation $error) {
            $this->assertSame('WARD_STATUS_INVALID', $error->errorCode);
        }
    }

    public function test_cb01_missing_ward_short_circuits_bed_repository(): void
    {
        $wards = $this->createMock(WardRepository::class);
        $beds = $this->createMock(BedRepository::class);
        $wards->expects($this->once())->method('findById')->with('missing')->willReturn(null);
        $beds->expects($this->never())->method('listByWardId');
        try {
            (new CatalogService($wards, $beds))->listBedsForWard('missing');
            $this->fail('Se permitió consultar una ward inexistente.');
        } catch (CatalogOperationFailure $error) {
            $this->assertSame('WARD_NOT_FOUND', $error->errorCode);
        }
    }

    public function test_cb02_invalid_bed_is_translated_without_insert(): void
    {
        $wards = $this->createMock(WardRepository::class);
        $beds = $this->createMock(BedRepository::class);
        $wards->expects($this->once())->method('findByCode')->with('W-1')
            ->willReturn(new Ward('ward-1', 'W-1', 'Sala', 'Piso'));
        $beds->expects($this->never())->method('insertBed');
        try {
            (new CatalogService($wards, $beds))->createBed(' W-1 ', [
                'code' => 'B-1', 'type' => 'standard', 'status' => 'broken',
            ]);
            $this->fail('Se aceptó una cama inválida.');
        } catch (CatalogOperationFailure $error) {
            $this->assertSame('BED_STATUS_INVALID', $error->errorCode);
            $this->assertInstanceOf(DomainRuleViolation::class, $error->getPrevious());
        }
    }
}
