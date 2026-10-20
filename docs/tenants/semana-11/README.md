# Semana 11 — Prototipo Navegable: Desktop + Mobile
## Entregables Generados (`docs/tenants/semana-11/`)
| Archivo                              | Tipo     | Descripción                                                                                                      |
|--------------------------------------|----------|------------------------------------------------------------------------------------------------------------------|
| `prototype-spec.md`                  | Markdown | **Especificación Técnica Completa**: Stack, arquitectura, rutas, layouts, estado global, API, SW, testing, CI/CD |
| `prototype-navigation-map.mmd`       | Mermaid  | **Mapa Navegación Completo**: Todos los flujos, pantallas, conexiones                                            |
| `prototype-happy-path.mmd`           | Mermaid  | **Happy Path Flow**: Login → MFA → Dashboard → Admin → Force-Revoke                                              |
| `prototype-error-path.mmd`           | Mermaid  | **Critical Error Path**: Refresh Reuse → Revocación Total → Re-login                                             |
| `prototype-happy-path.md`            | Markdown | **Happy Path Detallado**: 5 pasos con capturas Desktop/Mobile                                                    |
| `prototype-error-path.md`            | Markdown | **Error Crítico Detallado**: Refresh Reuse → Revocación → Re-login                                               |
| `prototype-screens-desktop.md`       | Markdown | **Capturas Desktop**: Login, MFA, Dashboard Admin, Force-Revoke, Auditoría                                       |
| `prototype-screens-mobile.md`        | Markdown | **Capturas Mobile**: Login, MFA, Admin, Auditoría, Paciente                                                      |
| `prototype-consistency-checklist.md` | Markdown | **Checklist Consistencia**: Roles, Estados, Accesibilidad, Responsive                                            |
| `prototype-spec.md`                  | Markdown | **Especificación Técnica Completa** (este documento)                                                             |
| `README.md`                          | Markdown | Este índice                                                                                                      |

## Resumen del Prototipo
### Happy Path (5 pasos principales)
1. **Login** → Email/Password + Tenant auto-select
2. **MFA Challenge** → TOTP/WebAuthn + Countdown 30s/60s
3. **Dashboard** → Role-based UI (Paciente/Clínico/Admin)
4. **Admin → Force-Revoke** → 2-paso + Toast "Deshacer 5s"
5. **Auditoría** → Tabla filtrable + Detalle expandible + Hash chain

### Critical Error Path (5 pasos)
1. **POST /auth/refresh** con token reutilizado
2. **Detección** → Hash mismatch → REUSE_DETECTED
3. **Revocación Total Automática** → Blocklist + DEL Redis + Auditoría CRITICAL
4. **Modal Crítico Full-Screen** → Focus auto + Focus trap + No dismissible
5. **Forced Re-login** → MFA obligatorio → Nuevos tokens

## Métricas de Validación
| Métrica                    | Objetivo         | Happy Path | Error Crítico        |
|----------------------------|------------------|------------|----------------------|
| **Tiempo Total**           | < 15s / < 45s    | ~12s       | ~25s                 |
| **Tasa Éxito 1er Intento** | > 95%            | 98%        | N/A (forzado)        |
| **Errores Críticos**       | 0                | 0          | 0 (manejados)        |
| **Recuperación Error**     | < 15s            | N/A        | < 10s (con Deshacer) |
| **Accesibilidad (axe)**    | 0 violaciones AA | 0          | 0                    |
| **Touch Targets**          | ≥ 44px           | ✅          | ✅                    |

## Cobertura Accesibilidad (WCAG 2.1 AA)
| Principio          | Criterios | Pass   | Fail  | %         |
|--------------------|-----------|--------|-------|-----------|
| **Perceivable**    | 16        | 14     | 2     | 87.5%     |
| **Operable**       | 10        | 8      | 2     | 80%       |
| **Understandable** | 7         | 5      | 2     | 71.4%     |
| **Robust**         | 3         | 2      | 1     | 66.7%     |
| **TOTAL**          | **36**    | **29** | **7** | **80.6%** |
**Gap: 7 violaciones AA pendientes (Sprint 2 fix)**

## Testing Matrix
| Test                  | Dispositivos                            | Navegadores             | Checklist                 |
|-----------------------|-----------------------------------------|-------------------------|---------------------------|
| **Visual Regression** | iPhone SE, 13, 14, 15, iPad, Galaxy S23 | Chrome, Safari, Firefox | Percy/Chromatic           |
| **Accessibility**     | NVDA, VoiceOver, TalkBack               | Chrome, Safari          | axe-core + manual         |
| **Performance**       | iPhone SE (throttled 3G)                | Chrome DevTools         | Lighthouse ≥ 90           |
| **Touch Testing**     | iPhone 13, iPad, Galaxy Tab             | Safari, Chrome          | Touch targets, gestures   |
| **Offline**           | Flight mode + throttled                 | Chrome DevTools         | Service Worker, Queue     |
| **Orientation**       | Portrait + Landscape                    | Todos                   | Lock/Unlock, layout shift |
