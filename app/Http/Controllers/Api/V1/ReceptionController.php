<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Reception\Application\ReceiveSpecimen;
use App\Modules\Reception\Application\OrderGateway;
use App\Modules\Reception\Application\ReceptionRepository;
use App\Modules\Reception\Domain\ReceptionError;
use App\Modules\Reception\Domain\SpecimenCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ReceptionController extends Controller
{
    private function context(Request $request): array
    {
        $tenant = (string) $request->attributes->get('tenant')?->getKey();
        abort_unless($tenant !== '' && $request->user(), 403);
        // El despliegue tiene una base clínica por hospital; nunca mezclar hospitales.
        abort_unless(hash_equals((string) config('reception.hospital_id'), $tenant), 403, 'Hospital clínico no configurado');
        $actor = DB::connection('hospital')->table('local_user_refs')
            ->where('central_user_id', $request->user()->getAuthIdentifier())->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('valid_to')->orWhere('valid_to', '>', now()))->value('local_user_ref_id');
        abort_unless($actor, 403, 'Actor clínico no sincronizado');
        return [$tenant, (string) $actor];
    }

    public function store(Request $request)
    {
        [$tenant, $actor] = $this->context($request);
        $data = $request->validate([
            'request_key' => ['required', 'uuid'], 'item_id' => ['required', 'uuid'],
            'patient_code' => ['required_unless:sin_etiqueta,true', 'nullable', 'string', 'max:100'],
            'patient_name' => ['required_unless:sin_etiqueta,true', 'nullable', 'string', 'max:200'],
            'type' => ['required', 'in:blood,urine,swab'], 'container' => ['required', 'string', 'max:100'],
            'volume' => ['required', 'numeric', 'between:0,1000'],
            'temperature' => ['required', 'numeric', 'between:-80,100'],
            'collected_at' => ['required', 'date_format:Y-m-d\TH:i:sP'],
            'hemolizada' => ['sometimes', 'boolean'], 'coagulada' => ['sometimes', 'boolean'],
            'derramada' => ['sometimes', 'boolean'], 'sin_etiqueta' => ['sometimes', 'boolean'],
        ]);
        $data['patient_code'] ??= ''; $data['patient_name'] ??= '';
        $data['volume'] = (float) $data['volume']; $data['temperature'] = (float) $data['temperature'];
        foreach (['hemolizada', 'coagulada', 'derramada', 'sin_etiqueta'] as $flag) $data[$flag] = (bool) ($data[$flag] ?? false);
        try {
            $sample = (new ReceiveSpecimen(app(ReceptionRepository::class), app(OrderGateway::class)))
                ->execute($tenant, $actor, $data, new \DateTimeImmutable());
            return response()->json(['data' => $this->publicSample($sample)], 201);
        } catch (ReceptionError $error) {
            return response()->json(['error' => $error->error, 'message' => $error->getMessage()], $error->getCode());
        } catch (\PDOException|\App\Modules\Reception\Domain\CodeCollision $error) {
            report($error);
            return response()->json(['error' => 'RECEPTION_UNAVAILABLE', 'message' => 'No se pudo confirmar la recepción. Reintente con la misma clave.'], 503);
        }
    }

    public function show(Request $request, string $code)
    {
        [$tenant] = $this->context($request);
        abort_unless(SpecimenCode::valid($code), 404);
        $sample = app(ReceptionRepository::class)->find($tenant, $code);
        abort_unless($sample, 404);
        return response()->json(['data' => $this->publicSample($sample)]);
    }

    public function pending(Request $request)
    {
        $this->context($request);
        $data = $request->validate(['search' => ['required', 'string', 'min:2', 'max:100']]);
        $search = $data['search'];
        $rows = DB::connection('hospital')->table('reception_order_items')->whereIn('status', ['pending', 'pending_new_sample'])
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('specimens')->whereColumn('specimens.lab_order_item_id', 'reception_order_items.item_id')->where('specimens.status', 'accepted'))
            ->where(function ($query) use ($search) { $query->where('patient_code', $search)->orWhereRaw('item_id::text = ?', [$search]); })
            ->orderBy('item_id')->limit(20)->get();
        return response()->json(['data' => $rows]);
    }

    private function publicSample(array $sample): array
    {
        return array_intersect_key($sample, array_flip(['id', 'item_id', 'status', 'reason', 'code', 'received_at']));
    }
}
