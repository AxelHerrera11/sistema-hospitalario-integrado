# Narrativa de alcance y trazabilidad

**Módulo:** `ASII-27 - Reportes operativos exportables y filtros administrativos` | **Issue:** `#210` | **PR:** hacia `develop` con `Refs #210`.
**Proceso modelado:** filtrado y exportación reproducible de un reporte operativo.

## Alcance y carácter propuesto

El proceso permite a un **usuario administrativo** consultar un reporte operativo, aplicar filtros administrativos, validarlos, visualizar los resultados y solicitar la exportación reproducible del archivo. Los tres diagramas representan el mismo proceso desde tres perspectivas y mantienen trazabilidad entre sí.

Los identificadores `CU-01`..`CU-04`, `RF-01`..`RF-09` y las excepciones `EX-01`..`EX-03` son una propuesta de esta entrega: la asignación pide modelar el proceso, no fija una numeración oficial. Solo se modela el diseño; no se implementa backend, endpoints ni interfaz en este entregable.

## Actores y participantes

| Elemento | Rol en el proceso |
|---|---|
| Usuario administrativo | Actor principal: consulta, filtra, visualiza y exporta |
| Servicio de autenticación y autorización | Actor secundario: valida acceso y autorización |
| Interfaz de reportes | Participante: canal de interacción del usuario |
| Módulo de reportes | Participante: valida filtros y coordina consulta y exportación |
| Base de datos | Participante: provee la información del reporte |

## Casos de uso

| ID | Caso de uso | Descripción |
|---|---|---|
| `CU-01` | Consultar reporte operativo | Inicia el proceso y habilita el filtrado y la visualización |
| `CU-02` | Aplicar filtros administrativos | Captura y valida los filtros antes de ejecutar la consulta |
| `CU-03` | Visualizar resultados | Presenta los resultados filtrados del reporte |
| `CU-04` | Exportar reporte | Genera y entrega el archivo reproducible del reporte |

`CU-01` incluye a `CU-02` y `CU-03`. `CU-02` y `CU-04` incluyen la validación de acceso (autenticación, autorización y hospital/tenant activo). Las extensiones `Informar filtros inválidos o sin resultados` e `Informar error durante la exportación` cubren los caminos alternos.

## Diagrama de actividad

Decisiones, excepciones y resultado:

- `¿Filtros válidos?` (No): mostrar error → corregir filtros → revalidar (`loop`).
- `¿Existen resultados?` (No): mostrar "Sin resultados" y terminar sin exportar.
- `¿Exportación exitosa?` (Sí/No): preparar descarga o informar error y terminar.

El flujo tiene una salida explícita por cada rama: sin resultados, error de exportación y entrega del archivo.

## Diagrama de secuencia

Participantes: usuario administrativo, interfaz de reportes, módulo de reportes, servicio de autenticación y autorización, y base de datos. La secuencia muestra: validación de acceso, envío y validación de filtros, consulta a la base de datos, alternativas `alt` para acceso denegado, filtros inválidos, error de consulta, sin resultados, error de exportación y exportación exitosa.

## Trazabilidad

| ID | Requisito | Caso de uso | Actividad | Secuencia |
|---|---|---|---|---|
| `RF-01` | Consultar un reporte operativo | `CU-01` | Consultar reporte / Ejecutar consulta | Usuario → Interfaz → Módulo |
| `RF-02` | Aplicar filtros administrativos | `CU-02` | Ingresar filtros / Validar filtros | Usuario → Interfaz → Módulo |
| `RF-03` | Validar los filtros antes de la consulta | `CU-02` | ¿Filtros válidos? | Validar filtros / `alt Filtros inválidos` |
| `RF-04` | Visualizar los resultados | `CU-03` | Mostrar resultados | Módulo → Interfaz → Usuario |
| `RF-05` | Gestionar una consulta sin resultados | `CU-01` | ¿Existen resultados? | `alt Sin resultados` |
| `RF-06` | Solicitar la exportación | `CU-04` | Solicitar exportación | Usuario → Interfaz → Módulo |
| `RF-07` | Generar el archivo del reporte | `CU-04` | Generar archivo | Módulo → Módulo |
| `RF-08` | Informar errores de exportación | `CU-04` | ¿Exportación exitosa? | `alt Error durante la exportación` |
| `RF-09` | Entregar el archivo exportado | `CU-04` | Preparar descarga / Recibir archivo | Módulo → Interfaz → Usuario |
| `RNF-01` | Verificar autenticación y autorización antes de acceder | `CU-01`,`CU-02`,`CU-04` | Condición de acceso | Módulo → Servicio de autenticación |
| `RNF-02` | Respetar el aislamiento por hospital/tenant | Condición de acceso | Condición de acceso | Validación del contexto de acceso |

## Trazabilidad de excepciones

| ID | Excepción | Actividad | Secuencia | Resultado esperado |
|---|---|---|---|---|
| `EX-01` | Filtros inválidos | Mostrar error → Corregir filtros | `alt Filtros inválidos` + `loop` | El usuario corrige y revalida |
| `EX-02` | Consulta sin resultados | ¿Existen resultados? (No) | `alt Sin resultados` | Se informa y finaliza sin archivo |
| `EX-03` | Error durante la exportación | ¿Exportación exitosa? (No) | `alt Error durante la exportación` | Se informa y finaliza sin archivo |

## Existente y propuesto

| Componente o regla | Estado |
|---|---|
| Módulo ASII-27, proceso y alcance | **Propuestos/descritos** en esta entrega (UML) |
| Contrato de filtros y exportación (`docs/asii-27-reportes/contrato-filtros-export.md`) | **Publicado por separado** en la tarea de contrato; no forma parte de este PR |
| Validación de acceso, autorización y tenant (RBAC) | **Scaffold en curso** en el repositorio; pendiente de acuerdos del grupo |
| Implementación de filtros, catálogo de reportes y exportadores | **Fuera de alcance** de la Semana 1 |

## Exclusiones

No se definen RF/RNF formales de arquitectura, SOLID, backend, endpoints ni interfaz implementada. Quedan fuera del proceso los analytics e indicadores, el dashboard y la UI/UX transversal, la auditoría y la creación o modificación de información clínica.
