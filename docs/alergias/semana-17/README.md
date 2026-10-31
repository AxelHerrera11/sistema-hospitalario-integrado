# Alergias clínicas y alerta visual en expediente: Semana 17, seguridad y despliegue final

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-12 · **Issue:** #38

| Requisito | Estado | Evidencia |
|---|---|---|
| Matriz de amenazas | **Hecho** | [matriz-amenazas.md](./matriz-amenazas.md): 14 amenazas; 11 mitigadas, 1 corregida en esta entrega, 1 parcial y 1 pendiente fuera del módulo |
| Pruebas de permisos y datos sensibles | **Hecho** | `AllergySecurityTest`: matriz de los 8 roles, suplantación por el cuerpo, longitud, traza y logs sin datos clínicos (5 pruebas) |
| Corrección de seguridad | **Hecho** | La API no tenía límite de longitud (la pantalla sí). Ahora el dominio aplica 50/200/500 y responde `TEXT_TOO_LONG` |
| Despliegue y reversión | **Hecho (local)** | [bitacora-despliegue.md](./bitacora-despliegue.md): bases vacías, migraciones, seed, build y recorrido de seguridad 10/10 |
| Despliegue final | **Bloqueado** | No hay staging ni on-premise autorizado; ver los bloqueos en la bitácora |

La parte de la Semana 17 que depende del módulo queda completa. No se declara un despliegue en staging ni en producción.

[Declaración de uso de IA](./DECLARACION_IA.md)
