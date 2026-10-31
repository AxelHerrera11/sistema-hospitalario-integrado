# Declaración de uso de inteligencia artificial: Semana 17

**Herramienta:** Claude Code (Anthropic), modelo Claude Opus 5.5.

**Propósito:**
- analizar las amenazas del módulo;
- escribir las pruebas de seguridad y el recorrido contra el despliegue local;
- corregir el manejo de entradas hostiles y el límite del motivo;
- redactar la matriz y la bitácora.

**Contenido aceptado o modificado:**
- Las salidas de las pruebas, del recorrido y de `psql` se copiaron sin cambios de ejecuciones locales con datos ficticios.
- El 500 por arreglos en el JSON se encontró al enviar entradas hostiles a la API.
- La falta de límite del motivo se encontró al comparar la pantalla (`maxlength`) con el dominio.
- Se comprobó que las pruebas fallan sin las correcciones.
- No se declaran despliegues en staging ni aprobaciones que no existan.

**Responsable:** Axel Eliú Herrera Sánchez es responsable de esta entrega.
