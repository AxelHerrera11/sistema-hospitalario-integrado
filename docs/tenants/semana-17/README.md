# Semana 17 — Seguridad y despliegue final

**Módulo:** Gestión de usuarios, tenants y endurecimiento de autenticación (ASII-25).

**Tarea:** matriz de amenazas, pruebas de permisos y de protección de datos sensibles, corrección de lo encontrado y
bitácora del despliegue final.

## Entregables

| Archivo | Contenido |
|---|---|
| [`matriz-amenazas.md`](matriz-amenazas.md) | 14 amenazas STRIDE de login, MFA, tokens, sesiones, administración y tenant |
| [`bitacora-despliegue.md`](bitacora-despliegue.md) | Despliegue on-premise del micro-HIS: secreto, zona horaria, rotación y rollback |
| [`evidencias/01-pruebas-seguridad.txt`](evidencias/01-pruebas-seguridad.txt) | Pruebas de identidad, roles, aislamiento, sesiones y caché |
| [`evidencias/02-ensayo-despliegue.txt`](evidencias/02-ensayo-despliegue.txt) | Salida del ensayo de despliegue con la hora de cada paso |

## Pruebas de permisos y datos sensibles (2026-10-29)

| Grupo | Resultado |
|---|---|
| Micro-HIS: MFA, rotación de refresh, logout, aislamiento entre hospitales, bloqueo y **caché** (prueba nueva) | 7/7 |
| Laravel (#501–#515): roles, administración, aislamiento, refresh y revocación | 27/27 |
| Todas las pruebas de Tenants (`tests/Feature/Tenants` y `tests/Unit/Tenants`) | 107 pruebas, 540 aserciones |

## Hallazgo corregido

**H-TEN-16-01:** las respuestas del micro-HIS con tokens no enviaban `Cache-Control: no-store`. Corregido en
`AuthResponseTransformer::json()` (agrega `no-store`, `Pragma: no-cache` y `nosniff` a toda respuesta). La prueba
«Token, session and error responses are never cacheable» revisa login, refresh, `me`, 401 y 404: falla sin la
corrección y pasa con ella. El ensayo de despliegue lo confirma en el servidor real (V1 y V7).

## Archivos cambiados

`app/Modules/Tenants/Presentation/Transformers/AuthResponseTransformer.php`,
`tests/Feature/Tenants/MicroHisSmokeTest.php` y esta carpeta.

## Recomendaciones (código de Marielos, sin cambios)

`no-store` en las respuestas de `/api/v1/auth/*` de Laravel (T-11) y 503 explícito cuando CENTRAL no responde (T-13).
