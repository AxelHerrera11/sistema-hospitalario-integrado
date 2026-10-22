# Semana 09 — Evaluación Heurística, Usabilidad y Accesibilidad
### Documentación Principal
| Archivo                                  | Tipo     | Descripción                                                                                                                                                      |
|------------------------------------------|----------|------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `evaluacion-heuristica-accesibilidad.md` | Markdown | **Evaluación Completa**: Heurísticas Nielsen (10 principios), WCAG 2.1 AA (axe-core + manual), Testing 25 usuarios, 15 hallazgos consolidados, Backlog 4 sprints |
| `hallazgos-consolidados.csv`             | CSV      | **15 Hallazgos** con ID, severidad, componente, corrección, criterio verificación, esfuerzo, owner, sprint                                                       |
| `backlog-priorizado.csv`                 | CSV      | **Backlog 4 sprints** (11h + 24h + 20h + 6h = 61h total) con criterios verificación                                                                              |
| `checklist-heuristico-wcag.md`           | Markdown | **Checklist Completo**: 10 Heurísticas Nielsen + WCAG 2.1 AA (36 criterios) por 6 pantallas                                                                      |
| `README.md`                              | Markdown | Este índice                                                                                                                                                      |

### Evidencia (`evidencia-hallazgos/`)
| Archivo                              | Hallazgo | Contenido                                                                                                                 |
|--------------------------------------|----------|---------------------------------------------------------------------------------------------------------------------------|
| `H-001-contraste-countdown-mfa.md`   | H-001    | Evidencia axe-core (2.8:1), testing usuarios 32%, corrección CSS                                                          |
| `H-002-force-revoke-sin-deshacer.md` | H-002    | Evidencia testing 24% usuarios, código problemático, corrección completa con toast "Deshacer" + doble confirmación mobile |

## Resumen Ejecutivo
### Hallazgos Consolidados (15 Total)
| Severidad   | Cantidad | Hallazgos                                                |
|-------------|----------|----------------------------------------------------------|
| **Crítica** | 2        | H-001 (Contraste MFA), H-002 (Force-Revoke sin Deshacer) |
| **Alta**    | 6        | H-003, H-004, H-005, H-006, H-009, H-014                 |
| **Media**   | 5        | H-007, H-008, H-010, H-013, H-015                        |
| **Baja**    | 2        | H-011, H-012                                             |

### Puntuaciones Heurísticas Nielsen
| Principio                     | Puntuación | Estado             |
|-------------------------------|------------|--------------------|
| 1. Visibilidad estado sistema | 4.2/5      | 1 Media            |
| 2. Correspondencia mundo real | 4.5/5      |                    |
| 3. Control y libertad usuario | 3.8/5      | 1 Crítica, 1 Media |
| 4. Consistencia y estándares  | 4.7/5      | 1 Baja             |
| 5. Prevención errores         | 4.0/5      | 1 Crítica, 2 Media |
| 6. Reconocimiento vs recuerdo | 4.3/5      | 1 Media            |
| 7. Flexibilidad y eficiencia  | 3.5/5      | 1 Alta, 1 Media    |
| 8. Estética minimalista       | 4.6/5      |                    |
| 9. Ayuda errores              | 4.1/5      | 1 Alta, 1 Media    |
| 10. Ayuda y documentación     | 3.8/5      | 1 Alta, 2 Media    |
**Promedio: 4.15/5** - Bueno, con 4 hallazgos Críticos/Alta prioridad inmediata

### Cumplimiento WCAG 2.1 AA (axe-core)
| Principio      | Criterios | Pass   | Fail  | %         |
|----------------|-----------|--------|-------|-----------|
| Perceivable    | 16        | 14     | 2     | 87.5%     |
| Operable       | 10        | 8      | 2     | 80%       |
| Understandable | 7         | 5      | 2     | 71.4%     |
| Robust         | 3         | 2      | 1     | 66.7%     |
| **TOTAL**      | **36**    | **29** | **7** | **80.6%** |
**Gap: 7 violaciones AA** → Target Sprint 1-2: 100%

## Testing Usuarios Reales (25 participantes / 5 roles)
| Rol          | Participantes | Dispositivos        |
|--------------|---------------|---------------------|
| MEDICO       | 5             | 3 Desktop, 2 Mobile |
| ENFERMERO    | 5             | 2 Tablet, 3 Mobile  |
| TENANT_ADMIN | 5             | 5 Desktop           |
| AUDITOR      | 5             | 5 Desktop           |
| PACIENTE     | 5             | 2 Desktop, 3 Mobile |

### Métricas Clave Observadas
| Métrica                           | Objetivo      | Resultado     | Gap      |
|-----------------------------------|---------------|---------------|----------|
| Tasa éxito Login                  | > 98%         | 100%          |          |
| Tasa éxito MFA (1er intento)      | > 95%         | 80%           | -15%     |
| Tasa abandono Login               | < 2%          | 0%            |          |
| Tiempo Login p50                  | < 15s         | 28s           | -13s     |
| Tasa éxito Force-Revoke           | 100%          | 100%          |          |
| Errores Force-Revoke accidental   | 0%            | 8%            | -8%      |
| Tiempo configuración MFA paciente | < 3 min       | 3.5 min       | -0.5 min |
| Accesibilidad axe-core            | 0 violaciones | 4 violaciones | -4       |

### Hallazgos Cualitativos Principales (Entrevistas)
| Hallazgo                                           | Frecuencia | Severidad | Comentario Representativo                         |
|----------------------------------------------------|------------|-----------|---------------------------------------------------|
| MFA TOTP: código expira muy rápido (30s)           | 8/25 (32%) | **Alta**  | "Apenas me da tiempo a cambiar de app y escribir" |
| Force-Revoke: sin "Deshacer" tras click accidental | 6/25 (24%) | **Alta**  | "Me dio miedo clicar, no hay vuelta atrás"        |
| QR MFA: muy pequeño en mobile                      | 5/25 (20%) | Media     | "Tuve que hacer zoom para escanear"               |
| Selector Tenant: solo muestra código               | 4/25 (16%) | Media     | "Solo veo HCH-001, no sé cuál es"                 |
| Toast errores: desaparecen muy rápido              | 7/25 (28%) | Media     | "No alcancé a leer el mensaje"                    |

## Backlog Priorizado (4 Sprints = 61h)
### Sprint 1 - Inmediato (11h) - Hotfixes Críticos
| ID    | Hallazgo                      | Corrección                                      | Esfuerzo | Owner              |
|-------|-------------------------------|-------------------------------------------------|----------|--------------------|
| H-001 | Contraste countdown MFA 2.8:1 | Color #666666 (4.5:1)                           | 2h       | Frontend           |
| H-002 | Force-Revoke sin Deshacer     | Toast "Deshacer" 5s + doble confirmación mobile | 8h       | Frontend + Backend |
| H-014 | Help links MFA rotos (404)    | Corregir ruta `/mfa/recovery`                   | 1h       | Frontend           |

### Sprint 2 - Accesibilidad + UX Crítica (24h)
| ID    | Hallazgo                      | Corrección                             | Esfuerzo |
|-------|-------------------------------|----------------------------------------|----------|
| H-003 | Tabla auditoría sin scope=col | Añadir scope="col" a todos <th>        | 4h       |
| H-004 | Panel detalle sin focus       | focus() en panel + tabindex="-1"       | 6h       |
| H-005 | Selector Roles sin label      | <label for="roles"> + aria-describedby | 2h       |
| H-006 | Timeout TOTP 30s → 60s        | TOTP_WINDOW=2 + warning 10s            | 4h       |
| H-007 | QR MFA pequeño mobile         | CSS min-width: 200px mobile            | 2h       |

### Sprint 3 - UX Media Prioridad (20h)
| ID    | Hallazgo                         | Corrección                            | Esfuerzo |
|-------|----------------------------------|---------------------------------------|----------|
| H-008 | Force-Revoke mobile spacing      | gap: 12px, min-height 48px            | 3h       |
| H-009 | Toast duración 5s + pauseOnHover | duration: 5000 + pauseOnHover         | 4h       |
| H-010 | Selector Tenant nombre+código    | API + UI "Hospital Central (HCH-001)" | 3h       |
| H-013 | Atajos teclado auditoría         | j/k, /, c keystrokes                  | 6h       |
| H-015 | Selector Tenant mobile nativo    | <select> nativo < 480px               | 4h       |

### Sprint 4 - Polish (6h)
| ID    | Hallazgo                             | Corrección               | Esfuerzo |
|-------|--------------------------------------|--------------------------|----------|
| H-011 | Filtros auditoría expandidos desktop | CSS @media display:block | 2h       |
| H-012 | Iconos MFA unificados                | Design tokens unificados | 2h       |
| H-016 | Password strength aria-live          | aria-live="polite"       | 2h       |

## Criterios de Verificación (Definition of Done)
| Criterio          | Requerido                                                      |
|-------------------|----------------------------------------------------------------|
| **Código**        | PR aprobado + Code Review + Tests unitarios/integración ≥ 80%  |
| **Accesibilidad** | axe-core 0 violaciones A/AA en CI + Test manual NVDA/VoiceOver |
| **Testing**       | Cypress test pasa + Cobertura ≥ 80% en componente modificado   |
| **Documentación** | CHANGELOG actualizado + Storybook actualizado (si aplica)      |
| **QA Sign-off**   | QA valida en Staging + Usuario real valida (si Crítico/Alta)   |

## Verificación Rápida
```bash
ls docs/tenants/semana-09/
wc -l docs/tenants/semana-09/hallazgos-consolidados.csv
wc -l docs/tenants/semana-09/backlog-priorizado.csv
ls docs/tenants/semana-09/evidencia-hallazgos/
grep -c "^\|" docs/tenants/semana-09/checklist-heuristico-wcag.md
```
