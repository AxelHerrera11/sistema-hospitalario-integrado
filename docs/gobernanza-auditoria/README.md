# Gobernanza y Auditoría de movimientos del sistema

> **Asignación:** en el `README.md` de `develop` el módulo es el #22 (`bquiroao`). La entrega la hizo `cindyruano` en ramas `feature/asii-25-semana-NN-gobernanza-auditoria-cindyruano`, integradas en `develop` con los PR #564–#654.

El módulo registra eventos de auditoría (quién, qué, sobre qué, cuándo y con qué resultado) mediante el contrato `AuditLogger` y permite consultarlos con autorización en el tenant actual, filtrando por usuario, módulo, acción, rango de fechas, resultado y correlación.

## Ubicación

| Contenido | Ruta |
|---|---|
| Código del módulo (PHP vanilla, por capas) | [`micro-his/gobernanza-auditoria/`](../../micro-his/gobernanza-auditoria/README.md) |
| Adaptador Laravel (proveedor y controlador) | [`app/GobernanzaAuditoria/`](../../app/GobernanzaAuditoria/) |
| Pruebas | [`micro-his/gobernanza-auditoria/tests/`](../../micro-his/gobernanza-auditoria/tests/) |
| Prototipo navegable | [`micro-his/gobernanza-auditoria/public/`](../../micro-his/gobernanza-auditoria/public/) |
| Propuestas SQL para Javier | [`propuestas-sql/`](propuestas-sql/) |
| Evidencias reales | [`evidencias/`](evidencias/README.md) |
| Cómo regenerar los diagramas | [`diagramas/README.md`](diagramas/README.md) |
| Declaración de uso de IA | [`DECLARACION_IA.md`](DECLARACION_IA.md) |

## Entregas semanales

| Semana | Tema | README |
|---|---|---|
| 1 | Análisis y casos de uso | [semana-01](semana-01/README.md) |
| 2 | Requisitos y diseño (SOLID/OCP) | [semana-02](semana-02/README.md) |
| 3 | Arquitectura y prototipo | [semana-03](semana-03/README.md) |
| 4 | MVC y persistencia | [semana-04](semana-04/README.md) |
| 5 | Contrato API y seguridad | [semana-05](semana-05/README.md) |
| 6 | Evaluación parcial | [semana-06](semana-06/README.md) |
| 7 | Componentes y refactorización | [semana-07](semana-07/README.md) |
| 8 | UX y wireframes | [semana-08](semana-08/README.md) |
| 9 | Usabilidad y accesibilidad | [semana-09](semana-09/README.md) |
| 10 | Propuesta responsive | [semana-10](semana-10/README.md) |
| 11 | Prototipo navegable | [semana-11](semana-11/README.md) |
| 12 | Evaluación y trazabilidad | [semana-12](semana-12/README.md) |
| 13 | Revisión técnica | [semana-13](semana-13/README.md) |
| 14 | Plan SQA | [semana-14](semana-14/README.md) |
| 15 | Matriz y pruebas finales | [semana-15](semana-15/README.md) |
| 16 | Integración, validación y primera entrega funcional | [semana-16](semana-16/README.md) |

## Inicio rápido

```bash
php micro-his/gobernanza-auditoria/tests/run.php
```

```bash
php -S 127.0.0.1:8091 -t micro-his/gobernanza-auditoria/public micro-his/gobernanza-auditoria/public/index.php
```

Después, abrir `http://127.0.0.1:8091/prototipo/`.

## Dependencias con otros módulos

| Dependencia | Responsable | Estado |
|---|---|---|
| `auditoria.ver` en `config/rbac.php` y `DenialRecorder` → `AuditDenials` | RBAC (María) y Tenants | Integrado en `develop` (hoy solo `superadmin` tiene el permiso) |
| `audit_log` / `audit_chain_state` (o decisión por `local_access_audits`) e índices | Javier y Tenants | Propuesta SQL 001 |
| `App\Models\Tenant`/`User` (middleware `tenant` y `jwt.auth`) | Usuarios/Tenants | Resuelto (H-GOB-07): el middleware `tenant` ya no usa `App\Models\Tenant`, sino `App\Modules\Tenants\Infrastructure\Persistence\Hospital` (PR #501–#515) |
| Registro del proveedor en `bootstrap/providers.php` | Equipo | Integrado (commit `8da19f17`, Mar-03, 21-oct-2026) |

## Cambios fuera de las carpetas del módulo

Ninguno desde este módulo. Solo se crearon `micro-his/gobernanza-auditoria/`, `app/GobernanzaAuditoria/` y `docs/gobernanza-auditoria/`. El registro del proveedor en `bootstrap/providers.php` lo integró el equipo de RBAC (ver tabla anterior).
