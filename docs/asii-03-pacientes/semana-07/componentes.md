# Componentes y contratos

| Componente real | Responsabilidad | Depende de | Contrato |
|---|---|---|---|
| Cliente/API (frontera documentada) | El cliente futuro consume las operaciones descritas en OpenAPI. | API HTTP por implementar | `micro-his/pacientes/openapi.yaml`; no hay endpoint de Pacientes conectado actualmente. |
| Presentation — `PatientController` | Coordina registro, búsqueda y actualización. | `PatientService`, `PatientResponsePresenter` | Métodos `register`, `search`, `update`; devuelve envelope de aplicación. |
| Presentation — `PatientResponsePresenter` | Traduce el resultado o fallos conocidos de Application a respuestas. | `PatientView`, excepciones de Application | `present(callable, status)` conserva status, códigos y estructura existentes. |
| Presentation — `PatientView` | Serializa el paciente y crea el envelope de error. | `Patient` | `patient()` y `error()` producen `status` y `body`. |
| Application — `PatientService` | Ejecuta registro, búsqueda por código y actualización; traduce reglas de dominio. | `PatientRepository`, Domain | Retorna `Patient`; expone `PatientOperationFailure` y `PersistenceFailure`. |
| Application — `PatientRepository` | Puerto de lectura y escritura de pacientes. | `Patient` | `findByCode`, `insert` y `update`. |
| Domain — `Patient` | Identidad, datos y reglas de paciente; `update()` conserva `id`, `globalId` y `code`. | `DomainRuleViolation` | Objeto de dominio usado por Application y adaptadores. |
| Persistence — `PdoPatientRepository` | Ejecuta SQL PDO y mapea filas. | PDO, `PatientRepository`, `Patient` | Implementa el puerto; convierte errores PDO a `PersistenceFailure`. |
| Persistence — `InMemoryPatientRepository` | Adaptador en memoria usado por las pruebas. | `PatientRepository`, `Patient` | Mismas operaciones del puerto, con IDs `mem-*`. |

## Fronteras

Presentation coordina la entrada y forma respuestas; no contiene SQL ni reglas de negocio. Application depende del puerto `PatientRepository`, no de PDO. Domain no depende de infraestructura. Persistence implementa el puerto y puede usar Domain para reconstruir objetos. Los fallos de persistencia vuelven a Application como `PersistenceFailure`; Presentation los convierte al error de respuesta sin exponer detalles internos.

## Cliente y seguridad transversal

No existe frontend funcional de Pacientes en `resources/js`: las vistas allí existentes son de autenticación, inicio y RBAC. El cliente futuro se comunicará mediante el contrato OpenAPI; no accederá directamente al repositorio o PDO.

OpenAPI define `patient_id` como identificador público estable, mientras `PatientView` emite el identificador local como `id` en el envelope interno existente. El adaptador HTTP futuro debe traducir el nombre sin cambiar el valor; esta entrega no altera ninguno de los dos contratos.

La integración transversal prevista conserva el orden `tenant → jwt.auth → rbac → Pacientes`. Laravel registra esos middlewares y declara los permisos en `config/rbac.php`, pero hoy las rutas API existentes no conectan Pacientes y el grupo de RBAC no demuestra una ruta de Pacientes activa. El Micro-HIS independiente no implementa autenticación, tenant ni otro RBAC.
