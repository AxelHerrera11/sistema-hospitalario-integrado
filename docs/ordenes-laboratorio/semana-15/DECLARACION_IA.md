# Declaración de uso de inteligencia artificial — Semana 15

**Herramienta:** OpenCode con el modelo `deepseek-v4.1-flash-free`.
**Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`).
**Módulo:** Órdenes de laboratorio desde EMR.

## Propósito

Diseñar y documentar pruebas unitarias, de caja blanca y de caja negra para el flujo «emisión de una
orden desde el expediente y seguimiento de su estado», con trazabilidad a RF/RNF.

## Aportes y límites

- La IA redactó `tests/semana15.php`, la matriz, el registro de defectos y este README a partir del
  código y los requisitos de las semanas 1–14.
- Las salidas de `semana15.txt`, `run.txt`, `mvc.txt`, `api.txt`, `client.txt` y `contrato.txt` son
  ejecuciones reales del 22-oct-2026; no se transcribieron resultados no ejecutados.
- La corrección H-01 se implementó en el dominio y se probó con 4, 5, 250 y 251 caracteres.
- La corrección H-02 se limitó a completar las respuestas del contrato OpenAPI; no se generó un
  cliente desde ese contrato.
- H-03 y H-04 siguen abiertos. La IA no los declara corregidos ni asume que el gateway de producción
  ya exista.
- Los módulos de expediente y catálogo se representan con dobles de prueba; no se validaron sus
  implementaciones reales.
- La captura `capturas/resultados.jpg` se generó desde los logs de evidencia, no desde una sesión
  manual de terminal.

## Validación humana

**Pendiente.** Javier Alexander Fajardo López debe revisar los casos, ejecutar los comandos en su
entorno, confirmar las cifras y decidir si acepta los riesgos residuales de H-03 y H-04 antes de
publicar el PR.
