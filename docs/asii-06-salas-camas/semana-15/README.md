# ASII-06 — Semana 15: Diseño y ejecución de pruebas

Autora: Madelin Jazmín Cerón Molina (`MadelinCeron`). Refs #11.
Rama: `feature/asii-06-semana-15-salas-camas-madelinceron`; destino: `develop`.
Base inicial: `3ea30faa44e6ece877f9141405374cb519279b45`, igual a origin/develop
tras fetch. Árbol limpio al inicio; upstream inicial origin/develop, sin pull
a ciegas ni incorporación de las ramas de Semanas 13/14.

## Entrega y resultados

- [Plan de pruebas](./plan-pruebas.md): casos, entradas, expectativas y resultados.
- [Matriz de trazabilidad](./matriz-trazabilidad.md): RF/RNF auténticos y límites.
- [Evidencias](./evidencias.md): comandos y resultados nuevos del 28/10/2026.
- [Registro de defectos](./registro-defectos.md): defecto previo, brechas y seguimiento.

Se implementaron **3 casos unitarios y 2 de caja blanca**, distintos y sin doble
conteo, en `CatalogRulesTest`. Se reutilizaron **4 casos de caja negra** ya
implementados en `WardCatalogEndpointTest`, evitando duplicar la suite HTTP.
Se añadieron **5 casos de integración negativos** en `CatalogConstraintsTest`:
FK, unicidad ward, unicidad cama y CHECKs de ambos estados, con rollback.
Todos aprobaron. Total de las cinco suites PHPUnit ejecutadas: **27 pruebas,
171 aserciones**; Node: **2/2**. El standalone emitió cuatro mensajes PASS.
Build aprobado (142 módulos). Resultados detallados y duraciones en evidencias.

## Alcance y límites

Flujo inspeccionado: Vue `/wards` → Axios → rutas con tenant/JWT/RBAC y guard
temporal → controlador → `CatalogService` → repositorios PDO → `wards`/`beds`.
GET `/api/v1/wards` y GET `/api/v1/wards/{ward}/beds` exigen `camas.ver`.
Sin payload; retornan `data` y `meta`. Los errores del controlador están en
`error.code`; RBAC usa `code`. Guard temporal usa `error` de tipo string.
El plan detalla el contrato observado; no se inventan endpoints de escritura.

La creación está implementada en dominio/servicio y micro-HIS y fue ejercitada
por la integración PDO existente; faltan POST Laravel y formularios Vue.
Capacidad máxima y transiciones clínicas no están modeladas. Rollback del test
no demuestra una transacción de negocio multirregistro. #1154 sigue siendo
contención temporal, sin resolución dinámica de base por hospital.
Sin cambios a código productivo, migraciones, rooms, .env, otros módulos ni
Semanas 13/14. Sin evidencia visual autenticada ni declaración WCAG.

Madelin Cerón responde por pruebas y documentación; revisión técnica,
verificación independiente y corrección técnica: **Pendiente de asignación**.
La aprobación del PR se registra en GitHub según `.github/BRANCH_PROTECTION.md`.

- [x] Mínimos 3 unitarias, 2 caja blanca y 4 caja negra ejecutados sin duplicar casos.
- [x] Evidencia nueva, integridad PostgreSQL y trazabilidad documentadas.
- [ ] Aislamiento dinámico definitivo y creación HTTP/UI completos.
- [ ] Recorrido visual autenticado y aprobación independiente.
