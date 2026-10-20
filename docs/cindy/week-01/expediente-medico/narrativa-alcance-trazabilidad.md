# Narrativa de Alcance y Trazabilidad

## Propósito

Este documento delimita y explica el proceso de apertura y consulta autorizada del expediente médico longitudinal. La finalidad es que el personal médico consulte información clínica únicamente después de validar su identidad, tenant y permisos, y que cada resultado quede trazado en la base CENTRAL.

## Alcance funcional

El flujo inicia cuando un profesional médico solicita localizar a un paciente, abrir su expediente longitudinal o consultar su historial clínico. La identidad del paciente se resuelve mediante el MPI en la base CENTRAL; los datos clínicos pertenecen a la base HOSPITAL correspondiente al tenant activo.

Antes de acceder a la información clínica, Laravel valida el JWT, el header `X-Tenant-ID` mediante Stancl Tenancy y el rol o permiso RBAC gestionado con Spatie Laravel Permission. La consulta documentada utiliza `GET /api/v1/emr/patients/{id}`. El frontend Vue 3, Pinia y Axios presenta el resultado autorizado al usuario.

La consulta de un expediente inexistente termina en `404 Not Found`. La creación es una operación distinta: si el usuario inicia una apertura y tiene el permiso requerido, el sistema puede crear el expediente en HOSPITAL y registrar el resultado en CENTRAL (`201 Created`). La creación no se ejecuta como efecto lateral de un `GET`.

## Trazabilidad entre los diagramas

Los identificadores de caso de uso conectan los objetivos funcionales con las
decisiones del flujo y las interacciones entre componentes:

| UC    | Objetivo / responsabilidad               | Actividad representada                                      | Interacción técnica principal                       |
|-------|------------------------------------------|-------------------------------------------------------------|-----------------------------------------------------|
| UC-01 | Buscar y resolver al paciente en el MPI  | Resolver identidad antes de buscar el expediente            | ExpedienteService consulta CENTRAL                  |
| UC-02 | Abrir el expediente longitudinal         | Rama autorizada de apertura                                 | Laravel coordina la creación en HOSPITAL            |
| UC-03 | Consultar historial clínico autorizado   | Buscar expediente existente después de autorizar            | ExpedienteService consulta HOSPITAL                 |
| UC-04 | Validar autenticación, tenant y permisos | Decisiones de JWT, tenant/módulo y RBAC                     | Middleware Laravel y Auth Guard JWT/Spatie consultan CENTRAL cuando corresponde |
| UC-05 | Registrar auditoría y resultado          | Trazabilidad accesos concedidos, denegados y no encontrados | Middleware/servicio registra metadatos en CENTRAL   |
| UC-06 | Crear el expediente cuando procede       | Rama condicional: apertura solicitada y expediente ausente. | Crear en HOSPITAL tras aceptar, auditar en CENTRAL  |

En el diagrama de casos de uso, UC-02 y UC-03 incluyen UC-01, UC-04 y UC-05 como responsabilidades necesarias. UC-06 extiende UC-02 únicamente cuando no existe el expediente y la operación solicitada es apertura/creación. El diagrama de actividades despliega esas dependencias en decisiones y resultados.

El diagrama de secuencia concreta el recorrido técnico de la consulta UC-03 y deja explícito que la apertura UC-02/UC-06 reutiliza las validaciones, pero no es una solicitud `GET`.

## Flujo y resultados

1. *Solicitud:* el frontend envía el identificador del paciente, un JWT en `Authorization: Bearer ...` y el tenant en `X-Tenant-ID`.

2. *Autenticación (UC-04):* si el token no está presente o no es válido, se registra el intento en CENTRAL y se responde `401 Unauthenticated`.

3. *Tenant y módulo (UC-04):* se resuelve y valida el tenant y el acceso al módulo. Una denegación se registra en CENTRAL y devuelve `403 Forbidden`.

4. *Autorización RBAC (UC-04):* se comprueba el rol y permiso para la acción solicitada. Si falta autorización, se registra la denegación y se responde `403 Forbidden`.

5. *Identidad del paciente (UC-01):* con el acceso autorizado, el servicio resuelve al paciente mediante el MPI en CENTRAL.

6. *Acceso clínico (UC-03):* la búsqueda del expediente se limita al contexto del tenant y se ejecuta en HOSPITAL. Si existe, el servicio devuelve el expediente y se responde `200 OK` con JSON; si no existe durante una consulta, se audita el resultado y se responde `404 Not Found`.

7. *Apertura/creación (UC-02/UC-06):* cuando se pidió apertura, la ausencia del expediente puede dar lugar a su creación en HOSPITAL, siempre que el permiso de apertura haya sido validado. Se registra el resultado en CENTRAL y se responde `201 Created`.

8. *Presentación:* ante `200 OK`, el frontend despliega el historial autorizado; los errores se muestran como respuestas no exitosas y no como expedientes vacíos.

## Límites de datos y auditoría

CENTRAL es responsable de identidades, MPI y auditoría; HOSPITAL conserva la información clínica dentro del tenant correspondiente. La bitácora de CENTRAL debe permitir correlacionar cada intento mediante metadatos como usuario, tenant, referencia del paciente, acción, resultado y marca de tiempo. No debe copiar el contenido clínico del expediente a los registros de auditoría.

## Archivos relacionados

- [Casos de uso](./01-casos-de-uso.svg)
- [Actividades](./02-actividad.svg)
- [Secuencia](./03-secuencia.svg)
- [README del módulo](./README.md)
