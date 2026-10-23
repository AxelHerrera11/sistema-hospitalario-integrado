# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code con el modelo Claude Sonnet 5.5 (`claude-sonnet-5-5`).

**Uso:** apoyo para analizar qué consumen los demás módulos de Médicos, adaptar el adaptador PDO al esquema HOSPITAL, escribir el controlador, el provider y las pruebas de contrato HTTP, y redactar esta documentación.

**Revisado y ajustado:** los resultados de la sección 4 salen de ejecutar las pruebas sobre el código de las ramas del módulo y sobre PostgreSQL 16 en contenedores efímeros. Las capturas de la sección 4.1 se tomaron en Chrome sin interfaz con un API simulado, y el documento lo declara: no se probó la pantalla contra el backend real. La tabla de dependencias entre módulos sale de migraciones y documentos de `develop`; donde no existe contrato escrito (Expediente) se indica.

**Validación humana:** realizada el 2026-10-22. Josué Fernando Hicho García revisó esta entrega y la aprueba. La aprobación cubre la versión de esa fecha; las secciones 1.3 y 4.1 y las actualizaciones de estado (PR #1042 a #1046 y capturas) se añadieron después y **requieren su reconfirmación**.
