# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code, modelo `claude-sonnet-5-5` (ejecutado sobre Windows, PHP 8.3.33 y PHPUnit 11.5.55).

**Propósito:** corregir los defectos D-01 y D-03 de la Semana 15, integrar Notas SOAP en el API v1 de Laravel con `tenant`, `jwt.auth` y RBAC, y documentar la entrega con sus bloqueos.

**Revisado y ajustado:**

- Las pruebas se ejecutaron: 7 de integración HTTP nuevas, 57 de regresión (catálogo de medicamentos y RBAC) y las cinco suites del micro-HIS. Todas pasan salvo el defecto D-02, que sigue abierto y marcado.
- Dos pruebas de la Semana 15 esperaban el `403` anterior y se actualizaron al `404` de D-03; el contrato se ajustó en el mismo sentido.
- Dos fallos de la primera versión de las pruebas HTTP se corrigieron: faltaba la cabecera de tenant en una llamada cruda y Laravel reutilizaba el controlador entre peticiones de una misma prueba.
- Al abrir este PR la persistencia real no se había probado. Después se ejecutaron los adaptadores PDO contra PostgreSQL 16 con las migraciones de `develop` (#1067), se corrigieron tres desalineaciones propias, se resolvió el mapeo de identidad (#1075) y se verificó un recorrido de extremo a extremo; el estado vigente está en el [README](./README.md#bloqueos-y-limitaciones-no-se-declaran-completados).
- D-02 se mitigó dentro del módulo (#1074) pero sigue abierto en la base; firma, corrección y versionado no se implementaron.

**Validación humana:** realizada el 2026-10-23. Josué Fernando Hicho García revisó el código de #1049 y #1059, los resultados y este documento, y aprueba el documento.

**Responsable:** Josué Fernando Hicho García es responsable de esta entrega.
