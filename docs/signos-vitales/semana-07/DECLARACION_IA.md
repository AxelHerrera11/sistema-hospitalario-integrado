# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code (Anthropic) con el modelo Claude Sonnet 5.5.

**Propósito:** apoyo para diseñar los componentes backend y frontend, redactar los contratos de entrada y salida, identificar el punto más acoplado del código y escribir la demo ejecutable `demo-refactor.php` y la comparación antes/después, siguiendo el formato de la Semana 7 que ya usan otros módulos del equipo.

**Prompt relevante:** «Semana 7: Diseño de componentes y refactorización», aplicado a `ASII-13`, «captura de signos vitales, evaluación de umbrales y generación de alerta».

**Contenido aceptado o modificado:** el refactor elegido es extraer `ThresholdProfile` y `Measurement`/`Unit`. El "antes" de la demo ejecuta el código real del micro-HIS y el "después" está definido en el propio archivo, sin modificar el micro-HIS. Los componentes de Laravel y Vue son propuestas: no existen en `develop`, y el alias `permission` y los permisos `signos_vitales.*` faltan en RBAC. El factor 18,016 y los umbrales son del perfil ilustrativo `adulto-2026.1`, sin validación clínica. La salida de la demo se copió sin cambios (PHP 8.3.33).

**Validación humana:** pendiente. Josué Fernando Hicho García debe revisar el diseño, ejecutar la demo y las pruebas, revisar la documentación y esta declaración antes de aprobarla.
