# Semana 16 — Integración, validación y primera entrega funcional

**Módulo:** QA, pruebas E2E, CI y guía de despliegue final · **Adaptación:** gestión de ejecuciones automatizadas y
calidad de versiones.

**Tarea:** integrar el pipeline y el módulo de calidad en la rama común, validarlos en un ambiente on-premise,
corregir lo encontrado y dejar la primera entrega funcional con evidencia.

## Qué quedó integrado en `develop`

Los 27 PR del módulo (#812–#838) están fusionados. En `develop` quedan:

| Pieza | Estado | Evidencia |
|---|---|---|
| Micro-HIS `micro-his/qa-calidad-versiones` (planificación, ejecución, evidencia, gate) | Integrado; su suite pasa | [01](evidencias/01-pipeline-local.txt) |
| Workflow `.github/workflows/qa-ci-e2e.yml` (5 jobs) | Integrado; **GitHub no lo ejecuta** | [04](evidencias/04-github-actions-bloqueado.txt) |
| `bin/pipeline-local.sh` (mismas etapas sin PostgreSQL) | Integrado y ejecutado | [01](evidencias/01-pipeline-local.txt) |
| E2E del módulo `tests/E2E/qa-calidad.e2e.mjs` | 9/9 en servidor aislado | [01](evidencias/01-pipeline-local.txt) |
| Quality gate `qg-0.1-propuesta` | Evaluado sobre la versión real de `develop` | [02](evidencias/02-gate-summary.md) |

## Ambiente on-premise usado

GitHub Actions no inicia ningún job desde el 24-oct-2026: la anotación de cada job dice *«recent account payments
have failed or your spending limit needs to be increased»* ([04](evidencias/04-github-actions-bloqueado.txt)). No es
un fallo del workflow ni del código. Mientras tanto, el pipeline se ejecutó **on-premise** con `pipeline-local.sh`
en el equipo de desarrollo: Windows 11, PHP 8.2.12, Node 22.13.1, copia limpia de `origin/develop` (`6e841849`) con
los cambios de la Semana 16 de los módulos de Cindy, sin PostgreSQL y con el E2E en el puerto 8094.

## Resultado del pipeline (2026-10-29)

| Etapa | Resultado |
|---|---|
| Sintaxis PHP | OK |
| Pint en archivos cambiados (informativo) | Marca el estilo previo de 4 archivos de micro-HIS; no bloquea |
| Build del frontend | OK |
| PHPUnit Unit / Integration | 72/72 y 4/4 |
| Micro-HIS de todos los módulos | **25 de 25 OK** |
| Frontend `node --test` | 47/47 |
| Contrato de rutas (tenant + JWT + RBAC) | 51/51 |
| E2E del módulo QA | 9/9 |
| PHPUnit Feature | 216 de 263; 47 fallos |
| **Quality gate** | **RECHAZADO** por `phpunit-feature` |

## Validación del gate: ¿rechazó por la razón correcta?

Sí. Se clasificaron los 47 fallos de Feature ([03](evidencias/03-clasificacion-fallos-feature.txt)):

| Causa | Fallos | Módulos |
|---|---|---|
| Necesitan PostgreSQL (no hay en el equipo) | 46 | Recepción (33), Salas y camas (10), Reportes (3) |
| Fallo real de contrato | 1 | `PublishedContractsTest`: un permiso de órdenes de laboratorio (acción «actualizar») sin catalogar, citado en `docs/ordenes-laboratorio` (el nombre exacto está en [03](evidencias/03-clasificacion-fallos-feature.txt)) |

En el job de GitHub, `Feature` corre con los servicios PostgreSQL levantados, así que se espera que los 46 primeros
pasen allí. El fallo de contrato sí bloquearía la versión, y es correcto que lo haga.

## Errores corregidos y estado de los bloqueos

| Id | Antes | Ahora | Verificación |
|---|---|---|---|
| D-01 | 4 micro-HIS sin `tests/run.php`; el gate se rechazaba por `SKIPPED_IN_REQUIRED` | **Cerrado.** Los 25 módulos tienen suite y pasan | Etapa micro-HIS en [01](evidencias/01-pipeline-local.txt) |
| E-QA-16-01 | `pendientes-y-bloqueos.md` y el README del módulo decían «sin commits, push ni PR» y D-01 abierto | Actualizados con el estado real | Esta entrega |
| E-QA-16-02 | Faltaba distinguir en el análisis del gate los fallos de entorno de los de código | Clasificación reproducible con su script en [03](evidencias/03-clasificacion-fallos-feature.txt) | 46 de entorno, 1 de contrato |

## Pendiente

- **B-03:** primera ejecución real del workflow en GitHub. Depende de que la organización regularice la facturación.
- Suite `Database` y E2E de pila completa: solo corren en el job `e2e` con PostgreSQL efímero.
- Avisar al responsable de Órdenes de laboratorio del permiso no catalogado (no se tocó su módulo).
