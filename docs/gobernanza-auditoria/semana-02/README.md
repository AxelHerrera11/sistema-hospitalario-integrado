# Gobernanza y Auditoría — Semana 2: requisitos y diseño

## Objetivo

Definir requisitos verificables (RF/RNF con identificador, prioridad, criterio de aceptación y prueba), las restricciones de seguridad y rendimiento, las reglas de aislamiento por tenant y el diseño inicial de responsabilidades con SOLID, en particular OCP.

## Entregables

| Entregable | Archivo |
|---|---|
| RF-GOB-01…10 y RNF-GOB-01…10 con criterios y pruebas | [requerimientos.md](requerimientos.md) |
| Restricciones de seguridad y de rendimiento | [requerimientos.md §3–4](requerimientos.md#3-restricciones-de-seguridad) |
| Reglas de aislamiento por tenant AT-01…05 | [requerimientos.md §5](requerimientos.md#5-reglas-de-aislamiento-por-tenant) |
| Responsabilidades y SOLID | [diseno-solid.md](diseno-solid.md) |
| Ejemplo antes/después (OCP) ejecutable | [ejemplo/ocp-antes-despues.php](ejemplo/ocp-antes-despues.php), diagramas `01-antes.svg`, `02-despues.svg` |

## Archivos creados

`README.md`, `requerimientos.md`, `diseno-solid.md`, `ejemplo/ocp-antes-despues.php`, `fuentes/01-antes.mmd`, `fuentes/02-despues.mmd`, `01-antes.svg`, `02-despues.svg` y `../evidencias/s02-ocp-antes-despues.txt`.

## Relación con la Semana 1

Cada UC-GOB de la Semana 1 se asigna a uno o más requisitos (ver la matriz de [narrativa-alcance.md §7](../semana-01/narrativa-alcance.md#7-matriz-de-trazabilidad-requisitos--diagramas)). Las reglas RN-01…RN-10 se convierten en criterios de aceptación.

## Uso

El ejemplo usa clases del módulo que se entregan en las Semanas 3–5 (`RecordAuditEvent`, `PdoAuditLog`, `SystemClock`). Se ejecuta una vez integradas esas semanas.

```bash
php docs/gobernanza-auditoria/semana-02/ejemplo/ocp-antes-despues.php
```

## Pruebas ejecutadas y resultados reales

Se ejecutó el ejemplo OCP con PHP 8.2.12, código de salida 0. La salida está en [../evidencias/s02-ocp-antes-despues.txt](../evidencias/s02-ocp-antes-despues.txt). Los criterios de aceptación se verifican en la Semana 15.

## Dependencias pendientes

- El permiso `auditoria.ver` (RF-GOB-07) lo tiene que aprobar RBAC.
- La meta de rendimiento (RNF-GOB-09) necesita PostgreSQL con datos de volumen y la propuesta de índices validada por Javier.

## Limitaciones conocidas

RNF-GOB-09 es un objetivo propuesto, sin medición. RNF-GOB-10 se evalúa con una revisión manual, sin usuarios reales (Semana 9).
