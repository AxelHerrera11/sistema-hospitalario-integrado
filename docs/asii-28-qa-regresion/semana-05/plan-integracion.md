# Plan incremental de integración

Todo lo de esta tabla es planificación salvo I0; fechas de futuras semanas se
confirmarán con el curso. Madelin Cerón mantiene contrato, pruebas y seguimiento.
Roles sin persona autorizada: Pendiente de asignación; no se atribuye trabajo a terceros.

| Etapa / actividad | Responsable | Dependencias | Criterio de aceptación / RF-RNF | Prueba requerida | Evidencia esperada | Estado |
|---|---|---|---|---|---|---|
| I0 Semana 5: contrato y frontera | Madelin Cerón | Semanas 1–4 integradas, código actual | Propuesta distingue CLI/HTTP y límites de RF-01 | Reejecutar suite y validar documentos | 17 aprobadas, contrato y plan revisables | Elaborado; revisión pendiente |
| I1 Antes de HTTP: propiedad y permisos | Diseño técnico/seguridad: Pendiente de asignación; Madelin documenta | Acuerdo QA hospitalario/transversal, RBAC y propietario de almacén QA | Ámbito persistido, puertos actualizados y política aprobada; base para RNF-01/04 | Tenant A/B, sin token, permiso ausente; paridad InMemory/PDO | Decisión de arquitectura y pruebas negativas | Pendiente; bloquea exposición de rutas |
| I2 Semana 6 propuesta: alta HTTP RF-01 | Implementación: Pendiente de asignación | I1; almacén QA durable aislado autorizado; adaptador HTTP | POST respeta JSON/tipos/regla de dominio y no filtra detalles | 201, 400, 401/403, 413/415, 422, 503 y caída tras escritura | Logs sanitizados/request_id, pruebas de contrato | Pendiente |
| I3 Consulta de caso y cliente mínimo | Implementación: Pendiente de asignación | I1/I2; caso de uso lectura e interfaz | GET propio 200, ausente/ajeno 404; UI no decide autorización | GET/id inválido; carga/error; POST sin reintento automático | Salidas HTTP y demo autorizada sin secretos | Pendiente |
| I4 Campañas y asociación | Implementación/revisión: Pendiente de asignación | Casos y contrato nuevo aprobado | RF-02: relación válida, sin casos ajenos/inexistentes | Asociación válida e inválida en ambos adaptadores | Casos de aceptación y almacenamiento consistente | Pendiente; fuera del contrato inicial |
| I5 Ejecución, resultado e historia | Implementación/verificación: Pendiente de asignación | I4; contrato de comparación y snapshot esperado | RF-03/04/08; RNF-02/03/05: resultado coherente, historia inmutable | Aprobado/fallido, reejecución y rollback de operación relacionada | Matriz de contrato común; sin PASS anticipado | Pendiente |
| I6 Evidencia, defecto y trazabilidad | Implementación/verificación: Pendiente de asignación | I5; seguridad de archivos y política de severidad | RF-05/06/07; RNF-01/04: defecto solo desde fallo y enlaces completos | Acceso a evidencia, defecto desde aprobado rechazado, recorrido de vínculos | Evidencia privada, resultados reproducibles | Pendiente |
| I7 Integración compartida y aceptación | Revisión/aprobación: Pendiente de asignación | Incrementos probados; contrato con gobernanza/CI | Auditoría acordada, módulo sin SQL externo y límites visibles | Fallo de dependencia, logs seguros, regresión y aceptación manual | Acta de revisión; PR aprobado por tercero | Pendiente |

## Estrategia de issue, rama y PR

Issue maestro #12 se referencia con Refs #12; no se cierra. Este incremento usa
`feature/asii-28-semana-05-qa-regresion-madelinceron` desde origin/develop en
52097752, **sin worktree**. PR documental hacia develop con plantilla oficial,
commit sugerido `docs(asii-28): semana 05 contrato API e integracion` y push
explícito a la rama propia. No se mezcla código de promoción ni se reescribe historia.

Para I1–I7, preparar tareas vinculadas a #12 con alcance, aceptación, dependencia
y responsable confirmado; no se afirma que esas issues estén creadas. Cada tarea
tendrá rama propia desde develop actualizado y PR pequeño con pruebas/evidencia.
Si depende de un PR pendiente, esperar su integración antes de iniciar la siguiente
rama; no duplicar commits ni cambiar la base oficial a otra feature.
Verificar el diff completo contra origin/develop, máximo 400 líneas agregadas más
eliminadas. Esta entrega cabe en un PR; no necesita Parte 2. Revisión independiente
y aprobación quedan pendientes, sin autoaprobar ni fusionar.

## Comprobaciones de salida

Trazabilidad: RF-01 → contrato POST/I2 e implementación CLI actual; RF-02 → I4;
RF-03/04/08 y RNF-02/03/05 → I5; RF-05/06/07 y RNF-01 → I6; RNF-04 →
paridad de puertos en I1 y contrato de ejecuciones en I5/I6. No confundir las
17 pruebas de casos con cobertura de campañas, aislamiento o registro de ejecución.
Los tests HTTP, seguridad y cliente siguen previstos; no se ejecutaron en Semana 5.
