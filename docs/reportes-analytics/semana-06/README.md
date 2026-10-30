# ASII-23 Semana 6 — Primer parcial: guion de exposición (reportes)

**Issue:** `Refs #152`. Evaluación tipo "Primer evaluativo" — el estudiante
defiende el diseño del cálculo; esta guía sirve para ensayar y para rendir.

## Guion de 6 minutos

1. **Problema (30 s):** coordinación necesita el % de hospitalizaciones
   canceladas; hoy se suma a mano con exportaciones parciales.
2. **Semana 1 — regla acordada (1 min):** `IND-RA-01`/`TCI-1.0` = `100 ×
   N_cancelados / N_finalizados` sobre `HOSPITAL.care_episodes`
   (`type='inpatient'`, `status ∈ {closed,cancelled}`); periodo medio abierto
   `[desde, hasta)` sobre `ended_at`; fallback obligatorio; sin denuestos
   inventados, con `SIN_DATOS_EN_PERIODO` cuando el periodo no trae datos.
3. **Semana 2 — LSP (1 min):** el ejemplo ejecutable muestra una jerarquía
   rígida que rompe el contrato (precondiciones más fuertes con
   denominador 0) y su sustitución por el puerto `SummaryReader`.
4. **Semanas 3–4 — arquitectura (2 min):** cuatro capas (Presentation/
   Application/Domain/Persistence) y repositorio con consulta preparada;
   `app.php` como raíz de composición.
5. **Demo o captura (1 min):** POST del endpoint con periodo y ver el
   porcentaje con dos decimales.
6. **Cierre (30 s):** flasado de garantías y PRs.

## Con enzyme del seminario — qué preguntan los jurados y cómo responder

- «¿Y si no hay datos?» — estado publicado, ni 0 % ni 500.
- «¿Qué pasa si la base cae?» — 503 sin cifras previas.
- «¿Filtrar por sala?» — `service_code` opcional en la consulta preparada.
- «¿Alguien ve datos de pacientes?» — no: solo agregados y trazas mínimas.
- «¿Quién autoriza?» — RBAC del backend (`reportes.ver`), no el SPA.
