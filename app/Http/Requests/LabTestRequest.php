<?php

namespace App\Http\Requests;

use App\Models\LabTest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Alta y edición de pruebas del catálogo — Área 7 (RF-CAT-02).
 *
 * El permiso lo exige la ruta (laboratorio.gestionar_catalogo). Además de los
 * tipos, valida la coherencia de los rangos para que la regla 8.3 de
 * docs/modulo-laboratorio.md nunca clasifique un valor como normal y crítico
 * a la vez. En la edición, un límite que no viene en la petición se toma del
 * valor guardado.
 */
class LabTestRequest extends FormRequest
{
    /** decimal(10,4) de la migración create_laboratory_tables. */
    private const DECIMAL = ['nullable', 'numeric', 'between:-999999.9999,999999.9999'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->attributes->get('tenant');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('lab_tests', 'name')
                    ->where('tenant_id', $tenant->id)
                    ->ignore($this->labTest()?->id),
            ],
            'category' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:50'],
            'reference_min' => self::DECIMAL,
            'reference_max' => self::DECIMAL,
            'critical_min' => self::DECIMAL,
            'critical_max' => self::DECIMAL,
            'turnaround_min' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                foreach ($this->rangeErrors() as $field => $message) {
                    $validator->errors()->add($field, $message);
                }
            },
        ];
    }

    /** @return array<string, string> */
    private function rangeErrors(): array
    {
        $refMin = $this->limit('reference_min');
        $refMax = $this->limit('reference_max');
        $critMin = $this->limit('critical_min');
        $critMax = $this->limit('critical_max');

        // Con un solo límite de referencia, ese límite es el borde del rango normal.
        $lowestNormal = $refMin ?? $refMax;
        $highestNormal = $refMax ?? $refMin;

        $errors = [];

        if ($refMin !== null && $refMax !== null && $refMin > $refMax) {
            $errors['reference_max'] = 'El máximo de referencia debe ser mayor o igual que el mínimo de referencia.';
        }

        if ($critMin !== null && $lowestNormal !== null && $critMin >= $lowestNormal) {
            $errors['critical_min'] = 'El mínimo crítico debe ser menor que el rango de referencia.';
        }

        if ($critMax !== null && $highestNormal !== null && $critMax <= $highestNormal) {
            $errors['critical_max'] = 'El máximo crítico debe ser mayor que el rango de referencia.';
        }

        if ($critMin !== null && $critMax !== null && $critMin >= $critMax && ! isset($errors['critical_max'])) {
            $errors['critical_max'] = 'El máximo crítico debe ser mayor que el mínimo crítico.';
        }

        return $errors;
    }

    private function limit(string $field): ?float
    {
        $value = $this->exists($field) ? $this->input($field) : $this->labTest()?->getAttribute($field);

        return $value === null ? null : (float) $value;
    }

    private function labTest(): ?LabTest
    {
        $labTest = $this->route('labTest');

        return $labTest instanceof LabTest ? $labTest : null;
    }
}
