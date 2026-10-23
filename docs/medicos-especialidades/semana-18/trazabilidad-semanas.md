# Trazabilidad de las 18 semanas del módulo

Cada semana corresponde a una entrega con su Pull Request. La columna «Estado» refleja la situación verificada el 22 de octubre de 2026 con `gh pr view`; un PR «Borrador» o «Abierto» **no** está integrado en `develop`.

| Semana | Entregable | PR | Estado |
|---:|---|---:|---|
| 1 | Modelado UML del flujo: casos de uso, actividad y secuencia, con narrativa y trazabilidad | #35 | Fusionado |
| 2 | Diseño con ISP: RF/RNF y criterios de aceptación; `DoctorDirectory` dividida en 6 puertos | #66 | Fusionado |
| 3 | Micro-HIS en PHP vanilla con capas: dominio, aplicación, persistencia PDO, entrada HTTP y vista C4 | #124, #126, #127 | Fusionados |
| 4 | Capas y patrón Repository (adaptadores PDO e InMemory), repositorio compartido y entrada MVC | #355, #357, #617, #618 | Fusionados (#356, la primera versión del MVC, se cerró y se rehízo como #617 y #618) |
| 5 | Cliente-servidor: contrato API v1, OpenAPI y frontera de microservicio | #371 | Fusionado |
| 5 | Servidor API v1 y cliente HTTP | #372 | **Abierto**: no está en `develop` (ver «Laguna de la Semana 5») |
| 6 | Primera evaluación parcial: tesis, decisiones defendidas y caso práctico | #381 | Fusionado |
| 7 | Componentes backend/frontend y refactorización de la consulta de disponibilidad | #382 | Fusionado |
| 8 | Flujo UX por rol, wireframes y reglas de interacción | #400 | Fusionado |
| 9 | Usabilidad (10 heurísticas) y accesibilidad (WCAG 2.1 AA): 9 hallazgos priorizados | #401 | Fusionado |
| 10 | Diseño para movilidad de 320 a 430 px | #402 | Fusionado |
| 11 | Prototipo navegable desktop y móvil con 9 capturas | #408 | Fusionado |
| 12 | Segunda evaluación parcial y guiones de evidencia reproducible (contraste y prototipo) | #428, #429 | Fusionados |
| 13 | Revisión técnica formal | #524 | **Borrador** |
| 14 | Plan SQA: métricas, riesgos y controles | #526 | Fusionado |
| 15 | Pruebas unitarias, de caja blanca y de caja negra trazadas a RF/RNF, con 5 defectos registrados | #527 | **Borrador** |
| 16 | Integración en Laravel: adaptador PostgreSQL, API, defectos corregidos, prueba en PostgreSQL 16, `slot_minutes`, pantalla Vue, zona horaria y documentación | #1038, #1039, #1042, #1043, #1044, #1045, #1046, #1040 | **Abiertos** (#1038 listo para revisión; el resto, borrador y apilado) |
| 17 | Seguridad: pruebas de permisos y datos sensibles, matriz de amenazas, bitácora de despliegue local, límite de tasa y longitudes máximas | #1052, #1061 | **Borrador**, apilados |
| 18 | Evaluación final y defensa: README, trazabilidad y guion (#1076); `trace_id` en Laravel (#1088); medición de RNF-MED-04 y lectura en lote (#1091); prueba de punta a punta contra el backend real (#1109); errores del cliente que no son 500 (#1111); contrato API actualizado (#1112); contraste y teclado de la pantalla real (este PR) | #1076, #1088, #1091, #1109, #1111, #1112 y este PR | Borrador, apilados |

## Continuidad entre entregas

- **Semana 1 → 2:** los casos de uso UC-MED-01 a 08 originan los RF-MED-01 a 07 y los RNF-MED-01 a 05.
- **Semana 2 → 3:** el ISP produce los puertos que las capas de la Semana 3 implementan; la consulta de disponibilidad no ve los puertos de escritura.
- **Semana 3 → 4:** `DoctorRepository` nace como el único punto de acceso a datos y permite que la misma suite pase con PDO y con InMemory.
- **Semana 4 → 5:** el Router y el controlador MVC son la base del contrato `/api/v1`.
- **Semana 5 → 16:** el contrato de la Semana 5 se conserva tal cual; en la Semana 16 el `Router` del micro-HIS se expone desde Laravel, de modo que los códigos y estados no cambian.
- **Semana 6 → 12 → 16:** la tesis de la Semana 6 («la disponibilidad que se ofrece es verdadera») se sostiene con los componentes y la UX de las semanas 7 a 12 y se completa con D-02 (franjas por intervalo) y `slot_minutes` en la Semana 16.
- **Semana 13 → 15 → 16:** la revisión técnica y la prueba de la Semana 15 detectaron D-01 a D-05; la Semana 16 corrige D-01, D-02 y D-04, y la integración en Laravel cubre D-03 y D-05.
- **Semana 16 → 17:** el API integrado es el objeto de la matriz de amenazas; MED-14 y MED-15 se cerraron con límite de tasa y longitudes máximas.

## Laguna de la Semana 5

El contrato API v1 (#371) está integrado, pero el servidor API v1 y el cliente HTTP (#372) siguen abiertos. En la práctica, las rutas de Laravel de la Semana 16 (#1039) ocupan el lugar del servidor; **no existe en `develop` un cliente HTTP** del módulo, ni lo necesita hoy ningún otro módulo. La evidencia de la Semana 15 se ejecutó contra la punta de la pila de #372. Qué se hace con ese PR queda como decisión pendiente del autor.

## Qué no está integrado todavía

Todo lo de las semanas 13, 15, 16, 17 y 18 está en PR abiertos. Hasta que se integren en orden (#1038 primero, y después cada PR de la cadena reorientado a `develop`), `develop` solo contiene el micro-HIS sin ruta en Laravel.
