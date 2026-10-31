# Registro de defectos y brechas

Estados: Abierto = falta solución definitiva; Corregido = corrección con evidencia;
Bloqueado = dependencia impide cerrar; No reproducido = no se observa el síntoma
en esta ejecución. Un riesgo o caso pendiente no se convierte en defecto confirmado.

| ID / naturaleza | Evidencia y severidad | Estado actual | Responsable / acción y verificación |
|---|---|---|---|
| REV-01 / defecto previo de #1073 | Acceso cruzado anterior a #1154; prioridad alta. Guard actual deniega cruces y pasa 3 tests/37 aserciones. | Abierto, contenido temporalmente; no se reprodujo exposición con el guard actual | Madelin Cerón: seguimiento documental. Corrección técnica y verificación independiente: Pendiente de asignación; acordar mapeo seguro y probar solución definitiva. |
| ENV-01 / bloqueo ambiental previo, no defecto de código | Semana 14 registró conexión rechazada 55433; esta ejecución pasa restricciones, aislamiento e integración. | No reproducido en Semana 15; no se modifica evidencia histórica | Madelin Cerón registra recuperación observada; repetir ante cambios de entorno. |
| GAP-01 / brecha funcional | P01: creación disponible en servicio/micro-HIS, ausente HTTP/UI; sin permiso de escritura acordado. | Abierto; casos HTTP de alta no ejecutables | Definición y corrección técnica: Pendiente de asignación; Madelin documenta el contrato que se apruebe. |
| GAP-02 / brecha de evidencia | P02/P04: no se demuestra atomicidad multirregistro de negocio ni paridad completa fake/PDO. | Abierto; sin PASS de esos criterios completos | Ejecución técnica/verificación: Pendiente de asignación; definir operación y matriz equivalente antes de cerrar. |
| GAP-03 / riesgo de interpretación | P03: wards sin capacidad máxima; conteo no es aforo ni disponibilidad clínica. | Bloqueado por definición funcional ausente | Responsable funcional: Pendiente de asignación; acordar regla sin inventar campos ni RF/RNF. |
| GAP-04 / evidencia visual | Sin recorrido autenticado, teclado, zoom o lector de pantalla; antecedentes REV-02/03/04 no se cierran. | Bloqueado por identidad autorizada y revisión pendientes | Madelin gestiona seguimiento; verificación independiente: Pendiente de asignación. |

Las pruebas nuevas no confirmaron defectos adicionales del código existente y
no hubo correcciones productivas en este PR. Ningún hallazgo se marca Corregido
solo porque las pruebas pasan. Roles de revisión, corrección técnica, verificación
y aprobación siguen pendientes de asignación; no se atribuyen a otros integrantes.
No se modifica #1073 ni #1364. Aprobación de esta entrega requiere nueva revisión.
