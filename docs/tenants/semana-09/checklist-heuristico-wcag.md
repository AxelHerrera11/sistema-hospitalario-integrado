# Semana 09 — Checklist Heurístico Nielsen + WCAG 2.1 AA
### 1. Visibilidad del Estado del Sistema
| #   | Criterio                                   | Login | MFA | Admin Create | Admin List | Audit | Patient | Notas                      |
|-----|--------------------------------------------|-------|-----|--------------|------------|-------|---------|----------------------------|
| 1.1 | Feedback inmediato tras acción usuario     | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | Spinner, toasts, progress  |
| 1.2 | Estado de carga visible (spinner/progress) | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | MFA countdown visible      |
| 1.3 | Confirmación acciones completadas          | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | Toast success              |
| 1.4 | Errores visibles y comprensibles           | ✅     | ⚠️  | ✅            | ✅          | ✅     | ✅       | MFA error mensaje genérico |
| 1.5 | Estados vacíos informativos                | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | Empty states en todas      |
**Puntuación: 4.2/5** | Hallazgo: MFA countdown no anuncia progreso a Screen Readers

### 2. Correspondencia Sistema-Mundo Real
| #   | Criterio                                      | Login | MFA | Admin Create | Admin List | Audit | Patient | Notas                                   |
|-----|-----------------------------------------------|-------|-----|--------------|------------|-------|---------|-----------------------------------------|
| 2.1 | Lenguaje natural, no técnico                  | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | "Credenciales inválidas" vs "Error 401" |
| 2.2 | Conceptos familiares (hospital, cita, receta) | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | Terminología médica                     |
| 2.3 | Metáforas visuales consistentes               | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | Iconos estándar                         |
| 2.4 | Flujo lógico según expectativa usuario        | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | Login → MFA → Dashboard                 |
**Puntuación: 4.5/5** | Sin hallazgos

### 3. Control y Libertad del Usuario
| #   | Criterio                                    | Login | MFA | Admin Create | Admin List | Audit | Patient | Notas                                      |
|-----|---------------------------------------------|-------|-----|--------------|------------|-------|---------|--------------------------------------------|
| 3.1 | Salidas de emergencia (Cancelar, Cerrar)    | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ESC cierra modales                         |
| 3.2 | Deshacer acciones críticas                  | ❌     | ❌   | ✅            | ❌          | ✅     | ✅       | Force-Revoke sin Deshacer                  |
| 3.3 | Confirmación antes de acciones destructivas | ✅     | ✅   | ✅            | ⚠️         | ✅     | ✅       | Force-Revoke mobile sin doble confirmación |
| 3.4 | Navegación hacia atrás funcional            | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | Browser back + breadcrumbs                 |
**Puntuación: 3.8/5** | Hallazgos: H-002 (Crítica), H-008 (Media)

### 4. Consistencia y Estándares
| #   | Criterio                                   | Login | MFA | Admin Create | Admin List | Audit | Patient | Notas                            |
|-----|--------------------------------------------|-------|-----|--------------|------------|-------|---------|----------------------------------|
| 4.1 | Patrones UI consistentes (botones, inputs) | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | Design system aplicado           |
| 4.2 | Terminología consistente                   | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | Glosario UI definido             |
| 4.3 | Iconografía consistente                    | ✅     | ⚠️  | ✅            | ✅          | ✅     | ✅       | Iconos MFA: TOTP=📱, WebAuthn=🔑 |
| 4.4 | Posición elementos estándar                | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | Botón primary siempre derecha    |
**Puntuación: 4.7/5** | Hallazgo: H-012 (Baja)

### 5. Prevención de Errores
| #   | Criterio                            | Login | MFA | Admin Create | Admin List | Audit | Patient | Notas                           |
|-----|-------------------------------------|-------|-----|--------------|------------|-------|---------|---------------------------------|
| 5.1 | Validación tiempo real (inline)     | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | Password strength, email format |
| 5.2 | Confirmación acciones irreversibles | ✅     | ✅   | ✅            | ⚠️         | ✅     | ✅       | Force-Revoke mobile sin 2 pasos |
| 5.3 | Validación formato antes de submit  | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | Client-side + Server-side       |
| 5.4 | Prevención errores deslizamiento    | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | Touch targets 44px              |
**Puntuación: 4.0/5** | Hallazgos: H-002 (Crítica), H-008 (Media)

### 6. Reconocimiento Antes que Recuerdo
| #   | Criterio                                | Login | MFA | Admin Create | Admin List | Audit | Patient | Notas                       |
|-----|-----------------------------------------|-------|-----|--------------|------------|-------|---------|-----------------------------|
| 6.1 | Información visible vs memorizar        | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | Tenant visible en header    |
| 6.2 | Autocompletado campos estándar          | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | autocomplete attributes     |
| 6.3 | Ayuda contextual visible                | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | Tooltips, hints, help links |
| 6.4 | Selector tenant muestra nombre (mobile) | ⚠️    | ⚠️  | ⚠️           | ⚠️         | ⚠️    | ⚠️      | Solo código HCH-001         |
**Puntuación: 4.3/5** | Hallazgo: H-010 (Media)

### 7. Flexibilidad y Eficiencia de Uso
| #   | Criterio                              | Login | MFA | Admin Create | Admin List | Audit | Patient | Notas                           |
|-----|---------------------------------------|-------|-----|--------------|------------|-------|---------|---------------------------------|
| 7.1 | Atajos teclado para usuarios expertos | ❌     | ❌   | ❌            | ❌          | ❌     | ❌       | Sin atajos j/k, /, c            |
| 7.2 | Personalización atajos/preferencias   | ❌     | ❌   | ❌            | ❌          | ❌     | ❌       | No configurables                |
| 7.3 | Acceleradores (doble click, swipe)    | ❌     | ❌   | ❌            | ❌          | ❌     | ❌       | No implementados                |
| 7.4 | Eficiencia flujos frecuentes          | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | Login < 15s, Alta usuario < 60s |
**Puntuación: 3.5/5** | Hallazgos: H-013 (Media), H-015 (Media)

### 8. Estética y Diseño Minimalista
| #   | Criterio                         | Login | MFA | Admin Create | Admin List | Audit | Patient | Notas                    |
|-----|----------------------------------|-------|-----|--------------|------------|-------|---------|--------------------------|
| 8.1 | Información relevante únicamente | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | No clutter               |
| 8.2 | Jerarquía visual clara           | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | Headings, spacing, color |
| 8.3 | Espacio en blanco adecuado       | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | 16px base spacing        |
| 8.4 | Carga cognitiva mínima           | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | Progressive disclosure   |
**Puntuación: 4.6/5** | Sin hallazgos

### 9. Ayuda a Reconocer, Diagnosticar, Recuperarse de Errores
| #   | Criterio                           | Login | MFA | Admin Create | Admin List | Audit | Patient | Notas                                    |
|-----|------------------------------------|-------|-----|--------------|------------|-------|---------|------------------------------------------|
| 9.1 | Mensajes error en lenguaje claro   | ✅     | ⚠️  | ✅            | ✅          | ✅     | ✅       | MFA reuse detection: mensaje críptico    |
| 9.2 | Indicación precisa ubicación error | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | Inline validation + focus                |
| 9.3 | Sugerencia corrección constructiva | ✅     | ⚠️  | ✅            | ✅          | ✅     | ✅       | MFA error: "Intento 2/3" sin "qué hacer" |
| 9.4 | Recuperación fácil tras error      | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | Reintentar, enlaces ayuda                |
**Puntuación: 4.1/5** | Hallazgos: H-001 (Crítica), H-009 (Media)

### 10. Ayuda y Documentación
| #    | Criterio                           | Login | MFA | Admin Create | Admin List | Audit | Patient | Notas                                  |
|------|------------------------------------|-------|-----|--------------|------------|-------|---------|----------------------------------------|
| 10.1 | Ayuda contextual (tooltips, hints) | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | Password hint, MFA help                |
| 10.2 | Documentación accesible/buscable   | ⚠️    | ❌   | ⚠️           | ⚠️         | ⚠️    | ⚠️      | Help links MFA rotos (H-014)           |
| 10.3 | Onboarding guiado primer uso       | ✅     | ✅   | ✅            | ⚠️         | ❌     | ✅       | MFA onboarding guiado                  |
| 10.4 | Ayuda contextual en errores        | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | "¿Olvidó contraseña?", "Problemas MFA" |
**Puntuación: 3.8/5** | Hallazgos: H-014 (Alta), H-011 (Baja)

## Checklist Completado: WCAG 2.1 AA (Principios POUR)
### PERCEPIBLE (Perceivable)
| Criterio WCAG | Descripción                            | Login | MFA | Admin Create | Admin List | Audit | Patient | Estado                |
|---------------|----------------------------------------|-------|-----|--------------|------------|-------|---------|-----------------------|
| **1.1.1**     | Contenido no textual (alt, aria-label) | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass                |
| **1.2.1**     | Solo audio/video (subtítulos)          | N/A   | N/A | N/A          | N/A        | N/A   | N/A     | N/A                   |
| **1.3.1**     | Info y relaciones (semántica, scope)   | ✅     | ✅   | ❌            | ❌          | ❌     | ✅       | **Fail** H-003, H-005 |
| **1.3.2**     | Secuencia significativa (orden DOM)    | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass                |
| **1.3.3**     | Instrucciones sensoriales              | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass                |
| **1.3.4**     | Orientación (portrait/landscape)       | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass                |
| **1.3.5**     | Identificar propósito inputs           | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass                |
| **1.4.1**     | Uso del color (no solo color)          | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass                |
| **1.4.3**     | Contraste mínimo (4.5:1)               | ✅     | ❌   | ✅            | ✅          | ✅     | ✅       | **Fail** H-001        |
| **1.4.4**     | Redimensionar texto 200%               | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass                |
| **1.4.5**     | Imágenes de texto                      | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass                |
| **1.4.10**    | Reflow (320px sin scroll horizontal)   | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass                |
| **1.4.11**    | Contraste no texto (3:1)               | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass                |
| **1.4.12**    | Espaciado texto                        | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass                |
| **1.4.13**    | Contenido hover/focus                  | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass                |

### OPERABLE (Operable)
| Criterio WCAG | Descripción              | Login | MFA | Admin Create | Admin List | Audit | Patient | Estado         |
|---------------|--------------------------|-------|-----|--------------|------------|-------|---------|----------------|
| **2.1.1**     | Teclado accesible        | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass         |
| **2.1.2**     | Sin trampas teclado      | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass         |
| **2.1.4**     | Atajos teclado           | ❌     | ❌   | ❌            | ❌          | ❌     | ❌       | **Fail** H-013 |
| **2.2.1**     | Tiempo ajustable         | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass         |
| **2.2.2**     | Pausar, detener, ocultar | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass         |
| **2.3.1**     | Tres destellos           | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass         |
| **2.4.1**     | Skip links               | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass         |
| **2.4.2**     | Título página            | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass         |
| **2.4.3**     | Orden foco               | ✅     | ✅   | ✅            | ❌          | ❌     | ✅       | **Fail** H-004 |
| **2.4.4**     | Propósito enlace         | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass         |
| **2.4.5**     | Múltiples vías           | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass         |
| **2.4.6**     | Headings/labels          | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass         |
| **2.4.7**     | Foco visible             | ✅     | ✅   | ✅            | ✅          | ❌     | ✅       | **Fail** H-004 |

### COMPRENSIBLE (Understandable)
| Criterio WCAG | Descripción                           | Login | MFA | Admin Create | Admin List | Audit | Patient | Estado         |
|---------------|---------------------------------------|-------|-----|--------------|------------|-------|---------|----------------|
| **3.1.1**     | Idioma página                         | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass         |
| **3.2.1**     | En foco (sin cambio contexto)         | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass         |
| **3.2.2**     | En entrada (sin auto-submit)          | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass         |
| **3.2.3**     | Navegación consistente                | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass         |
| **3.2.4**     | Identificación consistente            | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass         |
| **3.3.1**     | Identificación errores                | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass         |
| **3.3.2**     | Labels/instrucciones                  | ✅     | ✅   | ❌            | ✅          | ✅     | ✅       | **Fail** H-005 |
| **3.3.3**     | Sugerencia corrección                 | ✅     | ❌   | ✅            | ✅          | ✅     | ✅       | **Fail** H-009 |
| **3.3.4**     | Prevención errores (legal/financiero) | ✅     | ✅   | ✅            | ⚠️         | ✅     | ✅       | **Fail** H-002 |

### ROBUSTO (Robust)
| Criterio WCAG | Descripción                 | Login | MFA | Admin Create | Admin List | Audit | Patient | Estado         |
|---------------|-----------------------------|-------|-----|--------------|------------|-------|---------|----------------|
| **4.1.1**     | Parsing (HTML válido)       | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass         |
| **4.1.2**     | Nombre, rol, valor (ARIA)   | ✅     | ✅   | ❌            | ✅          | ✅     | ✅       | **Fail** H-005 |
| **4.1.3**     | Mensajes estado (aria-live) | ✅     | ✅   | ✅            | ✅          | ✅     | ✅       | ✅ Pass         |

## Resumen Cumplimiento WCAG 2.1 AA

| Principio          | Criterios Aplicables | Pass   | Fail  | % Cumplimiento |
|--------------------|----------------------|--------|-------|----------------|
| **Perceivable**    | 16                   | 14     | 2     | 87.5%          |
| **Operable**       | 10                   | 8      | 2     | 80%            |
| **Understandable** | 7                    | 5      | 2     | 71.4%          |
| **Robust**         | 3                    | 2      | 1     | 66.7%          |
| **TOTAL**          | **36**               | **29** | **7** | **80.6%**      |
**Objetivo: 100% AA** | **Gap: 7 criterios** | **Sprint 1-2 target: 100%**

## Testing Automatizado (CI/CD)
### Configuración axe-core
```json
{
  "rules": {
    "color-contrast": { "enabled": true },
    "keyboard": { "enabled": true },
    "aria-allowed-attr": { "enabled": true },
    "aria-required-attr": { "enabled": true },
    "label": { "enabled": true },
    "button-name": { "enabled": true },
    "link-name": { "enabled": true },
    "image-alt": { "enabled": true },
    "document-title": { "enabled": true },
    "html-has-lang": { "enabled": true },
    "landmark-one-main": { "enabled": true },
    "region": { "enabled": true },
    "skip-link": { "enabled": true }
  },
  "tags": ["wcag2a", "wcag2aa", "wcag21aa", "best-practice"]
}
```

### Pipeline CI/CD (GitHub Actions)
```yaml
# .github/workflows/accessibility.yml
name: Accessibility Testing
on: [push, pull_request]
jobs:
  a11y:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
      - run: npm ci
      - run: npm run build
      - run: npm run test:a11y
        env:
          CYPRESS_BASE_URL: http://localhost:3000
```
## Testing Manual Realizado
| Test                        | Herramienta               | Pantallas | Resultado  | Hallazgos           |
|-----------------------------|---------------------------|-----------|------------|---------------------|
| **Solo Teclado**            | Teclado físico            | 6/6       | ✅ Pass     | H-004 (Audit focus) |
| **Screen Reader NVDA**      | NVDA 2024.1               | 6/6       | ⚠️ Partial | H-003, H-005        |
| **Screen Reader VoiceOver** | macOS 14                  | 6/6       | ⚠️ Partial | H-003, H-005        |
| **Zoom 200%**               | Chrome DevTools           | 6/6       | ✅ Pass     | -                   |
| **High Contrast Mode**      | Windows 11                | 6/6       | ✅ Pass     | -                   |
| **Reduced Motion**          | prefers-reduced-motion    | 6/6       | ✅ Pass     | -                   |
| **Color Blindness**         | Chrome DevTools Emulation | 6/6       | ✅ Pass     | H-001 (contraste)   |
| **Zoom Text Only**          | Firefox                   | 6/6       | ✅ Pass     | -                   |

## Hallazgos de Testing Usuarios (25 participantes)
| Métrica                             | Objetivo      | Resultado     | Gap  |
|-------------------------------------|---------------|---------------|------|
| **Tasa éxito Login**                | > 98%         | 100%          | ✅    |
| **Tasa éxito MFA (1er intento)**    | > 95%         | 80%           | -15% |
| **Tasa abandono Login**             | < 2%          | 0%            | ✅    |
| **Tiempo Login p50**                | < 15s         | 28s           | -13s |
| **Tasa éxito Force-Revoke**         | 100%          | 100%          | ✅    |
| **Errores Force-Revoke accidental** | 0%            | 8%            | -8%  |
| **Satisfacción NPS**                | > 50          | 42            | -8   |
| **Accesibilidad axe-core**          | 0 violaciones | 4 violaciones | -4   |

## Próximos Pasos Inmediatos
| Acción                             | Responsable        | Fecha Límite   | Dependencia    |
|------------------------------------|--------------------|----------------|----------------|
| Fix H-001 (Contraste MFA)          | Frontend           | Sprint 1 Día 1 | Ninguna        |
| Fix H-002 (Force-Revoke Deshacer)  | Frontend + Backend | Sprint 1 Día 3 | H-008          |
| Fix H-014 (Help links MFA)         | Frontend           | Sprint 1 Día 1 | Ninguna        |
| Configurar axe-core en CI          | DevOps             | Sprint 1 Día 2 | Pipeline       |
| Ejecutar testing usuarios Sprint 2 | UX Research        | Sprint 2       | Fixes Sprint 1 |
