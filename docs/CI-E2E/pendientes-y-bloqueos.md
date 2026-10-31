# Pendientes, bloqueos y comentarios para el equipo

Estado al 21-oct-2026. Nada de esto se resolvió modificando archivos de otros módulos.

## D-01

> **Cerrado el 29-oct-2026.** Los 25 micro-HIS de `develop` tienen `tests/run.php` y pasan; ver [semana-16](semana-16/README.md). Se conserva el texto original como historial.

**Módulos micro-HIS sin `tests/run.php`.** En `origin/develop` 5e6f0044, `bin/micro-his-suites.php` marca 4 de 14 módulos como *skipped*; con la política propuesta (omitidas no permitidas en obligatorias) el gate local queda **RECHAZADO** ([evidencia](semana-15/evidencias/06-gate-summary.md)).

| Módulo | Responsable (autor en git) | Qué tiene |
|---|---|---|
| `micro-his/signos-vitales` | Josué (`Jhos-hgnu`) | `bootstrap.php` y `src/`, sin `tests/` |
| `micro-his/ingreso-resultados-lab` | Gerson (`gioore`) | solo `README.md` |
| `micro-his/validacion-resultados-bioquimico` | Gerson (`gioore`) | solo `README.md` |
| `micro-his/nativephp-extension-prototype` | Gerson (`gioore`) | solo `README.md` |

> Hola Josué. El pipeline de QA corre `php micro-his/<módulo>/tests/run.php` de cada micro-HIS. `micro-his/signos-vitales` tiene `bootstrap.php` y `src/` pero no `tests/run.php`, así que aparece como suite omitida y el quality gate propuesto rechaza la versión. ¿Podés agregar un `tests/run.php` que termine con exit 0 si todo pasa y ≠ 0 si algo falla (como `micro-his/notas-soap/tests/run.php`)? Si no llega antes del corte, avisame y propongo al grupo dejar esa suite como aviso temporal. — Cindy

> Hola Gerson. El pipeline de QA corre `php micro-his/<módulo>/tests/run.php` de cada micro-HIS. `micro-his/ingreso-resultados-lab`, `micro-his/validacion-resultados-bioquimico` y `micro-his/nativephp-extension-prototype` hoy solo tienen `README.md`, así que aparecen como suites omitidas y el quality gate propuesto rechaza la versión. Si el código de esos módulos vive en otra carpeta, decime cuál y ajusto el runner; si no, ¿podés agregar un `tests/run.php` (exit 0 si todo pasa, ≠ 0 si falla)? — Cindy

## Bloqueos y decisiones pendientes

| # | Tema | Con quién | Qué falta | Mientras tanto |
|---|---|---|---|---|
| B-01 | Permisos `qa.ejecuciones.ver`, `qa.ejecuciones.registrar`, `qa.gates.evaluar` y la cuenta de servicio de CI | María (RBAC) | Aprobación o nombres alternativos | `StaticAccessGate`; API solo en el micro-HIS. Comentario en [semana-05/contrato-api.md](semana-05/contrato-api.md#permisos-propuesta-pendiente) |
| B-02 | Tablas `qa_*` (opcionales) | Javier (BD) | Decidir si el historial vive en CENTRAL | SQLite por ejecución como artefacto. Comentario en [semana-04/propuesta-bd-javier.md](semana-04/propuesta-bd-javier.md) |
| B-03 | Primera ejecución del workflow en GitHub (job e2e con PostgreSQL) | Organización (facturación) | El PR ya está integrado; GitHub no inicia jobs desde el 24-oct por facturación | Pipeline on-premise con `pipeline-local.sh` (semana 16) |
| B-04 | Umbrales `qg-0.1-propuesta` y qué suites son obligatorias | Grupo | Aprobación | Pint, Database y E2E de pila completa informativos |
| B-05 | Reparto manual / automatizado | Madelin | Acordar la matriz | Propuesta en [semana-05/contratos-integracion.md](semana-05/contratos-integracion.md) (C-06) |
| B-06 | Formato común de errores | Lis | Acuerdo de grupo | E2E acepta `{error}` y `{code}` tal como están |
| B-07 | Seeder común | Cindy y Madelin | Seeder único con hospital, usuarios por rol y pacientes | `tests/E2E/fixtures/seed-e2e.php` con guardas |
| B-08 | Análisis estático | Grupo | Aceptar una dependencia de desarrollo nueva | Etapa como aviso |

## Comentarios cortos listos para el grupo

- **Madelin:** «Propongo que yo automatice auth/tenant/RBAC, contrato de rutas y las suites de todos los módulos, y que la regresión manual cubra los flujos clínicos con UI. Cada ejecución deja `gate-decision.json` como artefacto por si querés adjuntarlo a tu campaña. ¿Lo cerramos hoy?»
- **Lis:** «En el E2E vi que Tenants responde `{error, message}` y RBAC `{code, trace_id}`. Lo dejo anotado para el acuerdo de formato común; no cambié ningún controlador.»
- **Yamilet:** «El workflow `QA CI y E2E` corre en cada PR a develop. Mientras no se aprueben los umbrales, Pint, la suite Database y el E2E de pila completa son informativos. Si el gate bloquea por deuda de otro módulo, el resumen del job dice cuál y por qué.»
