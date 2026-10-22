# Semana 15 - Pruebas del prototipo NativePHP

**Modulo:** ASII-29 Investigacion y prototipo NativePHP
**Tarea Odoo:** Semana 15 - Investigacion y prototipo NativePHP

## Casos de prueba

| Tipo | Caso | Resultado esperado |
|---|---|---|
| Unitaria | Estado vacio y carga | Mensajes correctos |
| Unitaria | Sesion vencida | Se limpia contexto y vuelve a login |
| Caja blanca | Ramas de permiso y tenant | Cada decision tiene salida |
| Caja negra | Accion autorizada | Respuesta visible de exito |
| Caja negra | Rol no autorizado | 403 sin datos ni cambio |
| Caja negra | API no disponible | Error recuperable y reintento |
| Integracion | Repetir request id | No duplica la operacion |
| Plataforma | Inicio, uso y cierre NativePHP | Evidencia real o restriccion explicita |

## Ejecucion

El PR debe adjuntar comandos del pipeline, capturas del prototipo y, si existe,
la ejecucion real NativePHP. Un mock o una simulacion se etiqueta como tal y no
demuestra por si solo la viabilidad tecnica.

## Datos

Se usan usuarios, tenant y respuestas ficticias. No se almacenan payloads
clinicos en localStorage ni en archivos temporales sin proteccion.
