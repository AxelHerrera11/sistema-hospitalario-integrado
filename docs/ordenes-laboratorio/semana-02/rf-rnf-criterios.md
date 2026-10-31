# RF, RNF y criterios de aceptación — Órdenes de laboratorio desde EMR

**Semana 2.** Cada criterio está redactado para convertirse directamente en una prueba del micro-HIS
(semanas 3, 4, 5 y 15). Los permisos `laboratorio.ordenes.*` siguen siendo propuestos.

## 1. Requisitos funcionales

| ID | Requisito | Criterio de aceptación (Dado / Cuando / Entonces) |
|---|---|---|
| RF-OL-01 | Emitir una orden desde el expediente con al menos una prueba | Dado un paciente con episodio abierto, cuando el médico envía una orden con dos pruebas válidas, entonces se crea `ISSUED` con dos ítems y un evento inicial |
| RF-OL-02 | Validar coherencia paciente–episodio–nota | Dado un episodio de otro paciente, cuando se intenta emitir, entonces 422 `CARE_EPISODE_NOT_OPEN` y no se guarda nada |
| RF-OL-03 | Exigir pruebas activas y vigentes | Dada una prueba inactiva o fuera de vigencia, cuando se emite, entonces 422 `LAB_TEST_NOT_ACTIVE` |
| RF-OL-04 | Evitar duplicados por reintento | Dada una `Idempotency-Key` ya usada en el hospital, cuando llega la misma petición, entonces 200 con la orden existente y sin nuevas filas |
| RF-OL-05 | Consultar una orden y su historial | Dada una orden del tenant, cuando se consulta por ID, entonces 200 con orden, ítems y eventos ordenados por fecha |
| RF-OL-06 | Avanzar el estado solo por transiciones válidas | Dada una orden `ISSUED`, cuando laboratorio la pasa a `COLLECTED`, entonces 200; si intenta `VALIDATED`, entonces 409 `INVALID_STATUS_TRANSITION` |
| RF-OL-07 | Cancelar conservando el motivo | Dada una orden `IN_PROCESS`, cuando el médico autorizado cancela con motivo, entonces `CANCELLED` y el evento guarda actor, fecha y motivo |
| RF-OL-08 | Listar órdenes del paciente o episodio | Dado un paciente con tres órdenes, cuando se lista con `patient_id`, entonces solo aparecen las suyas y paginadas |
| RF-OL-09 | Aislar por hospital | Dada una orden del hospital A, cuando un usuario de B la consulta, entonces 404 sin revelar su existencia |
| RF-OL-10 | Registrar trazabilidad de cada transición | Cada cambio de estado genera un evento con estado anterior, nuevo, actor, fecha y `correlation_id` |
| RF-OL-11 | Devolver errores estables y seguros | Todo error devuelve `error` y `correlation_id`; nunca `SQLSTATE`, rutas ni datos del paciente |
| RF-OL-12 | Exigir permiso por operación | Sin `laboratorio.ordenes.crear` la emisión devuelve 403; sin `laboratorio.ordenes.ver` la consulta devuelve 403 |

## 2. Requisitos no funcionales

| ID | Requisito | Criterio verificable |
|---|---|---|
| RNF-OL-01 | Atomicidad de orden, ítems e historial | Si falla cualquier `INSERT`, la transacción revierte y no queda orden parcial |
| RNF-OL-02 | Falla segura ante persistencia | Con el repositorio obligado a fallar, la API responde 503 `PERSISTENCE_FAILURE` sin traza |
| RNF-OL-03 | Rendimiento de consulta | Listar 10 000 órdenes de un hospital con filtro por paciente responde en menos de 200 ms con índice por tenant/paciente |
| RNF-OL-04 | Seguridad de datos clínicos | Respuestas y logs no incluyen nombre del paciente ni resultados; solo IDs y códigos |
| RNF-OL-05 | Auditabilidad | El historial es de solo inserción: no se actualiza ni se borra un evento desde la aplicación |
| RNF-OL-06 | Portabilidad | PHP 8.2+ sin framework, PDO con sentencias preparadas; compatible con PostgreSQL 13+ y SQLite para pruebas |
| RNF-OL-07 | Pruebas reproducibles | `php tests/run.php` ejecuta la batería sin red ni servicios externos, con adaptador en memoria |
| RNF-OL-08 | Accesibilidad y movilidad | Formularios y estados usables con teclado y a 320 px (se materializa en semanas 8–11) |
| RNF-OL-09 | Paginación acotada | `per_page` entre 1 y 50; fuera de rango devuelve 422 |
| RNF-OL-10 | Mantenibilidad | Controlador sin SQL ni reglas de negocio; dominio sin dependencias de HTTP o PDO |

## 3. Matriz requisito → evidencia

| Requisito | Semana donde se demuestra | Evidencia prevista |
|---|---|---|
| RF-OL-01, 02, 03 | 3 (dominio y aplicación) | Pruebas de creación válida y de rechazo |
| RF-OL-04 | 4 (repositorio) | Prueba de idempotencia con ambos adaptadores |
| RF-OL-05, 06, 07, 08 | 4 y 5 | Casos de uso y pruebas HTTP |
| RF-OL-09, 11, 12 | 5 | Pruebas de tenant, error seguro y permiso |
| RF-OL-10 | 3–5 | Eventos persistidos y consultados |
| RNF-OL-01, 02 | 4 | Prueba de fallo con rollback |
| RNF-OL-03, 09 | 5 y 15 | Medición y límite de paginación |
| RNF-OL-04, 05 | 4 y 5 | Revisión de respuesta y log; historial append-only |
| RNF-OL-06, 07, 10 | 3 | Ejecución del micro-HIS y revisión de capas |
| RNF-OL-08 | 8–12 | Flujo UX, prototipo y hallazgos |

## 4. Decisiones que dependen del grupo

Estas no se pueden cerrar solo con este módulo y se registran como pendientes, no como hechos:

- nombres definitivos de los permisos `laboratorio.ordenes.*`;
- estados compartidos entre órdenes, muestras y resultados;
- formato común de `X-Correlation-ID` / `X-Request-ID`;
- ruta final `/api/v1/lab-orders` frente a `/api/v1/lab/orders`.

Mientras no haya acuerdo, el micro-HIS usa los valores del contrato #343 y los declara como propuesta.
