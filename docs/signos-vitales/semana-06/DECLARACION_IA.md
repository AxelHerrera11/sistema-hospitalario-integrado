# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code (Anthropic) con el modelo Claude Sonnet 5.5.

**Propósito:** apoyo para armar el diagrama trazable, la matriz decisión → evidencia, la presentación de ocho diapositivas y el registro del cambio práctico, siguiendo el formato de la Semana 6 que ya usan otros módulos del equipo.

**Prompt relevante:** «Semana 6: Primera evaluación parcial», aplicado a `ASII-13`, «captura de signos vitales, evaluación de umbrales y generación de alerta».

**Contenido aceptado o modificado:** el cambio práctico elegido es el de unidades (mmol/L y °F). Las severidades de la tabla «Qué pasa hoy» se calcularon ejecutando `GlucoseRule` con los valores indicados; el factor 18,016 y los umbrales son los del perfil ilustrativo `adulto-2026.1`, sin validación clínica. Los 50 casos de prueba se contaron de las salidas de `run.php`, `repository.php`, `api.php` y `client.php` con PHP 8.3.33. El cambio práctico es un diseño: no se implementó en esta semana.

**Validación humana:** pendiente. Josué Fernando Hicho García debe revisar la presentación, la matriz y el registro del cambio, ejecutar las pruebas y revisar esta declaración antes de aprobarla.
