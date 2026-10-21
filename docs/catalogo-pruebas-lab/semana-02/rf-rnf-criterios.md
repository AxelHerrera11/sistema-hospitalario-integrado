# RF/RNF y criterios de aceptación — Catálogo de pruebas de laboratorio

Derivados de los casos de uso de la [Semana 1](../semana-01/README.md).

## Requerimientos funcionales

| Código | UC | Requerimiento | Criterio de aceptación |
|---|---|---|---|
| RF-LAB-01 | UC-LAB-01 | Registrar una prueba con código, nombre, sección, muestra, tipo de resultado y vigencia. | Con `laboratorio.catalogo.administrar` y datos válidos se crea una versión `active` con `version_no = n + 1` (`201`). |
| RF-LAB-02 | UC-LAB-02 | Exigir requisitos de muestra completos. | Volumen ≤ 0, sin recipiente o ayuno sin horas → `422 SPECIMEN_REQUIREMENT_INVALID`. |
| RF-LAB-03 | UC-LAB-03 | Validar la definición según el tipo. | Numérica sin unidad o con rangos desordenados → `422 RANGES_INCOHERENT`; cualitativa u ordinal con menos de dos valores → `422 RESULT_DEFINITION_INVALID`. |
| RF-LAB-04 | UC-LAB-03 | Impedir altas retroactivas. | `valid_from` anterior a hoy → `422 VALIDITY_INVALID`. |
| RF-LAB-05 | UC-LAB-04 | Rechazar vigencias solapadas del mismo código. | Otra versión de «GLU» con vigencia abierta → `409 LAB_TEST_VALIDITY_OVERLAP`. |
| RF-LAB-06 | UC-LAB-05 | Listar las pruebas vigentes a una fecha, con búsqueda. | Solo versiones `active` con `valid_from ≤ fecha < valid_to`; sin coincidencias, `200` con lista vacía. |
| RF-LAB-07 | UC-LAB-06 | Mostrar el detalle con muestra, unidad y rangos. | Una versión no vigente se marca «no se puede ordenar»; si no existe, `404`. |
| RF-LAB-08 | UC-LAB-07 | Cerrar la vigencia sin borrar. | Con motivo y fecha ≥ hoy deja de ofrecerse; las órdenes emitidas conservan su versión. |
| RF-LAB-09 | UC-LAB-09 | Auditar altas y cierres. | Cada operación exitosa deja un evento en `local_access_audits`. |

## Requerimientos no funcionales

| Código | Categoría | Criterio medible |
|---|---|---|
| RNF-LAB-01 | Seguridad | El 100 % de las peticiones sin permiso o con `X-Tenant-ID` ajeno reciben `401`/`403`. |
| RNF-LAB-02 | Integridad clínica | Una versión usada por una orden no cambia: los cambios crean una versión nueva. |
| RNF-LAB-03 | Concurrencia | De 10 altas simultáneas con vigencias cruzadas, exactamente 1 termina en `201`. |
| RNF-LAB-04 | Consistencia | Si la auditoría falla, no queda ninguna versión sin su evento. |
| RNF-LAB-05 | Rendimiento | `GET /lab-tests?search=…` ≤ 300 ms (p95) con 2 000 versiones. |
| RNF-LAB-06 | Mantenibilidad (OCP) | Un tipo nuevo solo crea una clase y su registro; `LabTestValidator` y `ResultTypeRegistry` no cambian ([demo](./evidencia/demo-ocp.php)). |

## Escenario de aceptación (OCP)

```gherkin
Dado que el laboratorio necesita la proteinuria con escala negativo, 1+, 2+, 3+ y 4+
Cuando se registra la clase OrdinalResultSpec
Entonces la prueba ordinal con umbral crítico 4+ es válida
Y un umbral fuera de la escala responde 422 RESULT_DEFINITION_INVALID
Y LabTestValidator y ResultTypeRegistry no se modifican
```
