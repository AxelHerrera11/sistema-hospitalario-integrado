# Matriz de amenazas: alertas críticas y notificaciones internas (Semana 17)

Alcance: las cinco rutas de `/api/v1/critical-alerts`:
- detección: `result-validated`;
- bandejas: la propia y `service-head`;
- acuse: `acknowledge` y `acknowledge-as-service-head`.

También cubre la pantalla `/alertas` (#1282). Una alerta que se pierde, llega a otra persona o se acusa sin leer el valor puede dañar al paciente. La autoridad es el servidor.

Evidencia: **S** es `tests/Feature/Alertas/CriticalAlertSecurityTest.php`, **F** es `CriticalAlertApiTest.php` (#1322) y **R** es el [recorrido de seguridad](./evidencia/salida-recorrido-seguridad.txt) contra el despliegue local.

| ID | Amenaza | Control | Evidencia | Estado |
|---|---|---|---|---|
| C01 | Operar sin sesión o con un token alterado | `jwt.auth` antes de RBAC y del controller | R: sin token 401; firma alterada 401 | Mitigada |
| C02 | Ver o acusar alertas de otro hospital | `tenant` contrasta JWT y `X-Tenant-ID`; guard de #1154 | R: token A con cabecera B 403. F: guard 403 | Mitigada |
| C03 | Un rol sin permiso publica resultados, ve bandejas o supervisa | `lab-results.validate`, `alertas.ver`, `alertas.acusar` y `alertas.supervisar`, uno por ruta, con denegación por defecto | S: los 8 roles por las 5 rutas. R: médica 403 al publicar y en la bandeja de jefatura; recepción 403 | Mitigada |
| C04 | Elegir desde el cuerpo el destinatario, el nivel, el estado o quién acusó | El destinatario sale de `care_assignments`; nivel y estado, del dominio; quien acusa, del JWT | S y R: destinatario nulo, nivel 2 y `acknowledged` se ignoran ([psql](./evidencia/salida-psql.txt): médica tratante, nivel 0; acusada por la médica) | Mitigada |
| C05 | Un identificador como arreglo o un valor infinito tumba la API (500) | El controller exige texto UUID; `HandleResultValidated` exige un número finito → 422 `EVENT_INVALID` | S y R: arreglo y `1e400` dan 422 | **Corregida en esta entrega** |
| C06 | Una alerta con el episodio de otro paciente | Trigger `enforce_critical_alert_integrity` y FK de `critical_alerts` (#1321) → 422 `EVENT_INVALID` | R: episodio de PAC-E2E-02 con paciente PAC-E2E-01 da 422 | Mitigada |
| C07 | Un médico ve o acusa alertas de pacientes que no atiende | La bandeja filtra por destinatario; el acuse exige relación de cuidado (`NO_CARE_RELATION`) | S: otro médico ve 0 y recibe 403. R: jefatura por la ruta común 403 | Mitigada |
| C08 | Acusar sin haber leído el valor | Read-back de paciente, prueba y valor → `READ_BACK_MISMATCH` | F y R: lectura equivocada 422; correcta 200 | Mitigada |
| C09 | Reabrir o reescribir una alerta acusada | Dominio (`ALERT_ALREADY_ACKNOWLEDGED`) + trigger: una alerta acusada es inmutable y no se borra | F y R: segundo acuse 409. `CriticalAlertIntegrityTest` | Mitigada |
| C10 | Inyección en la traza o datos clínicos en logs o cachés | Patrón del `X-Request-ID`; los logs solo llevan resultado, `alert_id` y hospital; `no-store, private` | S: ni paciente, ni episodio, ni valor en los logs. R: `<script>` reemplazado y cabecera presente | Mitigada |
| C11 | Filtrar SQL, credenciales o host con la base caída | `PersistenceFailure` → 503 `CRITICAL_ALERTS_UNAVAILABLE` con `APP_DEBUG=false` | F: sin detalles. R: HOSPITAL en un puerto cerrado da 503 sin `SQLSTATE` ni host | Mitigada |
| C12 | Una alerta sin médico tratante queda sin dueño | Sin destinatario → bandeja de jefatura (nivel 1); la jefatura acusa por su ruta | F: cola de jefatura y acuse supervisado | Mitigada |
| C13 | Enfermería no recibe las alertas | `alertas.ver` y `alertas.acusar` para enfermería siguen en `pending` | S: la matriz lo fija | Pendiente: ASII-02 |
| C14 | Agotar la API o inundar bandejas con eventos | Idempotencia por `lab_result_version_id`; falta un límite de tasa común | F: el evento repetido da `duplicate` | Parcial: API Gateway |

## Riesgo residual

- **C13:** activar los permisos de enfermería depende de ASII-02.
- **C14:** la idempotencia evita alertas duplicadas, pero no hay un límite de tasa propio.
- **Escalamiento por tiempo:** `due_at` queda guardado, pero el proceso que escala las alertas vencidas no está en esta entrega.
