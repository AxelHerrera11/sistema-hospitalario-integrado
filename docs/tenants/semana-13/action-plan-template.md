# Semana 13 — Plantilla de Plan de Acción (Action Plan Template)
## Instrucciones
- Completar una fila por cada hallazgo identificado en la Revisión Técnica Formal (FTR)
- Asignar severidad: 🔴 Crítica | 🟠 Alta | 🟡 Media | 🟢 Baja | ℹ️ Informativa
- Prioridad: 🔴 Inmediata | 🟠 Sprint Actual | 🟡 Próximo Sprint | 🟢 Backlog
- Estado: 🟢 Abierto | 🟡 En Progreso | 🟠 En Revisión | 🔴 Cerrado | ⚫ Rechazado

## Plan de Acción Consolidado
| ID Hallazgo  | Título                                                 | Severidad  | Categoría     | Componente Afectado | Descripción Breve                                                  | Acción Correctiva                                                       | Prioridad         | Responsable        | Fecha Límite   | Estado     | Verificación                                                                          |
|--------------|--------------------------------------------------------|------------|---------------|---------------------|--------------------------------------------------------------------|-------------------------------------------------------------------------|-------------------|--------------------|----------------|------------|---------------------------------------------------------------------------------------|
| FTR-2026-001 | Contraste countdown MFA 2.8:1                          | 🔴 Crítica | Accesibilidad | MFA Challenge       | Countdown timer color #9E9E9E (2.8:1) no cumple WCAG 1.4.3 (4.5:1) | Cambiar color a #666666 (4.5:1) o #333333 (7:1)                         | 🔴 Inmediata      | Frontend           | Sprint 1 Día 1 | 🟢 Abierto | axe-core pass 1.4.3                                                                   |
| FTR-2026-002 | Force-Revoke sin Deshacer ni doble confirmación mobile | 🔴 Crítica | UX/Seguridad  | Admin Force-Revoke  | Click accidental irrecuperable, sin mecanismo Deshacer             | Toast "Deshacer 5s" + modal 2-paso confirmación mobile                  | 🔴 Inmediata      | Frontend + Backend | Sprint 1 Día 3 | 🟢 Abierto | Cypress test: click Revocar → toast "Deshacer" → click Deshace → sesiones restauradas |
| FTR-2026-003 | Tabla auditoría sin scope=col en headers dinámicos     | 🟠 Alta    | Accesibilidad | Auditoría           | <th> sin scope="col" en columnas dinámicas                         | Añadir scope="col" a todos <th> + test axe                              | 🟠 Sprint 2 Día 1 | Frontend           | Sprint 2 Día 1 | 🟢 Abierto | axe-core pass 1.3.1                                                                   |
| FTR-2026-004 | Panel detalle auditoría no recibe foco al expandir     | 🟠 Alta    | Accesibilidad | Auditoría           | Tab no llega a panel detalle expandido                             | focus() en panel detalle + tabindex="-1"                                | 🟠 Sprint 2 Día 3 | Frontend           | Sprint 2 Día 3 | 🟢 Abierto | Cypress: click fila → focus en panel → Tab navega                                     |
| FTR-2026-005 | Selector Roles multiselect sin label accesible         | 🟠 Alta    | Accesibilidad | Admin Create        | <select multiple> sin <label> asociado                             | <label for="roles"> + aria-describedby="roles-hint"                     | 🟠 Sprint 2 Día 1 | Frontend           | Sprint 2 Día 1 | 🟢 Abierto | axe-core pass 3.3.2                                                                   |
| FTR-2026-006 | Timeout TOTP 30s → 60s + warning 10s                   | 🟠 Alta    | UX/Seguridad  | MFA TOTP            | 32% usuarios fallan por tiempo (30s)                               | TOTP_WINDOW=2 (60s) + warning toast 10s + countdown color change        | 🟠 Sprint 2 Día 2 | Backend + Frontend | Sprint 2 Día 2 | 🟢 Abierto | Cypress: countdown 60s → warning 10s                                                  |
| FTR-2026-007 | QR MFA muy pequeño en mobile                           | 🟡 Media   | UX/Mobile     | MFA TOTP            | QR code requiere zoom en mobile                                    | CSS: @media (max-width: 480px) .qr-code { min-width: 200px; }           | 🟡 Sprint 3 Día 1 | Frontend           | Sprint 3 Día 1 | 🟢 Abierto | Cypress mobile viewport: QR width ≥ 200px                                             |
| FTR-2026-008 | Force-Revoke mobile: botones pegados sin spacing       | 🟡 Media   | UX/Mobile     | Admin Force-Revoke  | Touch targets 40px, gap 0px                                        | CSS: gap: 12px; min-height: 48px; min-width: 48px                       | 🟡 Sprint 3 Día 2 | Frontend           | Sprint 3 Día 2 | 🟢 Abierto | Cypress mobile: touch targets ≥ 44px; gap ≥ 12px                                      |
| FTR-2026-009 | Toast errores desaparecen en 3s (usuarios no leen)     | 🟡 Media   | UX            | Global Toasts       | Duration 3s, usuarios no leen                                      | duration: 5000 + pauseOnHover: true + pauseOnFocus: true                | 🟡 Sprint 3 Día 2 | Frontend           | Sprint 3 Día 2 | 🟢 Abierto | Cypress: toast visible 5s → hover pausa → focus pausa                                 |
| FTR-2026-010 | Selector Tenant solo muestra código (HCH-001)          | 🟡 Media   | UX            | Header/Tenant       | Confusión multi-hospital                                           | API devuelve {id, name, code} → UI muestra "Hospital Central (HCH-001)" | 🟡 Sprint 3 Día 3 | Backend + Frontend | Sprint 3 Día 3 | 🟢 Abierto | Cypress: dropdown muestra nombre + código                                             |
| FTR-2026-011 | Filtros auditoría colapsados por defecto               | 🟢 Baja    | UX            | Auditoría           | Descubrimiento tardío                                              | CSS: @media (min-width: 769px) .filters { display: block; }             | 🟢 Sprint 4 Día 1 | Frontend           | Sprint 4 Día 1 | 🟢 Abierto | Cypress desktop: filtros visibles sin click                                           |
| FTR-2026-012 | Iconos MFA inconsistentes (TOTP vs WebAuthn)           | 🟢 Baja    | Consistencia  | MFA Challenge       | TOTP=📱, WebAuthn=🔑 inconsistentes                                | Icon system unificado + design tokens                                   | 🟢 Sprint 4 Día 2 | Design + Frontend  | Sprint 4 Día 2 | 🟢 Abierto | Visual review: consistencia 100%                                                      |
| FTR-2026-013 | Tabla auditoría: atajos teclado faltantes (j/k, /, c)  | 🟡 Media   | Productividad | Auditoría           | Navegación lenta para auditores                                    | Keydown listener: j/k → prev/next, / → focus filtro, c → colapsar fila  | 🟡 Sprint 3 Día 3 | Frontend           | Sprint 3 Día 3 | 🟢 Abierto | Cypress: j → next row, k → prev, / → focus search                                     |
| FTR-2026-014 | Help links MFA recovery rotos (404)                    | 🟠 Alta    | UX/Seguridad  | MFA Recovery        | Links rotos → 404, usuario bloqueado sin ayuda                     | Corregir ruta /mfa-recovery → /mfa/recovery + test automatizado         | 🟠 Sprint 1 Día 1 | Frontend           | Sprint 1 Día 1 | 🟢 Abierto | Cypress test: click "¿Problemas?" → 200 OK                                            |
| FTR-2026-015 | Selector Tenant mobile: dropdown nativo vs custom      | 🟡 Media   | UX/Mobile     | Mobile Header       | UX inconsistente                                                   | Usar <select> nativo en mobile (< 480px) + custom en desktop            | 🟡 Sprint 3 Día 3 | Frontend           | Sprint 3 Día 3 | 🟢 Abierto | Cypress mobile: <select> nativo funcional                                             |
| FTR-2026-016 | Password strength meter: anunciar cambios a SR         | 🟡 Media   | Accesibilidad | Auth Forms          | Cambios de fortaleza no anunciados a SR                            | aria-live="polite" en strength meter + aria-valuenow                    | 🟡 Sprint 4 Día 1 | Frontend           | Sprint 4 Día 1 | 🟢 Abierto | axe-core: strength meter announced                                                    |

## Resumen por Sprint
### Sprint 1 (Inmediato - Semana 1) - 11 horas
| ID                 | Hallazgo                   | Esfuerzo | Owner              |
|--------------------|----------------------------|----------|--------------------|
| FTR-2026-001       | Contraste countdown MFA    | 2h       | Frontend           |
| FTR-2026-002       | Force-Revoke sin Deshacer  | 8h       | Frontend + Backend |
| FTR-2026-014       | Help links MFA rotos (404) | 1h       | Frontend           |
| **Total Sprint 1** |                            | **11h**  |                    |

### Sprint 2 (Semana 2-3) - 24 horas
| ID                 | Hallazgo                   | Esfuerzo | Owner              |
|--------------------|----------------------------|----------|--------------------|
| FTR-2026-003       | Tabla auditoría scope=col  | 4h       | Frontend           |
| FTR-2026-004       | Panel detalle sin focus    | 6h       | Frontend           |
| FTR-2026-005       | Selector Roles sin label   | 2h       | Frontend           |
| FTR-2026-006       | Timeout TOTP 60s + warning | 4h       | Backend + Frontend |
| FTR-2026-007       | QR MFA pequeño mobile      | 2h       | Frontend           |
| **Total Sprint 2** |                            | **20h**  |                    |

### Sprint 3 (Semana 4) - 20 horas
| ID                 | Hallazgo                         | Esfuerzo | Owner              |
|--------------------|----------------------------------|----------|--------------------|
| FTR-2026-008       | Force-Revoke mobile spacing      | 3h       | Frontend           |
| FTR-2026-009       | Toast duración 5s + pauseOnHover | 4h       | Frontend           |
| FTR-2026-010       | Selector Tenant nombre+código    | 3h       | Backend + Frontend |
| FTR-2026-013       | Atajos teclado auditoría         | 6h       | Frontend           |
| FTR-2026-015       | Selector Tenant mobile nativo    | 4h       | Frontend           |
| **Total Sprint 3** |                                  | **20h**  |                    |

### Sprint 4 (Semana 5) - 6 horas
| ID                 | Hallazgo                     | Esfuerzo | Owner             |
|--------------------|------------------------------|----------|-------------------|
| FTR-2026-011       | Filtros auditoría expandidos | 2h       | Frontend          |
| FTR-2026-012       | Iconos MFA unificados        | 2h       | Design + Frontend |
| FTR-2026-016       | Password strength aria-live  | 2h       | Frontend          |
| **Total Sprint 4** |                              | **6h**   |                   |

## Total Estimado: 61 horas (4 Sprints / 5 Semanas)
| Sprint    | Esfuerzo | Hallazgos                   | Enfoque            |
|-----------|----------|-----------------------------|--------------------|
| Sprint 1  | 11h      | 3 (2 Críticas, 1 Alta)      | Hotfixes Críticos  |
| Sprint 2  | 20h      | 4 (4 Altas - Accesibilidad) | Accesibilidad Core |
| Sprint 3  | 20h      | 5 (5 Medias - UX/Mobile)    | UX/Mobile Polish   |
| Sprint 4  | 6h       | 3 (3 Bajas - Polish)        | Polish Final       |
| **TOTAL** | **57h**  | **16 hallazgos**            | **Completo**       |

## Criterios de Cierre por Sprint
### Sprint 1 - Criterios de Cierre
- [ ] FTR-2026-001: axe-core pass 1.4.3 (contraste ≥ 4.5:1)
- [ ] FTR-2026-002: Cypress test "Deshacer" pasa + sesiones restauradas
- [ ] FTR-2026-014: Cypress test "¿Problemas?" → 200 OK
- [ ] Suite completa Feature tests pasa (18/18)
- [ ] Zero hallazgos Críticos abiertos

### Sprint 2 - Criterios de Cierre
- [ ] FTR-2026-003: axe-core pass 1.3.1 (scope=col)
- [ ] FTR-2026-004: Cypress test focus panel detalle
- [ ] FTR-2026-005: axe-core pass 3.3.2 (label + describedby)
- [ ] FTR-2026-006: TOTP 60s + warning 10s + countdown color
- [ ] FTR-2026-007: Cypress mobile QR width ≥ 200px
- [ ] Zero hallazgos Alta abiertos

### Sprint 3 - Criterios de Cierre
- [ ] FTR-2026-008: Cypress mobile touch targets ≥ 48px, gap ≥ 12px
- [ ] FTR-2026-009: Toast visible 5s + hover/focus pausa
- [ ] FTR-2026-010: Dropdown muestra "Hospital Central (HCH-001)"
- [ ] FTR-2026-013: Cypress j/k/ / /c shortcuts funcionan
- [ ] FTR-2026-015: Cypress mobile <select> nativo funcional
- [ ] Zero hallazgos Media abiertos

### Sprint 4 - Criterios de Cierre
- [ ] FTR-2026-011: Filtros visibles sin click en desktop
- [ ] FTR-2026-012: Visual review 100% consistencia iconos
- [ ] FTR-2026-016: axe-core strength meter announced
- [ ] 100% hallazgos cerrados
- [ ] Suite completa pasa (Unit + Feature + A11y)
- [ ] Demo a Stakeholders aprobada

## Seguimiento Semanal (Template)
### Semana 1 (Sprint 1)
| Día       | Hallazgos Abordados | Avance | Bloqueos | Próximos Pasos        |
|-----------|---------------------|--------|----------|-----------------------|
| Lunes     |                     |        |          |                       |
| Martes    |                     |        |          |                       |
| Miércoles |                     |        |          |                       |
| Jueves    |                     |        |          |                       |
| Viernes   |                     |        |          | **Revisión Sprint 1** |

### Semana 2-3 (Sprint 2)
| Día       | Hallazgos Abordados | Avance | Bloqueos | Próximos Pasos        |
|-----------|---------------------|--------|----------|-----------------------|
| Lunes     |                     |        |          |                       |
| Martes    |                     |        |          |                       |
| Miércoles |                     |        |          |                       |
| Jueves    |                     |        |          |                       |
| Viernes   |                     |        |          | **Revisión Sprint 2** |

### Semana 3 (Sprint 3)
| Día       | Hallazgos Abordados | Avance | Bloqueos | Próximos Pasos        |
|-----------|---------------------|--------|----------|-----------------------|
| Lunes     |                     |        |          |                       |
| Martes    |                     |        |          |                       |
| Miércoles |                     |        |          |                       |
| Jueves    |                     |        |          |                       |
| Viernes   |                     |        |          | **Revisión Sprint 3** |

### Semana 4 (Sprint 4)
| Día       | Hallazgos Abordados | Avance | Bloqueos | Próximos Pasos                         |
|-----------|---------------------|--------|----------|----------------------------------------|
| Lunes     |                     |        |          |                                        |
| Martes    |                     |        |          |                                        |
| Miércoles |                     |        |          |                                        |
| Jueves    |                     |        |          |                                        |
| Viernes   |                     |        |          | **Revisión Final + Demo Stakeholders** |

## Métricas de Seguimiento (Dashboard)
| Métrica                     | Sprint 1 | Sprint 2 | Sprint 3 | Sprint 4 | Objetivo |
|-----------------------------|----------|----------|----------|----------|----------|
| Hallazgos Críticos Abiertos | 2 → 0    | 0        | 0        | 0        | 0        |
| Hallazgos Altos Abiertos    | 6        | 4 → 0    | 0        | 0        | 0        |
| Hallazgos Medios Abiertos   | 5        | 5        | 3 → 0    | 0        | 0        |
| Hallazgos Bajos Abiertos    | 3        | 3        | 3 → 0    | 0        | 0        |
| Tests Unitarios Pasando     | 24/24    | 24/24    | 24/24    | 24/24    | 24/24    |
| Tests Feature Pasando       | 18/18    | 18/18    | 18/18    | 18/18    | 18/18    |
| Axe-core Violations AA      | 4        | 2 → 0    | 0        | 0        | 0        |
| Cobertura Domain Services   | 90%      | 92%      | 95%      | ≥95%     | ≥95%     |
| Cobertura Use Cases         | 85%      | 87%      | 90%      | ≥90%     | ≥90%     |

## Escalamiento y Bloqueos
| Bloqueo | Descripción | Impacto | Responsable | Fecha Escalamiento | Resolución |
|---------|-------------|---------|-------------|--------------------|------------|
|         |             |         |             |                    |            |
|         |             |         |             |                    |            |

## Comunicación y Reportes
| Reporte              | Frecuencia | Audiencia             | Formato           | Responsable  |
|----------------------|------------|-----------------------|-------------------|--------------|
| Daily Standup        | Diario     | Equipo Dev            | 15 min sync       | Scrum Master |
| Sprint Review        | Semanal    | Stakeholders + Equipo | 30 min demo       | Tech Lead    |
| Sprint Retrospective | Sprint     | Equipo Dev            | 45 min retro      | Scrum Master |
| Status Report        | Semanal    | Stakeholders + PM     | Email + Dashboard | PM           |
| Blocker Alert        | Inmediato  | Tech Lead + PM        | Slack + Email     | Dev Lead     |

## Criterios de Finalización del Plan de Acción
- [ ] **100% Hallazgos Críticos cerrados** (0 abiertos)
- [ ] **100% Hallazgos Altos cerrados** (0 abiertos)
- [ ] **100% Hallazgos Medios cerrados** (0 abiertos)
- [ ] **100% Hallazgos Bajos cerrados** (0 abiertos)
- [ ] **Axe-core: 0 violaciones WCAG 2.1 AA** en CI
- [ ] **Tests Unitarios: 100% pass** (24/24)
- [ ] **Tests Feature: 100% pass** (18/18 escenarios)
- [ ] **Tests E2E: 100% pass** (Happy Path + Critical Error Path)
- [ ] **Cobertura Domain Services ≥ 95%**
- [ ] **Cobertura Use Cases ≥ 90%**
- [ ] **Demo a Stakeholders aprobada** ✅
- [ ] **Documentación actualizada** ✅
- [ ] **Lecciones aprendidas documentadas** ✅
