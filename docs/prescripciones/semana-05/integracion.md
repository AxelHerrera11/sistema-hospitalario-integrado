# Integración, seguridad y frontera de servicio

Estado: análisis `PROPUESTO`; no se afirma que haya infraestructura desplegada.

## Seguridad y datos

- Cada operación requiere JWT y `X-Tenant-ID` simultáneamente; las dos
  credenciales se declaran en un solo objeto OpenAPI `security`.
- Permisos propuestos: `prescripciones.crear`, `prescripciones.ver` y
  `prescripciones.firmar`.
- La identidad autenticada se resuelve a un perfil profesional en la capa de
  entrada. El cliente no elige libremente el firmante.
- Prescripciones administra sus registros; catálogo, alergias y auditoría
  conservan sus límites de propiedad.

## Resiliencia y consistencia

- Fallas de dependencias se expresan como error, no como una respuesta clínica
  limpia ni una lista vacía.
- Definir timeout, reintentos y circuit breaker con datos operativos antes de
  fijar valores; no se inventan umbrales aquí.
- Mantener persistencia en la conexión hospitalaria hasta que exista evidencia
  y decisión aprobada para separar el servicio.
- Un 422 no crea ni altera una prescripción. La política de idempotencia y la
  transacción de firma requieren especificación de implementación.

## Decisión arquitectónica

No se propone desplegar un microservicio independiente con la evidencia
disponible. Mantener el límite como módulo y revisar la separación cuando
existan requisitos operativos y propietarios de datos verificables.
