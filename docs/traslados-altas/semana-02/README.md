# Traslados, altas y liberación de camas — Semana 2

## Ficha

- **Estudiante:** María Yamilet Lindo Pablo · **GitHub:** [`Yamilet1235`](https://github.com/Yamilet1235)
- **Módulo:** `ASII-08` — Traslados, altas y liberación de camas · **Issue:** `Refs #9` · **Destino:** `develop`
- **Asignación:** aplicar LSP al flujo «traslado o alta del paciente con liberación consistente de cama»; RF/RNF, criterios de aceptación, diseño antes/después, responsabilidades, dependencias y evidencia verificable.
- **Base:** [Semana 1](../semana-01/README.md) (`UC-TA-01`, `UC-TA-02`, reglas `R-TA-01` a `R-TA-07`).

## Alcance de esta primera parte

Esta parte (PR-1) entrega el análisis y el diseño. **No completa por sí sola la Semana 2**: la evidencia ejecutable de sustitución (demo en memoria y su registro de resultados) se entrega en un segundo PR, después de integrar este.

- [RF/RNF y criterios de aceptación](./rf-rnf-criterios.md)
- [Aplicación de LSP: diseño antes y después](./solid-lsp.md)
- [Declaración de uso de IA](./DECLARACION_IA.md)

No incluye servicios productivos, API, UI ni migraciones.

## Trazabilidad de requisitos al diseño

| Requisitos y criterios | Sección de `solid-lsp.md` |
|---|---|
| RF-TA-01, RF-TA-02 · CA-01 | Contrato común: construcción con solicitud válida |
| RF-TA-03 · CA-02 | Contrato común: precondiciones (autorización previa) |
| RF-TA-04, RF-TA-05 · CA-03 a CA-07 | Contrato común: validaciones al ejecutar y `RechazoConfirmado` |
| RF-TA-06 · CA-08 | Resultados específicos: `TrasladoInterno` |
| RF-TA-07 · CA-09 | Resultados específicos: `AltaHospitalaria` |
| RF-TA-08 · CA-12, CA-13 | Contrato común: `FalloConfirmado` y `ResultadoIncierto` |
| RF-TA-09, RNF-TA-01 · CA-10 | Clientes y justificación de la sustitución |
| RF-TA-10 · CA-11 | `ExitoConfirmado` (sin cambios en `beds.status`) y pendientes de integración |
| RNF-TA-02 a RNF-TA-06 | Contrato común e invariantes; tabla de existente, propuesto y pendiente |

## Validaciones ejecutadas sobre los documentos

- Los dos diagramas Mermaid de `solid-lsp.md` se generaron sin errores con `@mermaid-js/mermaid-cli` 11.4.2, a partir de archivos temporales fuera del repositorio, y se revisó la imagen resultante. Un diagrama con error deliberado sí falló, como control.
- Formato UTF-8 con saltos de línea LF; columnas consistentes en todas las tablas.
- Los enlaces relativos apuntan a archivos existentes.

## Revisión estática de esquema y pruebas

- Las migraciones HOSPITAL `000200` y `000300` definen `care_episodes_status_period_check`, `bed_stays_reason_check`, `bed_stays_period_check`, `bed_stays_bed_active_period_exclusion`, el índice `bed_stays_open_care_episode_unique` y los triggers de período e inmutabilidad de `bed_stays`.
- El código de `SchedulingAndBedIntegrityTest` contiene casos para la adyacencia y el rechazo de solapes por cama, el rechazo de una segunda estancia abierta del episodio y los límites del período al insertar una estancia. También comprueba que `beds.status` no representa por sí solo la ocupación.
- Ese test no contiene casos directos para `bed_stays_reason_check`, `bed_stays_period_check`, `care_episodes_status_period_check` ni para la inmutabilidad de una estancia finalizada.
- Los códigos de rechazo del diseño se compararon estáticamente con `rf-rnf-criterios.md`.

## Pruebas no ejecutadas y limitaciones

- No se verificó cómo GitHub muestra los diagramas.
- No se ejecutó `SchedulingAndBedIntegrityTest`: requiere PostgreSQL. Su cobertura se determinó leyendo el código; las restricciones, el índice y los triggers se confirmaron en las migraciones, no mediante ejecución en este PR.
- El diseño después es una propuesta: no hay código que lo implemente ni pruebas de bloqueos, concurrencia o atomicidad.
- La sincronización de `beds.status` y los roles de autorización siguen pendientes de coordinación con ASII-06, ASII-07 y ASII-09.
