# Integración corregida de semanas 16 y 17 — 20 de octubre de 2026

## Cinco PR contra develop, cada uno con un máximo de 400 líneas

Todos parten de la base `0544d5f591643ec5993123a9bb4b8cbdafc7b97e` de develop.
Los commits correctivos retiran del diff de cada rama los archivos de las demás;
no se reescribió historia, no se usó force push ni se sustituyeron los PR.

| PR | Rama | Contenido propio |
|---|---|---|
| #495 | `feature/asii-02-semana-16-rbac-mar-03` | Backend completo, matriz configurada, alias y pruebas HTTP base |
| #490 | `feature/asii-02-semana-16-rbac-matriz-mar-03` | Catálogo Vue, pruebas de matriz y evidencia S16 |
| #488 | `feature/asii-02-semana-16-rbac-docs-mar-03` | Contratos, matriz publicada, diagramas y esta guía |
| #483 | `feature/asii-02-semana-17-rbac-mar-03` | Pruebas de amenazas y evidencia S17 |
| #493 | `feature/asii-02-semana-17-rbac-pruebas-mar-03` | Pruebas SQL del resolver e integración JWT con Cindy |

Los tamaños se calculan contra develop, no contra la rama anterior. Los conjuntos
son disjuntos: cada archivo pertenece a un solo PR. Así no hay cambios acumulados
al abrir los cinco PR simultáneamente. El backend incluye la configuración final
para que otra entrega no vuelva a añadir el mismo archivo con contenido distinto.

## Integración y validación

Actualización de revisión: #495 ya está integrado. La antigua separación dejaba
historia compartida con retiradas de archivos; se reprodujeron los conflictos y
se reconciliaron #483, #488, #490 y #493 con develop `9183393`, conservando completo
su backend. Cada diff contiene únicamente sus archivos propios. No se reescribió
historia ni se usó force push. La corrección no depende de elegir squash.

Antes de fusionar, comprobar `git merge-tree` contra el develop vigente y que no
haya borrados de app/Rbac ni config/rbac.php. Si develop cambia, actualizar y volver
a revisar. La modalidad de merge la decide el equipo; no activar auto-merge.

#490, #483 y las pruebas de #493 necesitan el backend de #495 para ejecutarse.
#488 es documental. La prueba `CindyContextTest` requiere además el módulo de
Cindy: está fuera de Feature y se ejecuta expresamente tras integrar sus contratos.
La independencia del diff no significa que cada conjunto de pruebas tenga su
propia copia del backend. No duplicar archivos para satisfacer dependencias.

El catálogo final tiene 26 permisos y cinco roles literales; superadmin tiene 25
concesiones y admin_hospital, 8. La excepción de alergia sigue denegada.
`AuditDenials` consume el contrato público `AuditLogRepositoryInterface::logRaw()`;
el provider de Cindy debe conectarlo. Hasta entonces sigue activo `LogDenials`.
No se incluyen implementaciones ni migraciones de otros módulos.

Evidencia previa del mismo código: RBAC, 22 pruebas/218 aserciones; con Cindy local,
26/241, usando JWT real y SQLite. PostgreSQL, E2E, CI y staging siguen pendientes.
Los logs históricos de semanas 16/17 conservan su fecha y no acreditan por sí solos
la ejecución de cada nueva punta. La partición corregida se valida reuniendo los
cinco PR sin conflictos y comparando sus archivos con la entrega completa.