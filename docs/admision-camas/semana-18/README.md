# Admisión hospitalaria y asignación de cama: Semana 18, evaluación final y defensa

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-07 · **Issue:** #37

**Estado:** lista para la evaluación final. La demostración y la defensa oral se hacen en la evaluación, con el guion de abajo.

- [Trazabilidad de las 18 semanas](./trazabilidad-semanas.md)
- [Recorrido final sobre `develop`](./evidencia/salida-recorrido-final.txt)
- [Declaración de uso de IA](./DECLARACION_IA.md)

## Qué entrega el módulo

Recepción busca al paciente, ve las camas disponibles por tipo y registra el ingreso. En una sola transacción se abre el episodio de hospitalización, se crea la estancia y se ocupa la cama.

| Pieza | Dónde | PR |
|---|---|---|
| Micro-HIS por capas | `micro-his/admision-camas/` | #201, #1007 y #1022 |
| API v1 (`GET beds/available`, `POST admissions`) y su provider | `app/Http/Controllers/Api/V1/AdmissionController.php` | #1236 |
| Pantalla `/admision` | `resources/js/modules/admision/` | #1243 |
| Prueba contra PostgreSQL y recorrido con login real | `tests/Database/Hospital/AdmisionPostgresTest.php` | #1324 |
| Entradas hostiles, límite del motivo y matriz de amenazas | `tests/Feature/Admision/AdmissionSecurityTest.php` | #1360 |

## Evidencia final sobre `develop` (a24bfb36, 30/10/2026)

Entorno local, no staging: PHP 8.4.21, PostgreSQL 18.4, Node 24.14.1 y Chrome headless. Las bases `*_e2e` se recrearon vacías con las migraciones de `develop`, el seed de CI y datos ficticios, con `APP_DEBUG=false` (pasos en la [bitácora de la Semana 17](../semana-17/bitacora-despliegue.md)).

| Verificación | Resultado |
|---|---|
| Micro-HIS: `run`, `whitebox`, `blackbox` y `api` | 32, 12, 12 y 15 comprobaciones OK |
| `tests/Feature/Admision` y `AdmisionPostgresTest` | OK (14 tests, 149 assertions) |
| `node resources/js/modules/admision/admision.test.mjs` | 17 aserciones OK |
| Recorrido HTTP con login real (Semana 16) | **10/10** |
| Recorrido de la pantalla en Chrome (Semana 16) | **6/6** |
| Recorrido de seguridad, con la base caída en un segundo servidor (Semana 17) | **11/11** |

La salida completa está en [`evidencia/salida-recorrido-final.txt`](./evidencia/salida-recorrido-final.txt).

### Defectos encontrados en el recorrido final

| Dónde | Qué pasaba | Corrección en esta entrega |
|---|---|---|
| Recorrido de la pantalla (Semana 16) | Justo después de un ingreso, la pantalla recarga las camas y desactiva «Buscar» unos milisegundos. El script hacía clic en ese instante y el clic se perdía. En la Semana 16 pasaba porque guardaba capturas, y eso daba la pausa | El script espera a que «Buscar» esté activo. Pasó 3 de 3 veces. La pantalla no cambia: desactivar el botón mientras carga es lo esperado |
| Recorrido de seguridad (Semana 17) | El paso «token con la firma alterada» cambiaba el **último** carácter por «x». En una firma HS256 ese carácter solo lleva 4 bits de datos: si el token terminaba en «w», el cambio dejaba la misma firma y la API respondía 200 porque el token seguía siendo válido. Pasa en 1 de cada 16 tokens; apareció en el recorrido final de admisión (ASII-07) | El script cambia un carácter del **medio** de la firma. Se comprobó que la API rechaza (401) cualquier firma realmente distinta |

## Guion de la demostración (8 minutos)

Con el entorno anterior y los usuarios ficticios del seed. Cada paso coincide con uno de los recorridos automatizados.

| # | Actor | Acción | Resultado esperado |
|---|---|---|---|
| 1 | Medicina | Entrar | Sin «Admisión y camas» como enlace |
| 2 | Recepción | Abrir «Admisión y camas» y buscar `PAC-E2E-02` | Paciente y camas disponibles con su tipo |
| 3 | Recepción | Admitir sin elegir cama | «Seleccione una cama disponible.»; no se envía |
| 4 | Recepción | Elegir M-201, escribir el motivo y admitir | «Ingreso registrado en la cama M-201…» |
| 5 | Recepción | Buscar de nuevo a `PAC-E2E-02` e intentar otra cama | «El paciente ya tiene una hospitalización abierta.» |
| 6 | Recepción | Repetir a 375 px | Sin desplazamiento horizontal |
| 7 | Recepción | Listar camas con la base HOSPITAL caída (servidor 8096) | 503 `ADMISSIONS_UNAVAILABLE`, sin SQL ni host |

## Reglas que la defensa sostiene

1. Episodio, estancia, cama ocupada y auditoría se guardan juntos o no se guarda nada.
2. Dos admisiones no pueden tomar la misma cama: `UPDATE … WHERE status = 'available'` y, si otra gana, 409 `BED_NOT_AVAILABLE` sin restos.
3. Un paciente con hospitalización abierta no se admite otra vez (409 `PATIENT_ALREADY_ADMITTED`).
4. Quien asigna y la hora de inicio salen del servidor; el cuerpo no los elige.
5. El listado de camas solo expone `bed_id`, código y tipo.
6. Liberar la cama es de Traslados y altas (ASII-08), no de este módulo.

## Preguntas de defensa

- **¿Qué pasa si dos personas eligen la misma cama a la vez?** Solo una actualización encuentra la cama disponible; la otra recibe 409 y la pantalla recarga la lista.
- **¿Qué pasa si falla algo a mitad del ingreso?** La transacción se revierte: no queda episodio sin cama ni cama ocupada sin estancia (`AdmisionPostgresTest`).
- **¿Por qué el médico no ve la pantalla?** No tiene `admisiones.registrar` ni `camas.ver` en `config/rbac.php`; la API también le responde 403.
- **¿Qué defectos reales se encontraron?** Base caída que habría dado 500 (Semana 16); un arreglo en el JSON que daba 500 y el motivo sin límite (Semana 17).
- **¿Qué falta para producción?** Elegir la base por hospital, sincronizar `local_user_refs`, una vista de consulta para `admisiones.ver` y un staging autorizado.

## Cambio solicitado en la Semana 12

Jefatura pidió **reservar una cama 10 minutos**. No está implementado. Requiere un estado `reserved` con vencimiento o una tabla de reservas, a acordar con Salas y camas (ASII-06).

## Pendientes al cierre

| Pendiente | Responsable |
|---|---|
| Elegir la base HOSPITAL por hospital; hoy aplica el guard de #1154 | Integración y Tenants (ASII-25) |
| Sincronizar `local_user_refs` desde CENTRAL | Tenants (ASII-25) |
| Vista de consulta de admisiones para `admisiones.ver` (medicina, enfermería) | ASII-07, siguiente iteración |
| Reserva temporal de cama (cambio de la Semana 12) | ASII-07 con ASII-06 |
| Límite de tasa común de `/api/v1` | API Gateway |
| Staging u on-premise autorizado | Coordinación del proyecto |
