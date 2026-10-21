# Declaración de uso de inteligencia artificial - Semana 5

**Herramienta:** Claude Code con el modelo Claude Opus 5.5 (`claude-opus-5-5`).

**Propósito:** contrastar las convenciones del repositorio (prefijo, middlewares, RBAC, `trace_id`, controles del PR), redactar el contrato API, la especificación OpenAPI, el diseño cliente-servidor, la evaluación de microservicio y el plan de integración, y validar la especificación y los diagramas.

**Contenido generado o modificado:** todos los documentos de esta carpeta son borradores generados con IA. Durante el trabajo se corrigió la fuente citada del prefijo `/api/v1`, que está en `bootstrap/app.php` y no en `routes/api.php`. También se detectó que `config/rbac.php` ya concede `medicamentos.ver`, y se corrigió el supuesto de las Semanas 2 y 3.

**Validación:** las salidas de [EVIDENCIA.md](./EVIDENCIA.md) provienen de ejecuciones reales. Los permisos de escritura y los acuerdos con otros módulos son propuestas, no acuerdos. La revisión humana y la aprobación requerida en `develop` quedan **pendientes**.
