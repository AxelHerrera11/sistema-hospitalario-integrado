# Entrega revisable: usuarios, tenants y autenticación

Estado al 20 de octubre de 2026. Esta entrega **no declara completo el módulo**:
separa lo implementado y probado de los requisitos todavía sin código o sin
validación de infraestructura. No se abrió PR, no se hizo push ni merge.

## 1. Funcionalidad entregada y brechas

Implementado en esta rama:

- Login por credenciales CENTRAL, `/auth/me`, logout y acceso JWT de 15 minutos.
- Refresh opaco independiente del access token: cookie HttpOnly/SameSite Strict,
  secreto aleatorio, almacenamiento SHA-256, expiración de 7 días, rotación,
  detección de reutilización y revocación de la familia.
- Roles literales `superadmin`, `admin_hospital`, `medico`, `enfermeria` y
  `recepcion`. Contexto derivado de JWT, hospital validado y membresías CENTRAL.
- Listado paginado de usuarios con identidad/estado/roles solamente, filtrado y
  limitado al hospital actual; creación de hospital CENTRAL inactivo hasta
  aprovisionamiento; revocación de sesiones ajenas del hospital actual.
- Desactivación de membresías locales sin cambiar `central_users`; asignación y
  revocación de roles continúan siendo operaciones separadas de `rbac.asignar`.
- CLI de uso interactivo para provisionar el primer superadmin; contraseña oculta,
  confirmada, validada y hasheada. No hay valores predeterminados.
- Auditoría de login, logout, refresh, reutilización, denegaciones y cambios
  administrativos; migración incremental con ID/fecha, cadena SHA-256 y trigger
  PostgreSQL que bloquea `UPDATE`/`DELETE`.
- Vue: login, recuperación de sesión desde la cookie, refresh/reintento de access
  expirado, cierre de sesión, hospital activo y listado paginado de usuarios.

Pendiente o sin evidencia suficiente:

- Alta/activación administrativa de usuarios, invitación por correo y recuperación
  de contraseña. El comando CLI solo cubre el primer superadmin.
- MFA TOTP/WebAuthn, recuperación de MFA, bloqueo progresivo, historial y política
  completa de contraseñas, comprobación HIBP, SSO/JIT, sesiones por dispositivo,
  inactividad y rotación de claves/JWKS. No se simulan servicios externos.
- Edición/listado administrativo de hospitales y aprovisionamiento de base clínica.
  El endpoint de creación registra un hospital inactivo con `clinical_endpoint`
  nulo: la integración de infraestructura debe aprovisionarlo antes de activarlo.
- UI para registro/activación, MFA, recuperación, creación de hospital y
  revocación administrativa de sesiones; no se ejecutó E2E de navegador.
- Política legal de retención temporal de auditoría, revisión independiente de
  cumplimiento, PostgreSQL real, Redis/correo/SSO reales, CI y staging.
- Las pruebas HTTP locales usan un esquema SQLite armado por el test harness.
  No representan integración ni validación de migraciones PostgreSQL.

La guía del módulo declara RN-03 a RN-15, incluidos MFA, RS256/JWKS, lockout y
SSO/JIT. Por tanto, el estado de esta rama es **parcial**, no “módulo completo”.
La implementación actual conserva la configuración JWT existente; el alcance
RS256/JWKS de la guía no queda acreditado aquí.

## 2. Relación con PR existentes de Cindy

| PR | Contenido remoto revisado | Tratamiento de esta entrega |
|---|---|---|
| [#302](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/302) | `AuthenticateUserUseCase`, resultados y comandos de registro/revocación; base `feature/asii-25-semana-03-parte15`. | Se conserva como diseño previo. La acción Laravel `AuthenticateUser`/API actual no duplica esos archivos, pero debe reconciliar contratos antes de integración. |
| [#303](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/303) | Comandos de autenticación, MFA, refresh y registro de tenant; apilado sobre #302. | No se copian DTOs ni se da por funcional el MFA por la presencia de comandos. Revisar los contratos al integrar; este refresh sí se prueba por HTTP en Laravel. |
| [#341](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/341) | `public/index.php` más documentos de pruebas; base `feature/asii-25-semana-15-parte4`. | No adoptar su reemplazo de entrypoint: esta rama conserva bootstrap/rutas/providers Laravel. Integrar evidencia documental solo tras verificarla contra el commit real. |
| [#348](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/348) | `LoginMfaFeatureTest.php`, apilado sobre parte 8 de semana 15. | Es un test aislado con dobles, no prueba de kernel HTTP ni de MFA operativo. No se usa como evidencia de esta rama. |

Referencia de alcance: `../informes/2026-10-19-cierre-modulo-cindy-usuarios-tenants-auth.md`.
La guía `docs/worktree-guide.md`, enlazada desde README, no existe en este
worktree; seguir las reglas de rama/worktree descritas en README.

## 3. Orden de integración y apilado local

La rama de trabajo parte de `080eaa1` (`codex/auth-rbac-context-core`), cuyo
contrato RBAC explícito se mantiene. Las entregas locales son commits apilados
en ese orden; las ramas locales por commit y sus SHA se registran después de
crear el stack. Ninguna rama se publica:

1. Base RBAC y autorización por hospital, incluyendo permisos administrativos
   aprobados; no cambiar roles canónicos ni `prescripciones.excepcion_alergia`.
2. Identidad, credenciales, sesiones y emisión JWT contra el bootstrap Laravel.
3. Listado, desactivación local, creación de hospital pendiente y revocación de
   sesiones ajenas, con pruebas de permiso y aislamiento.
4. Refresh independiente, rotación/replay, sesión y pruebas de aislamiento.
5. Auditoría, migración incremental y provisionamiento CLI de primera cuenta.
6. Flujos Vue autenticados y pantalla de listado.
7. Documentación de entrega y evidencia.

Para consumir la entrega de Cindy, revisar primero los contratos de #302/#303,
después integrar el backend Laravel y el esquema acordado; no integrar #341 como
entrypoint alternativo. #348 puede integrarse solo después de actualizar sus
dobles al contrato real y agregar cobertura de HTTP/E2E. Mantener cada PR apilado
contra la base del anterior; cada commit publicado debe corresponder a un PR con
diff completo menor o igual a 400 líneas, inserciones y eliminaciones incluidas.

## 4. Contrato de esquema para Javier e instalación

La conexión de identidad/auth es `central` (`CENTRAL_DB_*`); la clínica usa
`hospital` (`HOSPITAL_DB_*`). Migraciones relevantes:

- `auth_credentials`: `user_id` CENTRAL, `email_normalized` único,
  `password_hash`; no guardar contraseña en claro.
- `auth_sessions`: sesión, usuario, hospital, `jti`, tiempos y revocación.
- `auth_refresh_tokens`: familia/sesión/usuario/hospital, hash del secreto,
  emisión/expiración/consumo/revocación. No contiene refresh token en claro.
- `audit_log`: `event_id`, `occurred_at`, `actor_id`, `hospital_id`, `event`,
  `result`, JSON `detail`, `trace_id`, `previous_hash` y `event_hash`.
  `audit_chain_state` serializa escritores. No añadir secretos al detalle.
- `hospitals`: usa `network_id`, `code`, `name`, `status`, `timezone`,
  `clinical_endpoint`, `schema_version` y `metadata` del esquema CENTRAL existente.
  El alta local no elige ni crea conexiones clínicas por hospital.

Antes de aplicar: usar una base CENTRAL y una HOSPITAL **desechables**, PostgreSQL
15+, y nunca producción/staging/DB compartida. Desde un checkout limpio:

```powershell
composer install --no-interaction --prefer-dist
npm ci
Copy-Item .env.example .env
php artisan key:generate
php artisan jwt:secret
```

Configurar en `.env` las credenciales CENTRAL/HOSPITAL, `JWT_TTL=15`,
`TENANTS_REFRESH_TTL_SECONDS=604800`, `TENANTS_REFRESH_COOKIE_SECURE=true` en
HTTPS, `TENANTS_JWT_ISSUER`, `TENANTS_JWT_AUDIENCE`, mail y servicios requeridos.
No versionar `.env`, claves ni contraseñas. Aplicar migraciones únicamente a las
bases desechables:

```powershell
composer migrate:central
composer migrate:hospital
composer migrate:framework
php artisan migrate:rollback --database=central --path=database/migrations/central --step=2
composer migrate:central
```

La validación de arriba es el procedimiento previsto, **no evidencia ya
ejecutada**: el daemon Docker no está disponible y no hay `psql`/`pg_ctl` en PATH.
Javier debe revisar el orden/rollback de las dos migraciones nuevas y confirmar
el contrato de `hospitals`, el endpoint clínico y el aprovisionamiento antes de
activar hospitales.

## 5. Provisionamiento de la primera cuenta administrativa

No se usa seed con credenciales conocidas ni inserción manual de contraseñas.
Después de migrar CENTRAL, tener una red y un hospital provisionado y activo,
conectarse a la consola en una sesión administrativa protegida y ejecutar:

```powershell
php artisan auth:provision-first-superadmin
```

El comando solicita una referencia de operador/ticket, UUID del hospital, correo,
nombre, contraseña oculta y confirmación. Rechaza hospital inactivo, cualquier
membresía `superadmin` previa, correo duplicado y contraseña menor de 12
caracteres o sin mayúscula/minúscula/número/símbolo. Usuario, hash de contraseña,
membresía y evento de auditoría se escriben en una transacción; el evento no
contiene la contraseña. No pasar la contraseña como argumento, variable de
entorno ni comando de shell. Guardar la referencia/ticket y rotar las credenciales
por el canal corporativo después de primer acceso. Este comando no reemplaza un
flujo de alta/activación de usuarios.

## 6. Contrato RBAC y ámbito hospitalario

- Roles válidos exactos: `superadmin`, `admin_hospital`, `medico`,
  `enfermeria`, `recepcion`. No traducir PascalCase ni variantes.
- `rbac.asignar` administra membresías usuario-hospital;
  `rbac.roles.asignar` administra permisos del rol. No son intercambiables.
- `usuarios.ver`: `superadmin`/`admin_hospital`, solo hospital autorizado.
- `usuarios.desactivar`: `superadmin`/`admin_hospital`, revoca acceso local,
  no cambia el usuario CENTRAL ni afecta otro hospital.
- `tenants.crear`: solo `superadmin`; crea hospital inactivo pendiente de
  provisionamiento externo.
- `sesiones.revocar_ajenas`: `superadmin`/`admin_hospital`, solo sesiones ajenas
  del hospital autorizado.
- Sin identidad/roles de headers; sin comodines ni privilegio implícito por rol.
  `prescripciones.excepcion_alergia` permanece pendiente y denegado.

## 7. Pruebas y evidencia

- `php artisan test tests\Feature tests\Unit`: **53 passed, 428 assertions**.
  Incluye autorización HTTP, aislamiento, refresh/replay, bootstrap y rollback
  ante fallo de auditoría; los datos se crean con SQLite manual.
- `composer test`: **exit code 2**; 57 tests pasaron y 49 no pudieron iniciar por
  conexión rechazada a `127.0.0.1:55431` (`his_central_test`). Es bloqueo de
  servicio PostgreSQL, no evidencia de assertions fallidas del código. Docker
  CLI existe, pero el daemon `dockerDesktopLinuxEngine` no está disponible.
  PHP `pdo_pgsql`/`pgsql` sí están instalados; `psql` y `pg_ctl` no están en PATH
  y no hay listeners en 5432/55431/55433.
- `npm ci`: instalación reproducible desde `package-lock.json`; npm reportó 11
  advisories (1 low, 8 high, 2 critical). No se ejecutó `npm audit fix` ni se
  cambiaron dependencias.
- `npm run build`: **pass**; Vite compiló 96 módulos. Emitió una advertencia de
  deprecación Node (`module.register()`), no un error de build.
- `php artisan route:list --path=api`: **pass**, 11 rutas.
- `php -l` de la migración incremental de auditoría y comando CLI: **sin errores
  de sintaxis**. Esto no valida SQL PostgreSQL ni rollback.

No ejecutado: migraciones/rollback PostgreSQL, integración PostgreSQL, Redis,
correo/SSO, CI, staging ni pruebas de navegador/E2E. Las pruebas HTTP/SQLite,
build y sintaxis deben reportarse separadas de esos niveles.

Comandos reproducibles locales:

```powershell
composer test
npm run build
php artisan route:list --path=api
```

## 8. Configuraciones externas y responsable

| Pendiente | Responsable de resolver |
|---|---|
| PostgreSQL 15 desechable, rol de aplicación con permisos mínimos y validación migrar/rollback | Cindy/Javier |
| Aprovisionamiento de base/esquema clínico y conexión dinámica por hospital | Javier + responsable de infraestructura |
| SMTP y plantillas/tokens de activación y recuperación | Cindy + responsable de correo |
| MFA TOTP/WebAuthn, custodia de secretos y recuperación | Cindy |
| Redis para lockout progresivo, límites y expiración de estado | Cindy/infraestructura |
| HIBP/API de contraseñas comprometidas, comportamiento ante indisponibilidad | Cindy |
| Proveedor SAML/OIDC, mapeo JIT y política de asignación de roles | Cindy + responsable de identidad |
| Política de retención temporal/legal de auditoría | Cindy + responsable de cumplimiento |
| RS256/JWKS y rotación de claves según RN-04 | Cindy/infraestructura |
| E2E Vue, CI y staging; revisión de los resultados contra el commit final | Cindy |

## 9. Validación que debe ejecutar Cindy

1. Revisar commits y verificar que el diff de cada PR de la pila no exceda 400
   líneas. No incluir artefactos `vendor`, `node_modules` ni `public/build`.
2. Instalar desde locks en checkout limpio; confirmar que no existe enlace a
   `s17-rbac/vendor`.
3. Configurar bases desechables y validar migración/rollback CENTRAL con PG 15;
   revisar trigger, cadena de hashes, concurrencia y permisos del rol DB.
4. Ejecutar `composer test`, `npm run build`, `php artisan route:list --path=api`.
5. Ejecutar pruebas HTTP con permisos positivos/negativos y datos de dos
   hospitales; probar refresh caducado/replay/concurrente en PostgreSQL.
6. Aprovisionar primer superadmin interactivamente; comprobar auditoría y que la
   contraseña no aparece en logs ni filas sin hash.
7. Configurar correo, Redis y proveedor de identidad antes de validar activación,
   MFA, lockout o SSO. Ejecutar flujos UI/E2E y smoke de un módulo consumidor.
8. Reportar unitarias, HTTP/SQLite, PostgreSQL, E2E y staging por separado; no
   convertir una categoría en evidencia de otra.

## 10. Mensaje listo para Cindy

> Cindy: dejé en `auth-core` una entrega local apilada para revisar: login/me/logout,
> refresh opaco independiente con rotación, administración RBAC acotada al
> hospital, listado Vue y auditoría. No hice push ni abrí PR. El build y las
> pruebas HTTP locales pasan, pero usan SQLite; PostgreSQL/Docker no estaba
> disponible. MFA, lockout, activación/recuperación, SSO/JIT y E2E siguen
> pendientes. Revisa primero el contrato de esquema con Javier, luego integra la
> pila por orden y valida migraciones/rollback en PostgreSQL desechable antes de
> staging. El documento de entrega enumera comandos, límites y responsables.
