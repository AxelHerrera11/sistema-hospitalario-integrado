# Declaración de uso de inteligencia artificial: Semana 17

**Herramienta:** Claude Code (Anthropic), modelo Claude Opus 5.5.

**Propósito:**
- analizar las amenazas del módulo;
- escribir las pruebas de seguridad y el recorrido contra el despliegue local;
- corregir el límite de longitud;
- redactar la matriz y la bitácora.

**Contenido aceptado o modificado:**
- Las salidas de las pruebas, del recorrido y de `psql` se copiaron sin cambios de ejecuciones locales con datos ficticios.
- La falta del límite de longitud se encontró al comparar la pantalla (`maxlength`) con el dominio. Se corrigió y se comprobó que la prueba falla sin la corrección.
- No se declaran despliegues en staging ni aprobaciones que no existan.

**Responsable:** Axel Eliú Herrera Sánchez es responsable de esta entrega.
