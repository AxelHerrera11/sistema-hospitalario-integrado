# Declaración de uso de inteligencia artificial: Semana 17

**Herramienta:** Claude Code (Anthropic), modelo Claude Opus 5.5.

**Propósito:**
- analizar las amenazas del catálogo;
- escribir las pruebas de seguridad y el recorrido contra el despliegue local;
- corregir el manejo de entradas hostiles y los límites de longitud;
- redactar la matriz y la bitácora.

**Contenido aceptado o modificado:**
- Las salidas de las pruebas, del recorrido y de `psql` se copiaron sin cambios de ejecuciones locales con datos ficticios.
- Los dos 500 (una unidad que no es texto y un número infinito) se encontraron al enviar entradas hostiles a la API.
- Se comprobó que la prueba falla sin la corrección.
- No se declaran despliegues en staging ni aprobaciones que no existan.

**Responsable:** Axel Eliú Herrera Sánchez es responsable de esta entrega.
