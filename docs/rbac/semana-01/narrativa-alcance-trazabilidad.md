# Narrativa de alcance y trazabilidad — RBAC

## Propósito

Controlar **quién puede hacer qué, en qué hospital y hasta cuándo**. El módulo asigna roles a los usuarios por hospital, decide en cada operación si el rol del usuario incluye el permiso que la ruta exige y, cuando la respuesta es no, deniega dejando una traza que permite explicar después qué pasó.

## Alcance funcional

**Incluido**

- Catálogo de roles del HIS (por ejemplo `SuperAdmin`, `AdminHospital`, `Medico`, `Enfermeria`, `TecnicoLab`, `Bioquimico`) y de permisos con la convención `modulo.accion` (`prescripciones.crear`, `muestras.recibir`, `rbac.roles.asignar`...).
- Asignación y revocación de un rol a un usuario dentro de un hospital, con vigencia (`valid_from` / `valid_to`) sobre `hospital_memberships`.
- Regla anti-escalamiento: un administrador solo asigna roles en su propio hospital y nunca un rol con más privilegios que el suyo. Solo el super administrador gestiona roles de administración.
- Middleware `permission:` para proteger rutas API y guard de Vue Router basado en `meta.permission`.
- Consulta de los permisos efectivos del usuario para armar el menú y las rutas del frontend.
- Respuesta de denegación uniforme: `403` con `{ "code": "RBAC_DENIED", "message": "...", "trace_id": "..." }`, sin revelar qué permiso faltó.
- Registro de cada asignación, revocación y denegación en la bitácora de auditoría.

**Fuera de alcance**

- Inicio de sesión, emisión y refresco del JWT y alta de usuarios: módulo *Gestión de usuarios, tenants y endurecimiento de autenticación*.
- Pantalla de consulta y filtros de la bitácora: módulo *Gobernanza y auditoría de movimientos del sistema*.
- Reglas de negocio propias de cada módulo clínico (por ejemplo, que el médico esté asignado al episodio). RBAC responde "¿tiene el permiso?"; cada módulo responde "¿tiene sentido clínico?".

## Supuestos y restricciones

- Un usuario puede tener varias membresías y roles, pero la autorización se evalúa siempre contra el hospital del header `X-Tenant-ID`.
- Una membresía `revoked`, `inactive` o vencida (`valid_to` en el pasado) no otorga permisos.
- La auditoría guarda metadatos (usuario, tenant, ruta, permiso requerido, motivo, IP, fecha y hora), **nunca contenido clínico**.
- **Falla segura:** si no se puede resolver el rol o escribir la traza de una denegación, el acceso sigue denegado. Si no se puede auditar una asignación, la asignación no se guarda.

## Trazabilidad entre los diagramas

| UC | Objetivo | Actividad (decisión o paso) | Secuencia (mensajes) |
|---|---|---|---|
| UC-RBAC-01 | Asignar rol a usuario en un hospital | Partición A, rama *Asignar* → "Crear membresía con role_code" | 1–2, 10–14, 22–31 |
| UC-RBAC-02 | Revocar rol o fijar vencimiento | Partición A, rama *Revocar* → "Marcar membresía como revoked" | Igual que UC-RBAC-01 con `DELETE /rbac/users/{id}/roles/{role}` |
| UC-RBAC-03 | Ejecutar operación protegida | Partición B, "Usuario ejecuta una operación protegida" | 32–37 |
| UC-RBAC-04 | Autorizar (token, tenant, rol, permiso) | Decisiones "¿JWT válido?", "¿Membresía activa…?", "¿El rol incluye el permiso…?" | 3–6, 35 |
| UC-RBAC-05 | Denegar operación con traza | Nodo "Denegar (UC-RBAC-05)" → 403 RBAC_DENIED + trace_id | 7–9, 15–17, 38–41 |
| UC-RBAC-06 | Registrar evento de auditoría | "Registrar evento de auditoría…" y "¿Se registró en la bitácora?" | 7–8, 16, 23–27, 38–39 |
| UC-RBAC-07 | Validar privilegio del asignador | Decisión "¿Tiene el permiso… y el rol no supera su privilegio?" | 13, 15–17 |
| UC-RBAC-08 | Consultar permisos efectivos | Base de la decisión del guard en el frontend | 33 |

## Flujo y resultados

1. **Solicitud:** el frontend envía `Authorization: Bearer <JWT>` y `X-Tenant-ID`.
2. **Autenticación (UC-RBAC-04):** sin token o con token inválido se responde `401`.
3. **Tenant y membresía (UC-RBAC-04):** si el usuario no tiene una membresía activa y vigente en ese hospital, se deniega (UC-RBAC-05) con `403`.
4. **Permiso (UC-RBAC-04):** si el rol no incluye el permiso de la ruta, se deniega con `403` y `trace_id`.
5. **Asignación (UC-RBAC-01/07):** además del permiso, el rol a asignar no puede superar el privilegio del administrador (`403`). El destino debe existir y estar activo (`422`) y no tener ya ese rol vigente (`409`).
6. **Persistencia y traza (UC-RBAC-06):** la membresía y el evento de auditoría se guardan en la misma transacción. Si la auditoría falla, se revierte (`500`).
7. **Efecto inmediato:** tras asignar o revocar se invalida la caché de permisos del usuario, para que el cambio aplique en su siguiente solicitud.
8. **Resultado:** `201` asignado, `200` revocado, `2xx` del módulo cuando la operación se autoriza, o `403 RBAC_DENIED` con `trace_id` cuando se deniega.

## Dependencias con otros módulos

- **Depende de:** *Gestión de usuarios y autenticación* (identidad y JWT). Mientras ese módulo se estabiliza, las pruebas usan un usuario simulado detrás de una interfaz `RoleResolver`.
- **Dependen de RBAC:** todos los módulos con endpoints (usan `permission:`), el *Dashboard base* (menú por rol) y *Gobernanza* (recibe las denegaciones).

## Archivos relacionados

- [Casos de uso](./01-casos-de-uso.svg)
- [Actividades](./02-actividad.svg)
- [Secuencia](./03-secuencia.svg)
- [README del módulo](./README.md)
