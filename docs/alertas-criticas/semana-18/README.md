# Alertas críticas y notificaciones internas: Semana 18, evaluación final y defensa

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-21 · **Issue:** #40

**Estado:** lista para la evaluación final. La demostración y la defensa oral se hacen en la evaluación, con el guion de abajo.

- [Trazabilidad de las 18 semanas](./trazabilidad-semanas.md)
- [Recorrido final sobre `develop`](./evidencia/salida-recorrido-final.txt)
- [Declaración de uso de IA](./DECLARACION_IA.md)

## Qué entrega el módulo

Cuando se valida un resultado de laboratorio fuera de los umbrales críticos del catálogo, crea una alerta para el médico tratante. Si no hay tratante, la alerta va a la jefatura del servicio. La alerta solo se acusa **leyendo de vuelta** paciente, prueba y valor.

| Pieza | Dónde | PR |
|---|---|---|
| Micro-HIS por capas | `micro-his/alertas-criticas/` | #202, #1008 y #1023 |
| Tabla `critical_alerts` (000660) y adaptación del micro-HIS | `tests/Database/Hospital/CriticalAlertIntegrityTest.php` | #1321 |
| API v1 (detección, bandejas y acuse) y su provider | `app/Http/Controllers/Api/V1/CriticalAlertController.php` | #1322 |
| Bandeja `/alertas` | `resources/js/modules/alertas/` | #1282 |
| Prueba contra PostgreSQL y recorrido con login real | `tests/Database/Hospital/AlertasPostgresTest.php` | #1592 |
| Entradas hostiles, pruebas de permisos y matriz de amenazas | `tests/Feature/Alertas/CriticalAlertSecurityTest.php` | #1594 |

## Evidencia final sobre `develop` (a24bfb36, 30/10/2026)

Entorno local, no staging: PHP 8.4.21, PostgreSQL 18.4, Node 24.14.1 y Chrome headless. Las bases `*_e2e` se recrearon vacías con las migraciones de `develop`, el seed de CI y datos ficticios, con `APP_DEBUG=false` (pasos en la [bitácora de la Semana 17](../semana-17/bitacora-despliegue.md)).

| Verificación | Resultado |
|---|---|
| Micro-HIS: `run`, `whitebox`, `blackbox` y `api` | 30, 15, 12 y 14 comprobaciones OK |
| `tests/Feature/Alertas`, `AlertasPostgresTest` y `CriticalAlertIntegrityTest` | OK (18 tests, 247 assertions) |
| `node resources/js/modules/alertas/alertas.test.mjs` | 17 aserciones OK |
| Recorrido HTTP con login real (Semana 16) | **15/15** |
| Recorrido de la pantalla en Chrome (Semana 16) | **9/9** |
| Recorrido de seguridad, con la base caída en un segundo servidor (Semana 17) | **16/16** |

La salida completa está en [`evidencia/salida-recorrido-final.txt`](./evidencia/salida-recorrido-final.txt).

### Defectos encontrados en el recorrido final

| Dónde | Qué pasaba | Corrección en esta entrega |
|---|---|---|
| Recorrido de seguridad (Semana 17) | El paso «token con la firma alterada» cambiaba el **último** carácter por «x». En una firma HS256 ese carácter solo lleva 4 bits de datos: si el token terminaba en «w», el cambio dejaba la misma firma y la API respondía 200 porque el token seguía siendo válido. Pasa en 1 de cada 16 tokens; apareció en el recorrido final de admisión (ASII-07) | El script cambia un carácter del **medio** de la firma. Se comprobó que la API rechaza (401) cualquier firma realmente distinta |

## Guion de la demostración (8 minutos)

Con el entorno anterior y los usuarios ficticios del seed. Cada paso coincide con uno de los recorridos automatizados.

| # | Actor | Acción | Resultado esperado |
|---|---|---|---|
| 1 | Bioquímica | Publicar por la API glucosa 450 de `PAC-E2E-01` (aún no hay pantalla de validación de resultados) | 201: alerta para la médica tratante |
| 2 | Bioquímica | Entrar | Sin «Alertas críticas» en el menú |
| 3 | Medicina | Abrir «Alertas críticas» | Solo su alerta: 450, sobre el umbral, anunciada como alerta |
| 4 | Medicina | Acusar sin confirmar ni escribir el valor | Error local; no se envía |
| 5 | Medicina | Confirmar y escribir 45 | «El valor leído no coincide…» (422) |
| 6 | Medicina | Escribir 450 | «Alerta acusada» y bandeja vacía |
| 7 | Jefatura médica | Elegir la bandeja del servicio | La alerta de `PAC-E2E-02`, sin tratante (500, nivel 1) |
| 8 | Jefatura médica | Acusarla con la lectura correcta | Bandeja del servicio vacía |
| 9 | Medicina | Consultar con la base HOSPITAL caída (servidor 8096) | 503 `CRITICAL_ALERTS_UNAVAILABLE`, sin SQL ni host |

## Reglas que la defensa sostiene

1. Un resultado genera a lo sumo una alerta: repetir el evento responde `duplicate`.
2. El destinatario sale de `care_assignments`. Sin médico tratante, la alerta va a la jefatura (nivel 1); nunca queda sin dueño.
3. Acusar exige leer de vuelta paciente, prueba y valor; una lectura distinta es 422 `READ_BACK_MISMATCH`.
4. Solo quien atiende al paciente acusa; la jefatura acusa por su ruta, con `alertas.supervisar`.
5. Una alerta acusada no se reabre ni se borra, y su episodio debe ser del mismo paciente (triggers de #1321).
6. Los logs llevan resultado, `alert_id` y hospital; nunca paciente ni valor.

## Preguntas de defensa

- **¿Por qué el read-back?** Para que el acuse demuestre que se leyó el valor y no solo que se hizo clic; el error de lectura se prueba en API y pantalla.
- **¿Qué pasa si el evento llega dos veces?** La alerta se identifica por `lab_result_version_id`; la segunda llamada responde `duplicate` y no crea otra.
- **¿Por qué la jefatura no puede acusar por la ruta común?** Porque no es tratante; usa la suya, con `alertas.supervisar`, y queda registrado quién acusó.
- **¿Qué defectos reales se encontraron?** El micro-HIS leía una `care_assignments` simplificada que no existe en HOSPITAL, y un episodio de otro paciente habría dado 503 (Semana 16); un id como arreglo o un valor `1e400` daban 500 (Semana 17).
- **¿Qué falta para producción?** Que Resultados publique el evento, los permisos de enfermería, el escalamiento por tiempo y un staging autorizado.

## Cambio solicitado en la Semana 12

Dirección médica pidió que la jefatura vea las **alertas escaladas de otros médicos**. Está en parte: la bandeja del servicio y el permiso `alertas.supervisar` existen desde #1322, pero hoy solo reciben alertas sin médico tratante, porque el escalamiento por tiempo no está implementado.

## Pendientes al cierre

| Pendiente | Responsable |
|---|---|
| Publicar «resultado validado» desde la validación real o una cola común | Resultados (ASII-19 y ASII-20) |
| Aprobar o rechazar `alertas.ver` y `alertas.acusar` para enfermería | RBAC (ASII-02) |
| Escalamiento por vencimiento (`due_at` ya se guarda) | ASII-21, siguiente iteración |
| FK de `critical_alerts.lab_result_version_id` cuando exista `lab_result_versions` | ASII-19 |
| Sincronizar `local_user_refs` y `care_assignments` desde sus módulos | Tenants (ASII-25) y Expediente |
| Staging u on-premise autorizado | Coordinación del proyecto |
