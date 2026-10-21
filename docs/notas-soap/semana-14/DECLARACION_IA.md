# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code, modelo `claude-sonnet-5-5` (ejecutado sobre Windows).

**Propósito:** definir el plan SQA de Notas SOAP y diagnósticos: siete métricas con umbral, fuente, responsable y acción, matriz de riesgos, controles preventivos y detectivos y calendario de seguimiento.

**Revisado y ajustado:**

- Las métricas parten de los RF-SOAP-01 a 07 y RNF-SOAP-01 a 05 de la Semana 2 y de los puntos de atención de la revisión técnica de la Semana 13; los riesgos R-06 y R-10 salen de las peticiones a la base de datos de la Semana 6.
- Las consultas Q-INT1 y Q-INT2 usan columnas que existen en la migración `000400`; Q-TRZ y Q-PRIV son provisionales porque `local_access_audits` aún no tiene migración. **Ninguna consulta se ejecutó sobre PostgreSQL**; el estado inicial de Q-06 se tomó del esquema SQLite de pruebas.
- Los responsables y las fechas son una propuesta: nadie ha aceptado todavía las tareas.
- Este PR sale de `develop` y no enlaza a las semanas anteriores, que aún no están integradas.
- En esta entrega no se modificó código del micro-HIS ni del HIS.

**Validación humana:** realizada el 2026-10-20. Josué Fernando Hicho García revisó el plan SQA, las siete métricas con su umbral, fuente, responsable y acción, la matriz de riesgos, los controles y el calendario de seguimiento, y aprueba el documento.

**Responsable:** Josué Fernando Hicho García es responsable de esta entrega.
