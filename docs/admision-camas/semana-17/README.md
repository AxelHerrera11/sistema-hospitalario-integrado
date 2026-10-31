# Admisión hospitalaria y asignación de cama: Semana 17, seguridad y despliegue final

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-07 · **Issue:** #37

| Requisito | Estado | Evidencia |
|---|---|---|
| Matriz de amenazas | **Hecho** | [matriz-amenazas.md](./matriz-amenazas.md): 14 amenazas; 10 mitigadas, 2 corregidas en esta entrega, 1 parcial y 1 pendiente fuera del módulo |
| Pruebas de permisos y datos sensibles | **Hecho** | `AdmissionSecurityTest`: matriz de los 8 roles, suplantación por el cuerpo, entradas hostiles, listado sin datos de paciente y logs sin motivo de ingreso (5 pruebas) |
| Correcciones de seguridad | **Hecho** | Un arreglo en `patient_id`, `bed_id` o `reason` producía un **500**; ahora responde 404 o 422. Además, el motivo tiene un límite de 500 caracteres, como la pantalla (`TEXT_TOO_LONG`) |
| Despliegue y reversión | **Hecho (local)** | [bitacora-despliegue.md](./bitacora-despliegue.md): bases vacías, migraciones, seed, build y recorrido de seguridad 11/11 |
| Despliegue final | **Bloqueado** | No hay staging ni on-premise autorizado; ver los bloqueos en la bitácora |

La parte de la Semana 17 que depende del módulo queda completa. No se declara un despliegue en staging ni en producción.

[Declaración de uso de IA](./DECLARACION_IA.md)
