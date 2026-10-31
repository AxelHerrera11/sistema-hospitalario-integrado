# Declaración de uso de inteligencia artificial

## Herramienta y propósito

Se utilizó GitHub Copilot en VS Code para organizar y redactar el contrato API
propuesto, sus respuestas y límites de integración. No se generaron ni
desplegaron endpoints.

## Instrucciones relevantes

- Documentar crear, listar, consultar detalle y firmar prescripciones.
- Usar `GET /api/v1/prescriptions/{id}` para detalle.
- Exigir `BearerAuth` y `TenantHeader` juntos en cada operación.
- Mantener la semántica clínica acordada, no guardar ante ningún 422 y no
  agregar endpoint de excepción de alergia.

## Uso y cambios humanos

Se aceptó el apoyo para organizar el borrador OpenAPI y las respuestas. Se
modificó el contenido para excluir la ruta de excepción, marcar como
`PROPUESTO` los esquemas no confirmados y evitar afirmar que hay middleware o
rutas implementadas. Los nombres de permisos se presentan como contrato a
validar, no como autorización desplegada.

## Validación humana

La persona usuaria confirmó el alcance de las cuatro operaciones y la regla
de seguridad conjunta. Se realizó una revisión estructural del documento; no
se ejecutó un validador OpenAPI/YAML ni pruebas contra un servidor. La revisión
final del contenido y esta declaración queda pendiente de aprobación humana.
