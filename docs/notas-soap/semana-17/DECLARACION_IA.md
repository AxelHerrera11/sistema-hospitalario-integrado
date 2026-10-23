# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code, modelo `claude-sonnet-5-5` (ejecutado sobre Windows, PHP 8.3.33, PHPUnit 11.5.55, PostgreSQL 16 en contenedores).

**Propósito:** diseñar y ejecutar pruebas de seguridad de Notas SOAP contra PostgreSQL real, corregir los hallazgos que revelaron, ejecutar un recorrido real con JWT y documentar la matriz de amenazas y la bitácora de despliegue.

**Revisado y ajustado:**

- Las 17 pruebas contra PostgreSQL y las 9 Feature pasan; las suites de `micro-his/notas-soap` también. El recorrido real (7 peticiones) se ejecutó con el servidor HTTP, el login y las bases reales.
- Las pruebas de seguridad destaparon tres defectos reales (500 por id que no es UUID, código CIE-10 sin validar y 500 por la segunda nota del expediente); se corrigieron y se verificaron de nuevo.
- Un error propio quedó registrado: se afirmó que `local_access_audits` no existía en `develop` y sí existe (#581, #582); la documentación se corrigió.
- La matriz de amenazas lista cuatro amenazas abiertas y una parcial; no se maquilló el estado.
- Se registró que los checks de GitHub no arrancan por un problema de facturación de la cuenta, ajeno al código.
- No se probaron cifrado, expiración de tokens ni otros roles con token real; no hay staging.

**Validación humana:** pendiente. Josué Fernando Hicho García debe revisar el código de #1077 y #1080, la evidencia y este documento antes de aprobarlo.

**Responsable:** Josué Fernando Hicho García es responsable de esta entrega.
