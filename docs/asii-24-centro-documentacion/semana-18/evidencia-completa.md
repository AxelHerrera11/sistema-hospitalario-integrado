# Evidencia completa y PR integrados

Indice final del modulo ASII-24, adaptacion "centro de documentacion y manuales por rol" (issue #208). Estados
de los PR consultados en GitHub el 2026-10-30.

## Semanas, carpetas y PR

| Semana | Tema | Carpeta | PR integrados | PR abiertos o rama |
|---|---|---|---|---|
| 01 | Analisis y UML | [`semana-01`](../semana-01/) | #270 | - |
| 02 | Requisitos y SOLID (ISP) | [`semana-02`](../semana-02/) | #271 | - |
| 03 | Arquitectura y micro-HIS por capas | [`semana-03`](../semana-03/) | #893, #894 | - |
| 04 | MVC y Repository | [`semana-04`](../semana-04/) | #1183 | - |
| 05 | Contrato API (OpenAPI) e integracion | [`semana-05`](../semana-05/) | #248, #1361 | - |
| 06 | 1.a evaluacion | [`semana-06`](../semana-06/) | #1192 | - |
| 07 | Componentes y refactorizacion | [`semana-07`](../semana-07/) | #1196 | - |
| 08 | UX y wireframes | [`semana-08`](../semana-08/) | #1200 | - |
| 09 | Usabilidad y accesibilidad | [`semana-09`](../semana-09/) | #1204 | - |
| 10 | Movilidad | [`semana-10`](../semana-10/) | #1208 | - |
| 11 | Prototipo navegable | [`semana-11`](../semana-11/) | #1212 | - |
| 12 | 2.a evaluacion | [`semana-12`](../semana-12/) | #1216 | - |
| 13 | Revision tecnica | [`semana-13`](../semana-13/) | #1220 | - |
| 14 | Plan SQA | [`semana-14`](../semana-14/) | #1226 | - |
| 15 | Pruebas unitarias, caja blanca y negra | [`semana-15`](../semana-15/) | #1405 | - |
| 16 | Integracion y primera entrega | [`semana-16`](../semana-16/) | #677 (formato comun de errores), #1613, #1617 | - |
| 17 | Seguridad y despliegue final | [`semana-17`](../semana-17/) | #1685 | - |
| 18 | Evaluacion final y defensa | [`semana-18`](./) | - | este PR |

La Semana 16 se dividio en dos PR por el limite de 400 lineas; las Semanas 17 y 18 se abrieron despues de
fusionar ambas partes, para que cada PR midiera solo su semana. La tarea Odoo #3330 (Semana 3) quedo
emparejada con #1226 por error; ver el issue #1292.

## Requisitos: estado final

| Requisito | Estado | Evidencia final |
|---|---|---|
| RF-01 Publicar un documento con sus roles | **Propuesto**: hoy se agrega una entrada en `config/centro_documentacion.php` | Catalogo en configuracion |
| RF-02 Listar el catalogo filtrado por rol | Implementado | Cada rol ve solo su catalogo |
| RF-03 Buscar por texto en el catalogo visible | **Propuesto** | - |
| RF-04 Descargar un documento permitido | Implementado | Entrega el contenido con su tipo y version |
| RF-05 Versiones y vigencia | Parcial: version en `X-Document-Version`; vigencia propuesta | Entrega el contenido con su tipo y version |
| RF-06 Consulta sin depender de publicacion | Implementado: `ConsultarCatalogo` es solo lectura | `DocumentacionController` |
| RNF-01 Cada cliente depende de lo que usa | Implementado | El controlador solo conoce `ConsultarCatalogo` |
| RNF-02 Cambios de firma aislados | Implementado en la API (una sola interfaz de consulta) | - |
| RNF-03 Catalogo probado sin publicacion | Implementado | `micro-his/centro-documentacion/tests/run.php` (7) |
| RNF-04 Un adaptador con varias interfaces | Demostrado en el micro-HIS de la Semana 4; la API solo usa la consulta | `semana-04` |

## Hallazgos y correcciones

| Origen | Hallazgo | Estado |
|---|---|---|
| S13 RTF-01 | Rol enviado por el cliente | Corregido en S16 (`RoleResolver` desde el JWT y el tenant) |
| S13 RTF-02 | Status HTTP deducido del texto | Resuelto: la API devuelve 404 explicito, sin interpretar excepciones |
| S13 RTF-03 | Documento sin titulo o sin roles | Corregido en S16 (se rechaza al cargar el catalogo) |
| S13 RTF-04 | Id aleatorio en la publicacion | No aplica: la publicacion sigue propuesta |
| S13 RTF-05 | Tabla de documentos sin tenant | No aplica hoy (catalogo compartido en configuracion); si pasa a tabla, `tenant_id` **propuesto** |
| S16 | Permiso no catalogado `.publicar` en ramas previas | Corregido en las ramas de las Semanas 5, 11 y 15 |
| S16 | El 404 no reutilizaba un `X-Request-Id` valido | Corregido con prueba (#1613) |

## Pendientes declarados

- Publicar, buscar por texto y vigencia de documentos, **propuestos**.
- Despliegue en un entorno autorizado (S17) y limite de tasa comun de `/api/v1` (API Gateway).
