# Semana 5 - Ingreso de resultados de laboratorio

## Entregable

Contrato REST preliminar y plan de integracion cliente-servidor para capturar y corregir resultados pendientes.

## Frontera propuesta

El modulo de resultados es propietario de versiones, estado pendiente, correcciones y auditoria. El catalogo de pruebas y la identidad pertenecen a servicios existentes del HIS. La separacion en microservicio solo se recomienda cuando volumen, despliegue independiente o aislamiento de datos lo justifiquen.

## Seguridad y resiliencia

- Requiere JWT, permiso de captura y `X-Tenant-ID`.
- Idempotencia por `Idempotency-Key` en captura.
- Respuestas 401, 403, 404, 409 y 422 con formato comun.
- Timeout de 3 segundos para dependencias y reintento solo en errores transitorios.
- Correlation ID, auditoria de cambios y metricas de latencia/error.

## Evidencia

- `fuentes/01-integracion-api.mmd`: cliente, API, propiedad de datos y dependencias.
- `contrato-api.md`: endpoints, payloads, errores y permisos.
