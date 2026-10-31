# Alertas críticas y notificaciones internas: Semana 17, seguridad y despliegue final

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-21 · **Issue:** #40

| Requisito | Estado | Evidencia |
|---|---|---|
| Matriz de amenazas | **Hecho** | [matriz-amenazas.md](./matriz-amenazas.md): 14 amenazas; 11 mitigadas, 1 corregida en esta entrega, 1 parcial y 1 pendiente de ASII-02 |
| Pruebas de permisos y datos sensibles | **Hecho** | `CriticalAlertSecurityTest`: matriz de los 8 roles por las 5 rutas, relación de cuidado, suplantación por el cuerpo, entradas hostiles, logs y traza (5 pruebas) |
| Corrección de seguridad | **Hecho** | Un identificador como arreglo o un valor `1e400` producían un **500** en la detección; ahora dan 422 `EVENT_INVALID` |
| Despliegue y reversión | **Hecho (local)** | [bitacora-despliegue.md](./bitacora-despliegue.md): bases vacías, migraciones, datos ficticios, build y recorrido de seguridad 16/16 |
| Despliegue final | **Bloqueado** | No hay staging ni on-premise autorizado; ver los bloqueos en la bitácora |

La parte de la Semana 17 que depende del módulo queda completa. No se declara un despliegue en staging ni en producción.

[Declaración de uso de IA](./DECLARACION_IA.md)
