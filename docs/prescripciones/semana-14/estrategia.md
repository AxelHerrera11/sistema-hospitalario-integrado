# Estrategia de pruebas — `PROPUESTO`

| Nivel | Propósito | Evidencia a conservar |
|---|---|---|
| Unitario | Reglas de alergia, validación de datos y decisiones del dominio | Caso, resultado real y versión |
| Aplicación | Orquestación, fallas de puertos y no persistencia en rechazo | Dobles controlados y verificación de escrituras |
| Persistencia | Mapeo de esquema, lectura y errores PDO | Base de prueba y salida de error |
| API/Feature | Autenticación, tenant, permisos y códigos de respuesta | Comando ejecutado y respuestas verificadas |
| Frontend | Navegación, validación, loading/error, advertencia y rechazo | Pruebas y pasos reproducibles |
| Accesibilidad | Teclado, foco, anuncios, contraste y reflujo | Evidencia manual y herramienta identificada |

La selección de framework y ambiente debe seguir al stack integrado. No se
declara un pipeline ni ejecución actual.
