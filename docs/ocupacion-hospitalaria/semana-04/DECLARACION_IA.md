# Declaración de uso de inteligencia artificial

**Herramienta:** OpenCode con modelos de OpenAI, Anthropic (Claude) y MiniMax, con apoyo de
GitHub CLI y del contenedor de pruebas `localhost/php85-pgsql-full`.

**Propósito:** producir la entrega documental de la semana 4 (arquitectura en capas y patrón
Repository) del dashboard de ocupación hospitalaria, y verificar en PostgreSQL 16 real las
reglas de ocupación que el módulo consume.

**Contenido aceptado o modificado:** las reglas del dominio no cambian. MVC separa
presentación, aplicación y dominio; el puerto `BedSnapshotReader` sustituye el acceso directo a
la base y mantiene a ASII-09 como consumidor de solo lectura de la base HOSPITAL. Los nombres de
clase, las rutas y la salida de verificaciones que aparecen en la entrega se copiaron del
código ya integrado en `develop` y de una ejecución real del 21 de octubre de 2026, no de
descripciones previas.

**Verificación:** `php micro-his/ocupacion-hospitalaria/tests/run.php` devuelve
`OK: 12 verificaciones aprobadas` sobre `develop` en `75bd69ce`; los dos diagramas se generaron
con Mermaid CLI y el SVG resultante pasó `xmllint --noout`.

**Validación humana:** revisada y aprobada por Javier Alexander Fajardo López. La declaración
original de esta semana, redactada el 14 de octubre de 2026 sobre el trabajo local previo a la
integración, se conserva en el historial del commit de la semana 4.
