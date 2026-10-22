# Evidencia - Semana 5

Entorno: Windows 11, Node 24.15.0, Java 24.0.2, PlantUML 1.2026.9, 20 de octubre de 2026.

| Comando | Resultado | Salida |
|---|---|---|
| `npx @redocly/cli@1.25.11 lint docs/catalogo-medicamentos/semana-05/openapi.yaml` (conjunto recomendado) | Válido; 1 advertencia: falta `info.license` | 0 |
| `java -jar plantuml.jar -tsvg -charset UTF-8 -failfast2 fuentes/*.puml` | Ambas vistas renderizan; los SVG regenerados son idénticos byte a byte a los versionados | 0 |
| Carga de cada SVG con `DOMDocument` (script temporal) | 2 SVG válidos | 0 |
| Revisión de enlaces relativos de los `.md` de las Semanas 2, 3 y 5 (script temporal) | 0 rotos | 0 |
| Inspección visual de ambos diagramas | Sin recortes ni textos superpuestos; las relaciones propuestas van en línea discontinua | — |

## Fuentes contrastadas en el repositorio

- `bootstrap/app.php` (`apiPrefix: 'api/v1'`) y `routes/api.php` (middlewares `tenant` y `jwt.auth`).
- `config/rbac.php`: `medicamentos.ver` para `superadmin`, `admin_hospital`, `medico` y `enfermeria`; sin permisos de escritura ni pendientes de medicamentos.
- `app/Rbac/RequirePermission.php`, `AuditDenials.php` y `LogDenials.php`, que sostienen la denegación registrada del diagrama de secuencia.
- `docs/rbac/semana-05/contrato-api.md` (`trace_id`) y `.github/workflows/branch-protection.yml` (controles del PR).

## No ejecutado

El API v1 no está implementado en Laravel; esta semana es diseño. Su ejecución y prueba corresponden a las Semanas 16 y 17.
