<?php

namespace Database\Seeders;

use App\Models\Medication;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MedicationCatalogSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $medications = [
            ['name' => 'Amoxicilina 500mg',       'generic_name' => 'Amoxicilina',                  'category' => 'antibiótico',                 'unit' => 'mg'],
            ['name' => 'Ciprofloxacina 500mg',     'generic_name' => 'Ciprofloxacina',               'category' => 'antibiótico',                 'unit' => 'mg'],
            ['name' => 'Metronidazol 500mg',        'generic_name' => 'Metronidazol',                 'category' => 'antibiótico',                 'unit' => 'mg'],
            ['name' => 'Azitromicina 500mg',        'generic_name' => 'Azitromicina',                 'category' => 'antibiótico',                 'unit' => 'mg'],
            ['name' => 'Ceftriaxona 1g',            'generic_name' => 'Ceftriaxona',                  'category' => 'antibiótico',                 'unit' => 'mg'],
            ['name' => 'Paracetamol 500mg',         'generic_name' => 'Acetaminofén',                 'category' => 'analgésico',                  'unit' => 'mg'],
            ['name' => 'Ibuprofeno 400mg',          'generic_name' => 'Ibuprofeno',                   'category' => 'antiinflamatorio',            'unit' => 'mg'],
            ['name' => 'Tramadol 50mg',             'generic_name' => 'Tramadol',                     'category' => 'analgésico',                  'unit' => 'mg'],
            ['name' => 'Morfina 10mg',              'generic_name' => 'Sulfato de Morfina',           'category' => 'analgésico opioide',          'unit' => 'mg'],
            ['name' => 'Losartán 50mg',             'generic_name' => 'Losartán Potásico',            'category' => 'antihipertensivo',            'unit' => 'mg'],
            ['name' => 'Enalapril 10mg',            'generic_name' => 'Maleato de Enalapril',         'category' => 'antihipertensivo',            'unit' => 'mg'],
            ['name' => 'Amlodipino 5mg',            'generic_name' => 'Amlodipino',                   'category' => 'antihipertensivo',            'unit' => 'mg'],
            ['name' => 'Metformina 850mg',          'generic_name' => 'Clorhidrato de Metformina',    'category' => 'antidiabético',               'unit' => 'mg'],
            ['name' => 'Glibenclamida 5mg',         'generic_name' => 'Glibenclamida',                'category' => 'antidiabético',               'unit' => 'mg'],
            ['name' => 'Insulina Regular',          'generic_name' => 'Insulina Humana Regular',      'category' => 'antidiabético',               'unit' => 'UI'],
            ['name' => 'Insulina NPH',              'generic_name' => 'Insulina Isofana NPH',         'category' => 'antidiabético',               'unit' => 'UI'],
            ['name' => 'Atorvastatina 20mg',        'generic_name' => 'Atorvastatina Cálcica',        'category' => 'hipolipemiante',              'unit' => 'mg'],
            ['name' => 'Omeprazol 20mg',            'generic_name' => 'Omeprazol',                    'category' => 'inhibidor bomba de protones', 'unit' => 'mg'],
            ['name' => 'Ranitidina 150mg',          'generic_name' => 'Clorhidrato de Ranitidina',    'category' => 'antihistamínico H2',          'unit' => 'mg'],
            ['name' => 'Salbutamol 100mcg',         'generic_name' => 'Sulfato de Salbutamol',        'category' => 'broncodilatador',             'unit' => 'mcg'],
            ['name' => 'Dexametasona 4mg',          'generic_name' => 'Dexametasona',                 'category' => 'corticosteroide',             'unit' => 'mg'],
            ['name' => 'Prednisona 5mg',            'generic_name' => 'Prednisona',                   'category' => 'corticosteroide',             'unit' => 'mg'],
            ['name' => 'Furosemida 40mg',           'generic_name' => 'Furosemida',                   'category' => 'diurético',                   'unit' => 'mg'],
            ['name' => 'Espironolactona 25mg',      'generic_name' => 'Espironolactona',              'category' => 'diurético',                   'unit' => 'mg'],
            ['name' => 'Digoxina 0.25mg',           'generic_name' => 'Digoxina',                     'category' => 'glucósido cardíaco',          'unit' => 'mg'],
            ['name' => 'Warfarina 5mg',             'generic_name' => 'Warfarina Sódica',             'category' => 'anticoagulante',              'unit' => 'mg'],
            ['name' => 'Enoxaparina 40mg',          'generic_name' => 'Enoxaparina Sódica',           'category' => 'anticoagulante',              'unit' => 'mg'],
            ['name' => 'Aspirina 100mg',            'generic_name' => 'Ácido Acetilsalicílico',       'category' => 'antiagregante plaquetario',   'unit' => 'mg'],
            ['name' => 'Diazepam 5mg',              'generic_name' => 'Diazepam',                     'category' => 'benzodiacepina',              'unit' => 'mg'],
            ['name' => 'Solución Salina 0.9%',      'generic_name' => 'Cloruro de Sodio 0.9%',        'category' => 'solución IV',                 'unit' => 'ml'],
        ];

        $tenants = Tenant::query()->get();

        foreach ($tenants as $tenant) {
            foreach ($medications as $med) {
                Medication::query()->firstOrCreate(
                    ['tenant_id' => $tenant->id, 'name' => $med['name']],
                    [
                        'tenant_id'      => $tenant->id,
                        'name'           => $med['name'],
                        'generic_name'   => $med['generic_name'],
                        'category'       => $med['category'],
                        'presentation'   => null,
                        'concentration'  => $med['unit'] ?? null,
                        'active'         => true,
                    ]
                );
            }
        }
    }
}
