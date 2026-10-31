# Catálogo de pruebas de laboratorio: Semana 18, evaluación final y defensa

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-17 · **Issue:** #39

**Estado:** lista para la evaluación final. La demostración y la defensa oral se hacen en la evaluación, con el guion de abajo.

- [Trazabilidad de las 18 semanas](./trazabilidad-semanas.md)
- [Recorrido final sobre `develop`](./evidencia/salida-recorrido-final.txt)
- [Declaración de uso de IA](./DECLARACION_IA.md)

## Qué entrega el módulo

Mantiene las pruebas de laboratorio con su muestra, su unidad y sus rangos de referencia y críticos, **por versiones con vigencia**. Es la fuente de los umbrales que usan Resultados (ASII-19) y Alertas críticas (ASII-21).

| Pieza | Dónde | PR |
|---|---|---|
| Micro-HIS por capas | `micro-his/catalogo-pruebas-lab/` | #199, #1005 y #1020 |
| Tabla `lab_tests` en HOSPITAL (000600) y su prueba de integridad | `tests/Database/Hospital/LabTestCatalogIntegrityTest.php` | #1244 |
| API v1 (`GET` y `POST /api/v1/lab-tests`) y su provider | `app/Http/Controllers/Api/V1/LabTestController.php` | #1320 |
| Pantalla `/laboratorio/catalogo` | `resources/js/modules/catalogo-lab/` | #1247 |
| Prueba contra PostgreSQL y recorrido con login real | `tests/Database/Hospital/CatalogoLabPostgresTest.php` | #1591 |
| Entradas hostiles, límites de texto y matriz de amenazas | `tests/Feature/CatalogoLab/LabTestSecurityTest.php` | #1593 |

## Evidencia final sobre `develop` (a24bfb36, 30/10/2026)

Entorno local, no staging: PHP 8.4.21, PostgreSQL 18.4, Node 24.14.1 y Chrome headless. Las bases `*_e2e` se recrearon vacías con las migraciones de `develop`, el seed de CI y datos ficticios, con `APP_DEBUG=false` (pasos en la [bitácora de la Semana 17](../semana-17/bitacora-despliegue.md)).

| Verificación | Resultado |
|---|---|
| Micro-HIS: `run`, `whitebox`, `blackbox` y `api` | 30, 20, 13 y 15 comprobaciones OK |
| `tests/Feature/CatalogoLab`, `CatalogoLabPostgresTest` y `LabTestCatalogIntegrityTest` | OK (19 tests, 215 assertions) |
| `node resources/js/modules/catalogo-lab/catalogo.test.mjs` | 19 aserciones OK |
| Recorrido HTTP con login real (Semana 16) | **9/9** |
| Recorrido de la pantalla en Chrome (Semana 16) | **6/6** |
| Recorrido de seguridad, con la base caída en un segundo servidor (Semana 17) | **12/12** |

La salida completa está en [`evidencia/salida-recorrido-final.txt`](./evidencia/salida-recorrido-final.txt).

### Defectos encontrados en el recorrido final

| Dónde | Qué pasaba | Corrección en esta entrega |
|---|---|---|
| Recorrido de seguridad (Semana 17) | El paso «token con la firma alterada» cambiaba el **último** carácter por «x». En una firma HS256 ese carácter solo lleva 4 bits de datos: si el token terminaba en «w», el cambio dejaba la misma firma y la API respondía 200 porque el token seguía siendo válido. Pasa en 1 de cada 16 tokens; apareció en el recorrido final de admisión (ASII-07) | El script cambia un carácter del **medio** de la firma. Se comprobó que la API rechaza (401) cualquier firma realmente distinta |

## Guion de la demostración (8 minutos)

Con el entorno anterior y los usuarios ficticios del seed. Cada paso coincide con uno de los recorridos automatizados.

| # | Actor | Acción | Resultado esperado |
|---|---|---|---|
| 1 | Recepción | Entrar | Sin «Catálogo de laboratorio» en el menú |
| 2 | Técnico de laboratorio | Abrir el catálogo | Ve GLU vigente; no ve el formulario de alta |
| 3 | Superadmin | Registrar HB con crítico bajo 12 = referencia baja 12 | Error de rangos; no se envía |
| 4 | Superadmin | Corregir el crítico bajo a 7 y registrar | «HB registrada» y aparece en la tabla vigente |
| 5 | Superadmin | Registrar GLU otra vez | «Ya hay una versión vigente de esa prueba.» |
| 6 | Técnico de laboratorio | Repetir a 375 px | La página no se desplaza; la tabla sí, dentro de su contenedor |
| 7 | Superadmin | Consultar con la base HOSPITAL caída (servidor 8096) | 503 `LAB_CATALOG_UNAVAILABLE`, sin SQL ni host |

## Reglas que la defensa sostiene

1. Una versión publicada no se edita: cambiar rangos es registrar otra versión con su vigencia.
2. Un código tiene a lo sumo una versión vigente a la vez (409 `LAB_TEST_VALIDITY_OVERLAP`, también como restricción de PostgreSQL).
3. Los rangos numéricos cumplen crítico bajo < referencia baja ≤ referencia alta < crítico alto, con unidad.
4. La vigencia no empieza antes de hoy y el código se guarda normalizado (`glu` → `GLU`).
5. El autor de la versión sale del token, y cada alta deja auditoría H8.
6. Solo superadmin administra mientras `admin_hospital` siga en `pending`.

## Preguntas de defensa

- **¿Por qué versiones y no editar la fila?** Porque un resultado ya emitido debe poder explicarse con los rangos que estaban vigentes en ese momento.
- **¿Qué impide dos versiones vigentes de GLU?** El dominio y una restricción de PostgreSQL; `CatalogoLabPostgresTest` prueba que la segunda no deja auditoría.
- **¿Por qué importa a otros módulos?** Alertas críticas lee `critical_low` y `critical_high` de esta tabla para decidir si un resultado es crítico.
- **¿Qué defectos reales se encontraron?** Base caída que habría dado 500 (Semana 16); una unidad no textual o un rango `1e400` que daban 500, y textos sin límite (Semana 17).
- **¿Qué falta para producción?** Decidir el permiso de `admin_hospital`, cerrar la vigencia de una versión, la FK desde Órdenes y un staging autorizado.

## Cambio solicitado en la Semana 12

Laboratorio pidió **mostrar la próxima versión programada** («GLU v2 desde 01/11»). No está implementado. Las versiones futuras ya existen en la tabla; faltaría el campo opcional `next_version` en el contrato y la etiqueta en la pantalla.

## Pendientes al cierre

| Pendiente | Responsable |
|---|---|
| Aprobar o rechazar `laboratorio.catalogo.administrar` para `admin_hospital` | RBAC (ASII-02) |
| FK de `lab_order_items.lab_test_id` a esta tabla | Órdenes (ASII-16) |
| Caso de uso «cerrar vigencia» para publicar otra versión del mismo código | ASII-17, siguiente iteración |
| Elegir la base HOSPITAL por hospital; hoy aplica el guard de #1154 | Integración y Tenants (ASII-25) |
| Límite de tasa común de `/api/v1` | API Gateway |
| Staging u on-premise autorizado | Coordinación del proyecto |
