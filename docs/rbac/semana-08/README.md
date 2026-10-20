# Semana 8 | ASII-02: UX de roles, permisos y protección de rutas

**Estudiante:** LÓPEZ FAJARDO, MARÍA DE LOS ÁNGELES

## Objetivo

Diseñar el flujo UX de «asignación de rol, autorización de una operación y denegación trazable» por sus roles autorizados, con estados vacío/carga/éxito, errores recuperables y protección de datos.

## Flujo por rol

1. El **admin_hospital** abre «Asignar rol», busca al usuario dentro de su hospital (tenant) y elige el rol.
2. El sistema valida el permiso `rbac.asignar`, los tipos y los campos no vacíos antes de habilitar guardar.
3. Si el asignador no supera el privilegio del rol, se produce una **denegación trazable** (`403` + `trace_id`).
4. El sistema confirma la asignación y enlaza la bitácora; cualquier rol puede **solicitar autorización** de una operación y ver su resultado.

## Estados, errores y ayudas

| Estado | Mensaje o ayuda |
|---|---|
| Carga | Mostrar usuario, hospital y catálogo de roles permitidos. |
| Campo inválido | Indicar tipo/obligatorio sin borrar lo capturado. |
| Denegado (403) | Mostrar motivo y `trace_id`; permitir reintentar solo al rol autorizado. |
| Rol ya asignado (409) | Informar que la membresía ya existe y ofrecer ver detalle. |
| Guardado (201) | Confirmar el rol asignado y enlace a la auditoría. |

## 1. Asignar rol (inicio y decisión)

```text
[Asignar rol]  Hospital: [H1 v]
Usuario: [buscar...        ]  Rol: [Enfermeria v]
Permiso requerido: rbac.asignar
[Validar] [Asignar]
```

Anotación: el admin solo ve usuarios y roles de su tenant; el botón «Asignar» queda deshabilitado hasta validar permiso, tipos y no vacíos.

## 2. Estado vacío y ayuda contextual

```text
[Asignar rol]
No hay usuarios que coincidan con el filtro en este hospital.
[Limpiar filtros] [Actualizar]
Ayuda: verifique el hospital (tenant) y el nombre del usuario.
```

Anotación: no se muestran datos de otros tenants; se ofrece una acción clara para continuar.

## 3. Carga y validación

```text
[Cargando roles permitidos...]
Usuario: [med1]  Rol: [SuperAdmin]
[Validando privilegio...]   [Cancelar]
```

Anotación: mientras carga la regla se bloquea el envío duplicado; la ayuda explica que el rol no puede superar el privilegio del asignador.

## 4. Confirmación y denegación recuperable

```text
[Confirmar asignacion]
Usuario: [med1]  Rol: [SuperAdmin]  Hospital: [H1]
[Cancelar] [Confirmar]

[DENEGADO 403] No autorizado para asignar este rol.
trace_id: a1b2c3d4e5f6
[Reintentar con otro rol] [Ver auditoria]
```

Anotación: la confirmación advierte que habrá trazabilidad; la denegación conserva lo capturado y muestra el `trace_id` para auditar.

## 5. Éxito y error recuperable

```text
[ASIGNADO 201] Rol Enfermeria asignado a enf1 en H1.
[Ver auditoria] [Asignar otro]

[ERROR RECUPERABLE 500] No se pudo registrar.
La asignación no se guardó (rollback). Lo capturado permanece.
[Reintentar] [Ver causa]
```

Anotación: el éxito enlaza la auditoría; el error mantiene usuario, rol y hospital sin duplicar la solicitud, y nada se guarda si falla la persistencia.

## 6. Autorizar una operación (permitido / denegado)

```text
[Autorizar operacion]
Usuario: [med1]  Accion: [prescripciones.crear]  Hospital: [H1]
[Solicitar autorizacion]

[PERMITIDO 200] allowed: true
[Continuar]

[DENEGADO 200] allowed: false  motivo: permiso_faltante
trace_id: a1b2c3d4e5f6   [Ver auditoria]
```

Anotación: la decisión usa el permiso `rbac.autorizar`; si se permite deja continuar, si se deniega muestra motivo y `trace_id` sin revelar datos de otros tenants.

## Reglas de interacción

- El botón «Asignar» permanece deshabilitado si falta usuario, rol u hospital, o si el permiso no está presente.
- Los errores aparecen junto al campo y se mantienen hasta corregirse.
- La denegación muestra el `trace_id` y no revela datos de otros tenants.
- La bitácora de denegaciones es de solo lectura para quien no tenga `rbac.denegaciones.ver`.
- Se protegen usuario, rol, hospital y motivo mediante tenant, rol y mínimo privilegio.
- La trazabilidad registra asignador, usuario, rol, hospital, fecha y `trace_id` de la denegación.

## Evidencia

- Flujo UX: `fuentes/01-user-flow.mmd`.
- Seis wireframes anotados con inicio, vacío, carga, confirmación/denegación, éxito/error recuperable y autorización de operación.
- Issue relacionado: `Refs #4`.
- PR menor de 400 líneas.
