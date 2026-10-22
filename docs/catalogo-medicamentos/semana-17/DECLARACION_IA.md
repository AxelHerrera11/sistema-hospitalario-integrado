# Declaración de uso de inteligencia artificial - Semana 17

**Herramienta:** Claude Code con el modelo Claude Opus 5.5 (`claude-opus-5-5`).

**Propósito:** elaborar la matriz de amenazas, escribir y ejecutar las pruebas de seguridad y registrar la bitácora del despliegue local.

**Contenido generado o modificado:** todo es un borrador generado con IA. La bitácora registra solo comandos ejecutados realmente, y no se declara ningún despliegue en staging.

**Ampliación del 21/10/2026:** con la misma herramienta se escribieron la migración H5, el adaptador PostgreSQL, la pantalla Vue y los scripts del recorrido, y se corrigieron tres defectos detectados al probar contra PostgreSQL. Todos los resultados citados provienen de ejecuciones reales en el entorno local descrito en `evidencia-funcional.md`.

**Validación:** el 21/10/2026, María Yamilet Lindo Pablo confirmó manualmente el alta, la búsqueda, el detalle y el cambio de vigencia de `IBU-400` y que el estado se conservaba al volver por el menú. En una segunda ronda, con testimonio y capturas, confirmó la validación local, el rechazo del duplicado, la solo lectura de médico y enfermería, el aviso de recepción y la vista móvil simulada a 440 y 472 px (ver «Revisión humana confirmada» en `evidencia-funcional.md`). La IA solo contrastó las capturas con esa evidencia y con las sesiones de CENTRAL. Siguen sin revisión humana la búsqueda sin resultados, el teclado, las escrituras forzadas por API, el aislamiento entre hospitales y la caída de la base, que solo tienen pruebas automatizadas, además de la matriz de amenazas, las pruebas de seguridad, la migración y el adaptador.
