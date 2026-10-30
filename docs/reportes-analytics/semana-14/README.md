# ASII-23 Semana 14 — Plan de aseguramiento de calidad (SQA)

**Issue:** `Refs #152` · **Estudiante:** María Yamilet Lindo Pablo (`Yamilet1235`).

Atributos: exactitud del indicador, confiabilidad ante fallos, seguridad y trazabilidad. **Medido** es un valor obtenido el 30/10/2026; **Objetivo** aún no tiene entorno para medirse.

## Métricas

| ID | Métrica | Umbral | Fuente | Valor | Estado | Acción si no cumple |
|---|---|---|---|---|---|---|
| M1 | Escenarios del micro-HIS en verde | 100 % | `evidencia/metricas.php` | 15/15 | Medido | Bloquear el PR |
| M2 | Contrato LSP | 7/7 | `metricas.php` | 7/7 | Medido | Bloquear el PR |
| M3 | Errores de sintaxis en PHP | 0 | `metricas.php` | 0 | Medido | Bloquear el PR |
| M4 | Reglas y accesibilidad de la pantalla | 100 % | `metricas.php` | 20 y 15 | Medido | Corregir antes de integrar |
| M5 | Tamaño de cada PR | ≤ 400 líneas | `git diff --numstat` | Máximo 354, en los PR del 30/10/2026 | Medido | Dividir |
| M6 | Recorrido en Chrome | 100 % | `semana-11/evidencia/recorrido-e2e.mjs` | 15/15 | Medido | Corregir y repetir |
| M7 | p95 del cálculo | ≤ 500 ms | Medición local con 20 peticiones | 364 ms en el servidor de desarrollo de PHP, sin opcache, con 4 episodios. Una medición anterior dio 1040 ms con el equipo sin memoria libre | **Cumple en local**; no representa producción | Repetir con volumen real antes de darlo por válido |

## Matriz de riesgos

| Riesgo | Probabilidad | Impacto | Nivel | Control |
|---|---|---|---|---|
| R1 Cifra incorrecta por cambio del esquema | Media | Alto | Alto | P1, D1 |
| R2 Ver datos de otro hospital | Baja | Alto | Medio | P2, D2 |
| R3 Mostrar 0 % cuando no hay datos o la base falló | Baja | Alto | Medio | P3, D1 |
| R4 Cifra obsoleta junto a un error | Baja | Medio | Bajo | P4, D3 |
| R5 Ningún módulo escribe `cancelled`: el indicador daría siempre 0 | Alta | Medio | Alto | P5 |
| R6 Sin CI por facturación de Actions | Alta | Medio | Alto | D4 |
| R7 Consulta lenta con volumen real | Media | Medio | Medio | D5 |

## Controles

| ID | Tipo | Control | Responsable | Momento |
|---|---|---|---|---|
| P1 | Preventivo | La prueba de integración usa las migraciones reales | Autora | Cada PR |
| P2 | Preventivo | Middleware `tenant`, `jwt.auth`, guard de base y permiso | Autora | Cada PR |
| P3 | Preventivo | Estados sin porcentaje en el dominio | Autora | Diseño |
| P4 | Preventivo | `limpiar()` antes de validar | Autora | Diseño |
| P5 | Preventivo | Confirmar quién cancela episodios | Autora | Pendiente |
| D1 | Detectivo | `run.php`, `tests/Feature/Reportes` | Autora | Cada PR |
| D2 | Detectivo | `test_miembro_de_otro_hospital_…` | Autora | Cada PR |
| D3 | Detectivo | `recorrido-e2e.mjs` | Autora | Antes de integrar la pantalla |
| D4 | Detectivo | Ejecución local declarada; CI cuando se libere | Autora | Cada PR |
| D5 | Detectivo | Repetir M7 con volumen real | Autora | Cuando exista staging |

## Calendario

| Semana | Actividad |
|---|---|
| 14 | Línea base de métricas |
| 15 | Matriz de pruebas y defectos |
| 16 | Integración y regresión global |
| 17 | Amenazas y pruebas de seguridad |
| 18 | Medición final antes de la demostración |

## Rastreo de defectos

Los defectos reales están en [`../semana-15/matriz-pruebas.md`](../semana-15/matriz-pruebas.md#defectos).
