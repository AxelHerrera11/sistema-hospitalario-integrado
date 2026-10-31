# Guion de la demo funcional (10 minutos)

Proceso de la adaptación: **planificación de pruebas → ejecución E2E → conservación de evidencia → quality gate**.
Salida real de esta demo: [`evidencias/01-demo.txt`](evidencias/01-demo.txt).

## Preparación

```bash
php micro-his/qa-calidad-versiones/tests/run.php       # «TODAS LAS SUITES APROBADAS»
php -S 127.0.0.1:8093 -t micro-his/qa-calidad-versiones/public micro-his/qa-calidad-versiones/public/index.php
```

Abrir `http://127.0.0.1:8093/prototipo/`. Plan B: capturas de la Semana 11 y la salida del pipeline local.

## Recorrido

| Min | Paso | Qué mostrar | Resultado esperado | Requisito |
|---|---|---|---|---|
| 0–1 | Contexto | El workflow `QA CI y E2E` y su réplica local `pipeline-local.sh`; por qué hoy se usa la local (facturación de GitHub) | — | RF-07 |
| 1–3 | E2E del módulo | Planificar → registrar resultados → gate: aprobado, rechazado y bloqueado | 9/9 | RF-01…RF-06 |
| 3–4 | Permisos y tenant | Observador no planifica; otro hospital no ve ejecuciones; RBAC caído → 503 | E2E-QA-04…07 | Seguridad |
| 4–6 | Pipeline sobre la versión integrada | Etapas, micro-HIS de los 25 módulos y veredicto del gate con sus motivos | Ver [`evidencias/01-demo.txt`](evidencias/01-demo.txt) | RF-07 |
| 6–7 | Lectura del gate | Por qué rechaza: fallos que requieren PostgreSQL frente a fallos de contrato | Clasificación reproducible | Semana 16 |
| 7–8 | Evidencias seguras | `escanear-secretos.php` sobre la carpeta de evidencias | 0 hallazgos | H-QA-17-01 |
| 8–9 | Despliegue | `desplegar-micro-his.sh`: release roto → rollback automático | Ensayos de la Semana 17 | Guía §4 |
| 9–10 | Cierre | Qué falta para la primera ejecución en GitHub (B-03) y decisiones del grupo (B-04) | — | Pendientes |

## Preguntas probables y respuesta corta

| Pregunta | Respuesta |
|---|---|
| ¿El gate aprueba si una suite no corrió? | No: una obligatoria ausente bloquea y una omitida rechaza (CI17, CI18). |
| ¿Por qué el gate local rechaza? | Feature tiene fallos que necesitan PostgreSQL y uno de contrato de otro módulo; en GitHub se levantan las bases. |
| ¿Cómo se protege lo que se guarda como evidencia? | Secretos efímeros enmascarados y escáner antes de conservar; nunca datos reales. |
| ¿Por qué no fijaron las acciones por SHA? | No se puede validar el workflow mientras GitHub no ejecute jobs; queda como riesgo medio con el SHA identificado. |
| ¿Qué no se probó? | La ejecución en GitHub Actions, la suite `Database` y el E2E de pila completa (necesitan PostgreSQL). |
