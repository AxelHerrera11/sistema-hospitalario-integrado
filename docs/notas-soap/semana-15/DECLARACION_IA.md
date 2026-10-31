# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code, modelo `claude-sonnet-5-5` (ejecutado sobre Windows, PHP 8.3.33). Las capturas se generaron con Chrome en modo headless a partir de la salida real de las suites.

**Propósito:** diseñar y ejecutar pruebas unitarias, de caja blanca y de caja negra de Notas SOAP y diagnósticos, trazadas a RF/RNF, y registrar los defectos que revelaron.

**Revisado y ajustado:**

- Las 40 pruebas se ejecutaron; todas las que deben pasar pasan y las cinco que exponen defectos quedan marcadas «ABIERTO». La regresión (34 y 19 verificaciones) pasa sin cambios.
- Se aplicaron seis mutaciones al código para comprobar que las pruebas las detectan; una primera versión de U-08 no detectaba una de ellas y se corrigió. Los archivos mutados se restauraron.
- La cobertura de ramas y condiciones se contó leyendo el código, sin herramienta de cobertura. RNF-SOAP-04 no se probó.
- Versionado y expediente no tienen código todavía y no se probaron.
- Los defectos D-01 a D-03 se registran, **no se corrigen** en este PR.
- Solo se añadieron archivos de prueba; no se modificó código del micro-HIS ni del HIS.

**Validación humana:** realizada el 2026-10-20. Josué Fernando Hicho García revisó las 40 pruebas, la matriz RF/RNF → prueba, los resultados, las capturas y los defectos D-01 a D-03, y aprueba el documento.

**Responsable:** Josué Fernando Hicho García es responsable de esta entrega.
