# Catálogo histórico de permisos y roles (Semana 16)

**Vigencia:** la conciliación transversal posterior está en
[catálogo común de módulos](../catalogo-modulos.md), junto con `config/rbac.php`.
Los conteos, exclusiones y matrices de esta página describen la entrega histórica.

**Propuesta vigente en `config/rbac.php` desde Semana 16 (2026-10-19), pendiente de
aprobación del equipo.** Cada permiso tiene un estado explícito:

- **Publicado:** su nombre aparece en un contrato de módulo.
- **Otorgado (activo):** está en `config/rbac.php` → `roles` y el middleware lo autoriza.
- **Pendiente:** está en `config/rbac.php` → `pending`, pero **no** se concede a ningún
  rol; el middleware lo deniega.
- **Fuera del catálogo:** publicado por otro módulo, sin acuerdo para incorporarlo.

Un comentario no cambia una autorización: solo `roles` alimenta a
`ConfiguredPermissions`; `pending` es documentación y una prueba verifica que sus
pares rol/permiso no estén activos.

## Dos operaciones distintas

| Permiso | Operación | Sujeto | Estado |
|---|---|---|---|
| `rbac.asignar` | Asignar o revocar una **membresía** (rol ↔ usuario) en un hospital | Usuario | Implementado por `AssignRole` del micro-HIS |
| `rbac.roles.asignar` | Cambiar el **conjunto de permisos de un rol** | Definición del rol | Contrato propuesto; endpoint no implementado |

No son alias ni sinónimos. `rbac.asignar` no autoriza editar la definición de un rol;
`rbac.roles.asignar` no autoriza asignar membresías. La conciliación anterior los
trató como alias y retiró `rbac.roles.asignar`; fue un error: se restauró con su
sujeto propio.

**Compatibilidad:** el motor existente conserva `rbac.asignar` sin cambios. El
endpoint `POST /api/v1/roles/{role}/permissions` se reserva `rbac.roles.asignar`.
No hay traducción automática entre ambos; el código que implemente el endpoint debe
exigir el segundo, no reutilizar el primero.

## Códigos de rol

Los códigos canónicos de integración son literales, en minúsculas y sensibles a
mayúsculas:

| Código canónico | Función |
|---|---|
| `superadmin` | Administración de plataforma |
| `admin_hospital` | Administración de RBAC del hospital |
| `medico` | Prescripción y atención clínica |
| `enfermeria` | Muestras y apoyo clínico |
| `recepcion` | Admisión y recepción de muestras |

`CentralRoles` devuelve `hospital_memberships.role_code` **sin normalizar**. Por eso
CENTRAL debe guardar exactamente esos códigos.

### Códigos heredados del micro-HIS

El micro-HIS conserva fixtures con PascalCase. Son datos de demostración/prueba y
**no** forman parte del contrato de integración:

| Código heredado (fixture) | Código canónico | Decisión |
|---|---|---|
| `SuperAdmin` | `superadmin` | Solo fixture; no se traduce en producción |
| `AdminHospital` | `admin_hospital` | Solo fixture; no se traduce en producción |
| `Medico` | `medico` | Solo fixture; no se traduce en producción |
| `Enfermeria` | `enfermeria` | Solo fixture; no se traduce en producción |
| `TecnicoLab`, `Bioquimico` | — | Sin rol canónico acordado; pendiente |
| `Reader` | — | Solo prueba |

Si CENTRAL ya almacena PascalCase, el dueño del dato (Cindy/Javier) debe normalizarlo
o el equipo debe acordar otro código; RBAC no agrega alias ni variantes. Una prueba
verifica que `Medico` y `AdminHospital` no reciben permisos.

## Permisos (26)

| Permiso | Descripción | Origen | Estado |
|---|---|---|---|
| `rbac.roles.ver` | Ver roles y sus permisos | Semana 5 | Activo |
| `rbac.asignar` | Asignar o revocar una membresía | Semana 5 / motor | Activo |
| `rbac.roles.asignar` | Cambiar los permisos de un rol | Semana 5 | Activo en la matriz; endpoint propuesto |
| `rbac.autorizar` | Solicitar una decisión de autorización | Semana 5 | Activo |
| `rbac.permisos.ver` | Ver el catálogo de permisos | Semana 5 | Activo |
| `rbac.denegaciones.ver` | Ver el historial de denegaciones | Semana 5 | Activo |
| `usuarios.ver` | Listar usuarios del hospital autorizado | Integración Cindy | Activo |
| `usuarios.desactivar` | Desactivar acceso en el hospital autorizado | Integración Cindy | Activo |
| `tenants.crear` | Crear hospital CENTRAL inactivo | Integración Cindy | Activo solo `superadmin` |
| `sesiones.revocar_ajenas` | Revocar sesiones ajenas del hospital autorizado | Integración Cindy | Activo |
| `prescripciones.ver` | Ver prescripciones | Semana 5 | Activo |
| `prescripciones.crear` | Crear prescripciones | Semana 5 | Activo |
| `prescripciones.firmar` | Firmar prescripciones | Semana 5 | Activo |
| `prescripciones.excepcion_alergia` | Registrar excepción de alergia | Semana 5 | **Pendiente** (SD-PRE-01) |
| `muestras.recibir` | Recibir muestras | Semana 5 | Activo |
| `muestras.ver` | Ver muestras | Semana 5 | Activo |
| `muestras.etiquetar` | Etiquetar muestras | Semana 5 | Activo |
| `laboratorio.catalogo.ver` | Consultar pruebas vigentes | Axel | Activo |
| `laboratorio.catalogo.administrar` | Registrar versiones de pruebas | Axel | Activo solo `superadmin`; propuesto para `admin_hospital` |
| `alergias.ver` | Ver el banner de alergias | Axel | Activo |
| `alergias.registrar` | Registrar alergias | Axel | Activo |
| `admision.crear` | Admitir a un paciente y asignarle cama | Axel | Activo |
| `camas.ver` | Ver las camas disponibles | Axel | Activo |
| `alertas.ver` | Ver la bandeja de alertas críticas | Axel | Activo; propuesto para `enfermeria` |
| `alertas.acusar` | Acusar recibo de una alerta crítica | Axel | Activo; propuesto para `enfermeria` |
| `alertas.cerrar` | Cerrar la alerta con la acción clínica | Axel | Activo |

## Matriz rol × permiso (solo concesiones activas)

`X` indica que el rol tiene el permiso activo. No hay comodines ni herencia. Un
permiso pendiente no tiene `X` en ninguna columna.

| Permiso | superadmin | admin_hospital | medico | enfermeria | recepcion |
|---|:---:|:---:|:---:|:---:|:---:|
| `rbac.roles.ver` | X | X |  |  |  |
| `rbac.asignar` | X | X |  |  |  |
| `rbac.roles.asignar` | X | X |  |  |  |
| `rbac.autorizar` | X |  | X | X | X |
| `rbac.permisos.ver` | X | X |  |  |  |
| `rbac.denegaciones.ver` | X | X |  |  |  |
| `usuarios.ver` | X | X |  |  |  |
| `usuarios.desactivar` | X | X |  |  |  |
| `tenants.crear` | X |  |  |  |  |
| `sesiones.revocar_ajenas` | X | X |  |  |  |
| `prescripciones.ver` | X |  | X | X |  |
| `prescripciones.crear` | X |  | X |  |  |
| `prescripciones.firmar` | X |  | X |  |  |
| `prescripciones.excepcion_alergia` |  |  |  |  |  |
| `muestras.recibir` | X |  |  | X | X |
| `muestras.ver` | X |  |  | X | X |
| `muestras.etiquetar` | X |  |  | X |  |
| `laboratorio.catalogo.ver` | X |  | X |  |  |
| `laboratorio.catalogo.administrar` | X |  |  |  |  |
| `alergias.ver` | X |  | X | X |  |
| `alergias.registrar` | X |  | X | X |  |
| `admision.crear` | X |  |  |  | X |
| `camas.ver` | X |  |  |  | X |
| `alertas.ver` | X |  | X |  |  |
| `alertas.acusar` | X |  | X |  |  |
| `alertas.cerrar` | X |  | X |  |  |

Conteo activo: `superadmin` 25, `admin_hospital` 8, `medico` 10, `enfermeria` 7,
`recepcion` 5. `superadmin` no recibe el permiso pendiente
`prescripciones.excepcion_alergia`, por eso tiene 25 de los 26 publicados.

## Justificación por rol (propuesta)

- **superadmin:** administra la plataforma con una lista explícita; no usa `*` ni
  recibe permisos por su nombre. Excluye los permisos pendientes de aprobación.
- **admin_hospital:** administra membresías y definiciones de roles de su hospital.
  No recibe permisos clínicos: administrar RBAC no debe permitir prescribir ni cerrar
  alertas.
- **medico:** prescribe, firma, consulta el catálogo de pruebas, ve y registra
  alergias y atiende alertas críticas. La excepción de alergia queda pendiente.
- **enfermeria:** ve prescripciones, recibe/etiqueta/consulta muestras y ve/registra
  alergias. No prescribe ni firma.
- **recepcion:** admite pacientes, ve camas y recibe/consulta muestras. No accede a
  prescripciones ni alergias.

## Pendientes de aprobación

| Permiso | Roles propuestos | Motivo | Efecto actual |
|---|---|---|---|
| `prescripciones.excepcion_alergia` | `medico`, `superadmin` | Política de severidad SD-PRE-01 sin validación clínica | Denegado para todos |
| `laboratorio.catalogo.administrar` | `admin_hospital` | No hay rol operativo de jefatura de laboratorio | Denegado para `admin_hospital`; `superadmin` sí lo tiene |
| `alertas.ver` | `enfermeria` | El evento de Axel dirige la alerta al médico con asignación activa | Denegado para `enfermeria` |
| `alertas.acusar` | `enfermeria` | Falta acuerdo sobre el acuse por enfermería | Denegado para `enfermeria` |

## Publicados fuera de esta entrega

Estos nombres aparecen en documentos de módulos, pero no están en el catálogo ni se
otorgan: `alergias.resolver`, `muestras.rechazar`, `admisiones.registrar`,
`admisiones.ver`, `camas.asignar`, `notas_soap.*`, `diagnosticos.asociar`,
`signos_vitales.*`, `medicos.*`.

`admisiones.registrar`/`admisiones.ver`/`camas.asignar` son variantes de
`admision.crear`/`camas.ver` en los aportes de Axel; la reconciliación con su dueño
sigue pendiente. No se agregaron alias.

## Permisos RBAC por endpoint

| Permiso | Endpoint |
|---|---|
| `rbac.roles.ver` | `GET /api/v1/roles`, `GET /api/v1/roles/{role}` |
| `rbac.roles.asignar` | `POST /api/v1/roles/{role}/permissions` |
| `rbac.autorizar` | `POST /api/v1/authorize` |
| `rbac.permisos.ver` | `GET /api/v1/permissions` |
| `rbac.denegaciones.ver` | `GET /api/v1/denials` |

`POST /api/v1/roles/{role}/permissions` sigue **propuesto**. `rbac.asignar` pertenece
al caso de uso de membresías del micro-HIS y no se expone en este contrato HTTP.

## Reglas

- Las reglas del dominio se combinan con **AND**: el permiso se concede solo si todas
  lo permiten; sin reglas, se deniega por defecto.
- El motor genera el `trace_id` al denegar y mantiene la **falla segura**: si la
  bitácora falla, la decisión sigue siendo denegar.
- Sin comodines, sin herencia entre roles, sin permisos implícitos y sin concesión
  automática a `superadmin`.
- `pending` solo documenta propuestas; no se fusiona con `roles` ni se interpreta
  como concesión.
- La asignación real de roles a usuarios depende de Usuarios/Auth (Cindy); RBAC no
  escribe membresías en CENTRAL.
