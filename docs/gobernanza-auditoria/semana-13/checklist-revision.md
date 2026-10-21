# Semana 13 — Checklist de revisión técnica

**Revisión realizada:** lectura del código fuente del módulo (`micro-his/gobernanza-auditoria/**`, `app/GobernanzaAuditoria/**`) y de sus dependencias en `origin/develop` @ 7c36ab4, más la ejecución de las suites. Fecha: 2026-10-20.
**Revisor:** asistente de IA (Claude) en sesión con la usuaria. **No hubo revisión por pares de otro integrante**: queda pendiente para el revisor que designe el equipo.

| # | Punto | Cómo se verificó | Resultado | Hallazgo |
|---|---|---|---|---|
| 1 | Toda ruta protegida usa el RBAC real. | Lectura de `GobernanzaAuditoriaServiceProvider.php:61` (`rbac:auditoria.ver` después de `tenant` y `jwt.auth`). | Cumple, aunque el permiso no existe todavía. | H-GOB-11 |
| 2 | El caso de uso autoriza aunque se use fuera de Laravel. | `SearchAuditEvents::execute` llama a `AccessGate` primero; TC-N-10/11, TC-I-04…06. | Cumple | H-GOB-06 |
| 3 | El tenant lo fija el servidor y se rechaza uno distinto. | `AuditHttpHandler.php:56,62` (`hash_equals`); TC-N-07…09, TC-L-03. | Cumple | — |
| 4 | Toda lectura está restringida por `hospital_id`. | `PdoAuditLog::where()`, cláusula inicial `hospital_id = :tenant`; TC-C-01, TC-N-02. | Cumple | — |
| 5 | La identidad sale del guard, no de cabeceras. | `AuditEventController::index`; TC-L-02. | Cumple | — |
| 6 | Validación de filtros, fechas y paginación. | `AuditFilterParser`; TC-U-10…16 y TC-N-12. | Cumple | H-GOB-05, H-GOB-12 |
| 7 | SQL solo con sentencias preparadas. | TC-W-10 (sin `query()`/`exec()`) y TC-W-11 (valor malicioso). | Cumple | — |
| 8 | Fallo de persistencia: 503 sin detalle, y falla cerrada para Security. | TC-N-16/17, TC-W-07/08, TC-U-17. | Cumple | H-GOB-04 (corregido) |
| 9 | Sin secretos ni datos clínicos en la bitácora ni en los logs. | `ContextSanitizer`; reporte de fallos solo con códigos; TC-U-08/09, TC-U-20, TC-W-14. | Cumple, con falsos positivos. | H-GOB-08 |
| 10 | Inmutabilidad operativa. | Sin métodos de modificación (TC-W-04); el trigger rechaza UPDATE y DELETE (TC-W-03). | Cumple (no es criptográfica). | — |
| 11 | La cadena de integridad es compatible con el otro escritor. | TC-R-01…03 contra el código real de Tenants. | Cumple | H-GOB-02 |
| 12 | Concurrencia y rendimiento de la escritura y la consulta. | Lectura de `PdoAuditLog.php:86` y de la propuesta 001. | **Riesgo** | H-GOB-02, H-GOB-05 |
| 13 | Las dependencias externas necesarias para la ruta existen. | `git show origin/develop:app/Http/Middleware/TenantMiddleware.php` (usa `App\Models\Tenant`; `app/Models` tiene 0 archivos en develop). | **Bloqueado** | H-GOB-07 |
| 14 | Las denegaciones de RBAC llegan a la bitácora. | `origin/develop:app/Providers/RbacServiceProvider.php:21` enlaza `LogDenials`. | **No**, todavía. | H-GOB-11 |
| 15 | La interfaz no expone lo que el rol no puede usar y escapa la salida. | `app.js` usa `textContent` (no hay `innerHTML` en el código); filtros visibles con 403. | Cumple tras la corrección. | H-GOB-10 (corregido) |
