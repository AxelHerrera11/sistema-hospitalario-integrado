# Matriz decisión → RF/RNF → evidencia (Semana 12)

Cada decisión de componentes, UX, accesibilidad o movilidad se liga al requisito que protege ([RF/RNF de la Semana 2](../semana-02/rf-rnf-criterios.md)) y a la evidencia que otra persona puede repetir. **Estado:** *Prototipo* = se ve y se prueba en [`prototipo.html`](../semana-11/prototipo.html); *Código* = lo cubre una prueba del micro-HIS; *Diseño* = está documentado, no construido.

| # | Decisión | RF / RNF | Evidencia verificable | Estado |
|---|---|---|---|---|
| 1 | La unidad no tiene valor por defecto: hay que elegirla (B-01) | RF-SV-03 · RNF-SV-02 | Con `5,5` en glucosa y sin unidad, «Siguiente» muestra «Elija la unidad.» y no avanza | Prototipo |
| 2 | Aviso suave si el valor es raro para la unidad (B-01) | RF-SV-03 | `5,5` con `mg/dL`: «5,5 es raro en mg/dL: ¿es mmol/L?»; con `mmol/L` no aparece | Prototipo |
| 3 | Coma o punto decimal; se rechaza lo ambiguo (B-02) | RF-SV-02 | `5,5` y `5.5` valen 5,5; `1.234,5` da «Escriba un número»; `138,5` se acepta | Prototipo |
| 4 | Conversión a la unidad canónica antes de evaluar (`Measurement`, `Unit`) | RF-SV-03, 04 | `php docs/signos-vitales/semana-07/evidencia/demo-refactor.php`: 15 `PASS` | Diseño (demo ejecutable) |
| 5 | La confirmación repite al paciente y muestra la conversión (B-03) | RF-SV-01 | Confirmar: «María Gómez · exp-7734 · Nac. 14/03/1981» y «5,5 mmol/L → 99,1 mg/dL» | Prototipo |
| 6 | Valor imposible: error junto al campo sin repetir el número | RF-SV-02 | FC `1380`: «El valor no es posible…» y el valor no aparece en el mensaje. Servidor: `run.php`, «temperatura 50 °C → 422» | Prototipo y código |
| 7 | La alerta crítica lleva texto, ícono y `role="alert"`; no se cierra sola (B-04) | RF-SV-04 · RNF-SV-02 | Resultado con FC 138: «■ ALERTA CRÍTICA» y `role="alert"` en el DOM. Servidor: `run.php`, «FC 138 + SpO2 94 → critical» | Prototipo y código |
| 8 | «No evaluado» distinto de «sin valores fuera de umbral» | RF-SV-06 | `?edad=9&demo=glu#/resultado`: «? NO EVALUADO». Servidor: `run.php`, «paciente de 9 años» | Prototipo y código |
| 9 | Sin respuesta: «No sabemos si se guardó», sin reintento automático | RF-SV-07 · RNF-SV-03 | «Simular error crítico» → Registrar: pantalla de error; «Revisar mediciones» antes de «Registrar de nuevo». Cliente: `client.php`, «registro sin servidor → 1 intento» | Prototipo y código |
| 10 | Enfermería en solo lectura: sin controles ni deshabilitados | RNF-SV-01 | `?rol=enfermeria#/episodio`: no hay `data-act=nueva` en el DOM | Prototipo (el servidor decide) |
| 11 | Falla cerrada: 401, 403, 503 con `trace_id` | RNF-SV-01 | `php micro-his/signos-vitales/tests/api.php`: 14 casos, entre ellos «RBAC caído → 503» | Código |
| 12 | El dato clínico no se guarda en el navegador | RNF-SV-01 | `grep -cE "localStorage\|sessionStorage\|indexedDB" docs/signos-vitales/semana-11/prototipo.html` devuelve `0` | Prototipo |
| 13 | Severidad y estados con contraste ≥ 4.5:1 (B-06) | RNF-SV-02 | `php docs/signos-vitales/semana-09/evidencia/contraste.php` y la tabla de contrastes de la [Semana 11](../semana-11/README.md#accesibilidad) | Prototipo |
| 14 | Móvil en una columna, objetivos de 44 px y barra de acción inferior (B-11) | RNF-SV-04 | Ventana de 390 px: sin desplazamiento horizontal; capturas `movil-*.png` de la [Semana 11](../semana-11/README.md) | Prototipo |

## Requisitos que faltan

La Semana 2 define RNF-SV-01 a 05 y ninguno cubre accesibilidad, movilidad ni unidades, aunque las decisiones 1 a 3, 13 y 14 dependen de ellos. Se proponen:

| Código | Requisito propuesto | Criterio medible |
|---|---|---|
| **RNF-SV-06** | Accesibilidad y movilidad: cada pantalla del flujo cumple WCAG 2.2 AA y se usa sin desplazamiento horizontal desde 320 px | Contraste ≥ 4.5:1 y objetivos ≥ 44 px; axe sin violaciones *serious* o *critical* en cada estado |
| **RNF-SV-07** | Unidades explícitas: ninguna medición se evalúa en una unidad que la persona no haya elegido | Con glucosa o temperatura sin unidad, el flujo no avanza (decisión 1) |

## Decisiones sin evidencia ejecutable

La decisión 4 está probada solo por una demo; la fecha con selector nativo (B-09), el aviso de sesión que vence (B-08) y la lista del episodio por la API son diseño o propuesta. No se afirma que estén construidas.
