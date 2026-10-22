<?php

declare(strict_types=1);

// Reglas de dominio, sin dobles ni base de datos.

use MicroHis\MedicationCatalog\Domain\Medication;
use MicroHis\MedicationCatalog\Domain\MedicationRuleViolation;
use MicroHis\MedicationCatalog\Domain\Validity;

echo "== Dominio ==\n";
$draft = ['code' => ' amx-500 ', 'name' => 'Amoxicilina 500 mg (ficticio)', 'active_ingredient' => 'Amoxicilina', 'pharmacological_group' => 'Antibiótico'];
$medication = Medication::register($draft);
check('alta válida: código normalizado, sin id y vigencia active', $medication->code === 'AMX-500' && $medication->id === null && $medication->validity === Validity::Active);

$field = static fn (array $data): ?string => thrown(static fn () => Medication::register($data), MedicationRuleViolation::class)?->field;
check('rechaza el alta sin principio activo', $field(['active_ingredient' => '   '] + $draft) === 'active_ingredient');
check('rechaza el alta sin grupo farmacológico', $field(array_diff_key($draft, ['pharmacological_group' => 1])) === 'pharmacological_group');
check('rechaza un código con espacios internos', $field(['code' => 'AMX 500'] + $draft) === 'code');
check('rechaza un nombre de más de 120 caracteres', $field(['name' => str_repeat('a', 121)] + $draft) === 'name');

check('la vigencia acepta " ACTIVE " normalizado', Validity::fromInput(' ACTIVE ') === Validity::Active);
check('la vigencia rechaza estados ajenos como "expired" (no es vencimiento de lote)', thrown(static fn () => Validity::fromInput('expired'), MedicationRuleViolation::class)?->field === 'validity');

$stored = Medication::restore('7', 'AMX-500', 'Amoxicilina 500 mg (ficticio)', 'Amoxicilina', 'Antibiótico', Validity::Active);
$inactive = $stored->changeValidity(Validity::Inactive);
check('inactivar conserva identificador y datos y solo cambia la vigencia', $inactive->validity === Validity::Inactive
    && [$inactive->id, $inactive->code, $inactive->name] === ['7', 'AMX-500', 'Amoxicilina 500 mg (ficticio)'] && $stored->validity === Validity::Active);
check('repetir la vigencia actual es una violación de dominio', thrown(static fn () => $inactive->changeValidity(Validity::Inactive), MedicationRuleViolation::class) !== null);
