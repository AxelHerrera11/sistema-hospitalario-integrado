# Matriz decisión → RF/RNF → evidencia (componentes, UX, accesibilidad y movilidad)

| # | Decisión | RF/RNF | Evidencia |
|---|---|---|---|
| U-D01 | Lógica de interfaz pura en `estados.js`, separada del DOM (`vista.js`) y de la red (`app.js`) | RNF-EXP-06 | `tests/ui-estados.test.mjs` (11 pruebas) |
| U-D02 | Un mensaje por código estable del API; ninguno repite datos del paciente | RF-EXP-06, RNF-EXP-01 | V-04, V-08; `MENSAJES` en `estados.js` |
| U-D03 | Validación local con el mismo patrón que el servidor | RNF-EXP-04 | V-01, U-07, N-07 |
| U-D04 | Confirmación modal antes de abrir, foco en «Cancelar» | RF-EXP-01, RNF-EXP-07 | `d-07`, `m-06`; W-09 |
| U-D05 | Reintentos acotados (3) con salida explícita; apertura «segura de reintentar» | RNF-EXP-05 | V-05, V-06, V-07; A3/A6 |
| U-D06 | Motivo plegado + «Ocultar datos» + limpiar al cambiar identidad, hospital o código | RNF-EXP-01 | `d-03`; V-10; `verificacion-despues.json` |
| U-D07 | Fechas con zona horaria; nacimiento sin conversión | RF-EXP-02 | V-11; `m-02` |
| U-D08 | Paleta con contraste ≥ 4.5:1 y doble anillo de foco | RNF-EXP-07 | `semana-09/evidencias/contraste.txt` |
| U-D09 | Móvil primero, una columna < 768 px, objetivos de 44 px, sin datos en el dispositivo | RNF-EXP-07, RNF-EXP-01 | `m-01`…`m-07`; `estilos.css` |
| U-D10 | `textContent` en todo el pintado (sin HTML del API) | RNF-EXP-01 | `vista.js` (función `nodo`) |
| U-D11 | El error crítico no muestra datos y queda auditado | RF-EXP-04, RF-EXP-05 | `d-04`, `m-03`; C3/C4; P-07 |
