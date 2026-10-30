# Registro del cambio práctico defendido

## Solicitud

«El expediente debe funcionar detrás de la autenticación real del HIS: el JWT identifica al usuario CENTRAL, pero el
vínculo asistencial (`care_assignments` → `doctor_profiles.local_user_ref_id`) está en la base del hospital con otro
identificador.» Surgió al conectar el micro-HIS con Laravel (2026-10-23), siguiendo cómo lo resuelve Traslados.

## Análisis

| Pregunta | Respuesta |
|---|---|
| ¿Qué se rompía? | El micro-HIS usaba un solo `actorId` para RBAC, auditoría y vínculo. En Laravel, `CentralRoles` resuelve roles por usuario CENTRAL; con ese id, `PdoCareRelationship` no encontraba asignaciones. |
| ¿Qué atributos de calidad toca? | Acceso autorizado (RF-EXP-04), aislamiento por tenant (RNF-EXP-03) y trazabilidad (la auditoría debe mostrar al usuario CENTRAL). |
| ¿Cambia el contrato público? | No. La cabecera interna `X-Local-User-Ref` solo la arma el servidor; `public/index.php` no la reenvía desde el cliente. |
| ¿Cambia la base? | No. Se reutiliza `App\TrasladosAltas\ResolutorActorLocal` (lectura de `local_user_refs`). |

## Cambio aplicado

| Archivo | Antes | Después |
|---|---|---|
| `src/Application/RequestContext.php` | `actorId`, `hospitalId`, `correlationId` | + `localUserRefId` privado y `localUserRef()` (si no se indica, es el `actorId`) |
| `src/Application/ConsultLongitudinalRecord.php` | `hasActiveAssignment($request->actorId, …)` | `hasActiveAssignment($request->localUserRef(), …)` |
| `src/Presentation/Request.php` | — | Lee `X-Local-User-Ref` con el mismo patrón de identificador |
| `app/ExpedienteMedico/ExpedienteMedicoController.php` | — | JWT → `ResolutorActorLocal` → 403 `ACTOR_SIN_REFERENCIA_LOCAL` si no hay referencia |

## Evidencia

- C8 (caja blanca): con identidad CENTRAL distinta de la local, el vínculo usa la local y el evento de auditoría
  guarda la CENTRAL; con la local de otro médico, 403.
- `tests/Feature/ExpedienteMedico`: médica → 200 y evento con su id CENTRAL; usuario sin referencia local → 403
  `ACTOR_SIN_REFERENCIA_LOCAL`.
- Todas las pruebas anteriores siguieron pasando sin cambios (en el micro-HIS ambas identidades coinciden).
