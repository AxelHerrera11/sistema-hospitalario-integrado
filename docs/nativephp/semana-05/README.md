# Semana 5 - NativePHP para extension movil/escritorio

## Entregable

Contrato de consumo y plan de migracion para una extension NativePHP que consulte y corrija resultados pendientes mediante la API del HIS.

## Estado de la evidencia

Este PR documenta un prototipo de integracion y no una ejecucion real de NativePHP. La viabilidad tecnica sigue condicionada a validar el entorno, extensiones, empaquetado y acceso a la API.

## Seguridad y resiliencia

- No se almacenan tokens ni datos clinicos en texto plano.
- La extension usa HTTPS, JWT de corta duracion y tenant explicito.
- Cola local solo para reintentos no clinicos; una correccion requiere confirmacion del servidor.
- Timeout, reintento limitado, estado offline visible y correlation ID.

## Evidencia

- `fuentes/01-integracion-api.mmd`: frontera entre cliente NativePHP y HIS.
- `contrato-api.md`: contrato consumido y plan de migracion.
