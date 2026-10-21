# Matriz de trazabilidad y evidencia

**Estados:** Implementado = comportamiento en código comprobable; Prototipo =
UI demostrativa no integrada a operación real; Documentado = decisión/contrato
sin implementación visible; Pendiente = no hay evidencia disponible.

| Necesidad / requisito | Decisión | Evidencia real | Estado |
|---|---|---|---|
| Alta de paciente | Servicio registra entidad validada usando el puerto repository. | `PatientService::register`; prueba de registro `201` en `tests/run.php`. | Implementado |
| Consulta | Buscar por código exacto. | `PatientService::search`; `PatientRepository::findByCode`; prueba InMemory. | Implementado |
| Edición | Actualizar demografía y conservar identidad/código. | `Patient::update`, SQL PDO de actualización; prueba verifica identidad. | Implementado |
| Validaciones | Requeridos, fecha no futura, sexo y estado enumerados. | `Domain/Patient.php`; prueba de fecha futura y OpenAPI. | Implementado |
| Duplicados | Servicio detecta código existente; persistencia impone unicidad de código y `global_id`. | `PatientService`; migración hospitalaria; prueba de duplicado del código. | Implementado |
| Contrato HTTP | POST, GET por código y PATCH documentados con headers/permisos. | `micro-his/pacientes/openapi.yaml`. | Documentado |
| Presentación y errores internos | Presenter traduce fallos conocidos a envelopes internos. | `PatientController`, `PatientResponsePresenter`, `PatientView`. | Implementado |
| Mensajes y estados UX | Diseñar carga, vacío, denegación, error recuperable, ayudas y siguiente acción. | Flujo/wireframes/decisiones de Semana 8; no hay UI. | Documentado |
| Accesibilidad | Etiquetas, teclado, foco, anuncios y errores son requisitos de diseño. | Semana 8; sin frontend para probar teclado/semántica/contraste. | Documentado |
| Responsive 320–430 px | Adaptar formularios, detalle y acciones a móvil. | Semana 10 no integrada; no hay pantalla Pacientes. | Pendiente |
| Frontend navegable | Navegar búsqueda, detalle, alta, edición y restricción. | No existe módulo/ruta Pacientes en `resources/js`/router. | Pendiente |
| Demo local explícita | Datos estáticos, rotulados, sin persistencia ni API. | No hay prototipo Semana 11 integrado en esta rama. | Pendiente |
| Permisos de Pacientes | `pacientes.ver`, `pacientes.registrar`, `pacientes.editar` figuran en la matriz; autorización por ruta requiere middleware. | `config/rbac.php`, `RequirePermission`, `routes/api.php` sin endpoints Pacientes. | Documentado |
| Permisos frontend efectivos | Ocultar acciones según fuente confiable de permisos. | Login/`me` entregan roles, no `user.permissions`; helper `can()` deniega sin esa lista. | Pendiente |
| Tenant y JWT | Middleware global valida tenant, identidad y sesión para las rutas que lo usan. | `TenantMiddleware`, `JwtAuth`, grupos de `routes/api.php`. No están enlazados a Pacientes. | Implementado |
| RBAC en endpoints Pacientes | Aplicar permiso correcto por operación tras tenant/JWT. | Alias `rbac` existe en `RbacServiceProvider`; no hay rutas de Pacientes que lo usen. | Pendiente |
| Repositorio PDO / PostgreSQL | PDO parametrizado contra `local_patients`; tabla está en migración hospitalaria. | `PdoPatientRepository`, migración `2026_08_11_000200_create_patient_and_care_tables.php`. El test usa InMemory; runtime no cableado. | Implementado |
| Persistencia integrada por tenant | Inyectar conexión hospitalaria apropiada al repositorio de cada solicitud. | Constructor PDO existe; no se encuentra wiring HTTP Pacientes ni selección de conexión del módulo. | Pendiente |
| Identidad local y pública | Traducir PK local y nombres entre capas/contrato. | BD: `local_patient_id`; dominio/vista: `id`; OpenAPI: `patient_id`. | Pendiente |
| Identidad global / MPI | Conservar `global_id` sin afirmar enlace activo. | Campo local y referencia lógica en contrato; sin evidencia de sincronización del módulo. | Documentado |
| Suite del módulo | Verificar casos principales con repositorio en memoria y doubles. | `php micro-his/pacientes/tests/run.php`. No es integración DB/HTTP. | Implementado |
| Pruebas E2E de UI/API/DB | Validar experiencia completa en navegador y backend real. | No hay frontend ni ruta integrada de Pacientes. | Pendiente |
