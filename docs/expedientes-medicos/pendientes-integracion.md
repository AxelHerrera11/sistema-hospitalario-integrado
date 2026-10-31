# Pendientes de integración y mensajes para los responsables

Nada de esta lista se modificó desde el módulo del expediente. Cada punto indica qué falta, quién lo da y cómo se
integra. Los mensajes están listos para enviar al grupo.

## 1. RBAC — permiso de apertura y criterio para enfermería (María)

**Falta:** el catálogo de `config/rbac.php` solo tiene `expediente.ver`. No existe permiso para abrir expedientes.

> Hola María: para el expediente médico necesito un permiso de apertura. Propuesta: acción `abrir` del módulo
> `expediente` (formato `modulo.accion`), concedida a `medico` y `recepcion`, primero en `pending` y en `catalog`,
> y en `roles` cuando lo apruebes. En el código ya está la constante `ExpedientePermissions::APERTURA_PROPUESTA`;
> con la matriz actual la apertura responde 403 a todos (prueba N-08), así que nada se activa sin tu cambio. Al
> aprobarlo, el adaptador Laravel publicará `POST /api/v1/patients/{code}/medical-record` con `rbac:` de ese permiso.
> Otra consulta: enfermería tiene `expediente.ver`, pero la consulta exige vínculo asistencial (`care_assignments`,
> que solo existe para médicos), así que hoy recibe 403 `SIN_VINCULO_ASISTENCIAL`. ¿Enfermería debe consultar? Si
> sí, ¿con qué relación (servicio, sala, asignación de enfermería)? No inventé esa regla.

**Dependencias afectadas:** `OpenMedicalRecord`, `app/ExpedienteMedico/ExpedienteMedicoServiceProvider.php`, supuestos S-01 y S-02.

## 2. Registro del provider en `bootstrap/providers.php` (archivo compartido) — HECHO

Autorizado por Cindy el 2026-10-23: se agregó `ExpedienteMedicoServiceProvider::class` (con su `use`) después de
`TrasladosAltasServiceProvider`, cuyo `ResolutorActorLocal` reutiliza. Es el único archivo compartido modificado
(+3 líneas). La ruta `GET /api/v1/patients/{patientCode}/medical-record` aparece en `php artisan route:list` y pasa
el guardián de rutas del CI (`semana-05/evidencias/route-guard.txt`). Conviene avisar al grupo del cambio, porque
quien edite ese archivo después debe conservar el orden.

## 3. Base de datos (Javier)

**Falta:** confirmar, sin crear tablas.

> Hola Javier: el expediente NO crea tablas ni migraciones. Escribe solo en `medical_records` (migración hospital
> 000400) y lee `local_patients`, `care_episodes`, `care_assignments`, `doctor_profiles` y `local_user_refs`.
> Te pido confirmar: (1) que `medical_records` queda registrada como tabla del expediente; (2) el formato de
> `record_number` = `EXP-` + código del paciente (supuesto S-03); (3) si existirá una tabla de asignación de
> enfermería (ver punto 1). Las pruebas PDO corren en SQLite con los mismos nombres; no pude validar en PostgreSQL
> porque no hay servidor en mi entorno.

## 4. Pacientes (Madelin)

> Hola Madelin: el expediente usa tu `PatientRepository::findByCode` (con `PdoPatientRepository`) sin modificarlo,
> a través de `PacientesDirectory`. Solo lee `local_patient_id`, código, nombres, nacimiento, sexo y estado; no
> expone `contact` ni `global_id`. ¿Confirmas que el código del paciente es el identificador estable para buscar?

## 5. Contratos y UI base (Lis)

> Hola Lis: el contrato del expediente está en `micro-his/expediente-medico/openapi.yaml` (errores `{code, trace_id?}`).
> Cuando publiques el formato común de respuestas/errores lo ajusto. Para la SPA: el menú tiene «Expediente» como
> «próximamente» en `resources/js/router/permissions.js`; registrar la ruta exige tocar `router/index.js` y
> `permissions.js` (compartidos). La lógica de la pantalla ya está separada en `public/ui/estados.js` para
> reutilizarla desde Vue, como hizo Traslados.

## 6. Traslados y altas (ASII-08)

> El adaptador Laravel del expediente reutiliza `App\TrasladosAltas\ResolutorActorLocal` y `PdoResolutorActorLocal`
> (usuario CENTRAL → `local_user_refs`) sin modificarlos. Si cambian su firma o lo mueven a un espacio compartido,
> avísenme para actualizar `ExpedienteMedicoServiceProvider`.

## 7. Consumidores del expediente (Axel, Josué, Javier, María)

> El contrato del expediente está publicado en `micro-his/expediente-medico/openapi.yaml`. La consulta devuelve el
> expediente y sus episodios; las notas SOAP, alergias y signos vitales siguen siendo de sus módulos.

## 8. Asignación del módulo (para confirmar)

El README del repositorio asigna «Expediente médico electrónico base» a la fila 10 (otra persona), mientras que el
plan de integración del 17–23 de octubre lo asigna a Cindy. Las ramas usan `asii-25`, como las demás ramas de Cindy;
confirmar con el grupo. En `develop` existe además un borrador anterior de la Semana 1 en
`docs/cindy/week-01/expediente-medico/`; esta entrega no lo modifica.
