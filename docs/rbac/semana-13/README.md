# RBAC: roles, permisos y protección de rutas — Semana 13

**Plan de revisión técnica formal del módulo ASII-02 (RBAC).**

- **Autora:** María de los Ángeles López Fajardo (`Mar-03`), mlopezf16@miumg.edu.gt.
- **Módulo:** ASII-02 — RBAC: roles, permisos y protección de rutas (`README.md:37`).
- **Rama:** `feature/asii-02-semana-13-rbac-mar-03`, base `origin/develop`.
- **Semana del plan:** 13 — «Plan de revisión técnica formal. Checklist de revisión, responsables y evidencia» (`README.md:185`).

## 1. Objetivo

Definir una revisión técnica formal, reproducible y verificable del módulo RBAC: qué se
revisa, con qué criterios de entrada y salida, quién revisa, cómo se registra la evidencia y
cómo se cierran los hallazgos. El plan distingue lo que **ya existe y se comprobó** de lo que
**está pendiente, en riesgo o bloqueado**.

## 2. Alcance

**Incluido.**

- Prototipo PHP 8.2 vanilla `micro-his/rbac/` (Presentation, Application, Domain, Persistence).
- Diseño base en `docs/rbac/semana-01`, `semana-02` y `semana-03` de esta rama.
- Contratos propuestos de rutas RBAC (`docs/rbac/semana-01/README.md:21`) y el middleware
  `permission:modulo.accion`.
- Integración Laravel prevista: middlewares, configuración Spatie/tenancy, migraciones de
  identidad y membresías y la capa de auditoría.

**Fuera de alcance.**

- Revisar la lógica interna de módulos de terceros; solo se revisan sus fronteras e interfaces.
- Implementar o corregir código de producción: esta semana solo entrega el plan, el checklist,
  los hallazgos y el informe. **No se modificó código de producción.**
- La revisión formal del código Laravel del módulo RBAC: ese código **no existe todavía** en
  `routes/`, `app/Http/Middleware/` ni en seeders, por lo que no puede revisarse (ver informe).

## 3. Criterios de entrada (para iniciar la revisión)

1. PR del módulo abierto desde un worktree creado de `origin/develop` hacia `develop`.
2. Issue del módulo vinculado y RF/RNF con criterios de aceptación (`docs/rbac/semana-02/requerimientos.md`).
3. Evidencia de diseño (casos de uso, actividad, secuencia y C4) en la rama.
4. Contrato API con endpoints, payloads, errores y permisos por ruta.
5. Comandos de validación declarados y ejecutables (`composer test`, `php artisan route:list --path=api`, `npm run build`).
6. Sin credenciales, tokens ni datos clínicos reales en el diff.

## 4. Criterios de salida (para aprobar la revisión)

1. Todo el checklist de `checklist-revision.md` en verde o con justificación explícita.
2. Aprobación de al menos un par revisor y del responsable del módulo, según `responsables-procedimiento.md`.
3. Ningún bloqueo abierto sin compensación documentada y plan de cierre con responsable y fecha.
4. Hallazgos de `registro-hallazgos.md` cerrados o reclasificados con su evidencia actualizada.
5. Informe técnico actualizado que distinga hechos comprobados, riesgos y bloqueos.
6. Diff del PR pequeño y revisable; ver límite de tamaño en el procedimiento.

## 5. Estructura de la entrega

| Archivo | Contenido |
|---|---|
| `README.md` | Este plan: objetivo, alcance, criterios de entrada/salida. |
| `checklist-revision.md` | Checklist por código, permisos, aislamiento por hospital, errores, auditoría y dependencias. |
| `responsables-procedimiento.md` | Roles de revisión, pasos, evidencia de decisión y límite de diff. |
| `registro-hallazgos.md` | Hallazgos con evidencia verificable (`archivo:línea`) y estado. |
| `informe-tecnico.md` | Hechos comprobados, riesgos y bloqueos; conclusión de la revisión. |

## 6. Estado real al redactar este plan

- **Comprobado por lectura y ejecución:** el prototipo `micro-his/rbac/` pasa sus 9 pruebas
  (`php micro-his/rbac/tests/run.php`, ver `informe-tecnico.md` §1).
- **No ejecutado:** no se corrió la suite Laravel ni la revisión del código de producción
  porque las rutas RBAC, el middleware `permission:` y los modelos referenciados no existen
  en esta rama. Esta entrega **no genera PR, no hace push y no modifica código de producción**.
