# Catálogo común de integración RBAC

Base revisada: develop f89bd05 y los 200 PR abiertos, 20 de octubre de 2026. Este documento y
`config/rbac.php` sustituyen las matrices históricas como referencia de integración.
Catálogo: 77 permisos; 8 roles canónicos. No hay comodines ni herencia.

## Matriz activa

S = superadmin; A = admin_hospital; M = medico; E = enfermeria;
R = recepcion; T = tecnico_laboratorio; B = bioquimico; J = jefatura_medica. «—» significa denegado.

| Permiso | S | A | M | E | R | T | B | J |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| `citas.ver` | Sí | Sí | Sí | Sí | Sí | — | — | — |
| `citas.crear` | Sí | Sí | — | — | Sí | — | — | — |
| `citas.confirmar` | Sí | Sí | Sí | — | Sí | — | — | — |
| `citas.cancelar` | Sí | Sí | Sí | — | Sí | — | — | — |
| `reportes.ver` | Sí | Sí | Sí | — | — | Sí | Sí | — |
| `reportes.exportar` | Sí | Sí | — | — | — | — | — | — |
| `documentacion.ver` | Sí | Sí | Sí | Sí | Sí | Sí | Sí | Sí |
| `admision.ver` | Sí | — | Sí | Sí | Sí | — | — | — |
| `expediente.ver` | Sí | — | Sí | Sí | — | — | — | — |
| `expediente.abrir` | Sí | — | Sí | — | — | — | — | — |
| `expediente.asignar` | Sí | Sí | — | — | — | — | — | Sí |
| `qa.ejecuciones.ver` | Sí | Sí | — | — | — | — | — | — |
| `qa.ejecuciones.registrar` | Sí | Sí | — | — | — | — | — | — |
| `qa.gates.evaluar` | Sí | Sí | — | — | — | — | — | — |
| `medicamentos.ver` | Sí | Sí | Sí | Sí | — | — | — | — |
| `medicamentos.registrar` | — | Sí | — | — | — | — | — | — |
| `medicamentos.cambiar_vigencia` | — | Sí | — | — | — | — | — | — |
| `laboratorio.ver` | Sí | Sí | Sí | — | — | Sí | Sí | — |
| `auditoria.ver` | Sí | — | — | — | — | — | — | — |
| `signos_vitales.corregir` | Sí | — | Sí | — | — | — | — | — |
| `alertas.supervisar` | Sí | — | — | — | — | — | — | Sí |
| `admisiones.registrar` | Sí | — | — | — | Sí | — | — | — |
| `admisiones.ver` | Sí | — | Sí | Sí | Sí | — | — | — |
| `camas.asignar` | Sí | — | — | — | Sí | — | — | — |
| `alergias.resolver` | Sí | — | Sí | — | — | — | — | — |
| `muestras.rechazar` | Sí | — | — | — | — | Sí | — | — |
| `notas_soap.ver` | Sí | — | Sí | Sí | — | — | — | — |
| `notas_soap.crear` | Sí | — | Sí | — | — | — | — | — |
| `notas_soap.asociar_diagnostico` | Sí | — | Sí | — | — | — | — | — |
| `notas_soap.firmar` | Sí | — | Sí | — | — | — | — | — |
| `notas_soap.corregir` | Sí | — | Sí | — | — | — | — | — |
| `signos_vitales.ver` | Sí | — | Sí | Sí | — | — | — | — |
| `signos_vitales.registrar` | Sí | — | Sí | — | — | — | — | — |
| `ocupacion.ver` | Sí | Sí | — | Sí | Sí | — | — | — |
| `laboratorio.ordenes.crear` | Sí | — | Sí | — | — | — | — | — |
| `laboratorio.ordenes.ver` | Sí | — | Sí | — | — | Sí | Sí | — |
| `laboratorio.ordenes.cancelar` | Sí | — | Sí | — | — | — | — | — |
| `laboratorio.ordenes.actualizar` | — | — | — | — | — | — | — | — |
| `lab-results.read` | Sí | — | — | — | — | Sí | Sí | — |
| `lab-results.create` | Sí | — | — | — | — | Sí | — | — |
| `lab-results.correct` | Sí | — | — | — | — | Sí | — | — |
| `lab-results.validate` | Sí | — | — | — | — | — | Sí | — |
| `notas_soap.transferir_autoria` | — | — | — | — | — | — | — | — |
| `medicos.registrar` | Sí | Sí | — | — | — | — | — | — |
| `medicos.asociar_especialidad` | Sí | Sí | — | — | — | — | — | — |
| `medicos.ver_disponibilidad` | Sí | Sí | Sí | — | Sí | — | — | — |
| `pacientes.ver` | Sí | — | Sí | Sí | Sí | — | — | — |
| `pacientes.registrar` | Sí | — | — | — | Sí | — | — | — |
| `pacientes.editar` | Sí | — | — | — | Sí | — | — | — |
| `rbac.roles.ver` | Sí | Sí | — | — | — | — | — | — |
| `rbac.asignar` | Sí | Sí | — | — | — | — | — | — |
| `rbac.roles.asignar` | Sí | Sí | — | — | — | — | — | — |
| `rbac.autorizar` | Sí | — | Sí | Sí | Sí | Sí | Sí | — |
| `rbac.permisos.ver` | Sí | Sí | — | — | — | — | — | — |
| `rbac.denegaciones.ver` | Sí | Sí | — | — | — | — | — | — |
| `usuarios.ver` | Sí | Sí | — | — | — | — | — | — |
| `usuarios.crear` | Sí | Sí | — | — | — | — | — | — |
| `usuarios.desactivar` | Sí | Sí | — | — | — | — | — | — |
| `tenants.crear` | Sí | — | — | — | — | — | — | — |
| `sesiones.revocar_ajenas` | Sí | Sí | — | — | — | — | — | — |
| `prescripciones.ver` | Sí | — | Sí | Sí | — | — | — | — |
| `prescripciones.crear` | Sí | — | Sí | — | — | — | — | — |
| `prescripciones.firmar` | Sí | — | Sí | — | — | — | — | — |
| `prescripciones.excepcion_alergia` | — | — | — | — | — | — | — | — |
| `muestras.recibir` | Sí | — | — | Sí | Sí | Sí | — | — |
| `muestras.ver` | Sí | — | — | Sí | Sí | Sí | Sí | — |
| `muestras.etiquetar` | Sí | — | — | Sí | — | Sí | — | — |
| `laboratorio.catalogo.ver` | Sí | — | Sí | — | — | Sí | Sí | — |
| `laboratorio.catalogo.administrar` | Sí | — | — | — | — | — | — | — |
| `alergias.ver` | Sí | — | Sí | Sí | — | — | — | — |
| `alergias.registrar` | Sí | — | Sí | Sí | — | — | — | — |
| `admision.crear` | Sí | — | — | — | Sí | — | — | — |
| `camas.ver` | Sí | — | — | — | Sí | — | — | — |
| `alertas.ver` | Sí | — | Sí | — | — | — | — | Sí |
| `alertas.acusar` | Sí | — | Sí | — | — | — | — | Sí |
| `alertas.cerrar` | Sí | — | Sí | — | — | — | — | — |
| `traslados.registrar` | — | — | — | Sí | — | — | — | — |
| `altas.registrar` | — | — | Sí | — | — | — | — | — |
| `traslados.ver` | — | — | Sí | Sí | — | — | — | — |

## Contratos conciliados y decisiones

| Módulo/fuente | Permisos y alcance |
| --- | --- |
| Pacientes, `docs/asii-03-pacientes` | pacientes.ver/registrar/editar; conserva la integración #567 |
| Médicos, `docs/medicos-especialidades/semana-05/contrato-api.md` | medicos.registrar/asociar_especialidad/ver_disponibilidad; matriz SD-MED-11 |
| Admisión, `docs/admision-camas/semana-01` | admisiones.registrar/ver y camas.asignar; mantiene admision.crear y camas.ver como operaciones distintas |
| Alergias, `docs/alergias/semana-01` | alergias.ver/registrar/resolver; resolver exige además médico tratante |
| SOAP, `docs/notas-soap/semana-05/contrato-api.md` | notas_soap.ver/crear/asociar_diagnostico/firmar/corregir; transferencia de autoría reservada |
| Signos, `docs/signos-vitales/semana-01` | signos_vitales.ver/registrar; registrar exige médico asignado, enfermería solo consulta |
| Ocupación, `docs/ocupacion-hospitalaria/contrato-api.md` | ocupacion.ver; administración, recepción y enfermería, sin mutar camas |
| Órdenes, `docs/ordenes-laboratorio/contrato-api.md` | laboratorio.ordenes.crear/ver/cancelar; médico muta, laboratorio consulta |
| Catálogo de pruebas, `docs/catalogo-pruebas-lab` | conserva laboratorio.catalogo.ver y administración pendiente según pending |
| Catálogo de medicamentos, #586–#604 | medicamentos.ver activo; medicamentos.registrar y medicamentos.cambiar_vigencia activos exclusivamente para admin_hospital; los otros siete roles siguen denegados |
| Muestras, `docs/recepcion-muestras/semana-01` | muestras.recibir/ver/etiquetar/rechazar; técnico opera, bioquímico consulta |
| Resultados, `docs/ingreso-resultados/semana-05/contrato-api.md` | lab-results.create/correct/read; captura separada de validación |
| Validación, `docs/validacion-resultados/semana-05/contrato-api.md` | lab-results.validate/read; bioquímico valida, técnico no valida |
| NativePHP, `docs/nativephp/semana-05/contrato-api.md` | consume lab-results.read/correct; no recibe permisos por ser cliente |
| Traslados y altas, `docs/traslados-altas` | traslados.registrar y traslados.ver activos para enfermería; altas.registrar y traslados.ver activos para médico. Ver la sección de activación |
| Prescripciones y alertas | conserva permisos activos y la política pendiente de excepción de alergia |
| Usuarios/tenants/RBAC | conserva usuarios.*, tenants.crear, sesiones.revocar_ajenas y operaciones RBAC existentes |

Los guiones de `lab-results.*` son literales del contrato, no alias de `results.*`.
El middleware admite esos guiones y sigue rechazando comodines y nombres inválidos.
`diagnosticos.asociar` queda sustituido por `notas_soap.asociar_diagnostico` según
el contrato SOAP vigente; `rbac.permisos.asignar` es nomenclatura histórica:
la operación de permisos de rol usa `rbac.roles.asignar`, y membresías `rbac.asignar`.
`prescripciones.anular` aparece como ejemplo OCP, no como contrato implementado.
No se concede automáticamente ninguno de esos nombres antiguos.

## Uso común y aislamiento (incluido H-01 de Médicos)

Aplicar `tenant`, `jwt.auth` y `rbac:<permiso literal>` a cada ruta del módulo.
Ejemplo: POST /api/v1/doctors exige rbac:medicos.registrar; POST
/api/v1/doctors/{id}/specialties exige rbac:medicos.asociar_especialidad y GET
/api/v1/availability exige rbac:medicos.ver_disponibilidad.
No duplicar la matriz ni crear un comprobador en memoria para producción.
La identidad proviene de getAuthIdentifier() y el hospital de getKey() del tenant
resuelto por el servidor, no de X-User-ID ni de roles enviados por el cliente.
CentralRoles filtra usuario, hospital y membresía activos y vigentes, incluso para
superadmin. Cambiar X-Tenant-ID no transfiere una concesión de un hospital a otro.
Header ausente: 400 TENANT_REQUIRED; discrepancia: 403 TENANT_MISMATCH;
sin permiso: 403 RBAC_DENIED; contexto ausente: 503 RBAC_CONTEXT_UNAVAILABLE.
Los middleware anteriores de autenticación/tenant pueden rechazar antes.
El módulo debe seleccionar su conexión HOSPITAL con ese mismo contexto y aplicar
las reglas de recurso: médico tratante, autoría, pertenencia del expediente, etc.
RBAC no impone «solo mi disponibilidad» ni sustituye esas comprobaciones.
PdoDoctorStore recibe un PDO; su integrador debe pasar la conexión del hospital
validado. H-01 no se cierra hasta probar el flujo real con dos hospitales y un
usuario con permisos en ambos, sin lecturas ni escrituras cruzadas.

## Coordinación con Cindy y límites de cobertura

Los códigos coordinados con Cindy son tecnico_laboratorio, bioquimico y jefatura_medica. Se incorporan también a
CanonicalRole de la entrega #501 de Cindy, sin traducir PascalCase ni cambiar BD.
La administración existente valida CanonicalRole antes de asignar o revocar;
la asignación no crea permisos fuera de la matriz y revoca las sesiones afectadas.
Los perfiles se asignan por hospital; no se asignan ambos a un usuario de forma
automática. Si el negocio requiere impedir toda combinación de ambos perfiles,
esa política adicional debe acordarse e implementarse en administración.
Prescripciones.excepcion_alergia y notas_soap.transferir_autoria siguen reservados:
no se activan por publicar sus nombres. Los restantes pares pending son propuestas
por rol; consulte config/rbac.php para las concesiones exactas que siguen denegadas.

Se revisaron los 30 apartados del README y los 200 PR abiertos de la captura de
auditoría. Las 200 referencias estaban disponibles. Se inspeccionaron sus archivos
de texto modificados (MD, PHP, YAML/YML, JS/TS, Vue, HTML y JSON), sin depender de una
lista fija de módulos para extraer los literales. Se buscaron tanto
menciones de permisos como literales en tablas, código y navegación. Los eventos
de auditoría, campos SQL, nombres de archivos y variables no se trataron como permisos.
La evidencia de los contratos pendientes queda fijada por PR, SHA y ruta en
`tests/Fixtures/Rbac/pending-contracts.json`: 206 fuentes por PR, SHA y ruta,
comprobadas contra el catálogo. El registro de las 200 referencias revisadas se
entrega en el PR #687 para respetar el límite de 400 líneas. Los tests comprueban
también que cada concesión activa o pendiente pertenezca al catálogo, que ningún
par permiso/rol pendiente esté activo y que esta matriz coincida con la configuración.

## Ampliación de contratos pendientes

| Fuente | Incorporación y decisión |
| --- | --- |
| #245, #227 | citas.ver/crear/confirmar/cancelar; recepción y administración operan; médico consulta/confirma/cancela; enfermería consulta |
| #247, #227 | reportes.ver para administración, médico y laboratorio; reportes.exportar solo administración y superadmin |
| #227 | documentacion.ver para los ocho roles canónicos |
| #236 | admision.ver, expediente.ver, medicamentos.ver, laboratorio.ver; permisos de navegación, sin conceder mutaciones ni omitir las reglas de cada recurso |
| #546 y #549 | signos_vitales.corregir para médico y superadmin, con reglas clínicas del módulo |
| #235 | alertas.supervisar para jefatura_medica y superadmin; no para cualquier médico ni admin_hospital |
| #564 | auditoria.ver solo superadmin; conserva el aislamiento hospitalario |

La matriz común prevalece sobre menús que proponen acceso administrativo a toda
información clínica. admin_hospital no recibe notas SOAP, expediente, signos ni
resultados clínicos por poder abrir una sección de navegación.
`jefatura_medica` permite supervisar alertas del servicio; no hereda el rol médico.
El módulo de alertas debe validar servicio, alcance y auditoría además del permiso.
Para reportes, cada definición debe validar el permiso del dato fuente y el hospital:
reportes.ver no autoriza exportar ni consultar expedientes clínicos arbitrarios.

Los dos alias soportados son rbac:permiso y permission:permiso. Ambos ejecutan
RequirePermission y exigen tenant/JWT antes. `permission:citas.*` es inválido:
cada operación debe declarar su permiso literal. El alias no usa una segunda matriz.
La entrega de Cindy publica user.permissions en login (POST), /auth/me y renovación,
resueltos por PermissionProvider a partir de las membresías del hospital validado.
El frontend consume ese conjunto; no obtiene permisos de una tabla estática por rol.
Las autorizaciones del backend siguen comprobándose en cada operación.

La revisión cubre nombres concretos de contratos existentes, incluidos los PR
pendientes, no funcionalidades futuras sin especificación. Las operaciones todavía
sin nombres suficientes (mutaciones de catálogos de salas/camas,
traslados/altas y analytics) requieren contrato antes de habilitar concesiones.
Los permisos de navegación no se convierten en permisos genéricos de escritura.
La autenticación/tenant de Cindy y el acuerdo CENTRAL (#502/#507) siguen pendientes
de integración; catálogo y alias no sustituyen esas dependencias.

## Activación de escritura de medicamentos (20 de octubre de 2026)

Decisión RBAC solicitada por Mar-03 sobre la propuesta ASII-14 (#586, #622):
`medicamentos.registrar` y `medicamentos.cambiar_vigencia` se conceden exclusivamente
al rol `admin_hospital` del hospital validado. Se retiran de `pending`.
Los otros siete roles, incluido `superadmin`, siguen sin estas concesiones;
no hay herencia administrativa implícita. La lectura conserva la matriz existente.

El módulo ASII-14 debe proteger cada escritura con tenant, JWT y el permiso literal
correspondiente. Registrar no autoriza cambiar vigencia, ni viceversa.
La autorización no sustituye validación de duplicados, reglas de vigencia,
auditoría ni selección de la conexión hospitalaria. Las pruebas de este cambio
verifican matriz y middleware RBAC; no acreditan endpoints de medicamentos.
La política entra en vigor al integrar este cambio; #622 por sí solo no la activa.

## Activación de traslados y altas

Solicitada por ASII-08 (#908, #913). Decisión final de RBAC (ASII-02), 30/10/2026:
`traslados.ver` para `enfermeria` y `medico`, `traslados.registrar` para `enfermeria` y `altas.registrar` para `medico`.
Se retiran de `pending`. Los demás roles, incluido `superadmin`, siguen sin estas concesiones;
no hay herencia administrativa implícita.

El alta corresponde a cualquier médico con permiso vigente en el hospital del contexto.
**Esta versión no la restringe al médico tratante** ni verifica la relación médico-paciente.

La autorización no sustituye las reglas del módulo: guard de base hospitalaria, referencia activa en
`local_user_refs`, estancia esperada, cama de destino disponible y transacción. La política entra en
vigor al integrar este cambio; publicar los nombres no la activó.

## Activación de la apertura del expediente

Solicitada por ASII-25 (expediente médico electrónico base). Decisión de la responsable del módulo, 30/10/2026:
`expediente.abrir` para `medico` y `superadmin`. Enfermería conserva solo la consulta con `expediente.ver`
(sin vínculo asistencial vigente, la consulta sigue respondiendo 403). Los demás roles no reciben la concesión.

La autorización no sustituye las reglas del módulo: guard de base hospitalaria, referencia activa en
`local_user_refs`, paciente existente y apertura idempotente con su evento de auditoría en la misma transacción.

## Activación de calidad de versiones

Solicitada por ASII-25 (QA, pruebas E2E y CI; adaptación «gestión de ejecuciones automatizadas y calidad de
versiones»). Decisión de la responsable del módulo, 30/10/2026: `qa.ejecuciones.ver`, `qa.ejecuciones.registrar`
y `qa.gates.evaluar` para `superadmin` y `admin_hospital`. Ningún rol clínico ni de laboratorio los recibe.

Las ejecuciones son metadatos de pruebas (versión, commit, suites, resultados y evidencias), sin datos clínicos.
La autorización no sustituye las reglas del módulo: tenant de la petición, transición de estados válida y
evaluación del quality gate con la política versionada.

## Propuesta ASII-16 (30-oct-2026)

`laboratorio.ordenes.actualizar` se registra en `catalog` y en `pending` para
`tecnico_laboratorio`, según el contrato propuesto de ASII-16. No se activa ni
se concede a ningún rol; el proveedor de permisos continúa leyendo solo `roles`.
