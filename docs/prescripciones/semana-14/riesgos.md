# Riesgos y controles propuestos

| Riesgo | Control propuesto | Evidencia futura |
|---|---|---|
| Un rechazo 422 guarda parcialmente | Pruebas de aplicación y persistencia que inspeccionen escrituras | Resultado verificable antes/después |
| Error del lector de alergias se interpreta como ausencia | Propagar el error como falla explícita | Prueba de puerto fallido |
| Respuesta clínica se confunde con error técnico | Contrato y mensajes diferenciados | Pruebas API/UI |
| Se incorpora por accidente flujo de excepción pendiente | Revisar contrato, rutas y navegación contra el alcance aprobado | Búsqueda en artefactos integrados |
| Ruta o permiso no coincide entre UI y backend | Prueba integrada con sesión autorizada/no autorizada | Resultado de ambos casos |
| Cambio de esquema pierde autoría médica | Revisar migración y restricciones contra requisito aprobado | Prueba de integridad de base |
| Documento presenta propuesta como hecho | Revisión de estado y trazabilidad de afirmaciones | Checklist documental |

No se asignan probabilidades ni puntajes de riesgo sin análisis aprobado.
