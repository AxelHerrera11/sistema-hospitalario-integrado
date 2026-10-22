# Walkthrough de defensa — 5 a 8 minutos

## 0:00–0:40 — Enmarcar la entrega

“Voy a defender el estado que sí está en esta rama. El módulo de lógica de
Pacientes existe, pero el prototipo navegable de Semana 11 no está integrado
aquí. Voy a separar código ejecutable, diseño documentado e integración
pendiente.”

Mostrar este README y señalar las semanas disponibles: 1–5, 7 y 8. Aclarar
que 6 y 9–11 no aparecen en el `develop` actualizado.

## 0:40–1:40 — Arquitectura

Abrir `micro-his/pacientes/src/Presentation/PatientController.php`, luego
`Application/PatientService.php`, `Application/PatientRepository.php`,
`Domain/Patient.php` y `Persistence/PdoPatientRepository.php`.

Explicar que Presentation traduce operaciones; Application coordina; Domain
valida invariantes; el puerto Repository desacopla persistencia. Nombrar los
adaptadores PDO e InMemory. La prueba usa InMemory, no una base PostgreSQL
activa.

## 1:40–2:30 — Casos y datos

Mostrar `PatientService`: alta revisa duplicado, búsqueda usa código exacto
y edición modifica el paciente encontrado. Mostrar reglas de fecha/sexo/
estado en `Patient`. Después abrir `openapi.yaml`: contrato HTTP descrito,
no conectado.

Explicar IDs: tabla local usa `local_patient_id`; el modelo los llama `id`;
OpenAPI usa `patient_id`; `PatientView` interno aún devuelve `id`. `global_id`
es referencia lógica al MPI, no prueba de sincronización.

## 2:30–3:20 — UX y permisos

Abrir los wireframes/decisiones de Semana 8. Recorrer búsqueda, detalle,
alta, edición y estados de error. Indicar que etiquetas, foco, teclado,
mensajes y privacidad son especificaciones; sin pantalla no son resultados
de pruebas de accesibilidad.

Abrir `config/rbac.php` y mostrar los tres permisos. Explicar que la matriz
configura permisos por roles y que el middleware general puede autorizar rutas
que lo usen; Pacientes no tiene rutas en `routes/api.php`. No afirmar que
estas capacidades protejan una API de Pacientes activa.

## 3:20–4:15 — Tenant, JWT y RBAC existentes

Mostrar `routes/api.php`, `bootstrap/app.php` y `RbacServiceProvider.php`.
Laravel sí tiene middleware tenant y JWT y alias RBAC para otras rutas; la
sesión frontend contiene roles, pero no `user.permissions`. La ruta API y
adaptador HTTP de Pacientes faltan.

## 4:15–5:10 — Pruebas

Ejecutar `php micro-his/pacientes/tests/run.php` o mostrar salida reciente.
Nombrar los escenarios cubiertos y precisar que fallos de persistencia usan un
double; no es prueba de integración con PostgreSQL, API o UI.

## 5:10–6:00 — Cierre y siguiente paso

Proponer como siguiente trabajo cablear, no saltar, la frontera:
`Vue → routes/api.php → tenant → JWT → RBAC → adaptador HTTP → PatientService
→ repositorio PDO/DB por tenant`. Antes de escribir cliente, acordar el
transporte de permisos efectivos, el mapeo de IDs/envelopes y el mapeo de
duplicado `422` a conflicto `409` del OpenAPI.

## Respuestas a preguntas previsibles

- **¿Ya está conectado a la base?** “Hay migración PostgreSQL y un
  `PdoPatientRepository`, pero la prueba ejecutable usa memoria y no hay
  adaptador HTTP/wiring de Pacientes que conecte la sesión tenant con ese
  repositorio. No lo presento como un flujo conectado extremo a extremo.”
- **¿Dónde está el prototipo?** “No está en esta rama actualizada; el router
  solo tiene las pantallas generales, login, RBAC y usuarios. Por eso la
  Semana 12 documenta la brecha en vez de mostrar una pantalla inexistente.”
- **¿Los roles ya autorizan Pacientes?** “Los nombres de permisos están en la
  matriz y el motor/middleware RBAC existe, pero no hay ruta de Pacientes que
  lo aplique. Las reglas de UI no serían control de seguridad; el servidor
  debe autorizar cada endpoint.”
- **¿`global_id` ya sincroniza con MPI?** “No. Es una referencia lógica en
  contrato/esquema. No hay evidencia aquí de un proceso de enlace/sincronización
  operativo.”
- **¿Qué sigue?** “Acordar permisos y mapeos del contrato, implementar el
  adaptador HTTP conectado al tenant y repositorio adecuado, y después
  integrar el frontend con pruebas por capa y extremo a extremo.”
