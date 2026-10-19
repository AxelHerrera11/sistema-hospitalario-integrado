# Catálogo de pruebas de laboratorio — Semana 6: primera evaluación parcial

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-17 · **Entrega:** defensa teórica y caso práctico arquitectónico.

## Tesis arquitectónica

En el flujo «alta y consulta de prueba con requisitos de muestra y vigencia», **una versión de prueba nace válida o no nace, y una vez usada nunca cambia**. Las reglas viven en el dominio y no en el controlador ni en la base. El acceso a datos pasa por un puerto (`LabTestRepository`) con dos adaptadores intercambiables, y el resto del HIS solo conoce el contrato `/api/v1/lab-tests` y guarda el `lab_test_id` exacto de la versión que usó. Así, un cambio en la regla, en la base o en el consumidor queda contenido en una sola pieza.

## Trazabilidad de punta a punta

```mermaid
flowchart LR
  J([Jefatura de laboratorio]) --> UC1[UC-LAB-01..04<br/>Registrar prueba]
  O([Órdenes ASII-16]) --> UC5[UC-LAB-05<br/>Consultar vigentes]
  UC1 --> RF1[RF-LAB-01..05<br/>muestra · rangos · vigencia · sin solapes]
  UC5 --> RF6[RF-LAB-06]
  RF1 --> DOM[Domain: LabTest::register<br/>DomainRuleViolation]
  RF1 --> APP[Application: RegisterLabTest]
  RF6 --> APP2[Application: FindActiveLabTests]
  APP & APP2 --> REP{{LabTestRepository<br/>PDO · InMemory}}
  APP --> EP1[POST /api/v1/lab-tests]
  APP2 --> EP2[GET /api/v1/lab-tests]
```

| Semana | Qué aportó | Evidencia |
|---|---|---|
| 1 | Actores, UC-LAB-01..09, actividad, secuencia y Anexo A (`lab_tests` propuesta) | `docs/catalogo-pruebas-lab/semana-01/` (PR #43, integrado) |
| 2 | RF/RNF medibles y OCP para los tipos de resultado | `docs/catalogo-pruebas-lab/semana-02/` (PR #197) |
| 3 | Micro-HIS por capas con PDO y pruebas | `micro-his/catalogo-pruebas-lab/` (PR #199) |
| 4 | MVC + Repository con adaptadores PDO e InMemory y prueba de contrato | Reentrega de la Semana 4 (diseño del PR #59) |
| 5 | API v1, cliente con falla segura, OpenAPI y frontera de microservicio | Reentrega de la Semana 5 (diseño del PR #63) |

## Decisiones defendidas

| Decisión | Por qué | Evidencia verificable |
|---|---|---|
| Las reglas viven en `LabTest::register` (dominio) | Ningún adaptador ni controlador puede saltárselas; la versión nace válida o no nace | La prueba «ningún alta rechazada se guardó» de `tests/run.php` |
| Tipos de resultado abiertos a extensión (OCP) | Un tipo nuevo (ordinal, título) no debe tocar el validador | Demo `semana-02/evidencia/demo-ocp.php`: 12 aserciones |
| Versiones inmutables y vigencias sin solapes | Una orden emitida conserva su definición; dos versiones vigentes serían ambiguas | `409 LAB_TEST_VALIDITY_OVERLAP`, `UNIQUE (code, version_no)` y exclusión GiST propuesta |
| Alta y auditoría en una transacción | No puede quedar una versión sin evidencia | La prueba de rollback con la auditoría caída |
| Repository como puerto | La aplicación no conoce SQL; las pruebas corren sin base | La misma batería con PDO e InMemory (Semana 4) |
| Contrato `/api/v1` y cliente con falla segura | Los consumidores no dependen del código ni de la base del módulo | `openapi.yaml` validado; `CatalogUnavailable` en lugar de ordenar a ciegas (Semana 5) |

**Riesgos que sigo defendiendo como abiertos:** `lab_tests` aún no tiene migración (se coordina con Javier) y la columna de escala de resultados (`result_scale`) es una propuesta.

## Caso práctico arquitectónico

**Pedido:** el laboratorio necesita la **proteinuria en tira reactiva**, con escala ordinal `negativo, 1+, 2+, 3+, 4+` y valor crítico desde `4+`. Hoy el micro-HIS solo acepta `numeric`, `qualitative` y `text`.

| Capa o pieza | ¿Cambia? | Qué cambia |
|---|---|---|
| Domain | Sí, una regla | Admitir `ordinal` con escala sin repetidos y umbral dentro de la escala. En el diseño OCP de la Semana 2 es una clase nueva (`OrdinalResultSpec`); el micro-HIS condensado aplica las reglas en un `match`, así que hoy es un brazo más |
| Application (`RegisterLabTest`, `FindActiveLabTests`) | No | Siguen pidiendo «validar y guardar» y «vigentes a una fecha» |
| Repository (PDO e InMemory) | Mínimo | Guardar y leer la escala (`result_scale`) |
| Base HOSPITAL | Sí | Ampliar el `CHECK` de `result_type` y agregar `result_scale jsonb`: migración acordada con Javier |
| Contrato API | Sí, compatible | Nuevo valor en el `enum` de `result_type` y el campo opcional `result_scale`; los clientes actuales no se rompen |
| Consumidores (Órdenes, Resultados, Alertas) | No | Siguen usando el `lab_test_id`; Resultados (Gerson) acepta un valor de la escala y Alertas compara contra `critical_from` |

**Conclusión:** el cambio queda contenido en el dominio, la persistencia y una versión compatible del contrato, sin tocar a los consumidores. Ese es el beneficio de separar reglas, puerto y contrato.

Guion de la exposición: [presentacion.md](./presentacion.md) · Uso de IA: [DECLARACION_IA.md](./DECLARACION_IA.md)
