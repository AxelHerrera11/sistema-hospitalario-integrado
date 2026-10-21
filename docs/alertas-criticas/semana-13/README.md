# Alertas críticas y notificaciones internas — Semana 13: plan de revisión técnica formal

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-21 · **Entrega:** checklist de revisión, responsables y evidencia.

## Objetivo

Definir una revisión técnica formal, reproducible y verificable del módulo: qué se revisa, con qué criterios de entrada y salida, quién revisa, cómo se registra la evidencia y cómo se cierran los hallazgos. El plan distingue lo que **ya se comprobó** de lo que **sigue abierto**.

## Alcance

`micro-his/alertas-criticas/` (dominio `CriticalAlert`, casos de uso, `AlertRepository`, `ApiKernel`, `CriticalAlertsClient` y `openapi.yaml`) y `docs/alertas-criticas/semana-01` a `semana-12`.

## Criterios de entrada

- PR abiertos hacia `develop` con el CI «Validate ASII PR» en verde y menos de 400 líneas cada uno.
- `php micro-his/alertas-criticas/tests/run.php` y `tests/api.php` terminan con código 0.
- `openapi.yaml` valida sin advertencias con `npx @redocly/cli@1.34.5 lint`.
- El autor entrega el paquete: enlaces a los PR, salida de las pruebas y el registro de hallazgos previo.

## Criterios de salida

- Ningún hallazgo de severidad **Alta** abierto.
- Los hallazgos **Media** cerrados o con responsable y fecha.
- La moderadora deja constancia en el issue del módulo (#40).

## Revisores

Revisor técnico propuesto: Gerson Orellana (validación de resultados, ASII-20), que publica el evento, y Josué Hicho (signos vitales, ASII-13). Roles y pasos en [responsables-procedimiento.md](./responsables-procedimiento.md); qué revisar en [checklist-revision.md](./checklist-revision.md); hallazgos en [registro-hallazgos.md](./registro-hallazgos.md). Uso de IA: [DECLARACION_IA.md](./DECLARACION_IA.md).
