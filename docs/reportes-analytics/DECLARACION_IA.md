# Declaración de uso de IA — ASII-23 (documentación final)

## Propósito

Unifica las declaraciones por semana (Semana 2: `semana-02/DECLARACION_IA.md`;
Semana 3: `semana-03/DECLARACION_IA.md`; Semana 4:
`semana-04/DECLARACION_IA.md`) y añade la responsable de los PRs de
integración (#1237–#1271) y del manual del módulo.

## Cómo me apoyé en la IA

- Se desglosó el contrato HTTP y los casos límite en prueba-driven: cálculo
  con datos, `SIN_DATOS_EN_PERIODO`, 422 por regla del periodo, 503 por
  persistencia, 503 por lectura incoherente, 401/403/aislamiento y método
  no permitido, imitando el patrón probado del módulo de ocupación.
- María Yamilet Lindo Pablo diseñó/validó la estructura de capas, la
  fórmula con dos decimales y los estados publicados; el texto del manual
  y de esta declaración es de la estudiante.
- Toda evidencia de ejecución descrita se tomó de comandos realmente
  ejecutados (phpunit, `run.php`, `example-lsp.php` y los `node *.test.mjs`)
  en el entorno local.
