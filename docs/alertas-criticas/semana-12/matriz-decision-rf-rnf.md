# Matriz de decisiones de diseño — ASII-21

| Decisión de diseño | Requerimiento que la justifica | Semanas | Evidencia verificable |
|---|---|---|---|
| Lectura de verificación escrita a mano | RF-ALR-05, RNF-ALR-04 (seguridad) | Semanas 2, 8 y 11 | Paso «lectura incorrecta → READ_BACK_MISMATCH» |
| «Alertas no disponibles» distinto de «No hay alertas pendientes» | RNF-ALR-02 (ningún valor crítico se pierde) | Semanas 9 (H1) y 11 | Paso «servidor caído» del recorrido |
| Orden por vencimiento y minutos restantes | RF-ALR-07, RNF-ALR-01 (oportunidad clínica) | Semanas 8 y 11 | Captura `01-bandeja-desktop.png` |
| Notificación del sistema sin datos del paciente | Privacidad de los datos clínicos (regla común de la Semana 10) | Semana 10 | Escenario «médica de guardia» |
| «Crítica» en texto, no solo el 🔴 | WCAG 2.1 AA, criterio 1.4.1 (Semana 9) | Semanas 9 (H5) y 11 | Bandeja del prototipo |

Cada decisión responde a un requerimiento de la Semana 2 y se comprueba en el prototipo de la Semana 11 o en sus capturas.
