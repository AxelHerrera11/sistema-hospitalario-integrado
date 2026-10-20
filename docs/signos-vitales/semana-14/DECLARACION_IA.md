# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code (Anthropic) con el modelo Claude Sonnet 5.5.

**Propósito:** apoyo para definir el plan SQA: las siete métricas con su fórmula, umbral, fuente, responsable y acción, la matriz de riesgos con sus controles preventivos y detectivos, los responsables y el calendario, siguiendo el formato de la Semana 14 que ya usan otros módulos del equipo.

**Prompt relevante:** «Semana 14: Confiabilidad y aseguramiento de calidad», aplicado a `ASII-13`, «captura de signos vitales, evaluación de umbrales y generación de alerta».

**Contenido aceptado o modificado:** el autor asume el rol de dueño del perfil de umbrales sin ser médico certificado y lo deja escrito como limitación; la línea base sale de ejecutar las pruebas, la sonda de la Semana 13 y consultas al código real con PHP 8.3.33; en particular, que temperatura, sistólica y diastólica no tienen regla de umbral se confirmó enviando valores críticos, que dieron `201 OK`. Los umbrales de las métricas, las fechas del calendario y los nombres de los responsables son propuestas sin aceptar; el dueño de Alertas críticas no está definido y no hay revisión clínica externa. Las consultas SQL de Q-TRZ y Q-PRIV usan columnas provisionales de `local_access_audits`, que aún no existe, y no se ejecutaron contra una base. No se implementó código.

**Validación humana:** realizada el 2026-10-20. Josué Fernando Hicho García revisó el plan, las métricas y esta declaración, y la aprueba.
