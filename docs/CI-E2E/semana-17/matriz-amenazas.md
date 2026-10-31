# Matriz de amenazas — Pipeline CI, evidencias y gestión de ejecuciones

Método: STRIDE sobre el workflow `QA CI y E2E`, el guardián de PR, los artefactos de evidencia, el micro-HIS de
calidad de versiones y el despliegue. Evidencia: [`evidencias/01-pruebas-seguridad.txt`](evidencias/01-pruebas-seguridad.txt)
y [`evidencias/02-ensayo-guia-laravel.txt`](evidencias/02-ensayo-guia-laravel.txt).

P × I de 1 a 3 antes del control. Residual: Bajo / Medio / Alto.

| Id | STRIDE | Amenaza | P×I | Control implementado | Verificación | Residual |
|---|---|---|---|---|---|---|
| Q-01 | Manipulación | Un PR de un fork ejecuta código con secretos del repositorio | 2×3 | Disparador `pull_request` (no `pull_request_target`); `permissions: contents: read`; el workflow no usa `secrets.*` | `grep` del workflow | Bajo |
| Q-02 | Manipulación | Inyección de comandos con el cuerpo o la rama del PR | 2×3 | `PR_BODY`, `HEAD_REF` y `PR_AUTHOR` pasan por `env`, nunca interpolados en el script | `grep` de `branch-protection.yml` | Bajo |
| Q-03 | Manipulación | Una acción de terceros cambia bajo la misma etiqueta | 2×3 | Acciones por etiqueta mayor (`@v4`, `@v2`) | Lista de `uses:` | **Medio**: fijar `shivammathur/setup-php` por SHA (hoy `v2` = `eb7c497e…`, 2.40.0) |
| Q-04 | Divulgación | Secretos en logs o artefactos de evidencia | 2×3 | Secretos efímeros por ejecución con `::add-mask::`; clave pública de pruebas; **nuevo** `escanear-secretos.php` | CI22, CI23; 42 artefactos reales con 0 hallazgos | Bajo |
| Q-05 | Divulgación | Datos personales reales en evidencias | 2×3 | Datos ficticios `.test`; el escáner marca correos de dominios reales | CI23 | Bajo |
| Q-06 | Manipulación | Semillas o E2E contra una base real | 1×3 | `seed-e2e.php` exige `E2E_ALLOW_SEED=1` y bases `*_e2e`; la guía lo prohíbe en el ambiente desplegado | Guía §3; workflow línea de la semilla | Bajo |
| Q-07 | Repudio | Aprobar una versión sin evidencia | 2×3 | El gate guarda veredicto y SHA-256 de cada evidencia; etapa sin reporte → BLOQUEADO | CI15, CI18 | Bajo |
| Q-08 | Elevación | Aprobar por omisión (suite que no corrió) | 2×3 | Omitidas en obligatorias → rechazo; ausentes → bloqueo | CI17, CI18 | Bajo |
| Q-09 | Elevación | Bajar el umbral con `continue-on-error` | 2×2 | Solo Pint y el E2E de pila completa son informativos, y el resumen lo dice | `grep` del workflow | Medio (decisión B-04 del grupo) |
| Q-10 | Suplantación / elevación | Registrar resultados sin permiso o en otro hospital | 2×3 | RBAC y tenant del servidor en la API del micro-HIS | BN01…BN04, BN21; E2E-QA-04…06 | Bajo |
| Q-11 | Denegación de servicio | RBAC o almacén caídos aprueban por error | 2×3 | Falla cerrada: 503 sin detalle | BN22, BN23, E2E-QA-07 | Bajo |
| Q-12 | Divulgación | Errores con detalle técnico en producción | 2×3 | `APP_DEBUG=false` en el `.env` de despliegue | Ensayo de la guía: 500 con «Server Error» y nada más | Bajo |
| Q-13 | Denegación de servicio | Con CENTRAL caída, las rutas protegidas responden 500 en vez de 503 | 2×2 | — (el middleware `tenant` consulta CENTRAL antes del JWT) | Ensayo de la guía, V3 | **Medio**: avisar a Tenants (Marielos) |
| Q-14 | Manipulación | Desplegar un release roto | 2×3 | **Nuevo** `desplegar-micro-his.sh`: releases inmutables por commit, `php -l`, salud y rollback automático | Ensayos del expediente y de Gobernanza (Semana 17) | Bajo |

## Hallazgos de esta semana

- **H-QA-17-01 (Q-04/Q-05):** las evidencias se conservaban como artefacto de 30 a 90 días sin ninguna revisión de
  secretos. Se agrega `bin/escanear-secretos.php` (7 reglas, nunca imprime el valor) con controles positivo y
  negativo (CI22, CI23). Sobre los 42 artefactos reales del pipeline de la Semana 16: 0 hallazgos.
- **H-QA-17-02 (Q-13):** el ensayo de la guía mostró que `GET /auth/me` con tenant y sin token responde 500 si CENTRAL
  no está disponible; la guía esperaba 401. No se modificó el middleware (es de Tenants/Marielos); queda como aviso.
- **Q-03:** no se cambió el workflow porque GitHub Actions no puede ejecutarlo hoy para validar el cambio.
