# Semana 7 — Diseño de componentes backend/frontend

**Módulo:** ASII-19 — Ingreso de resultados de laboratorio

## Objetivo

Diseñar componentes para que un técnico capture resultados pendientes y un usuario autorizado corrija una captura sin mezclar UI, reglas clínicas ni persistencia.

## Componentes

- Frontend: formulario de pendientes, editor de corrección, validación de unidad/motivo y visor de historial.
- Backend Presentation: controlador HTTP y serialización de respuesta.
- Backend Application: casos de uso de captura y corrección.
- Backend Domain: entidad, estados, versiones y reglas de valor/unidad.
- Persistence: contrato Repository con adaptadores InMemory y PDO.
- Auditoría: puerto separado para registrar captura y corrección.

El diagrama de componentes muestra dependencias dirigidas hacia contratos, no hacia implementaciones concretas.

## Propuesta de refactorización

1. Extraer el historial de correcciones a un componente de versión inmutable.
2. Mantener el controlador limitado a request/response y códigos HTTP.
3. Encapsular la concurrencia en el Repository mediante tenant, id y versión esperada.
4. Reutilizar el componente de validación de unidad en captura y corrección.
5. Reemplazar el formulario duplicado por un componente frontend con estados pendiente, guardando y error.

## Criterios de aceptación

- El técnico puede capturar solo resultados pendientes y con unidad compatible.
- La corrección requiere motivo y conserva la versión anterior.
- Un error de auditoría o persistencia no cambia el estado guardado.
- La UI muestra el historial sin permitir editar versiones anteriores.

