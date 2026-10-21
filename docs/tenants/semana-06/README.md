# Semana 06 — Primera Evaluación Parcial
## Entregables Generados (`docs/tenants/semana-06/`)
| Archivo                              | Tipo     | Descripción                                                                                                                                                                                                      |
|--------------------------------------|----------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `evaluacion-parcial-presentacion.md` | Markdown | **Presentación 8 diapositivas**: Contexto/Actores, RF/RNF, SRP Antes/Después, Arquitectura Capas+Repository, API+Micro eval, Matriz Trazable, Cambio Práctico Defendido (RS256→ES256), Matriz Decisión→Evidencia |
| `matriz-trazabilidad-rf-rnf.md`      | Markdown | **Trazabilidad completa**: 15 RF + 15 RNF → Código (clases/archivos) → Tests (unit/feature) + Cobertura + Gaps + Plan acción                                                                                     |
| `matriz-decision-evidencia.md`       | Markdown | **14 decisiones arquitectónicas** con justificación, 3 artefactos evidencia cada una (doc + código + tests), comandos verificación                                                                               |
| `cambio-practico-defendido.md`       | Markdown | **Defensa cambio RS256→ES256**: Análisis impacto por capa (4 archivos vs 8+ monolito), plan implementación 6 pasos, zero-downtime, riesgos, rollback, métricas                                                   |
| `README.md`                          | Markdown | Este índice                                                                                                                                                                                                      |
## Resumen de la Evaluación Parcial
### ✅ Criterios Cumplidos
| Criterio Requerido                  | Estado | Evidencia                                                                             |
|-------------------------------------|--------|---------------------------------------------------------------------------------------|
| **Actores y UML conectados**        | ✅      | Diapositiva 2 + Semana 01 UML (Casos de Uso, Actividad, Secuencia)                    |
| **RF/RNF + Criterios Aceptación**   | ✅      | Diapositiva 3 + `matriz-trazabilidad-rf-rnf.md` (15 RF, 15 RNF, 4 Gherkin)            |
| **Principio SOLID (SRP) aplicado**  | ✅      | Diapositiva 4 + Semana 02-03 (12+ clases, métricas -57% líneas, -60% ciclomática)     |
| **Arquitectura Capas + Repository** | ✅      | Diapositiva 5 + Semana 03-04 (5 interfaces, 3 implementaciones, controlador delgado)  |
| **Contrato API + Evaluación Micro** | ✅      | Diapositiva 6 + Semana 05 (OpenAPI 3.1 25 endpoints, Decision Matrix 4 candidatos)    |
| **Matriz Trazable Req→Code→Tests**  | ✅      | Diapositiva 7 + `matriz-trazabilidad-rf-rnf.md` (15 RF + 15 RNF mapeados)             |
| **Cambio Práctico Defendido**       | ✅      | Diapositiva 8 + `cambio-practico-defendido.md` (RS256→ES256, 4 archivos vs 8+)        |
| **Matriz Decisión→Evidencia**       | ✅      | Diapositiva 8 + `matriz-decision-evidencia.md` (14 decisiones, 3 artefactos cada una) |

## Métricas Clave de la Arquitectura Actual
| Métrica                                  | Valor                             | Objetivo | Estado |
|------------------------------------------|-----------------------------------|----------|--------|
| **Clases Domain Services**               | 6                                 | ≥ 5      | ✅      |
| **Repository Interfaces**                | 5                                 | 5        | ✅      |
| **Repository Implementaciones**          | 13 (3 PDO + 5 Redis + 5 InMemory) | ≥ 3      | ✅      |
| **Domain Events**                        | 14                                | ≥ 10     | ✅      |
| **Value Objects**                        | 8                                 | ≥ 6      | ✅      |
| **Use Cases**                            | 4                                 | 4        | ✅      |
| **Cobertura Domain Services**            | ~90%                              | ≥ 90%    | ✅      |
| **Cobertura Use Cases**                  | ~85%                              | ≥ 80%    | ✅      |
| **Tests Unitarios**                      | 24 casos                          | ≥ 20     | ✅      |
| **Tests Feature**                        | 18 escenarios                     | ≥ 15     | ✅      |
| **Tiempo Suite Unit Tests**              | < 30s                             | < 30s    | ✅      |
| **Decisiones Arquitectónicas Trazables** | 14                                | ≥ 10     | ✅      |

## Gaps Identificados (Plan Semana 07)
| Gap                                      | Prioridad   | Acción                                        |
|------------------------------------------|-------------|-----------------------------------------------|
| `DeviceTrustService` sin tests unitarios | 🟡 Media    | Crear `DeviceTrustServiceTest.php`            |
| Lockout progresivo tests específicos     | 🟡 Media    | Añadir casos en `LoginMfaFeatureTest`         |
| Single Session Policy tests              | 🟡 Media    | Test dedicado                                 |
| RateLimitMiddleware tests                | 🟡 Media    | Crear `RateLimitMiddlewareTest.php`           |
| PDO Repositories con Testcontainers      | 🔴 **Alta** | Setup Testcontainers PostgreSQL + Redis       |
| axe-core WCAG 2.1 AA en CI               | 🟢 Baja     | Configurar en pipeline Semana 08              |
| k6 Load Tests ejecución real             | 🔴 **Alta** | Ejecutar en staging Semana 06-07              |
| Chaos Engineering (LitmusChaos)          | 🟡 Media    | Experimentos pod kill, Redis latency, DB pool |

## Verificación Rápida de Entregables
```bash
# Verificar estructura
ls docs/tenants/semana-06/

# Verificar presentación (8 diapositivas)
grep -c "^### Diapositiva" docs/tenants/semana-06/evaluacion-parcial-presentacion.md

# Verificar trazabilidad RF/RNF
grep -c "^| RF-" docs/tenants/semana-06/matriz-trazabilidad-rf-rnf.md
grep -c "^| RNF-" docs/tenants/semana-06/matriz-trazabilidad-rf-rnf.md

# Verificar decisiones (14)
grep -c "^| [0-9]" docs/tenants/semana-06/matriz-decision-evidencia.md

# Verificar cambio práctico
grep -c "Paso [0-9]" docs/tenants/semana-06/cambio-practico-defendido.md
```
## Referencias Cruzadas (Artefactos Previos)
| Semana | Artefactos Clave                                                    | Ubicación                                                     |
|--------|---------------------------------------------------------------------|---------------------------------------------------------------|
| **01** | UML: Casos de Uso, Actividad, Secuencia                             | `docs/cindy/week-01/gestion-usuarios-tenants/`                |
| **02** | RF/RNF, Criterios Aceptación, SRP Analysis                          | `docs/tenants/semana-02/`                                     |
| **03** | C4 Diagrams, Secuencias, Código Base PHP 8.2+                       | `docs/tenants/semana-03/` + `app/Modules/Tenants/`            |
| **04** | Arquitectura Capas, Repository Pattern, MVC, Repositorio Compartido | `docs/tenants/semana-04/` + `app/Modules/Tenants/` + `tests/` |
| **05** | OpenAPI 3.1, Evaluación Microservicio, Patrones Comunicación        | `docs/tenants/semana-05/`                                     |
| **06** | **Evaluación Parcial Completa**                                     | `docs/tenants/semana-06/`                                     |
