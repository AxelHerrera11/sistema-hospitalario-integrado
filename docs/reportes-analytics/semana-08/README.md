# ASII-23 Semana 8 — Flujo y experiencia de usuario

**Issue:** `Refs #152` · **Estudiante:** María Yamilet Lindo Pablo (`Yamilet1235`).

## Entregables

| Requisito | Evidencia |
|---|---|
| Flujo por rol | [`01-flujo-por-rol.svg`](./01-flujo-por-rol.svg) |
| 4 a 6 wireframes anotados | [`wireframes.md`](./wireframes.md) (6) y el resumen [`02-wireframe-estados.svg`](./02-wireframe-estados.svg) |
| Reglas de interacción | Esta página |

## Reglas de interacción

| Regla | Comportamiento |
|---|---|
| R1 | Sin `reportes.ver`, el menú no ofrece la entrada y la ruta redirige |
| R2 | Se valida en la pantalla antes de llamar a la API; la API vuelve a validar |
| R3 | Un resultado solo vale para sus filtros: cambiar uno lo retira |
| R4 | Nunca se muestra una cifra junto a un error |
| R5 | N/A siempre lleva su razón; 0.00 % es un valor real |
| R6 | Los mensajes salen del código de error, nunca del detalle del servidor |
| R7 | El botón se desactiva mientras se calcula y anuncia «Calculando…» |
| R8 | Todo el formulario funciona con teclado y Enter |

## Roles

- **Con `reportes.ver`** (según `config/rbac.php`): `superadmin`, `admin_hospital`, `medico`, `tecnico_laboratorio` y `bioquimico`.
- **Sin el permiso:** por ejemplo `recepcion` y `enfermeria`. No ven la entrada y la API responde 403.

## Iteraciones

| Hallazgo | Resolución |
|---|---|
| No se sabía qué periodo se enviaba | Etiquetas, ayuda y validación local |
| Un 25.00 % no decía qué mide | Leyenda fija y trazabilidad |
| N/A parecía un fallo | Texto del estado |
| Mensajes con detalle técnico | Mensajes por código |

La evaluación con hallazgos numerados está en `../semana-09/README.md`. **No hubo prueba con usuarios reales.**
