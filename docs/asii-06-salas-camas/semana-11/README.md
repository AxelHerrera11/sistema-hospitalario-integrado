# ASII-06 — Semana 11: prototipo funcional Vue

## Alcance académico

La plantilla `.github/ISSUE_TEMPLATE/module_task.md` identifica Semana 11 como
**“mockup o prototipo”**. El issue maestro #11 describe el alcance general del
módulo, sin criterios semanales adicionales. En esta semana el prototipo se
lleva a una vista funcional, conectada a las consultas existentes de wards y
camas.

## Funcionalidad

La ruta `/wards` muestra wards desde `GET /api/v1/wards`. Al seleccionar una
ward, consulta `GET /api/v1/wards/{ward}/beds` y presenta las camas relacionadas.
Se incluyen carga, listas vacías, errores seguros, reintento y acceso restringido.
La navegación y la ruta requieren el permiso efectivo existente `camas.ver`;
el backend mantiene la autorización final.

El diseño usa listas de tarjetas en pantallas pequeñas, una presentación de dos
columnas en pantallas amplias, controles de teclado con foco visible y mensajes
semánticos. Se muestran únicamente campos de los contratos actuales. No se
agregan `rooms`, CRUD, cambios de estado ni funcionalidades clínicas.

## Archivos de esta semana

- `resources/js/modules/salas-camas/pages/WardCatalogPage.vue`: consulta de
  wards/camas, estados de interfaz y diseño adaptable.
- `resources/js/router/index.js`: ruta autenticada `/wards`.
- `resources/js/router/permissions.js`: permiso requerido y entrada de menú.
- `tests/Frontend/WardCatalogNavigationTest.mjs`: permiso y visibilidad de
  navegación.
- `docs/asii-06-salas-camas/semana-11/evidencia-prototipo.md`: contratos,
  validaciones, ejecución y limitaciones.

## Ejecución local

Desde la raíz del repositorio, con dependencias instaladas:

```powershell
npm run dev
php artisan serve
```

Abrir el origen que informa Vite/Laravel, iniciar sesión en el HIS con una
identidad que tenga `camas.ver` en el hospital activo y seleccionar
**Salas y camas** en la navegación. La API debe estar disponible y la identidad
debe contar con acceso al hospital activo. Para producción, `npm run build`
genera los assets mediante Vite.

## Limitaciones

No se declara conformidad WCAG completa; teclado, lector de pantalla, contraste
y reflow requieren validación manual en navegador/dispositivo. La conexión
`hospital` aún es fija: el middleware valida hospital, pero no existe selección
dinámica de base ni aislamiento multi-tenant completo.
