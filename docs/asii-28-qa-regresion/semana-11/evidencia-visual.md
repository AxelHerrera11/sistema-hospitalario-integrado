# Evidencia visual real — 29/10/2026

Ambiente Windows local; Edge headless 156.0.4314.50, Node 24, perfil temporal
aislado. No se usaron cuentas/datos clínicos, servidor HIS o PostgreSQL.
Las imágenes son del prototipo tras acciones simuladas, no de producción:

- [Desktop: defecto confirmado](./capturas/desktop-defecto.png): origen E-demo-1 y confirmación D-demo-1.
- [Desktop: panel](./capturas/panel-1280.png): estado de sesión tras recorrido.
- [Móvil 390 px](./capturas/panel-390.png): menú envuelto y panel de la misma simulación.
- [Móvil 320 px](./capturas/panel-320.png): adaptación a pantalla pequeña.

| Viewport CSS px | scrollWidth observado | Resultado |
|---|---|---|
| 1280 | 1280 | Sin desbordamiento horizontal |
| 390 | 375 | Sin desbordamiento horizontal |
| 320 | 305 | Sin desbordamiento horizontal |

Reproducción: iniciar Edge con perfil temporal nuevo y depuración **local**:

```text
msedge.exe --headless --disable-gpu --no-first-run --remote-debugging-port=9341 --user-data-dir=<TEMP>/perfil-qa about:blank
node docs/asii-28-qa-regresion/semana-11/verificar-prototipo.mjs http://127.0.0.1:9341 docs/asii-28-qa-regresion/semana-11/capturas
node --check docs/asii-28-qa-regresion/semana-11/verificar-prototipo.mjs
```

La prueba ligera usa WebSocket/fetch de Node para DevTools local y vuelve a
generar las cuatro capturas. El HTML del prototipo no usa fetch ni almacenamiento.
Resultado nuevo: 17 PASS, 0 fallos; sintaxis del verificador aprobada. El proceso
temporal de Edge se cerró al terminar. No abrir depuración con perfil personal.

Se inspeccionaron capturas de escritorio/móvil; labels/IDs/encabezados y foco de
navegación/error están comprobados. Pares CSS opacos calculados con luminancia sRGB:
texto #171717/#fff 17.93:1; enlaces #202020/#fff 16.29:1; foco #333/#fff 12.63:1;
no se certifica una auditoría de contraste completa. No hubo prueba con personas,
lector de pantalla o zoom real. Reflow medido no sustituye esas pruebas.
Enlaces locales, cuatro archivos de captura, alcance exclusivo, git diff --check y
git diff --cached --check: PASS. No se modificó PHP;
no se presenta el recorrido como integración frontend/backend ni como tests PHP.
