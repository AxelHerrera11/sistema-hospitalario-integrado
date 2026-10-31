# Matriz de amenazas — Expediente médico electrónico base

Método: STRIDE sobre el flujo «apertura y consulta autorizada del expediente longitudinal». Activos: datos clínicos
del paciente (episodios, motivos), identidad del paciente, número de expediente y bitácora de accesos. Cada fila
indica el control que existe **hoy** en el código y la prueba que lo verifica; las pruebas `S-*` son nuevas de esta
semana ([`evidencias/01-pruebas-seguridad.txt`](evidencias/01-pruebas-seguridad.txt)).

Probabilidad (P) e impacto (I) de 1 a 3; riesgo = P × I antes del control. Residual: Bajo / Medio / Alto.

| Id | STRIDE | Amenaza | P×I | Control implementado | Prueba | Residual |
|---|---|---|---|---|---|---|
| A-01 | Suplantación | Llamar con la identidad de otro usuario | 3×3 | En Laravel la identidad sale del JWT (`JwtAuth`) y se traduce a `local_user_refs`; `X-User-ID` solo existe en el prototipo local | Feature «Identity, tenant and permission are enforced…» | Bajo (prototipo solo en 127.0.0.1) |
| A-02 | Suplantación | Elegir otro hospital con `X-Tenant-ID` | 2×3 | RBAC exige membresía en ese hospital; cada hospital tiene su base | S-05; Feature «Another hospital…» | Bajo |
| A-03 | Manipulación | Inyección SQL o de rutas en el código del paciente | 2×3 | Patrón `^[A-Za-z0-9][A-Za-z0-9_-]{0,39}$` antes de cualquier consulta; 100 % sentencias preparadas | S-04; R-05 | Bajo |
| A-04 | Manipulación | Borrar o reescribir un expediente o la bitácora | 2×3 | Sin ruta de borrado (405); `audit_log` append-only con cadena de hashes | N-13; smoke de la Semana 16 (V-06) | Bajo |
| A-05 | Repudio | Consultar o abrir sin dejar rastro | 2×3 | Cada consulta, apertura, apertura repetida y denegación se registra; si la bitácora falla no se entrega nada | S-07, S-08, N-12, A3 | Bajo (corregido esta semana, ver H-EXP-17-01) |
| A-06 | Divulgación | Personal sin relación asistencial lee el expediente | 3×3 | Permiso `expediente.ver` **y** vínculo asistencial activo | S-01; N-02…N-04 | Bajo |
| A-07 | Divulgación | Averiguar qué pacientes existen (enumeración) | 2×2 | Paciente inexistente y paciente sin vínculo responden el mismo 403 | S-03 | Bajo |
| A-08 | Divulgación | Respuesta con datos de más (contacto, id global) o guardada en caché | 2×3 | Presentador con campos mínimos; `Cache-Control: no-store` y `nosniff` en toda respuesta JSON | S-06; N-01 | Bajo |
| A-09 | Divulgación | Datos clínicos en la bitácora o en mensajes de error | 2×3 | Eventos solo con identificadores y resultado; errores con código estable | S-07; N-11; S-04 | Bajo |
| A-10 | Divulgación | Leer archivos del servidor desde la interfaz (`../`) | 2×3 | `StaticAsset` solo entrega `.html/.js/.css` dentro de `public/ui` (PR de la Semana 16) | N-14 | Bajo |
| A-11 | Denegación de servicio | Consultas masivas a la API | 2×2 | Dependencias caídas → 503 explícito; la interfaz limita reintentos a 3 | N-11; `ui-estados.test.mjs` | **Medio**: no hay límite de peticiones por usuario |
| A-12 | Elevación | Abrir expedientes sin permiso aprobado | 2×3 | La apertura no se publica en Laravel hasta que RBAC catalogue su permiso | S-01, S-02; Feature «Opening is not published…» | Bajo |
| A-13 | Elevación | Enfermería obtiene acceso por una regla inventada | 1×3 | No se inventó regla: enfermería recibe 403 hasta que RBAC defina el criterio | S-01 | Bajo (decisión pendiente de RBAC) |

## Hallazgo corregido esta semana

**H-EXP-17-01 (A-05):** repetir la apertura devolvía el número y el id del expediente **sin registrar el acceso**
(tampoco cuando otro proceso ganaba la carrera). Contradecía RF-EXP-05 («registrar cada apertura»). Las pruebas A3 y
A6 de la Semana 15 afirmaban «sin evento nuevo»: probaban el comportamiento, no el requisito. Corrección: el evento
`expediente_medico.apertura_repetida` se registra en ambos caminos y, si la bitácora falla, se responde 503 sin datos
(S-08). A3 y A6 se actualizaron al requisito.

## Riesgo residual y recomendación

A-11 queda en **Medio**. No se agregó `throttle` por IP: en un hospital muchos usuarios salen por la misma NAT y se
bloquearía la atención. Se recomienda un limitador con nombre por usuario autenticado, decidido por el grupo para
todas las rutas clínicas.
