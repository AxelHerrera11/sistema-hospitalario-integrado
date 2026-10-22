# Semana 14 - Plan SQA

**Modulo:** ASII-29 Investigacion y prototipo NativePHP
**Tarea Odoo:** Semana 14 - Investigacion y prototipo NativePHP

## Objetivo

Medir la calidad del flujo NativePHP acotado en sesion, permisos, estados de
interfaz, sincronizacion, errores de red y proteccion de datos.

## Metricas

| Metrica | Meta | Evidencia |
|---|---:|---|
| Estados UX cubiertos | 100% | Matriz de pantallas |
| Errores recuperables documentados | 100% | Tabla de errores |
| Acciones con permiso y tenant | 100% | Contrato API |
| Reintentos idempotentes | 100% | Casos de red |
| Datos clinicos persistidos en cache | 0 | Revision de almacenamiento |

## Estrategia

- Pruebas de interfaz para carga, vacio, exito y error.
- Pruebas de contrato para headers y RFC 9457.
- Pruebas de sesion vencida y cierre de sesion.
- Pruebas de reintento sin duplicacion.
- Validacion manual en la plataforma real antes de declarar viabilidad.

La simulacion y la ejecucion real se reportan en secciones separadas.
