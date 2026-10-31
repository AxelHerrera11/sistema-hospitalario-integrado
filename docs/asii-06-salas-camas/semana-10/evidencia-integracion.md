# Evidencia de integración — Semana 10

## Base y alcance

`ASII06_SEMANA10_BASE`:
`1ba0a9b4ec82cc331f76b244809633581acf96c3`.

Rama de trabajo: `feature/asii-06-semana-10-salas-camas-madelinceron`.

La plantilla de tarea del repositorio identifica Semana 10 como
“diseño responsive/móvil”. El issue #11 aporta el objetivo general de ASII-06,
pero no define un brief semanal adicional ni contiene comentarios del docente.
La entrega aplica el alcance disponible mediante estrategia responsive,
wireframes y escenarios móviles; no implementa el prototipo reservado para
Semana 11.

## Referencias inspeccionadas

- Plantilla `.github/ISSUE_TEMPLATE/module_task.md` e issue maestro #11.
- Documentos ASII-06 de Semanas 1–9, con foco en el flujo UX de Semana 8 y
  hallazgos de Semana 9.
- Entregables de Semana 10 de ASII-03 Pacientes como referencia de formato y
  nivel de detalle, no como funcionalidades que deban copiarse.
- `resources/js/shared/components/AppLayout.vue`: navegación móvil tipo
  sidebar, enlace “Saltar al contenido” y breakpoint de escritorio de 1024 px.
- `resources/js/modules/patients/pages/PatientsPage.vue`: referencia de
  etiquetas, `role="status"`/`role="alert"`, foco y adaptación bajo 600 px.

## Decisiones de diseño

- Listas de tarjetas en una columna en los anchos 320–430 px; no se inventan
  breakpoints de CSS ni componentes en esta semana.
- Mantener contexto visible de ward al consultar camas, atendiendo UX-02 de
  Semana 9.
- Distinguir carga, vacío, fallo temporal, `401`, `403` y `404`; reservar
  estados y foco accesibles para la implementación.
- Usar solo los campos actuales de los contratos de lectura y el permiso
  existente `camas.ver`.
- No acordar etiquetas localizadas para estados por suposición; mostrar el
  valor textual hasta validar terminología funcional.

## Contratos considerados

- `GET /api/v1/wards`: cada item contiene `ward_id`, `code`, `name`,
  `location`, `status`; la colección vacía es respuesta `200` con `data: []`.
- `GET /api/v1/wards/{ward}/beds`: cada item contiene `bed_id`, `ward_id`,
  `code`, `type`, `status`; ward existente sin camas devuelve `200` con
  `data: []`.
- Ward inexistente: `404 WARD_NOT_FOUND`. Errores de catálogo: `503` con
  código seguro. Las rutas existentes aplican `api`, `tenant`, `jwt.auth` y
  `rbac:camas.ver`.

## Integración prevista para Semana 11

1. Añadir vista Vue al módulo ASII-06 y ruta protegida dentro del router HIS.
2. Añadir entrada de navegación usando permisos efectivos y el patrón del
   layout compartido.
3. Consumir ambos endpoints mediante el cliente HTTP compartido; conservar
   respuestas y errores definidos por la API, sin fixtures como sustituto.
4. Implementar selección de ward, estados descritos y tarjetas responsive con
   semántica/teclado adecuados.
5. Ejecutar pruebas de frontend y validar anchos 320, 360, 390 y 430 px,
   incluyendo teclado, zoom/reflow, foco y anuncios.

## Validación de esta entrega

Es documentación y diseño, sin archivos de aplicación modificados. Se
ejecutaron `git diff --check` y revisión de estado y conteo del worktree.
No se ejecutó build frontend, pues no hubo cambios Vue/CSS, ni PostgreSQL, pues
no se alteró persistencia. Las pruebas de regresión de API se ejecutan y sus
resultados se consignan en el cierre de la tarea.

## Riesgos y pendientes

- Los wireframes no verifican visualmente contraste, tamaño táctil, zoom,
  lector de pantalla ni conformidad WCAG; esos puntos requieren prototipo.
- Confirmar etiquetas españolas para `active`, `inactive`, `available`,
  `occupied` y `maintenance` con la responsable funcional antes de su
  implementación.
- La conexión `hospital` continúa fija. `TenantMiddleware` valida hospital,
  pero no existe selección dinámica de base por tenant ni aislamiento
  multi-tenant completo.
- Semanas 6–9 están preservadas como base de rama; esta entrega no modifica sus
  endpoints ni documentación.
