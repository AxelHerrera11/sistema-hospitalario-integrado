# Semana 09 — Evaluación Heurística, Usabilidad y Accesibilidad
## 1. Metodología de Evaluación
### 1.1 Alcance
- **Flujos evaluados**: Login, MFA, Admin Alta Usuario, Force-Revoke, Auditoría, Paciente Portal
- **Roles cubiertos**: MEDICO, ENFERMERO, TENANT_ADMIN, AUDITOR, PACIENTE, PLATFORM_ADMIN
- **Dispositivos**: Desktop (1920x1080), Tablet (768x1024), Mobile (375x667)
- **Navegadores**: Chrome 119, Firefox 119, Safari 17, Edge 119

### 1.2 Herramientas y Técnicas
| Técnica                       | Herramienta               | Frecuencia     | Responsable   |
|-------------------------------|---------------------------|----------------|---------------|
| **Heurística Nielsen**        | Checklist 10 principios   | 100% pantallas | UX Expert     |
| **Accesibilidad WCAG 2.1 AA** | axe-core + jest-axe       | CI/CD + Manual | QA + Dev      |
| **Testing Usuarios Reales**   | 5 usuarios/rol (25 total) | Sesiones 45min | UX Researcher |
| **Testing Automatizado**      | Cypress + axe-core        | CI/CD pipeline | DevOps        |
| **Revisión Código**           | ESLint + a11y rules       | PR review      | Dev Team      |

### 1.3 Criterios de Severidad (Hallazgos)
| Severidad   | Definición                                                   | SLA Corrección     | Ejemplo                                   |
|-------------|--------------------------------------------------------------|--------------------|-------------------------------------------|
| **Crítica** | Bloquea tarea crítica, viola ley/seguridad, WCAG A/AA fallo  | Inmediato (Hotfix) | Focus trap ausente en modal Force-Revoke  |
| **Alta**    | Dificulta tarea importante, WCAG AA fallo, confusión usuario | 1 Sprint           | Contraste insuficiente en badges estado   |
| **Media**   | Molestia usabilidad, WCAG AAA, inconsistencia menor          | 2 Sprints          | Mensaje error genérico "Error inesperado" |
| **Baja**    | Mejora UX, preferencia, sin impacto funcional                | Backlog            | Microcopy optimizable en tooltip          |

## 2. Evaluación Heurística Nielsen (10 Principios)
### Resultados por Principio
| #      | Principio                                              | Puntuación (1-5) | Hallazgos Críticos/Alta                                                           | Estado |
|--------|--------------------------------------------------------|------------------|-----------------------------------------------------------------------------------|--------|
| **1**  | **Visibilidad del estado del sistema**                 | 4.2/5            | 1 Media (spinner MFA no anuncia progreso a SR)                                    | 🟡     |
| **2**  | **Correspondencia sistema-mundo real**                 | 4.5/5            | 0                                                                                 | ✅      |
| **3**  | **Control y libertad del usuario**                     | 3.8/5            | 1 Alta (Force-Revoke sin "Deshacer" inmediato), 1 Media (Logout sin confirmación) | 🟠     |
| **4**  | **Consistencia y estándares**                          | 4.7/5            | 1 Media (iconos MFA inconsistentes TOTP vs WebAuthn)                              | 🟡     |
| **5**  | **Prevención de errores**                              | 4.0/5            | 1 Alta (Force-Revoke sin doble confirmación en mobile), 2 Media                   | 🟠     |
| **6**  | **Reconocimiento antes que recuerdo**                  | 4.3/5            | 1 Media (selector tenant no muestra nombre completo en mobile)                    | 🟡     |
| **7**  | **Flexibilidad y eficiencia**                          | 3.5/5            | 1 Alta (atajos teclado faltantes en tabla auditoría), 1 Media                     | 🟠     |
| **8**  | **Estética y diseño minimalista**                      | 4.6/5            | 0                                                                                 | ✅      |
| **9**  | **Ayuda a reconocer/diagnosticar/recuperarse errores** | 4.1/5            | 1 Alta (error reuse detection no explica "qué hacer"), 1 Media                    | 🟠     |
| **10** | **Ayuda y documentación**                              | 3.8/5            | 1 Alta (help links rotos en MFA recovery), 2 Media                                | 🟠     |

**Promedio General: 4.15/5** - **Bueno**, con 4 hallazgos Alta prioridad que requieren acción inmediata.

## 3. Evaluación Accesibilidad WCAG 2.1 AA (Resultados axe-core)
### 3.1 Resumen Ejecución Automatizada (CI/CD)
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  AXE-CORE RESULTS - WCAG 2.1 AA Audit                                       │
│  ─────────────────────────────────────────────────────────────────────────  │
│  Fecha: 2026-10-18 | Pipeline: #1427 | Commit: a1b2c3d                      │
│  ┌─────────────────────┬──────────┬──────────┬──────────┬────────────────┐  │
│  │ Pantalla            │ Violaciones│ A/AA     │ AAA      │ Tiempo (ms)  │  │
│  ├─────────────────────┼──────────┼──────────┼──────────┼────────────────┤  │
│  │ Login Form          │ 0        │ 0        │ 2        │ 245            │  │
│  │ MFA Challenge       │ 1        │ 1        │ 3        │ 312            │  │
│  │ Admin Create User   │ 0        │ 0        │ 1        │ 189            │  │
│  │ Admin User List     │ 2        │ 2        │ 4        │ 298            │  │
│  │ Audit Logs          │ 1        │ 1        │ 2        │ 267            │  │
│  │ Patient Portal      │ 0        │ 0        │ 2        │ 198            │  │
│  ├─────────────────────┼──────────┼──────────┼──────────┼────────────────┤  │
│  │ TOTAL               │ 4        │ 4        │ 14       │ 1,509          │  │
│  └─────────────────────┴──────────┴──────────┴──────────┴────────────────┘  │
│   4 VIOLACIONES WCAG 2.1 AA - ACCIÓN REQUERIDA                              │
└─────────────────────────────────────────────────────────────────────────────┘
```
### 3.2 Detalle Violaciones WCAG 2.1 AA (4 Hallazgos)
| #           | Pantalla          | Regla WCAG                     | Violación                                                                | Severidad | Elemento Afectado                      |
|-------------|-------------------|--------------------------------|--------------------------------------------------------------------------|-----------|----------------------------------------|
| **AXE-001** | MFA Challenge     | **1.4.3 Contraste (Mínimo)**   | Countdown timer: texto gris #9E9E9E sobre blanco (#FFFFFF) = 2.8:1       | **Alta**  | `<span class="countdown">25s</span>`   |
| **AXE-002** | Admin User List   | **1.3.1 Info y Relaciones**    | Tabla auditoría: `<th>` sin `scope="col"` en columnas Dinámicas          | **Alta**  | `<th>Acciones</th>`                    |
| **AXE-003** | Audit Logs        | **2.4.7 Foco Visible**         | Fila expandida: panel detalle no recibe foco al expandir (Tab salta)     | **Alta**  | `<div class="detail-panel">`           |
| **AXE-004** | Admin Create User | **3.3.2 Labels/Instrucciones** | Selector Roles: `<select multiple>` sin `<label>` asociado correctamente | **Alta**  | `<select multiple aria-label="Roles">` |

## 4. Testing con Usuarios Reales (25 Usuarios / 5 Roles)
### 4.1 Perfil Participantes
| Rol              | Participantes | Perfil                                                   | Dispositivo         |
|------------------|---------------|----------------------------------------------------------|---------------------|
| **MEDICO**       | 5             | 2 Cardiólogos, 1 Internista, 1 Urgenciólogo, 1 Residente | 3 Desktop, 2 Mobile |
| **ENFERMERO**    | 5             | 3 UCI, 1 Urgencias, 1 Planta                             | 2 Tablet, 3 Mobile  |
| **TENANT_ADMIN** | 5             | 3 Admins Hospital, 2 Red Regional                        | 5 Desktop           |
| **AUDITOR**      | 5             | 3 Auditores Internos, 2 Compliance                       | 5 Desktop           |
| **PACIENTE**     | 5             | 3 Crónicos, 2 Agudos, edades 35-72                       | 2 Desktop, 3 Mobile |

### 4.2 Tareas Evaluadas y Métricas
| Tarea                  | Rol          | Tiempo Objetivo | Tiempo Real (p50) | Tasa Éxito | Errores Comunes                                 |
|------------------------|--------------|-----------------|-------------------|------------|-------------------------------------------------|
| **Login + MFA**        | MEDICO       | < 30s           | 28s               | 100%       | -                                               |
| **Login + MFA**        | TENANT_ADMIN | < 45s           | 52s               | 80%        | 2/5 fallaron MFA TOTP (código expirado)         |
| **Alta Usuario**       | TENANT_ADMIN | < 60s           | 68s               | 100%       | 1/5 dudó en selector Roles                      |
| **Force-Revoke**       | TENANT_ADMIN | < 30s           | 41s               | 100%       | 2/5 dudaron en "Motivo" dropdown                |
| **Consulta Auditoría** | AUDITOR      | < 60s           | 55s               | 100%       | 1/5 no encontró filtro "Severidad"              |
| **Configurar MFA**     | PACIENTE     | < 180s          | 210s              | 60%        | 2/5 no escanearon QR, 1/5 perdió recovery codes |

### 4.3 Hallazgos Cualitativos (Entrevistas Post-Tarea)
| Hallazgo                                                  | Frecuencia | Severidad | Comentario Usuario                                   |
|-----------------------------------------------------------|------------|-----------|------------------------------------------------------|
| **MFA TOTP: código expira muy rápido (30s)**              | 8/25 (32%) | **Alta**  | "Apenas me da tiempo a cambiar de app y escribir"    |
| **Force-Revoke: no hay 'Deshacer' tras click accidental** | 6/25 (24%) | **Alta**  | "Me dio miedo clicar 'Revocar', no hay vuelta atrás" |
| **QR MFA: muy pequeño en mobile**                         | 5/25 (20%) | **Media** | "Tuve que hacer zoom para escanear"                  |
| **Selector Tenant: no muestra nombre hospital**           | 4/25 (16%) | **Media** | "Solo veo código HCH-001, no sé cuál es"             |
| **Toast errores: desaparecen muy rápido**                 | 7/25 (28%) | **Media** | "No alcancé a leer el mensaje de error"              |
| **Auditoría: filtros colapsados por defecto**             | 3/25 (12%) | **Baja**  | "No vi los filtros, están escondidos"                |

## 5. Consolidación Hallazgos (Consolidado: Heurística + Accesibilidad + Users)
### 5.1 Matriz Consolidada de Hallazgos (Top 15)
| ID        | Título                                                   | Fuente                 | Severidad   | Componente         | Impacto Negocio                              |
|-----------|----------------------------------------------------------|------------------------|-------------|--------------------|----------------------------------------------|
| **H-001** | Countdown MFA contraste insuficiente (2.8:1)             | Axe-core + Users       | **Crítica** | MFA Challenge      | Usuarios no ven tiempo restante → fallos MFA |
| **H-002** | Force-Revoke sin "Deshacer" ni doble confirmación mobile | Heurística 3,5 + Users | **Crítica** | Admin Force-Revoke | Revocación accidental irrecuperable          |
| **H-003** | Tabla auditoría sin `scope="col"` en headers dinámicos   | Axe-core               | **Alta**    | Auditoría          | Lectores pantalla no navegan tabla           |
| **H-004** | Panel detalle auditoría no recibe foco al expandir       | Axe-core               | **Alta**    | Auditoría          | Navegación teclado rota                      |
| **H-005** | Selector Roles multiselect sin label accesible           | Axe-core               | **Alta**    | Admin Create       | Lectores pantalla no identifican campo       |
| **H-006** | Código MFA expira en 30s (usuarios piden 60s)            | Users (32%)            | **Alta**    | MFA TOTP           | 32% fallos MFA por tiempo                    |
| **H-007** | QR MFA muy pequeño en mobile (zoom requerido)            | Users (20%)            | **Media**   | MFA TOTP           | Frustración, tiempo extra                    |
| **H-008** | Force-Revoke mobile: botones muy juntos, sin spacing     | Heurística 5           | **Media**   | Admin Mobile       | Click accidental Revocar                     |
| **H-009** | Toast errores desaparecen en 3s (usuarios no leen)       | Users (28%)            | **Media**   | Global Toasts      | Errores no leídos → reintentos               |
| **H-010** | Selector Tenant solo muestra código (HCH-001)            | Users (16%)            | **Media**   | Header/Tenant      | Confusión multi-hospital                     |
| **H-011** | Filtros auditoría colapsados por defecto                 | Users (12%)            | **Baja**    | Auditoría          | Descubrimiento tardío                        |
| **H-012** | Iconos MFA inconsistentes (TOTP vs WebAuthn)             | Heurística 4           | **Baja**    | MFA Challenge      | Confusión visual                             |
| **H-013** | Tabla auditoría: atajos teclado faltantes (j/k, /, c)    | Heurística 7           | **Media**   | Auditoría          | Eficiencia auditor lento                     |
| **H-014** | Help links MFA recovery rotos (404)                      | Heurística 10          | **Alta**    | MFA Recovery       | Usuario bloqueado sin ayuda                  |
| **H-015** | Selector Tenant mobile: dropdown nativo vs custom        | Heurística 6           | **Media**   | Mobile Header      | UX inconsistente                             |

## 6. Backlog Priorizado de Correcciones
### 6.1 Sprint 1 (Inmediato - Hotfixes Críticos)
| ID        | Hallazgo                                                               | Corrección Propuesta                                                           | Criterio Verificación                                                                         | Esfuerzo | Owner              |
|-----------|------------------------------------------------------------------------|--------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------|----------|--------------------|
| **H-001** | Contraste countdown MFA 2.8:1                                          | Cambiar color countdown a `#666666` (4.5:1) o `#333333` (7:1)                  | axe-core pass 1.4.3; contraste ≥ 4.5:1 verificado                                             | 2h       | Frontend           |
| **H-002** | Force-Revoke: añadir "Deshacer" (5s toast) + doble confirmación mobile | Añadir toast con action "Deshacer" (5s) + modal confirmación 2 pasos en mobile | Cypress test: click Revocar → toast "Deshacer" aparece → click Deshace → sesiones restauradas | 8h       | Frontend + Backend |
| **H-014** | Help links MFA recovery rotos (404)                                    | Corregir rutas `/mfa-recovery` → `/mfa/recovery` + test automatizado           | Cypress test: click "¿Problemas?" → 200 OK + página carga                                     | 1h       | Frontend           |

### 6.2 Sprint 2 (Alta Prioridad - Accesibilidad + UX Crítica)
| ID        | Hallazgo                                                     | Corrección Propuesta                                                           | Criterio Verificación                                    | Esfuerzo | Owner              |
|-----------|--------------------------------------------------------------|--------------------------------------------------------------------------------|----------------------------------------------------------|----------|--------------------|
| **H-003** | Tabla auditoría: añadir `scope="col"` a todos `<th>`         | Añadir `scope="col"` a todos `<th>` en tabla auditoría + test axe              | axe-core pass 1.3.1; screen reader navega columnas       | 4h       | Frontend           |
| **H-004** | Panel detalle auditoría: focus management al expandir        | `focus()` en panel detalle tras expandir + `tabindex="-1"`                     | Cypress: click fila → focus en panel → Tab navega dentro | 6h       | Frontend           |
| **H-005** | Selector Roles: `<label for="roles">` + `aria-describedby`   | `<label for="roles">Roles</label>` + `aria-describedby="roles-hint"` en select | axe-core pass 3.3.2; screen reader anuncia label + hint  | 2h       | Frontend           |
| **H-006** | Aumentar timeout TOTP a 60s + countdown visual + warning 10s | `TOTP_WINDOW=2` (60s) + warning toast a 10s + countdown color change           | Cypress: countdown 60s → warning 10s → expiración        | 4h       | Backend + Frontend |
| **H-007** | QR MFA responsive: min-width 200px en mobile                 | CSS: `@media (max-width: 480px) .qr-code { min-width: 200px; }`                | Cypress mobile viewport: QR width ≥ 200px                | 2h       | Frontend           |

### 6.3 Sprint 3 (Media Prioridad - UX + Consistency)
| ID        | Hallazgo                                                              | Corrección Propuesta                                                      | Criterio Verificación                                 | Esfuerzo | Owner              |
|-----------|-----------------------------------------------------------------------|---------------------------------------------------------------------------|-------------------------------------------------------|----------|--------------------|
| **H-008** | Force-Revoke mobile: spacing botones + touch target 44px              | CSS: `gap: 12px; min-height: 48px; min-width: 48px;` en botones modal     | Cypress mobile: touch targets ≥ 44px; spacing ≥ 12px  | 3h       | Frontend           |
| **H-009** | Toast duración configurable: 5s default + persist en hover            | `duration: 5000` + `pauseOnHover: true` + `pauseOnFocus: true`            | Cypress: toast visible 5s → hover pausa → focus pausa | 4h       | Frontend           |
| **H-010** | Selector Tenant: mostrar nombre + código (Hospital Central - HCH-001) | API devuelve `{id, name, code}` → UI muestra "Hospital Central (HCH-001)" | Cypress: dropdown muestra nombre + código             | 3h       | Backend + Frontend |
| **H-013** | Tabla auditoría: atajos teclado (j/k navegar, / buscar, c colapsar)   | Keydown listener: j/k → prev/next, / → focus filtro, c → colapsar fila    | Cypress: j → next row, k → prev, / → focus search     | 6h       | Frontend           |
| **H-015** | Selector Tenant mobile: custom dropdown vs nativo                     | Usar `<select>` nativo en mobile (< 480px) + custom en desktop            | Cypress mobile: `<select>` nativo funcional           | 4h       | Frontend           |

### 6.4 Sprint 4 (Baja Prioridad - Polish + Consistency)
| ID        | Hallazgo                                               | Corrección Propuesta                                          | Criterio Verificación                       | Esfuerzo | Owner             |
|-----------|--------------------------------------------------------|---------------------------------------------------------------|---------------------------------------------|----------|-------------------|
| **H-011** | Filtros auditoría: expandidos por defecto en desktop   | CSS: `@media (min-width: 769px) .filters { display: block; }` | Cypress desktop: filtros visibles sin click | 2h       | Frontend          |
| **H-012** | Unificar iconos MFA: TOTP=📱, WebAuthn=🔑, Passkey=🔐  | Icon system unificado + design tokens                         | Visual review: consistencia 100%            | 2h       | Design + Frontend |
| **H-016** | (Nuevo) Password strength meter: anunciar cambios a SR | `aria-live="polite"` en strength meter + `aria-valuenow`      | axe-core: strength meter announced          | 2h       | Frontend          |

## 7. Plan de Acción y Seguimiento
### 7.1 Cronograma Estimado
| Sprint       | Fechas     | Hallazgos                         | Esfuerzo Total | Objetivo                            |
|--------------|------------|-----------------------------------|----------------|-------------------------------------|
| **Sprint 1** | Semana 1   | H-001, H-002, H-014               | 11h            | Cero hallazgos Críticos             |
| **Sprint 2** | Semana 2-3 | H-003, H-004, H-005, H-006, H-007 | 24h            | Cero hallazgos Alta (Accesibilidad) |
| **Sprint 3** | Semana 4   | H-008, H-009, H-010, H-013, H-015 | 20h            | Cero hallazgos Alta (UX)            |
| **Sprint 4** | Semana 5   | H-011, H-012, H-016               | 6h             | Cero hallazgos Media/Baja           |

### 7.2 Definición de Hecho (Definition of Done) por Hallazgo
| Criterio          | Requerido                                                      |
|-------------------|----------------------------------------------------------------|
| **Código**        | PR aprobado + Code Review + Tests unitarios/integración        |
| **Accesibilidad** | axe-core 0 violaciones A/AA en CI + Test manual NVDA/VoiceOver |
| **Testing**       | Cypress test pasa + Cobertura ≥ 80% en componente modificado   |
| **Documentación** | CHANGELOG actualizado + Storybook actualizado (si aplica)      |
| **QA Sign-off**   | QA valida en Staging + Usuario real valida (si Crítico/Alta)   |

### 7.3 Métricas de Seguimiento (Dashboard)
| Métrica                                | Objetivo | Actual (Pre-fix) | Frecuencia Medición |
|----------------------------------------|----------|------------------|---------------------|
| **Hallazgos Críticos abiertos**        | 0        | 2                | Diario              |
| **Hallazgos Alta abiertos**            | 0        | 6                | Diario              |
| **Violaciones axe-core A/AA**          | 0        | 4                | Cada PR             |
| **Tasa éxito MFA (1er intento)**       | > 95%    | 80%              | Semanal             |
| **Tasa error Force-Revoke accidental** | 0%       | 2/25 (8%)        | Por release         |
| **Tiempo configuración MFA paciente**  | < 3 min  | 3.5 min          | Semanal             |
| **Satisfacción NPS**                   | > 50     | 42               | Trimestral          |

## 8. Evidencia de Hallazgos (Muestra)
### 8.1 Captura: H-001 Contraste Countdown MFA
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  AXE VIOLATION: 1.4.3 Contrast (Minimum)                                    │
│  Element: <span class="countdown">25s</span>                                │
│  Color: #9E9E9E (foreground) on #FFFFFF (background) = 2.81:1               │
│  Required: 4.5:1 (AA)                                                       │
│  Fix: Change to #666666 (4.5:1) or #333333 (7:1)                            │
└─────────────────────────────────────────────────────────────────────────────┘
```
### 8.2 Captura: H-002 Force-Revoke Mobile Sin Spacing
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  MOBILE FORCE-REVOKE MODAL                                                  │
│  ─────────────────────────────────────────────────────────────────────────  │
│  [ Cancelar ]  [ REVOCAR SESIONES ]  ← Botones pegados, 40px height         │
│  Touch targets: 40x40px (requiere 44x44px)                                  │
│  Gap: 0px (requiere ≥ 12px)                                                 │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 8.3 Captura: H-003 Tabla Auditoría Sin Scope
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  AXE VIOLATION: 1.3.1 Info and Relationships                                │
│  Element: <th>Acciones</th> (columna dinámica)                              │
│  Issue: Missing scope="col" attribute                                       │
│  Fix: <th scope="col">Acciones</th>                                         │
└─────────────────────────────────────────────────────────────────────────────┘
```
## 9. Entregables Semana 09
| Archivo                                  | Ubicación                                     | Descripción                                                  |
|------------------------------------------|-----------------------------------------------|--------------------------------------------------------------|
| `evaluacion-heuristica-accesibilidad.md` | `docs/tenants/semana-09/`                     | **Este documento** completo                                  |
| `hallazgos-consolidados.csv`             | `docs/tenants/semana-09/`                     | 15 hallazgos con ID, severidad, componente, corrección       |
| `backlog-priorizado.xlsx`                | `docs/tenants/semana-09/`                     | Backlog 4 sprints con esfuerzo, owner, criterio verificación |
| `evidencia-hallazgos/`                   | `docs/tenants/semana-09/evidencia-hallazgos/` | Capturas axe-core, testing usuarios, heurística              |
| `checklist-heuristico-wcag.xlsx`         | `docs/tenants/semana-09/`                     | Checklist completado 10 heurísticas + WCAG 2.1 AA            |
| `README.md`                              | `docs/tenants/semana-09/`                     | Este índice                                                  |
