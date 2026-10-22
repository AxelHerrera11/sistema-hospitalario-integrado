# Declaración de uso de inteligencia artificial

**Herramienta:** opencode, modelo `opencode/minimax-m3.1-flash-free` (ejecutado sobre Windows; el diagrama se generó con `@mermaid-js/mermaid-cli` 12).

**Propósito:** diseñar los componentes backend y frontend del módulo dentro de Laravel 12 y Vue 3, identificar el punto de mayor acoplamiento del flujo «registro de nota SOAP y asociación de diagnóstico clínico», redactar la refactorización conceptual por capas y generar el diagrama de componentes (fuente Mermaid y SVG).

**Revisado y ajustado:**

- Lo que ya está en `develop` (middlewares `tenant` y `jwt.auth`, alias de `bootstrap/app.php`, prefijo `api/v1`, conexión `hospital`, `spatie/laravel-permission`, plugin `axios` con JWT y `X-Tenant-ID`, store de Pinia, `authGuard`, `AppLayout`) se verificó directamente en el repositorio; el alias `permission` sigue sin existir y así queda anotado.
- Las referencias al micro-HIS (nombres de clases, puertos, rutas del contrato y códigos de error) se tomaron de los PR #128, #129, #376, #378, #404 y #405; el `INSERT` sin `medical_record_id`, los triggers `clinical_notes_author_assignment_trigger`, `diagnoses_note_episode_author_trigger`, `clinical_notes_signed_immutable` y `clinical_notes_record_version_unique` se comprobaron contra la migración `database/migrations/hospital/2026_08_11_000400_create_medical_record_tables.php`.
- El caso práctico de la [Semana 6](../semana-06/README.md) es la fuente del punto de acoplamiento elegido (la frontera de la nota clínica) y de la petición a Javier que esta semana deja como pendiente.
- El código Laravel/Vue queda **propuesto**: en esta entrega no se modificó código del micro-HIS ni del HIS.

**Validación humana:** realizada el 2026-10-19. Josué Fernando Hicho García revisó el diseño de componentes, el punto de acoplamiento elegido y su evidencia en el código, la refactorización conceptual y los pendientes con María, Javier y Expediente, y aprueba el documento.

**Responsable:** Josué Fernando Hicho García es responsable de esta entrega.
