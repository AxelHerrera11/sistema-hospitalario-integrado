---
marp: true
paginate: true
size: 16:9
style: |
  section { font-size: 26px; color: #0f172a; }
  h1 { color: #1e3a8a; font-size: 42px; }
  table { font-size: 22px; }
  code { font-size: 22px; }
---

# Reportes y analytics básicos (ASII-23)

Segunda evaluación parcial: diseño de la experiencia con el prototipo

María Yamilet Lindo Pablo · `Yamilet1235` · Issue #152

---

# Recorrido navegable

![h:300](../../semana-11/capturas/01-formulario-1280.png) ![h:300](../../semana-11/capturas/02-resultado-25-1280.png)

Menú Reportes → fechas → Calcular → resultado con su definición.

---

# Decisiones visibles

- **Hasta (no incluido):** la ayuda explica el intervalo con un ejemplo.
- **Un solo mensaje:** nunca hay una cifra junto a un error.
- **N/A tiene razón:** el estado se explica con texto.

![h:230](../../semana-11/capturas/03-periodo-invertido-1280.png)

---

# Móvil y errores

![h:400](../../semana-11/capturas/06-resultado-375.png) ![h:400](../../semana-11/capturas/07-periodo-invertido-375.png)

Columna única, 44 px táctiles y sin desplazamiento horizontal a 320, 375 y 430 px.

---

# Decisión → requisito → evidencia

| Decisión | Requisito | Evidencia |
|---|---|---|
| Limpiar antes de validar | Sin cifra obsoleta | `recorrido-e2e.mjs` |
| Ayuda asociada a las fechas | Periodo comprensible | `reportes.a11y.test.mjs` |
| Sin datos ≠ cero | No inventar datos | `run.php` |
| Mensaje por hospital | Aislamiento | `recorrido-e2e.mjs` |

---

# Cambio practicado y pendientes

- **Ejercicio propio, no pedido por el docente:** retirar el resultado al cambiar un filtro.
- **Sin probar:** lector de pantalla y dispositivo físico.
- **Sin realizar:** exposición, evaluación docente y prueba con usuarios.
