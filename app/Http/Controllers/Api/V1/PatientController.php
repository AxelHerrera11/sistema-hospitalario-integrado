<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Tenant;
use App\Services\PatientCodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Pacientes — Área 2.
 *
 * Toda ruta exige ['tenant', 'auth.jwt'] y un permiso (ver routes/api.php).
 * El aislamiento por hospital no se escribe aquí: lo aplica el global scope
 * del trait BelongsToTenant. Por eso un paciente de otro hospital no resuelve
 * y findOrFail responde 404 sin revelar que existe.
 *
 * {patient} se resuelve aquí con findOrFail y no con binding implícito de
 * modelo. Ya no es una necesidad de seguridad: desde el PR #17,
 * bootstrap/app.php hace que 'tenant' corra ANTES que SubstituteBindings, así
 * que el binding implícito también encuentra currentTenant y respeta el
 * hospital (cubierto en TenantIsolationTest). Resolver por id en el controller
 * sigue siendo válido y equivalente: en ambos casos el global scope ya está
 * activo y un id de otro hospital responde 404.
 */
class PatientController extends Controller
{
    /** Valores exactos de los enums de la migración create_admission_catalogs. */
    private const GENDERS = ['M', 'F', 'otro'];

    private const BLOOD_TYPES = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];

    /** Columnas por las que se permite ordenar (lista blanca, nunca input crudo). */
    private const SORTABLE = ['last_name', 'first_name', 'code', 'birth_date', 'created_at'];

    /**
     * Solo el resumen del expediente, para que el listado y el detalle
     * indiquen si el paciente ya tiene expediente sin traer los antecedentes.
     * NO se carga vitalSigns: esa relación trae orderByDesc y contarla revienta
     * en PostgreSQL (pertenece al Área 5).
     */
    private const MEDICAL_RECORD = 'medicalRecord:id,tenant_id,patient_id,record_number,opened_at';

    public function __construct(private readonly PatientCodeGenerator $codes) {}

    public function index(Request $request): JsonResponse
    {
        // 'search' es el nombre del contrato de Área 2; 'q' se acepta como
        // alias porque es el parámetro que ya usa el módulo de médicos/citas.
        $term = $request->query('search') ?: $request->query('q');
        $perPage = max(1, min((int) $request->integer('per_page', 15), 50));
        $sortBy = $request->query('sort_by', 'last_name');
        $sortBy = in_array($sortBy, self::SORTABLE, true) ? $sortBy : 'last_name';
        $sortDir = $request->query('sort_dir') === 'desc' ? 'desc' : 'asc';

        $patients = Patient::query()
            ->with(self::MEDICAL_RECORD)
            ->when($term, fn ($query) => $query->whereSearch(
                ['first_name', 'last_name', 'dpi', 'code', 'phone'],
                $term,
            ))
            ->orderBy($sortBy, $sortDir)
            // 'id' de desempate: sin él dos apellidos iguales pueden repetirse
            // o saltarse entre páginas al paginar.
            ->orderBy('id')
            ->paginate($perPage)
            // Conserva search/per_page/sort_* en los enlaces de paginación.
            ->withQueryString();

        return response()->json($patients);
    }

    public function show(int $id): JsonResponse
    {
        $patient = $this->findPatient($id);

        return response()->json([
            'patient' => $patient->load(self::MEDICAL_RECORD),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        // validate() devuelve SOLO las claves declaradas en rules(): si el
        // cliente manda 'tenant_id' u otro campo no permitido, queda descartado.
        $data = $request->validate($this->rules($tenant));

        $data['code'] ??= $this->codes->generate($tenant);

        // Barrera explícita: el hospital se toma del contexto ya validado por
        // TenantMiddleware, jamás del cuerpo de la petición.
        $data['tenant_id'] = $tenant->id;

        $patient = Patient::query()->create($data);

        return response()->json([
            'patient' => $patient->load(self::MEDICAL_RECORD),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');

        $patient = $this->findPatient($id);

        // 'tenant_id' no está en rules(), así que un tenant_id enviado por el
        // cliente se ignora y el paciente no puede moverse de hospital.
        $patient->update($request->validate($this->rules($tenant, $patient)));

        return response()->json([
            'patient' => $patient->fresh()->load(self::MEDICAL_RECORD),
        ]);
    }

    /**
     * Busca el paciente dentro del hospital de la petición.
     *
     * El global scope de BelongsToTenant ya agrega where tenant_id = ?; los
     * pacientes de otro hospital (y los borrados lógicamente) no resuelven y
     * findOrFail devuelve 404 en lugar de 403, para no confirmar que existen.
     */
    private function findPatient(int $id): Patient
    {
        return Patient::query()->findOrFail($id);
    }

    /**
     * Reglas derivadas de las columnas reales de la tabla patients.
     *
     * @see database/migrations/2026_04_26_100000_create_admission_catalogs.php
     */
    private function rules(Tenant $tenant, ?Patient $patient = null): array
    {
        $uniqueCode = Rule::unique('patients', 'code')->where('tenant_id', $tenant->id);

        if ($patient !== null) {
            $uniqueCode->ignore($patient->id);
        }

        return [
            'code' => ['sometimes', 'string', 'max:20', $uniqueCode],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'after_or_equal:1900-01-01', 'before_or_equal:today'],
            'gender' => ['required', Rule::in(self::GENDERS)],
            'dpi' => ['nullable', 'string', 'max:20', 'regex:/^\d{13}$/'],
            'nit' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:200'],
            'insurance_company' => ['nullable', 'string', 'max:100'],
            'insurance_policy' => ['nullable', 'string', 'max:50'],
            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'blood_type' => ['nullable', Rule::in(self::BLOOD_TYPES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
