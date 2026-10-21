# Checklist de revisión técnica formal (Semana 13)

Checklist reutilizable del módulo de verificación de BD para revisar cualquier PR del
grupo que toque persistencia. Cada punto se marca con el resultado real de la
revisión del PR #376.

## A. Consulta y acceso a datos

| # | Punto | Resultado en #376 |
|---|---|---|
| A1 | Las consultas usan sentencias preparadas con parámetros vinculados | Cumple |
| A2 | No hay concatenación de valores en el SQL | Cumple |
| A3 | La conexión es explícita y corresponde a la base del hospital | No aplica (doble local) |
| A4 | El SQL no incluye datos clínicos en logs ni mensajes | Cumple |

## B. Transacciones

| # | Punto | Resultado en #376 |
|---|---|---|
| B1 | Las escrituras sobre varias tablas están en una transacción | Cumple |
| B2 | La reversión se ejecuta si cualquier parte falla, incluida la auditoría | Cumple |
| B3 | No hay escritura fuera de la transacción | Cumple |

## C. Correspondencia con el esquema federado

| # | Punto | Resultado en #376 |
|---|---|---|
| C1 | Los identificadores son UUID | **No cumple** |
| C2 | Los tipos coinciden con el esquema real (`uuid`, `timestamptz`, `date`, `text`) | **No cumple** |
| C3 | Las claves foráneas coinciden con las del esquema real | **No cumple** |
| C4 | Las restricciones del esquema real están replicadas (unicidad, checks, triggers) | **No cumple** |
| C5 | El doble declara qué restricciones del esquema sustituye | **No cumple** |

## D. Entidades y nomenclatura

| # | Punto | Resultado en #376 |
|---|---|---|
| D1 | Toda tabla nueva está registrada en la lista de entidades | **No cumple** |
| D2 | Los nombres no colisionan con entidades de la especificación con otro diseño | **No cumple** |
| D3 | La propiedad de cada tabla es explícita | Parcial |

## E. Proceso del PR

| # | Punto | Resultado en #376 |
|---|---|---|
| E1 | La rama parte de `origin/develop` | Cumple |
| E2 | El PR solo toca carpetas de su módulo | Cumple |
| E3 | La documentación coincide con el código | Cumple |
| E4 | Las validaciones ejecutadas están en el cuerpo del PR | Cumple |
| E5 | No hay credenciales, tokens ni datos sensibles | Cumple |
| E6 | Los hallazgos se comunican al autor el mismo día | Cumple (comentario en el PR) |

## Resultado de la revisión

| Severidad | Cantidad |
|---|---:|
| Bloqueante | 0 |
| Mayor | 3 |
| Moderado | 2 |
| Menor | 2 |
| Positivo | 3 |

**Veredicto:** aprobado con condiciones. El módulo puede continuar; antes de cerrar su
adaptador real debe resolver R-01 a R-07.
