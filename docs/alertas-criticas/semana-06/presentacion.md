# Guion de la defensa — Alertas críticas (ASII-21)

1. **Problema** (1 min): un potasio de 7,1 sin nadie que lo vea a tiempo es un riesgo vital.
2. **Tesis** (1 min): una alerta por resultado, un responsable que la confirma con lectura de verificación y auditoría de cada paso.
3. **Del UML al código** (2 min): UC-ALR-01, 02 y 04 → `CriticalAlert::detect` y `acknowledge` → casos de uso → `AlertRepository` → `/api/v1`.
4. **SOLID** (1 min): SRP; la demo de la Semana 2 cambia canal y plazo sin tocar el detector.
5. **Idempotencia y concurrencia** (2 min): `UNIQUE` por resultado y `UPDATE … WHERE status = 'open'`.
6. **Cliente-servidor** (1 min): la publicación se reintenta porque el servidor deduplica, y `retry_later` deja el mensaje en el inbox. Frontera: el núcleo queda como módulo y el despachador se separa como *worker*.
7. **Caso práctico** (2 min): la saturación de oxígeno de Signos vitales como segunda fuente.
8. **Riesgos abiertos** (30 s): migración de `critical_alerts`, formato del evento y el *worker*.

**Preguntas que preparo:**
- ¿Qué pasa si la notificación falla? La alerta ya está guardada y el plazo sigue corriendo; el escalamiento la sube de nivel.
- ¿Por qué no basta con que la UI evite el doble clic? Porque dos personas distintas pueden acusar a la vez; lo decide la base.
