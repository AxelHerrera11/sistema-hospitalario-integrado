# Mediciones de la evaluación (21-oct-2026)

Ejecutadas con `javascript_exec` en el navegador integrado sobre `http://127.0.0.1:8093/prototipo/` (servidor de demo, datos ficticios).

## Antes de corregir

```json
{
  "contrast": [
    "--text sobre --bg: 14.67", "--text sobre --surface: 15.73",
    "--muted sobre --surface: 7.53", "--muted sobre --bg: 7.02",
    "--primary-text sobre --primary: 7.17", "--primary sobre --surface: 7.17",
    "--ok sobre --ok-bg: 6.93", "--bad sobre --bad-bg: 6.72",
    "--warn sobre --warn-bg: 7.33", "--warn sobre --bg: 7.45",
    "--focus sobre --surface: 4.72", "--border sobre --surface: 1.57"
  ],
  "unlabeled": [],
  "nameless": [],
  "describedMissing": ["r-total", "r-failed"],
  "lang": "es"
}
```

- Plan sin E2E obligatoria: `{"active": "main", "fieldsetDescribed": null, "suitesError": "Incluya al menos una suite obligatoria unitaria y una E2E."}`
- Versión inválida: `{"active": "release", "invalid": "true"}` (correcto).
- Resultados registrados desde la UI: `"bytes": [1, 1]`.
- Doble clic en «Evaluar quality gate»: `"gatePosts": 2`; foco posterior: `"main"`.
- Captura a 360 px: aviso de error visible sin error (atributo `hidden` anulado por CSS).

## Después de corregir

```json
{
  "h1_borderContrast": 4.46,
  "h2_gatePosts": 1,
  "h3": { "focus": "r-path", "msg": "Indique el tamaño real del archivo en bytes (mínimo 1)." },
  "bytes": [2370, 911],
  "h4_described": "suites-error",
  "h4_focus": "s-name"
}
```

- Tras «Evaluar»: `{"focus": "gate-panel", "panel": "Quality gate: Bloqueado", "status": "Evaluar quality gate: listo."}`
- `node --test micro-his/qa-calidad-versiones/tests/view-model.test.mjs` → `# pass 6`, `# fail 0`.
