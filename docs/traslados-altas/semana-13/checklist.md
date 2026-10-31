# Checklist de revisión — ASII-08

La columna «Autoverificación» es de la **autora**, con apoyo de herramientas; no es el resultado de los revisores. Las pruebas citadas se ejecutaron el 21/10/2026 sobre develop `03675bf`.

| # | Área | Ítem | Autoverificación |
|---|---|---|---|
| C1 | Reglas | La estancia esperada debe ser la abierta de un episodio `inpatient` abierto, y T posterior a ambos inicios (RF-TA-04) | Sí: `tests/reglas.php`, todas las ramas y los límites de T |
| C2 | Reglas | El traslado cierra el origen `[inicio, T)` y abre el destino `[T, ∞)` en el mismo episodio, que sigue abierto | Sí: `tests/postgres.php`, estancias contiguas |
| C3 | Reglas | El alta cierra la estancia y el episodio con el mismo T | Sí: `tests/postgres.php` |
| C4 | Reglas | Motivo y destino son propios del traslado; el alta no los recibe | Sí: `SolicitudAlta` solo tiene la estancia; `tests/ejecutor.php` |
| C5 | Reglas | Liberar la cama no cambia `beds.status` | Sí en el código y en las pruebas; **acuerdo pendiente** con ASII-06 |
| C6 | Integridad | Un rechazo no escribe nada y un error después de la primera escritura se revierte por completo | Sí: `tests/postgres.php` (FK del actor) |
| C7 | Integridad | Dos solicitudes sobre la misma estancia no tienen éxito ambas | **Sin evidencia**: hay `FOR UPDATE` y un índice único, pero falta la prueba con conexiones independientes (Semana 15) |
| C8 | Integridad | Un COMMIT sin respuesta se informa como incierto, sin reintento ciego | Parcial: probado con el doble y con red emulada; el corte real en el COMMIT no se probó |
| C9 | API | Cada resultado tiene su estado HTTP; solo el 503 lleva `Retry-After` | Sí: `tests/http.php` |
| C10 | API | El contrato OpenAPI coincide con la implementación | Sí por inspección: mismas rutas y estados que `tests/http.php` |
| C11 | Permisos | La autorización ocurre antes de abrir la transacción | Sí: `tests/protocolo.php` (sin llamadas a HOSPITAL) |
| C12 | Permisos | Los permisos por rol están definidos | **No**: RBAC no tiene permisos de traslado ni de alta (ASII-02) |
| C13 | Tenant | La operación usa solo los datos del hospital activo | **No aplica todavía**: el micro-HIS no recibe tenant; se integra en la Semana 16 |
| C14 | Datos | Las respuestas y la interfaz exponen solo identificadores | Sí: `VistaResultado`, `simulaciones.md` y las capturas |
| C15 | Arquitectura | Domain y Application no dependen de PDO; Presentation no contiene SQL | Sí: `tests/arquitectura.php` |
