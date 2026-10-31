---
marp: true
paginate: true
size: 16:9
style: |
  section { font-size: 22px; color: #0f172a; }
  h1 { color: #1e3a8a; font-size: 34px; }
  h2 { color: #1e3a8a; font-size: 32px; }
  table { font-size: 18px; }
  code { font-size: 18px; }
---

# Segundo parcial — Traslados, altas y liberación de camas (ASII-08)

Presentación breve en 5 diapositivas (Markdown; cada `---` separa una). Cubre la etapa de experiencia de usuario, semanas 8 a 11. No se declara ninguna defensa oral ni solicitud del docente.

---

## 1. Del micro-HIS a la interfaz

- El prototipo `public/ui/` usa la **misma API y la misma base** del micro-HIS: no hay datos simulados en la lógica.
- Flujo: paciente → situación → datos → confirmación → resultado ([mapa](../semana-11/prototipo.md#mapa-de-navegación)).

---

## 2. Recorrido

1. Consultar la estancia: muestra el episodio, la cama y si la estancia sigue vigente.
2. Elegir «Trasladar»: cama y motivo, con validación local.
3. Revisar el resumen y confirmar: el botón se bloquea y se anuncia «Procesando…».
4. Éxito con la nueva estancia y la hora, y «Operar sobre la nueva estancia» para el alta.

Evidencia: recorrido de 9/9 a 1024, 430, 375 y 320 px.

---

## 3. Evaluar con evidencia y corregir

- Semana 9: 10 sondas automáticas, de las que **3 cumplían**; 7 hallazgos reales.
- Semana 10: se corrigieron H1–H4, H6 y H7 y las sondas pasaron a **9/10**. Queda H5, la lista de camas, que depende de la integración.

---

## 4. Conexión limitada e incertidumbre

- Sin red no se envía nada y se puede reintentar.
- Con red lenta, a los 15 s el resultado se informa como **incierto**. La consulta posterior mostró que el traslado **sí** se había confirmado: tratarlo como «sin cambios» habría sido un éxito o un fallo falso.

---

## 5. Cambio documentado y pendientes

- Cambio (**ejercicio simulado**): Escape retrocede un paso y el foco ya no queda en un botón oculto. Teclado 1/4 → 4/4 ([detalle](./cambio-practico.md)).
- Pendientes: RBAC, tenant y JWT; lista de camas; concurrencia con conexiones independientes; pruebas con usuarios.
