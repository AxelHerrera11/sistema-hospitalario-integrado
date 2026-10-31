# Formato de hallazgos

## Campos

| Campo | Descripción | Valores |
|---|---|---|
| Id | `RTF-NN` correlativo | — |
| Ítem | Ítem del checklist que lo originó | RT-01…RT-15 |
| Ubicación | `archivo:línea` o prueba | — |
| Descripción | Qué está mal, en una frase verificable | — |
| Evidencia | Comando, salida o fragmento | — |
| Severidad | Crítica: expone datos o permite acceso indebido · Mayor: rompe integridad o contrato · Menor: calidad sin riesgo inmediato | Crítica / Mayor / Menor |
| Tipo | Defecto · Omisión · Inconsistencia doc/código · Pregunta | — |
| Responsable | Quien corrige | — |
| Estado | Abierto · Corregido (commit) · Aceptado con issue | — |

## Plantilla

| Id | Ítem | Ubicación | Descripción | Evidencia | Severidad | Tipo | Responsable | Estado |
|---|---|---|---|---|---|---|---|---|
| RTF-01 | | | | | | | | Abierto |

## Reglas

- Se registran hechos, no personas; se discute la solución fuera de la reunión.
- Un hallazgo sin evidencia reproducible se registra como «Pregunta».
- La revisión todavía **no se realizó** (requiere a los revisores). Este documento es el formato que se usará.
