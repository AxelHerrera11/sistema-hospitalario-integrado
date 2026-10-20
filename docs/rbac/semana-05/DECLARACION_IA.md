# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code (Anthropic) con el modelo Claude Opus 4.8 para el diseño y la verificación; la creación de los archivos en el repositorio la realiza OpenCode.

**Propósito:** apoyo para evolucionar el Micro-HIS RBAC hacia un diseño cliente-servidor: especificar el contrato API REST `/api/v1`, el catálogo de permisos y la matriz de roles, y evaluar la frontera de microservicio con una decisión razonada.

**Prompt relevante:** «Semana 5: cliente-servidor, API REST, microservicios e integración», aplicado a `ASII-02` «RBAC: roles, permisos y protección de rutas», continuando el mismo Micro-HIS de las semanas anteriores.

**Contenido aceptado o modificado:** el contrato API, los códigos de estado y la falla segura se derivan del diseño de las semanas 1 a 4. Los endpoints son un contrato propuesto; su implementación corresponde a una semana posterior. Los permisos, roles y políticas son supuestos de diseño (SD-RBAC-13 a SD-RBAC-16) pendientes de validación con el grupo.

**Validación humana:** realizada el 2026-10-15. María de los Ángeles López Fajardo revisó el contrato, los permisos, la evaluación de frontera y esta declaración, y la aprueba.
