# Semana 10 | ASII-19: Diseño responsive móvil

## Propuesta 320–430 px

El flujo de captura y corrección controlada prioriza primero paciente, prueba,
valor, unidad, rango y estado pendiente. La acción principal queda al alcance
del pulgar y el historial se abre bajo demanda.

| Pantalla | Contenido prioritario | Regla responsive |
|---|---|---|
| Pendientes | Paciente, prueba, estado y filtro | Una tarjeta por resultado; filtros en panel desplegable. |
| Captura | Valor, unidad, rango y validación | Campos apilados; rango siempre visible bajo el valor. |
| Corrección | Anterior, nuevo valor y motivo | El valor anterior es solo lectura; confirmación al final. |
| Resultado | Estado, auditoría y siguiente acción | Acciones de ancho completo y mensaje persistente. |

## Reglas de interacción

- En 320 px no se ocultan unidad, rango ni estado; se elimina información secundaria.
- El envío se deshabilita si falta valor, unidad o motivo de corrección.
- Un error de conexión conserva los datos y ofrece `Reintentar` sin duplicar.
- La confirmación advierte que la corrección será trazable y requerirá autorización.

## Escenarios móviles

### Camino feliz

1. El técnico abre Pendientes y filtra por paciente.
2. Selecciona la prueba, ve unidad y rango, y captura el valor.
3. El sistema valida formato y permite enviar a revisión.
4. La pantalla confirma `En revisión` y ofrece ver auditoría.

### Error recuperable

1. El técnico confirma una corrección con conexión limitada.
2. El sistema conserva valor, unidad y motivo y muestra `No se pudo enviar`.
3. `Reintentar` reutiliza la solicitud; `Cancelar` devuelve a pendientes sin pérdida.

## Criterios verificables

- El prototipo conserva información clínica crítica entre 320 y 430 px.
- El camino feliz y el error muestran navegación, confirmación y recuperación.
- Los controles tienen etiquetas, foco visible y mensajes junto al campo.
- Tenant, rol, estado pendiente y auditoría se mantienen en cada pantalla.

## Evidencia

- Requisito Odoo: `Semana 10: Diseño para movilidad`.
- Entregable: propuesta responsive de cuatro pantallas y dos escenarios.
- Validación: revisión visual en 320 px y 430 px; alcance documental, sin datos reales.
