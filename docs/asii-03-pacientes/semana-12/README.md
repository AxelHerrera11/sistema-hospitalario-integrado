# ASII-03 Pacientes — Semana 12: segunda evaluación parcial

## Objetivo

Preparar una defensa verificable del avance de Pacientes: explicar lo que el
código realmente hace, qué decisiones existen solo en documentos y qué falta
para una integración extremo a extremo. No completar vacíos con funcionalidades
supuestas.

## Estado actual

- **IMPLEMENTADO:** módulo PHP por capas; casos de uso de registro, búsqueda
  por código y actualización; reglas de dominio; puerto `PatientRepository`;
  adaptadores PDO y en memoria; `PatientResponsePresenter`; pruebas ejecutables.
- **DOCUMENTADO:** contrato OpenAPI, decisiones UX, flujo/wireframes de Semana 8,
  RBAC propuesto por operación y plan de integración.
- **PROTOTIPO:** no hay prototipo navegable de Pacientes integrado en esta rama;
  no se etiqueta documentación ni el frontend general como prototipo del módulo.
- **PENDIENTE:** endpoints HTTP Pacientes, conexión del frontend, permisos por
  operación en esos endpoints, integración de tenant/JWT con el módulo y
  evidencia ejecutable de UI.
- **NO INTEGRADO EN ESTA RAMA:** Semana 6, Semanas 9–11 y un frontend navegable
  de Pacientes. No se atribuyen a esta rama los entregables que no contiene.

## Material disponible después de actualizar `develop`

La rama se actualizó con `git fetch origin`, cambio temporal a `develop` y
`git pull --ff-only origin develop`; `develop` ya estaba al día. Semanas 1–5,
7 y 8 están en `docs/asii-03-pacientes/`. No aparecen Semanas 6, 9, 10 o 11.
La rama de Semana 12 y `origin/develop` apuntan al mismo commit al iniciar
esta entrega.

Referencias principales: [arquitectura de Semana 3](../semana-03/arquitectura-c4.md),
[capas de Semana 4](../semana-04/capas-responsabilidades.md),
[contrato e integración de Semana 5](../semana-05/),
[refactor de Semana 7](../semana-07/refactor-antes-despues.md) y
[UX de Semana 8](../semana-08/).

## Arquitectura y UX

Presentation (`PatientController`, `PatientResponsePresenter`, `PatientView`)
coordina y traduce respuestas; `PatientService` usa el puerto
`PatientRepository`; `Patient` aplica invariantes; `PdoPatientRepository`
persiste por PDO e `InMemoryPatientRepository` permite pruebas aisladas.
El contrato no está conectado a HTTP.

Semana 8 documenta consulta por código, detalle mínimo, registro, edición,
estados y accesibilidad prevista. No hay prototipo visual que permita afirmar
que teclado, foco, etiquetas semánticas, contraste o responsive están
implementados o probados. Semana 9/10 no están en esta rama.

## Identidad, permisos y privacidad

La migración usa `local_patient_id`; el objeto de dominio lo expone como `id`;
OpenAPI lo llama `patient_id`, pero `PatientView` interno aún serializa `id`.
`global_id` es una referencia lógica al MPI; este módulo no demuestra
sincronización MPI. `code` es el identificador local. Nombres, nacimiento y
contacto son datos sensibles. No se agregan datos reales a esta documentación.

`config/rbac.php` contiene `pacientes.ver`, `pacientes.registrar` y
`pacientes.editar`. Los middleware globales tenant/JWT y el motor RBAC existen,
pero `routes/api.php` no declara rutas de Pacientes. Login/`me` no exponen
`user.permissions` al frontend. La configuración de permisos no equivale a
autorización activa de una operación de Pacientes.

## Evidencia, pruebas y pendientes

La suite `php micro-his/pacientes/tests/run.php` usa un repositorio en memoria;
cubre registro, consulta, edición, duplicado, fecha futura y fallo de
persistencia simulado. No prueba el adaptador PDO contra una base activa ni
una integración HTTP.

La defensa, [presentación](./presentacion.md), [walkthrough](./walkthrough.md)
y [matriz de trazabilidad](./matriz-trazabilidad-evidencia.md) distinguen
**IMPLEMENTADO**, **PROTOTIPO**, **DOCUMENTADO** y **PENDIENTE**. No se hizo
cambio práctico de código: el frontend Pacientes no está presente para una
corrección pequeña segura.

Pendiente para integración real: alinear `id`/`patient_id`; cablear un
adaptador HTTP con tenant/JWT/RBAC en el servidor; resolver permisos efectivos
por endpoint; inyectar el repositorio/conexión PostgreSQL por tenant; mapear
errores al OpenAPI y probar flujos de extremo a extremo, privacidad y UI.
