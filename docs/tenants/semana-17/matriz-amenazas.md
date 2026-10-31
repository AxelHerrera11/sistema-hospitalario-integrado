# Matriz de amenazas — Usuarios, tenants y autenticación

Método: STRIDE sobre login, MFA, emisión y rotación de tokens, sesiones, administración de usuarios y selección de
hospital. Cubre el micro-HIS de Tenants (Cindy) y la autenticación Laravel integrada en #501–#515 (Marielos), sin
modificar esta última. Evidencia: [`evidencias/01-pruebas-seguridad.txt`](evidencias/01-pruebas-seguridad.txt) y
[`evidencias/02-ensayo-despliegue.txt`](evidencias/02-ensayo-despliegue.txt).

P × I de 1 a 3 antes del control. Residual: Bajo / Medio / Alto.

| Id | STRIDE | Amenaza | P×I | Control implementado | Prueba | Residual |
|---|---|---|---|---|---|---|
| T-01 | Suplantación | Adivinar contraseñas | 3×3 | Bloqueo de la membresía tras 5 fallos (423); mensaje igual para correo inexistente | «Failed logins lock…»; smoke V-07 de la Semana 16 | Bajo |
| T-02 | Suplantación | Robo de contraseña de una cuenta administrativa | 2×3 | MFA TOTP obligatorio para `admin_hospital`; desafío de un solo uso | «Admin needs totp and each challenge is single use» | Bajo |
| T-03 | Suplantación | Reutilizar un refresh token robado | 2×3 | Rotación en cada uso; reutilizar uno viejo revoca la familia | «Refresh rotates…», «Reusing a rotated refresh token revokes the session» | Bajo |
| T-04 | Suplantación | Token válido después del logout o la desactivación | 2×3 | El access token vale solo mientras vive su sesión | «Logout invalidates…», «Deactivation revokes…» | Bajo |
| T-05 | Suplantación | Tokens firmados con un secreto filtrado | 1×3 | Secreto de ≥ 32 bytes obligatorio; al rotarlo, los tokens anteriores dejan de valer | Ensayo: secreto corto rechazado; token viejo 401 tras rotar | Bajo |
| T-06 | Manipulación | Usar el token de un hospital en otro | 2×3 | El tenant va en el token y en la membresía | «Token of hospital a cannot be used against hospital b» | Bajo |
| T-07 | Elevación | `admin_hospital` se da `superadmin` o cambia sus propios roles | 2×3 | Reglas de asignación por rol | «Admin hospital cannot grant superadmin», «Admin cannot change own roles» | Bajo |
| T-08 | Elevación | Rol sin permiso usa la administración | 2×3 | RBAC en cada operación administrativa | «Non admin role cannot use administrative capabilities» | Bajo |
| T-09 | Repudio | Cambios de roles o sesiones sin rastro | 2×3 | Auditoría con cadena de hashes; denegaciones RBAC persistidas | «Rbac denial is persisted…»; `verifyHashChain()` | Bajo |
| T-10 | Divulgación | Tokens o datos de sesión guardados en cachés | 2×3 | **Micro-HIS:** `Cache-Control: no-store`, `Pragma: no-cache`, `nosniff` (corregido, H-TEN-16-01) | «Token, session and error responses are never cacheable»; ensayo V1 | Bajo en el micro-HIS |
| T-11 | Divulgación | Lo mismo en Laravel | 2×3 | Laravel envía por defecto `no-cache, private`, sin `no-store` | Comprobación al final de [01](evidencias/01-pruebas-seguridad.txt) | **Medio**: recomendar `no-store` en `/auth/*` (código de Marielos) |
| T-12 | Divulgación | Listar usuarios de otro hospital | 2×3 | Listado paginado y filtrado por hospital | «Hospital user listing is paged and isolated…» | Bajo |
| T-13 | Denegación de servicio | Con CENTRAL caída, `/auth/me` responde 500 | 2×2 | `APP_DEBUG=false` evita filtrar detalles | Ensayo de la guía (módulo QA, Semana 17) | **Medio**: devolver 503 explícito |
| T-14 | Manipulación | Fechas ambiguas por la zona del servidor | 1×2 | Despliegue con `date.timezone=UTC` (H-TEN-16-02) | Ensayo P3 | Bajo |

## Hallazgo corregido esta semana

**H-TEN-16-01 (T-10):** las respuestas del micro-HIS, incluidas las que llevan `access_token` y `refresh_token`, no
tenían cabeceras de caché. Corrección en `AuthResponseTransformer::json()` (todas las respuestas pasan por ahí) y
prueba nueva en `MicroHisSmokeTest`, que falla sin la corrección y pasa con ella.

## Recomendaciones para Marielos (sin cambios en su código)

1. T-11: agregar `Cache-Control: no-store` a las respuestas de `/api/v1/auth/*` en Laravel.
2. T-13: que el middleware `tenant` responda 503 cuando CENTRAL no está disponible.
