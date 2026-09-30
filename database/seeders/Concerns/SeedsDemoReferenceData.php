<?php

namespace Database\Seeders\Concerns;

trait SeedsDemoReferenceData
{
    protected function guatemalanFirstNamesMale(): array
    {
        return [
            'José', 'Juan', 'Carlos', 'Luis', 'Miguel', 'Pedro', 'Erick', 'Mario',
            'Hugo', 'Diego', 'Rodrigo', 'Fernando', 'Antonio', 'Roberto', 'Francisco',
        ];
    }

    protected function guatemalanFirstNamesFemale(): array
    {
        return [
            'María', 'Ana', 'Rosa', 'Claudia', 'Sandra', 'Lucía', 'Gloria', 'Elena',
            'Carmen', 'Patricia', 'Silvia', 'Marta', 'Isabel', 'Jessica', 'Karina',
        ];
    }

    protected function guatemalanLastNames(): array
    {
        return [
            'García', 'López', 'Martínez', 'González', 'Hernández', 'Pérez', 'Ramírez',
            'Torres', 'Flores', 'Rivera', 'Chávez', 'Morales', 'Reyes', 'Castillo',
            'Aguilar', 'Vásquez', 'Dávila', 'Ruiz', 'Molina', 'Fuentes',
        ];
    }

    protected function diagnosesCie10(): array
    {
        return [
            ['code' => 'I10', 'desc' => 'Hipertensión arterial esencial', 'type' => 'principal'],
            ['code' => 'E11.9', 'desc' => 'Diabetes mellitus tipo 2', 'type' => 'principal'],
            ['code' => 'J18.9', 'desc' => 'Neumonía no especificada', 'type' => 'presuntivo'],
            ['code' => 'N39.0', 'desc' => 'Infección del tracto urinario', 'type' => 'secundario'],
            ['code' => 'K35.80', 'desc' => 'Apendicitis aguda', 'type' => 'definitivo'],
        ];
    }

    protected function allergyCatalog(): array
    {
        return [
            'medicamento' => [
                ['allergen' => 'Penicilina', 'reaction' => 'Urticaria generalizada'],
                ['allergen' => 'Aspirina', 'reaction' => 'Broncoespasmo agudo'],
                ['allergen' => 'Sulfas', 'reaction' => 'Erupción cutánea'],
                ['allergen' => 'Codeína', 'reaction' => 'Náuseas severas'],
            ],
            'alimento' => [
                ['allergen' => 'Mariscos', 'reaction' => 'Angioedema'],
                ['allergen' => 'Maní', 'reaction' => 'Anafilaxia'],
                ['allergen' => 'Leche de vaca', 'reaction' => 'Diarrea y vómitos'],
            ],
            'ambiental' => [
                ['allergen' => 'Polen', 'reaction' => 'Rinitis alérgica'],
                ['allergen' => 'Látex', 'reaction' => 'Dermatitis de contacto'],
            ],
        ];
    }

    protected function labTestsCatalog(): array
    {
        return [
            ['name' => 'Hemoglobina', 'category' => 'Hematología', 'unit' => 'g/dL', 'reference_min' => 12, 'reference_max' => 17.5, 'critical_min' => 7, 'critical_max' => 20],
            ['name' => 'Hematocrito', 'category' => 'Hematología', 'unit' => '%', 'reference_min' => 36, 'reference_max' => 52, 'critical_min' => 20, 'critical_max' => 60],
            ['name' => 'Leucocitos', 'category' => 'Hematología', 'unit' => 'x10³/µL', 'reference_min' => 4.5, 'reference_max' => 11, 'critical_min' => 2, 'critical_max' => 30],
            ['name' => 'Plaquetas', 'category' => 'Hematología', 'unit' => 'x10³/µL', 'reference_min' => 150, 'reference_max' => 400, 'critical_min' => 50, 'critical_max' => 1000],
            ['name' => 'Glucosa', 'category' => 'Química', 'unit' => 'mg/dL', 'reference_min' => 70, 'reference_max' => 100, 'critical_min' => 40, 'critical_max' => 400],
            ['name' => 'Creatinina', 'category' => 'Química', 'unit' => 'mg/dL', 'reference_min' => 0.7, 'reference_max' => 1.2, 'critical_min' => 0.3, 'critical_max' => 10],
            ['name' => 'Potasio', 'category' => 'Química', 'unit' => 'mEq/L', 'reference_min' => 3.5, 'reference_max' => 5, 'critical_min' => 2.5, 'critical_max' => 6.5],
            ['name' => 'Troponina I', 'category' => 'Cardiología', 'unit' => 'ng/mL', 'reference_min' => 0, 'reference_max' => 0.04, 'critical_min' => null, 'critical_max' => 0.5],
            ['name' => 'ALT (TGP)', 'category' => 'Química', 'unit' => 'U/L', 'reference_min' => 7, 'reference_max' => 56, 'critical_min' => null, 'critical_max' => 500],
            ['name' => 'pH Urinario', 'category' => 'Orina', 'unit' => '', 'reference_min' => 4.5, 'reference_max' => 8, 'critical_min' => 4, 'critical_max' => 9],
        ];
    }

    protected function generateDpi(): string
    {
        return sprintf('%013d', random_int(1000000000000, 3999999999999));
    }

    protected function generateGuatemalaPhone(): string
    {
        return (string) random_int(3, 6) . random_int(1000000, 9999999);
    }

    protected function randomAddress(): string
    {
        $zone = random_int(1, 21);
        $av = random_int(1, 15);
        $calle = random_int(1, 15);
        $num = random_int(1, 99);

        return "Zona {$zone}, {$av} Avenida {$calle}-{$num}";
    }

    protected function randomBloodType(): string
    {
        $pool = ['O+', 'O+', 'A+', 'A+', 'B+', 'AB+', 'O-', 'A-'];

        return $pool[array_rand($pool)];
    }
}
