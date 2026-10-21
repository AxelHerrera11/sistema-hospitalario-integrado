# ASII-03 Pacientes — Semana 7

## Objetivo y alcance

Identificar los componentes reales de Pacientes y sus contratos, revisar sus dependencias y reducir de forma práctica el acoplamiento de Presentation. El cambio separa la traducción de resultados/errores de `PatientController`; conserva casos de uso, reglas, persistencia e interfaz pública.

## Componentes y decisión

El módulo contiene `PatientController` y `PatientView` en Presentation; `PatientService`, `PatientRepository` y sus fallos en Application; `Patient` y `DomainRuleViolation` en Domain; y los adaptadores `PdoPatientRepository` e `InMemoryPatientRepository` en Persistence.

Se seleccionó `PatientController` porque coordinaba los tres casos de uso y, además, clasificaba excepciones de Application y decidía estados y envelopes de respuesta. `PatientResponsePresenter` recibe ahora esa responsabilidad y utiliza `PatientView` para serializar. El resultado reduce el conocimiento de capas que necesita el Controller, sin agregar patrones ajenos al módulo.

## Resultado y validaciones

Las respuestas de registro, búsqueda y actualización y el mapeo de errores se conservan. La suite `php micro-his/pacientes/tests/run.php` cubre registro, búsqueda, actualización, código duplicado, fecha futura y error de persistencia. Se validó además la sintaxis PHP de los archivos PHP modificados o nuevos y el diff.

El OpenAPI conserva `patient_id` como identificador público e inmutable. La vista y las pruebas internas actuales lo nombran `id`; como aún no existe adaptador HTTP, este refactor conserva esa forma interna y no cambia el contrato. El adaptador futuro deberá mapear el mismo identificador a `patient_id`.

## Continuidad e integración

- Semana 3 estableció el módulo PHP independiente y sus casos de uso.
- Semana 4 separó capas y definió `PatientRepository` con adaptadores PDO y en memoria.
- Semana 5 documentó el contrato API y aclaró que todavía no existe un adaptador HTTP conectado.
- Se mantienen `pacientes.ver`, `pacientes.registrar` y `pacientes.editar`. Tenant, JWT y RBAC pertenecen a la integración Laravel transversal; Pacientes no los implementa ni duplica.

No se cambia el contrato API de Semana 5, `patient_id`, `code` ni `global_id`; tampoco se tocan migraciones, otros módulos ni ASII-06 Camas.

## Fuera de alcance

No se encontró una pantalla, servicio ni componente frontend funcional de Pacientes. La frontera cliente/API es la especificación OpenAPI existente; su conexión visual y el adaptador HTTP quedan para semanas posteriores. Esta entrega no crea frontend ficticio ni cablea rutas nuevas.
