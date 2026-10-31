# Evidencia completa y PR integrados

Indice final del modulo ASII-05 (issue #207). Estados de los PR consultados en GitHub el 2026-10-30.

## Semanas, carpetas y PR

| Semana | Tema | Carpeta | PR integrados | PR abiertos o rama |
|---|---|---|---|---|
| 01 | Analisis y UML | [`semana-01`](../semana-01/) | #268 | - |
| 02 | Requisitos y SOLID (DIP) | [`semana-02`](../semana-02/) | #269 | - |
| 03 | Arquitectura y micro-HIS por capas | [`semana-03`](../semana-03/) | #876, #877 | - |
| 04 | MVC y Repository | [`semana-04`](../semana-04/) | #1182 | - |
| 05 | Contrato API e integracion | [`semana-05`](../semana-05/) | #245, #1404 | - |
| 06 | 1.a evaluacion | [`semana-06`](../semana-06/) | #1191 | - |
| 07 | Componentes y refactorizacion | [`semana-07`](../semana-07/) | #1195 | - |
| 08 | UX y wireframes | [`semana-08`](../semana-08/) | #1199 | - |
| 09 | Usabilidad y accesibilidad | [`semana-09`](../semana-09/) | #1203 | - |
| 10 | Movilidad | [`semana-10`](../semana-10/) | #1207 | - |
| 11 | Prototipo navegable | [`semana-11`](../semana-11/) | #1211 | - |
| 12 | 2.a evaluacion | [`semana-12`](../semana-12/) | #1215 | - |
| 13 | Revision tecnica | [`semana-13`](../semana-13/) | #1219 | - |
| 14 | Plan SQA | [`semana-14`](../semana-14/) | #1223 | - |
| 15 | Pruebas unitarias, caja blanca y negra | [`semana-15`](../semana-15/) | #1605 | - |
| 16 | Integracion y primera entrega | [`semana-16`](../semana-16/) | #246 (dominio y repositorio), #1612, #1616 | - |
| 17 | Seguridad y despliegue final | [`semana-17`](../semana-17/) | #1684 | - |
| 18 | Evaluacion final y defensa | [`semana-18`](./) | - | este PR |

La Semana 16 se dividio en dos PR por el limite de 400 lineas; las Semanas 17 y 18 se abrieron despues de
fusionar ambas partes, para que cada PR midiera solo su semana.

## Requisitos: estado final

| Requisito | Estado | Evidencia final |
|---|---|---|
| RF-01 Registrar en `scheduled` tras validar | Implementado | Recepcion agenda y consulta la cita; Valida en el borde |
| RF-02 Solape de paciente o doctor con 409 | Implementado en la API; la exclusion GiST real falta verificarla con PostgreSQL | El solape se traduce a 409 |
| RF-03 Confirmar (`scheduled` a `confirmed`) | Implementado | Matriz de permisos por rol |
| RF-04 Cancelar (`scheduled`/`confirmed` a `cancelled`) | Implementado | Transiciones de estado y cita finalizada inmutable |
| RF-05 Transicion invalida con error controlado | Implementado: 409 `appointments_finalized_immutable` | Transiciones de estado y cita finalizada inmutable |
| RF-06 Misma logica sin importar la persistencia | Implementado: Eloquent en produccion, memoria en pruebas | `CitasEnMemoria` |
| RNF-01 Dominio sin Eloquent | Parcial: el micro-HIS (S3-S4) no depende de Eloquent; en Laravel el contrato devuelve el modelo `Appointment` | `AppointmentRepositoryContract` |
| RNF-02 Probar sin base real | Implementado | 10 pruebas Feature sin PostgreSQL |
| RNF-03 Cambiar persistencia sin tocar el controlador | Implementado: binding en `AppServiceProvider` | `AppointmentController` |
| RNF-04 Garantias de la base en el adaptador real | Implementado en el esquema; verificacion con PostgreSQL pendiente | Migraciones HOSPITAL |

## Hallazgos y correcciones

| Origen | Hallazgo | Estado |
|---|---|---|
| S13 RTF-01 | Status HTTP deducido del texto | Corregido en S16 (`AppointmentWriteException->status`) |
| S13 RTF-02 | Validacion de campos en el borde | Corregido en S16 (422 con `errors`) |
| S13 RTF-03 | Fechas con zona horaria | Corregido en S16 (normalizacion a UTC) |
| S13 RTF-04 | Solape calculado cargando todas las citas | Resuelto por diseno: la API delega el solape a la exclusion GiST |
| S13 RTF-05 | Lectura sin guarda HOSPITAL | Corregido en S16 (`EnsureHospitalDatabaseTenant`) |
| S16 | El cast `datetime` perdia el offset | Corregido en el controlador (#1612) |

## Pendientes declarados

- Recorrido contra PostgreSQL real (exclusion GiST y triggers) y despliegue en un entorno autorizado (S17).
- **Supuesto pendiente de validar:** zona horaria UTC de la conexion `hospital`.
- Limite de tasa comun de `/api/v1`, a cargo del API Gateway.
