# Semana 11 — Prototipo: escritorio + móvil

**Tipo de entrega:** diseño con mockups estáticos (ASCII y Mermaid) más un prototipo navegable
parcial en el SPA Vue (`/prototipos/tenants-responsive`, Semana 10). Los documentos v1.1 marcan cada
pantalla como **[Implementado]** (existe en `develop`) o **[Propuesto]** (diseño sin contrato).

## Entregables (`docs/tenants/semana-11/`)

| Archivo | Tipo | Contenido |
|---|---|---|
| `prototype-spec.md` | Markdown v1.1 | Mapa de navegación, camino feliz y error crítico con su estado real |
| `prototype-spec1.md` | Markdown v1.1 | Roles canónicos, componentes, stack Vue, entregables y plan de pruebas |
| `prototype-screens-mobile.md` | Markdown v1.1 | Móvil parte 1: acceso, MFA (propuesto) y alta de usuario (propuesto) |
| `prototype-screens-mobile1.md` | Markdown v1.1 | Móvil parte 2: usuarios, cerrar sesión ajena y auditoría (propuesta) |
| `prototype-screens-desktop.md`, `prototype-screens-desktop1.md` | Markdown v1.0 | Pantallas de escritorio |
| `prototype-happy-path.md`, `prototype-happy-path1.md` | Markdown v1.0 | Camino feliz detallado |
| `prototype-error-path.md` | Markdown v1.0 | Error crítico detallado |
| `prototype-consistency-checklist.md` | Markdown v1.0 | Checklist de consistencia |
| `prototype-navigation-map.mmd`, `prototype-happy-path.mmd`, `prototype-error-path.mmd` | Mermaid | Diagramas |

Los archivos v1.0 todavía mencionan «Deshacer», WebAuthn y roles en mayúsculas. Ante cualquier
diferencia prevalecen los documentos v1.1.

## Resumen

### Camino feliz
1. **Inicio de sesión** con UUID del hospital (`X-Tenant-ID`), correo y contraseña. [Implementado]
2. **MFA TOTP** para `superadmin` y `admin_hospital`. [Propuesto en el HIS; probado en el micro-HIS de Semana 3]
3. **Inicio** con el menú según los permisos de la sesión. [Implementado]
4. **Usuarios del hospital** (`usuarios.ver`) → cerrar **una** sesión ajena (`sesiones.revocar_ajenas`) con confirmación explícita. [Implementado]
5. **Auditoría** (`auditoria.ver`). [Propuesto: sin vista ni endpoint]

No existe «Deshacer» una revocación: al revocar se invalidan los tokens y el acceso se recupera
solo con un nuevo inicio de sesión (ver `prototype-screens-mobile1.md`, sección 6).

### Error crítico (reutilización de refresh token)
1. `POST /api/v1/auth/refresh` con un refresh ya consumido. [Implementado]
2. Se revoca la familia de refresh y su sesión, y se audita `auth.refresh_reuse_detected` como crítico. [Implementado]
3. Respuesta 401 `REFRESH_TOKEN_REUSED`; el SPA limpia la sesión y vuelve a `/login`. [Implementado]
4. Aviso «Sesión cerrada por seguridad». [Propuesto]

## Objetivos de validación (no medidos)

Son **metas de diseño**. No hubo pruebas con usuarios ni corridas de axe/Lighthouse, así que no
se informan resultados. La v1.0 mostraba cifras (98 % de éxito, 0 violaciones axe, tiempos de
~12 s y ~25 s) que no provenían de ninguna medición; se retiraron.

| Métrica | Objetivo | Cómo se medirá | Resultado |
|---|---|---|---|
| Tiempo del camino feliz | < 15 s | Prueba con usuarios, 5 participantes | Sin medir |
| Éxito al primer intento | > 95 % | Misma prueba | Sin medir |
| Violaciones AA (axe) | 0 | axe-core sobre las pantallas implementadas | Sin medir |
| Objetivos táctiles | ≥ 44 px | Inspección del CSS | **Cumple** en los componentes de Semana 10 (`BaseButton`, `FormField`) |
| Diseño responsive | Sin desplazamiento horizontal a 375 px | Captura con Chrome headless | **Cumple** (`docs/tenants/semana-10/evidencias/`) |

## Accesibilidad (WCAG 2.1 AA)

La revisión manual del diseño de Semana 9 dejó hallazgos abiertos (tabla de `docs/tenants/semana-09/README.md`,
entre ellos H-003, H-004 y H-005). Siguen pendientes y no hay una medición automatizada posterior.
La v1.0 resumía «36 criterios, 29 pasan, 80,6 %», pero esas cifras no tenían respaldo de ninguna
corrida y contradecían la meta de «0 violaciones»; se retiraron.

## Pruebas

| Prueba | Estado |
|---|---|
| Lógica de formularios y cola offline (`node *.test.mjs`) | Ejecutada (Semana 10) |
| Capturas responsive a 375, 768 y 1280 px | Ejecutada (Semana 10) |
| Rotación y reutilización de refresh (`SessionLifecycleTest`) | Ejecutada en la suite del backend |
| Teclado, lectores de pantalla, axe-core, Lighthouse, orientación | Pendiente (plan en `prototype-spec1.md`, sección 7) |
