# Hallazgos — revisión técnica ASII-06

Los hallazgos separan riesgos ya documentados de defectos nuevos. No se
identificó en esta revisión un error reproducible de código que justifique un
cambio funcional inmediato.

## REV-01 — La conexión del catálogo no se selecciona por tenant

- **Prioridad:** Alta para integración multi-hospital; no es una prueba de
  exposición explotable en este entorno.
- **Evidencia:** `TenantMiddleware` busca y valida el hospital en CENTRAL. El
  `SalasCamasServiceProvider` resuelve siempre `DB::connection('hospital')`.
  Las tablas `wards` y `beds` no contienen `tenant_id`.
- **Impacto:** una petición puede tener tenant autenticado y autorizado, pero
  el catálogo no deriva una base/conjunto de registros distinto según ese
  hospital. No se debe presentar la validación del header como aislamiento de
  datos.
- **Recomendación:** antes de habilitar varios hospitales, diseñar y probar
  selección de conexión hospitalaria conforme al registro central, o una
  estrategia explícita de aislamiento en esquema/base. Mantener la decisión
  fuera del frontend; usar tests de aislamiento cross-tenant y no cambiar
  silenciosamente migraciones en esta entrega.

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

No se hicieron cambios de aplicación: los cuatro hallazgos son brechas de
integración/evidencia o limitaciones ya conocidas, no un bug concreto
reproducido que deba corregirse en Semana 13. Se proponen acciones verificables
en lugar de expandir el CRUD o debilitar controles.
