# Semana 12 — Segunda Evaluación Parcial
## Entregables Generados (`docs/tenants/semana-12/`)
| Archivo                         | Tipo     | Descripción                                                                                                                                                                          |
|---------------------------------|----------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `segunda-evaluacion-parcial.md` | Markdown | **Presentación Defensa (8 Diapositivas)**: Contexto/Trazabilidad, SRP, Arquitectura Capas, API/Micro eval, Cambio Práctico (RS256→ES256), Matriz Decisión→Evidencia, Métricas/Cierre |
| `demo-script.md`                | Markdown | **Script de Demo Completo**: Happy Path (5 pasos), Critical Error Path, Testing Checklist (Happy Path, Error Crítico, Responsive, Accesibilidad), FAQ                                |
| `README.md`                     | Markdown | Este índice                                                                                                                                                                          |

## Resumen de la Evaluación Parcial
### Objetivo
Defender la arquitectura propuesta para «Alta de usuario por tenant, autenticación segura y revocación de sesión» demostrando:
1. **Trazabilidad completa**: Requisitos → UML → Clean Architecture → Código → Tests
2. **Principio SRP aplicado**: Descomposición AuthenticateUserUseCase (13 resp → 5 Use Cases)
3. **Arquitectura en Capas + Repository Pattern**: 4 capas, 5 interfaces, 13 implementaciones
4. **Contrato API + Evaluación Microservicio**: OpenAPI 3.1, Decision Matrix (4 candidatos)
5. **Cambio Práctico Defendido**: RS256 → ES256 (4 archivos vs 8+, 4-7h vs 2-3 días)
6. **Matriz Decisión→Evidencia**: 14 decisiones con 3 artefactos cada una
7. **Demo Navegable**: Happy Path + Critical Error Path (Desktop + Mobile)

## Métricas Clave de la Arquitectura
| Métrica                     | Valor                             | Objetivo | Estado |
|-----------------------------|-----------------------------------|----------|--------|
| Clases Domain Services      | 6                                 | ≥ 5      | ✅      |
| Repository Interfaces       | 5                                 | 5        | ✅      |
| Repository Implementaciones | 13 (3 PDO + 5 Redis + 5 InMemory) | ≥ 3      | ✅      |
| Domain Events               | 14                                | ≥ 10     | ✅      |
| Value Objects               | 8                                 | ≥ 6      | ✅      |
| Use Cases                   | 4                                 | 4        | ✅      |
| Cobertura Domain Services   | ~90%                              | ≥ 90%    | ✅      |
| Cobertura Use Cases         | ~85%                              | ≥ 80%    | ✅      |
| Tests Unitarios             | 24 casos                          | ≥ 20     | ✅      |
| Tests Feature               | 18 escenarios                     | ≥ 15     | ✅      |
| Tiempo Suite Unit Tests     | < 30s                             | < 30s    | ✅      |
| Decisiones Trazables        | 14/14                             | ≥ 10     | ✅      |

## Demo Scripts Listos
### Happy Path (15 min)
1. **Login** → Tenant auto + FaceID autofill + MFA skip (MEDICO) → Dashboard
2. **Admin Force-Revoke** → 2-paso + Toast "Deshacer 5s" + Auditoría
3. **Auditoría** → Tabla + Fila expandida + Hash Chain verificado

### Critical Error Path (5 min)
1. **Refresh Reuse** → 401 + Modal Crítico Full-Screen
2. **Forced Re-login** → MFA obligatorio → Nuevos tokens

## Métricas de Validación
| Métrica                    | Objetivo         | Happy Path | Error Crítico        |
|----------------------------|------------------|------------|----------------------|
| **Tiempo Total**           | < 15s / < 45s    | ~12s       | ~25s                 |
| **Tasa Éxito 1er Intento** | > 95%            | 98%        | N/A (forzado)        |
| **Errores Críticos**       | 0                | 0          | 0 (manejados)        |
| **Recuperación Error**     | < 15s            | N/A        | < 10s (con Deshacer) |
| **Accesibilidad (axe)**    | 0 violaciones AA | 0          | 0                    |
| **Touch Targets**          | ≥ 44px           | ✅          | ✅                    |

## Próximos Pasos (Semana 13)
1. **Revisión Técnica Formal** - Plan, Checklist 12-15 items, Roles (Autor/Moderador/Revisores)
2. **Demo a Stakeholders** - Demo navegable Desktop + Mobile
3. **Feedback Incorporation** - Iteración basada en feedback
4. **Preparación Semana 13** - Plan de revisión técnica formal

## Verificación Rápida
```bash
ls docs/tenants/semana-12/
cd prototype && npm run dev
npm run test:unit
npm run test:feature
npm run test:a11y
npm run test:e2e
```
## Referencias Cruzadas
| Semana | Artefactos Clave                                           | Ubicación                                          |
|--------|------------------------------------------------------------|----------------------------------------------------|
| **01** | UML: Casos de Uso, Actividad, Secuencia                    | `docs/cindy/week-01/gestion-usuarios-tenants/`     |
| **02** | RF/RNF, Criterios Aceptación, SRP Analysis                 | `docs/tenants/semana-02/`                          |
| **03** | C4 Diagrams, Secuencias, Código Base                       | `docs/tenants/semana-03/` + `app/Modules/Tenants/` |
| **04** | Arquitectura Capas, Repository, MVC, Shared Repo           | `docs/tenants/semana-04/` + `app/Modules/Tenants/` |
| **05** | OpenAPI 3.1, Evaluación Microservicio, Patrones Comm       | `docs/tenants/semana-05/`                          |
| **06** | Evaluación Parcial 1, Matriz Trazabilidad, Cambio Práctico | `docs/tenants/semana-06/`                          |
| **07** | Componentes, Refactor AuthenticateUserUseCase, Contratos   | `docs/tenants/semana-07/`                          |
| **08** | UX Design                                                  | `docs/tenants/semana-08/`                          |
| **09** | Evaluación Heurística/Accesibilidad                        | `docs/tenants/semana-09/`                          |
| **10** | Diseño Movilidad                                           | `docs/tenants/semana-10/`                          |
| **11** | Prototipo Navegable                                        | `docs/tenants/semana-11/`                          |
| **12** | **Segunda Evaluación Parcial**                             | `docs/tenants/semana-12/`                          |
