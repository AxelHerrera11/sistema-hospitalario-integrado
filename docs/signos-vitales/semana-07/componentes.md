# Componentes backend y frontend

## Backend (Laravel 12)

| Componente | Responsabilidad | Dónde está hoy |
|---|---|---|
| Middlewares `tenant`, `jwt.auth` y `EnsureHospitalDatabaseTenant` | Resolver el hospital (`X-Tenant-ID`) y el usuario del JWT, y exigir que el hospital sea el de la base HOSPITAL de esta instancia | En `develop`: `TenantMiddleware` y `JwtAuth`, con alias en `bootstrap/app.php`, y `App\Http\Middleware\EnsureHospitalDatabaseTenant` |
| Middleware `permission:signos_vitales.*` | Proteger cada ruta con su permiso de RBAC | En `develop`: alias `permission` (y `rbac`) hacia `RequirePermission` en `RbacServiceProvider`; `signos_vitales.registrar`, `.ver` y `.corregir` en `config/rbac.php` (médico los tres, enfermería `.ver`). En el micro-HIS lo reemplaza `ApiKernel` con `PermissionChecker` |
| `Api/V1/VitalSignController` + `StoreVitalSignsRequest` | Controller delgado: el FormRequest valida tipos (número u objeto `{value, unit}`), el controller llama al caso de uso y traduce `VitalSignViolation` | No (propuesto); hoy `VitalSignController` + `Request` en el micro-HIS |
| Application: `RecordVitalSigns` · `GetVitalSign` | Orquestan el flujo; tras el refactor `RecordVitalSigns` ya no elige el perfil por edad | Micro-HIS, `src/Application` (#131, #549) |
| Domain: `VitalSigns` · `VitalSignRule` y reglas · `ThresholdEvaluator` · `Assessment` · `Severity` | Plausibilidad, umbrales y severidad máxima | Micro-HIS, `src/Domain` (#131); sin cambios |
| Domain **nuevo**: `Measurement` · `Unit` | Convertir cada medida a la unidad canónica antes de crear `VitalSigns`; rechazar unidades desconocidas | No (propuesto); prototipo en la demo |
| Domain **nuevo**: `ThresholdProfile` · `ProfileCatalog` | Un perfil sabe a quién aplica, cuál es su versión y cómo evalúa; el catálogo elige el perfil por edad | No (propuesto); prototipo en la demo |
| Puertos: `VitalSignRepository` · `CareEpisodeReader` · `AlertNotifier` · `PermissionChecker` | La aplicación define lo que necesita; PDO y Eloquent quedan en el borde | Micro-HIS, `src/Application` (#529, #549) |
| `EloquentVitalSignRepository` · `DbCareEpisodeReader` | Persistencia sobre `DB::connection('hospital')`, con `RETURNING` y la auditoría en la **misma transacción**; lectura de solo lectura de Admisión | No (propuesto); hoy `PdoVitalSignRepository` y `PdoCareEpisodeReader` (#529) |
| `InMemoryVitalSignRepository` · `InMemoryCareEpisodeReader` | Dobles para las pruebas: la misma batería corre contra ambos adaptadores | Micro-HIS, `src/Persistence` (#529) |
| `QueuedAlertNotifier` (cola Laravel `afterCommit`) | Notificar la alerta después del `COMMIT`; el canal real está pendiente (C3) | Micro-HIS: cola en memoria (#132) |

## Frontend (Vue 3 · Pinia · Axios)

Ya en `develop`: `resources/js/stores/auth.js`, el plugin `resources/js/plugins/axios.js` (agrega JWT y `X-Tenant-ID`), el router con `authGuard` y el módulo `modules/auth`. El módulo nuevo sigue esa estructura.

| Componente | Responsabilidad |
|---|---|
| `modules/signos-vitales/pages/VitalSignsCapturePage.vue` | Pantalla de captura del episodio `/signos-vitales/:careEpisodeId`, con `meta: { requiresAuth: true }` |
| `components/VitalSignsForm.vue` | Formulario de los nueve signos; exige al menos uno y la fecha `measured_at`; arma el cuerpo del contrato |
| `components/MeasurementField.vue` | Un campo de signo con su valor y, si el signo admite varias unidades (temperatura, glucosa), un selector de unidad |
| `components/AlertResult.vue` | Muestra el resultado del `201`: severidad, hallazgos y el aviso `THRESHOLDS_NOT_APPLICABLE` |
| `components/SeverityBadge.vue` · `FindingsList.vue` | Insignia de `none`, `warning` o `critical` y lista de signos fuera de umbral |
| `stores/signosVitales.js` (Pinia) | Estado: `submitting`, `result`, `error` (`code`, `trace_id`); acción `record()` |
| `api/signosVitalesApi.js` | `POST /api/v1/care-episodes/{id}/vital-signs` y `GET /api/v1/vital-signs/{id}` con el plugin `axios` |

Reglas de UI que hereda del contrato, no inventa:

- El botón «Registrar» se deshabilita mientras el `POST` está en curso y **el `POST` nunca se reintenta**: no es idempotente y duplicaría la medición y la alerta. El médico decide repetir tras ver el `code` y el `trace_id`.
- `THRESHOLDS_NOT_APPLICABLE` se muestra como **«no evaluado»**, jamás como «normal»: la ausencia de alerta no significa que el valor sea normal.
- La unidad elegida se muestra siempre junto al valor, y la respuesta se presenta en la unidad canónica para que dos pantallas no discrepen.
- Los errores se pintan por `code` del contrato (`FORBIDDEN`, `NOT_TREATING_PHYSICIAN`, `EPISODE_CLOSED`, `VITAL_SIGN_IMPLAUSIBLE`, `UNIT_NOT_SUPPORTED`, `AUTHORIZATION_UNAVAILABLE`…), nunca con el texto crudo de la excepción.

## Rutas

`bootstrap/app.php` ya monta `routes/api.php` con el prefijo `api/v1`, así que las rutas del [contrato de la Semana 5](../semana-05/contrato-api.md) no cambian:

```php
Route::middleware(['tenant', 'jwt.auth', EnsureHospitalDatabaseTenant::class])->group(function (): void {
    Route::post('/care-episodes/{careEpisode}/vital-signs', [VitalSignController::class, 'store'])->middleware('permission:signos_vitales.registrar');
    Route::get('/vital-signs/{vitalSign}', [VitalSignController::class, 'show'])->middleware('permission:signos_vitales.ver');
});
```
