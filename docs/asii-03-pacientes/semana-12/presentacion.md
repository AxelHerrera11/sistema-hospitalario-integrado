# Presentación — ASII-03 Pacientes

Guion de 10 diapositivas para una segunda evaluación parcial. Las etiquetas
aclaran si cada afirmación está **IMPLEMENTADA**, **DOCUMENTADA** o
**PENDIENTE**; no hay frontend Pacientes en esta rama.

## Diapositiva 1 — ASII-03 Pacientes

- **Objetivo:** mantener el registro local de pacientes y sus datos
  demográficos con operaciones controladas.
- **Responsable:** Madelin Jazmín Cerón Molina.
- **Alcance actual:** módulo PHP de registro, consulta por código y edición;
  la UI/API HTTP integrada todavía no existe.

## Diapositiva 2 — Problema y actores

- El HIS necesita una identidad local estable a la cual relacionar episodios
  y otros registros del hospital.
- Actores previstos: personal con permisos efectivos de ver, registrar o
  editar; no se deducen permisos desde el nombre del rol en la interfaz.
- `local_patients` pertenece al hospital; `global_id` referencia lógicamente
  al MPI central, sin demostrar sincronización o enlace operativo.
- **IMPLEMENTADO:** casos de uso del módulo. **DOCUMENTADO:** actores y
  frontera prevista. La pantalla para usuarios está pendiente.

## Diapositiva 3 — Requisitos principales

- Registrar datos requeridos y valores permitidos.
- Consultar exactamente por código.
- Actualizar campos demográficos permitidos, preservando identidad.
- Rechazar código duplicado y fecha futura; sexo/estado usan conjuntos
  permitidos.
- Minimizar exposición de nombre, nacimiento, contacto e identificadores.
- **IMPLEMENTADO:** reglas/operaciones PHP y pruebas con repositorio en
  memoria. **DOCUMENTADO:** contrato HTTP OpenAPI. No hay endpoint activo.

## Diapositiva 4 — Arquitectura real

```text
PatientController
  → PatientResponsePresenter → PatientView
  → PatientService → Patient / reglas
                 → PatientRepository (puerto)
                    ↙                  ↘
       PdoPatientRepository     InMemoryPatientRepository
                → PostgreSQL     → pruebas en memoria
```

- **IMPLEMENTADO:** capas Presentation, Application, Domain y Persistence.
- `PatientService` depende de `PatientRepository`, no directamente de PDO.
- El adaptador PDO y la migración existen; la suite ejecutable usa el
  adaptador en memoria. El runtime HTTP no inyecta el PDO al flujo.

## Diapositiva 5 — Evolución técnica disponible

- Semanas 3–4: módulo PHP independiente, capas y Repository con adaptadores
  PDO/en memoria.
- Semana 5: contrato OpenAPI 3.1 y plan de integración; no crea servidor HTTP.
- Semana 7: `PatientResponsePresenter` separa el mapeo de errores del
  controller.
- Semana 8: flujo, wireframes y decisiones UX antes de UI.
- **No disponibles en esta rama:** Semana 6 y Semanas 9–11. En particular,
  no se puede afirmar que haya prototipo navegable integrado.

## Diapositiva 6 — UX, accesibilidad y privacidad

- **DOCUMENTADO:** búsqueda exacta, estados de carga/vacío/error, formularios
  con etiquetas, ayuda, errores recuperables y acciones por permiso.
- **DOCUMENTADO:** intención de operar por teclado, mostrar foco, asociar
  errores, anunciar estados y no depender solo del color.
- No existe interfaz Pacientes para probar esas decisiones; no se declara
  cumplimiento WCAG.
- Los errores previstos no deben revelar datos de paciente, SQL ni detalles
  internos de RBAC.

## Diapositiva 7 — Responsive

- La propuesta UX móvil no está integrada como Semana 10 en esta rama.
- No hay pantalla Pacientes cuya adaptación a 320–430 px pueda demostrarse.
- **PENDIENTE:** implementar UI de una columna, controles táctiles, mensajes
  visibles con teclado virtual y detalle apilado; validar zoom y contraste.
- No presentar responsive como capacidad comprobada del módulo.

## Diapositiva 8 — Prototipo navegable

- **No implementado en esta rama.** No existe `resources/js/modules/patients/`,
  ruta `/patients` ni componente Pacientes en el router.
- Vue 3, Vue Router, layout, auth y helper `can()` son infraestructura
  existente, pero no equivalen a un prototipo Pacientes.
- No hay modo demo integrado ni datos demo de Pacientes en el árbol revisado.
- Siguiente evidencia: construir el prototipo en una entrega integrada y
  rotular explícitamente cualquier dato estático, sin simular persistencia.

## Diapositiva 9 — Validaciones reales

- `php micro-his/pacientes/tests/run.php`: prueba registro, búsqueda,
  actualización, duplicado, fecha futura y fallo de persistencia simulado.
- Suite usa `InMemoryPatientRepository`; no valida PostgreSQL, HTTP,
  middleware tenant/JWT/RBAC ni frontend.
- Para esta entrega documental: ejecutar PHP y `git diff --check`; no se
  modificó frontend, por lo que no se requiere build Vite.
- La evidencia debe presentarse con ese alcance, no como integración E2E.

## Diapositiva 10 — Integración pendiente

```text
Vue Pacientes (pendiente)
 → routes/api.php (sin rutas del módulo)
 → tenant → jwt.auth → rbac (middleware general existente)
 → adaptador HTTP Pacientes (pendiente)
 → PatientService → PatientRepository
 → PDO/PostgreSQL (adapter/migración existen; wiring por tenant pendiente)
```

- Alinear `local_patient_id` → `Patient.id` → `patient_id` de contrato.
- Definir transporte de permisos efectivos al cliente, manteniendo autorización
  servidor en cada operación.
- Traducir envelopes internos y errores (`422` duplicado) al OpenAPI (`409`
  conflicto); inyectar conexión por tenant y probar PostgreSQL.
- MPI/global identity: solo referencia lógica en contrato/esquema; no afirmar
  sincronización.
