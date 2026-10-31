# Catálogo de pruebas de laboratorio: Semana 17, seguridad y despliegue final

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-17 · **Issue:** #39

| Requisito | Estado | Evidencia |
|---|---|---|
| Matriz de amenazas | **Hecho** | [matriz-amenazas.md](./matriz-amenazas.md): 12 amenazas; 9 mitigadas, 2 corregidas en esta entrega y 1 pendiente fuera del módulo |
| Pruebas de permisos y datos sensibles | **Hecho** | `LabTestSecurityTest`: matriz de los 8 roles, suplantación por el cuerpo, entradas hostiles y traza (4 pruebas) |
| Correcciones de seguridad | **Hecho** | Una unidad que no era texto (arreglo o número) o un rango `1e400` producían un **500**; ahora dan 422. Además, nombre, muestra y unidad tienen límites de longitud (`TEXT_TOO_LONG`) |
| Despliegue y reversión | **Hecho (local)** | [bitacora-despliegue.md](./bitacora-despliegue.md): bases vacías, migraciones, seed, build y recorrido de seguridad 12/12 |
| Despliegue final | **Bloqueado** | No hay staging ni on-premise autorizado; ver los bloqueos en la bitácora |

La parte de la Semana 17 que depende del módulo queda completa. No se declara un despliegue en staging ni en producción.

[Declaración de uso de IA](./DECLARACION_IA.md)
