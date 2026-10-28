# ASII-23 Semana 12 — Segundo parcial: guion y rubro (reportes)

**Issue:** `Refs #152`. Evaluación tipo "Segundo evaluativo": presentación
del módulo ya aplicable con demo local; guía para ensayar y rendir.

## Guion de 7 minutos (30 % implementación, 40 % funcional, 30 % evidencias)

1. **Recapitulación (40 s):** regla del indicador `IND-RA-01`/`TCI-1.0`
   (Semana 1) y su vigencia en todo el módulo.
2. **Arquitectura (1.5 min):** cuatro capas + aeropuerto de repositorio con
   SQL preparado; raíz de composición `app.php`.
3. **Integración (1.5 min):** endpoint protegido
   `POST /api/v1/reports/indicators/IND-RA-01/calculate`
   (`tenant` + `jwt.auth` + `rbac:reportes.ver`), conexión perezosa con
   503 honesto.
4. **Pantalla (1.5 min):** "Reportes" con periodo/sala, leyenda de estados,
   mensajes seguros; igual permiso en backend y frontend.
5. **Demo en vivo (1.5 min):** los 5 pasos del prototipo (S11) con datos
   reales; mostrar 422 y 503 a propósito.
6. **Cierre (30 s):** tabla semana→PR→evidencia del manual del módulo.

## Preguntas probables del jurado

- «¿Evita el 0 % inventado?» — denominador 0 → estado publicado; el 0 %
  legítimo se muestra con datos (prueba explicita de cero válido).
- «¿Qué pasa con un dato corrupto (cancelados > finalizadas)?» — 503
  `LECTURA_NO_DISPONIBLE` sin porcentaje; se prueba en `run.php`.
- «¿Cómo evita filtrar datos personales?» — consulta agregada con `count`
  y `SUM`; el cuerpo de respuesta trae solo el agregado y su traza (test
  `test_la_respuesta_y_los_errores_no_exponen_datos_de_pacientes`).
- «¿Aislamiento entre hospitales?» — cada hospital es una base propia
  HOSPITAL pura; el tenant vía `X-Tenant-ID` y la cabecera la resuelve el
  middleware; las pruebas verifican hospital declarado vs. requerido.

## Rubro de la semana

| Criterio | Peso | Evidencia enumerable |
|---|---|---|
| Regla de dominio correcta | 25 % | `run.php` (11 escenarios) + tests Feature |
| Integración API + seguridad | 30 % | PRs #1245/#1269 + `config/rbac.php` |
| Pantalla/persuasión de uso | 25 % | PR #1248 + `reportes.test.mjs` (15) + a11y (11) |
| Evidencias y PRs verificables | 20 % | Manual del módulo: S1–S18 con PRs/promos |
