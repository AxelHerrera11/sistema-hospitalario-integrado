# Gobernanza y Auditoría — Semana 14: plan SQA

## Objetivo

Definir el plan de aseguramiento de calidad del módulo: objetivos, alcance, métricas con método y umbral, riesgos y controles, responsables, estrategia de pruebas, criterios de aceptación y seguimiento de defectos.

## Entregables

[plan-sqa.md](plan-sqa.md) incluye 7 métricas: M-01…05 **medidas** con datos reales y M-06…07 **objetivos** no medibles en este entorno.

## Archivos creados

`README.md` y `plan-sqa.md`.

## Relación con semanas anteriores

Las métricas salen de las suites de las Semanas 3–13, del registro de hallazgos de la Semana 13 y de la matriz de la Semana 15.

## Instrucciones (recalcular las métricas)

M-01: aprobación de pruebas.

```bash
php micro-his/gobernanza-auditoria/tests/run.php | grep -cE '^OK '
```

M-03: archivos de más de 400 líneas.

```bash
find micro-his/gobernanza-auditoria app/GobernanzaAuditoria docs/gobernanza-auditoria -type f \( -name '*.php' -o -name '*.md' -o -name '*.js' -o -name '*.css' -o -name '*.html' -o -name '*.mmd' -o -name '*.sql' \) -exec wc -l {} + | awk '$1>400 && $2!="total"'
```

M-06 (cuando haya PCOV):

```bash
php -d pcov.enabled=1 micro-his/gobernanza-auditoria/tests/run.php
```

## Pruebas ejecutadas y resultados reales

- **M-01:** 112/112.
- **M-02:** 18/20 (los M: 15/15).
- **M-03:** 0.
- **M-04:** 0.
- **M-05:** Alta propia 0.

## Dependencias pendientes

- **M-06:** requiere PCOV o Xdebug.
- **M-07:** requiere PostgreSQL con datos de volumen y la propuesta 001.

## Limitaciones conocidas

La cobertura de líneas no se midió, así que no se reporta ningún porcentaje para ella.
