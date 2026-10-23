# Hallazgos — revisión técnica ASII-06

Los hallazgos separan riesgos ya documentados de defectos nuevos. REV-01 fue
reproducido como un defecto de aislamiento y cuenta con una contención temporal
en #1154; la asignación dinámica definitiva continúa pendiente.

## REV-01 — Acceso cruzado al catálogo por conexión fija (confirmado)

- **Prioridad:** Alta; contención temporal integrada en #1154, solución
  definitiva pendiente.
- **Evidencia:** `TenantMiddleware` busca y valida el hospital en CENTRAL. El
  `SalasCamasServiceProvider` resuelve siempre `DB::connection('hospital')`.
  Las tablas `wards` y `beds` no contienen `tenant_id`. Antes de #1154, una
  prueba con Hospital A y B activos, tokens válidos y `camas.ver` obtuvo HTTP
  200 para ambos y el mismo catálogo al enviar cada tenant sobre la conexión
  fija.
- **Impacto:** el tenant autenticado no seleccionaba una base/conjunto de
  registros distinto; la validación de header/JWT no proporcionaba aislamiento
  físico.
- **Contención:** #1154 añade `HOSPITAL_DB_TENANT_ID`; tenant distinto recibe
  403 y asignación ausente 503 antes del acceso al catálogo, conservando JWT y
  RBAC. La prueba actual usa esquemas PostgreSQL separados en una base de test
  transaccional para comprobar la denegación en ambas direcciones.
- **Recomendación definitiva:** la arquitectura documentada establece una base
  HOSPITAL por hospital. Resolver dinámicamente esa base desde un mapeo
  autenticado por `hospital_id`, con política de credenciales y provisión
  acordada. `hospitals.clinical_endpoint` no tiene un contrato de resolución
  documentado y `RegisterHospital` lo crea como `null`; no inventar valores ni
  asignar datos existentes. No sustituir esta arquitectura por `tenant_id`
  compartido sin una decisión formal.

## REV-02 — Recorrido autenticado local aún no verificado

- **Prioridad:** Media, bloquea la demostración local completa.
- **Evidencia:** las pruebas de catálogo usan identidad y permisos de prueba;
  `IdentityContextTest` levanta contexto SQLite aislado. No se ejecutó login
  visual con hospital y usuario autorizados.
- **Impacto:** el build, los tests de endpoint y el test PDO no prueban la
  secuencia real login → guard Vue → menú → wards → camas.
- **Recomendación:** obtener de administración, por canal seguro, un UUID de
  hospital existente/activo y una cuenta de prueba con membresía vigente y
  permiso efectivo `camas.ver`; ejecutar la secuencia completa sin desactivar
  JWT, tenant o RBAC. Si no hay identidad autorizada, conservar el pendiente.

## REV-03 — Mensaje local de tenant no permite identificar el valor enviado

- **Prioridad:** Baja; diagnóstico de una incidencia local, no una debilidad
  del middleware.
- **Evidencia:** “El hospital indicado no es válido.” es el mensaje de
  `400 TENANT_INVALID` si `X-Tenant-ID` no es UUID. Un UUID bien formado pero
  inexistente produce `404 HOSPITAL_NOT_FOUND`.
- **Impacto:** sin el valor local (que deliberadamente no se inspeccionó), no
  se puede afirmar si es ID incorrecto, campo vacío u otra causa. No se debe
  adivinar un UUID ni publicarlo en logs o documentación.
- **Recomendación:** usar un ID confirmado por fuente administrativa segura;
  reemplazar el tenant guardado por el formulario de login y volver a probar.
  Si el mensaje exacto cambia, atender el código de respuesta correspondiente.

## REV-04 — Build no sustituye una prueba de interacción visual

- **Prioridad:** Baja para esta revisión, pendiente antes de cerrar accesibilidad.
- **Evidencia:** Vite compila el componente y la prueba Node cubre permiso y
  visibilidad de navegación, pero no monta Vue ni opera el flujo en navegador.
- **Impacto:** el estado de carga, foco tras selección, teclado, zoom/reflow y
  lector de pantalla no quedan confirmados por esas automatizaciones.
- **Recomendación:** con sesión de prueba autorizada, probar anchos móviles y
  escritorio, teclado y anuncios de estados; documentar lo observado sin
  afirmar conformidad WCAG sin evaluación suficiente.

## Decisión de cambios

Este PR de Semana 13 mantiene alcance documental. REV-01 sí es un defecto
reproducido; la corrección temporal de las dos rutas se integró por separado
en #1154. La asignación dinámica definitiva requiere el contrato y los datos
de conexión que faltan, por lo que no se modifica el esquema ni se inventa un
enrutamiento en esta entrega. Los otros hallazgos siguen siendo brechas de
evidencia o integración.
