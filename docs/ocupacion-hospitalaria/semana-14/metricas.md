# Métricas propuestas de calidad

Los valores sin línea base medida son objetivos propuestos. Cada medición debe registrar SHA, entorno, volumen, comando y fecha.

| ID | Métrica y fórmula | Umbral propuesto | Frecuencia | Responsable / fuente |
|---|---|---|---|---|
| M-01 | Camas clasificadas / camas leídas | 100 % | Cada suite | Autor / prueba de dominio |
| M-02 | Global igual a suma de salas | 100 % | Cada suite | Autor / prueba de agregación |
| M-03 | Casos capacidad cero con tasa `null` | 100 % | Cada suite | Autor / prueba de borde |
| M-04 | Fallas simuladas que devuelven `503` sin métricas | 100 % | Cada suite | Autor / prueba HTTP |
| M-05 | Payloads revisados sin PII | 100 % | Cada cambio de contrato | Revisor seguridad / esquema JSON |
| M-06 | Casos negativos JWT, permiso y tenant aprobados | 100 % | Cada PR | QA / suite HTTP |
| M-07 | Latencia de consulta p95 con 10 000 camas | `<= 1 s` | Antes de entrega y tras cambiar SQL | QA / prueba PostgreSQL |
| M-08 | Tasa de respuestas `5xx` | `< 1 %` excluyendo pruebas de caos | Diaria en entorno integrado | Operación / métricas HTTP |
| M-09 | Edad p95 de fotografía mostrada como actual | `<= 5 min` | Continua | Frontend/operación |
| M-10 | Tareas críticas completables solo con teclado | 100 % | Cada cambio UI | QA accesibilidad |
| M-11 | Contraste de texto/controles | `>= 4.5:1` / `>= 3:1` | Cada cambio visual | QA / medición |
| M-12 | Viewports 320-1024 sin scroll horizontal crítico | 100 % | Cada cambio responsive | QA / navegador |
| M-13 | Pruebas obligatorias aprobadas / ejecutadas | 100 % | Cada commit candidato | CI |
| M-14 | Hallazgos bloqueantes/mayores abiertos | 0 | Antes del PR | Moderador / acta |
| M-15 | Hallazgos cerrados en plazo / hallazgos con vencimiento en el período | `>= 90 %`; `N/A` si no vencía ninguno | Semanal | Secretario / registro |
| M-16 | Defectos reabiertos / defectos cerrados | `< 10 %` | Por entrega | QA / issue tracker |

## Interpretación

- Un promedio no reemplaza p95 porque puede ocultar consultas lentas.
- Cero inconsistencias operativas no es meta de ASII-09: el módulo debe detectarlas, no ocultarlas.
- Cobertura de líneas puede acompañar M-13, pero no sustituye casos de comportamiento.
- M-08 requiere volumen suficiente; sin tráfico se reporta `sin datos`, nunca 0 %.
- Un incumplimiento documenta tendencia, causa y acción; no se ajusta el umbral para aparentar cumplimiento.

## Registro de medición

| Fecha | SHA | Métrica | Resultado | Entorno/volumen | Estado | Evidencia | Acción |
|---|---|---|---|---|---|---|---|
|  |  |  |  |  | Cumple / No cumple / Sin datos |  |  |
