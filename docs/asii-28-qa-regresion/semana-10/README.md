# ASII-28 - QA Funcional - Semana 10 - Diseño móvil

Madelin Jazmín Cerón Molina (`MadelinCeron`). Refs #12.
Rama feature/asii-28-semana-10-qa-regresion-madelinceron; destino develop.
HEAD inicial f1e7ab4b98d13069dd8c6f090ece2c683d516014, rama limpia al iniciar.
Tras fetch, origin/develop avanzó a 7ff013aa (29 commits por delante); se conserva
la base inicial sin merge/rebase ni incorporación ajena. El PR se mide con
git diff origin/develop...HEAD (base común), no por el último commit.

## Entrega y visualización

Cinco pantallas mobile-first en [HTML estático editable](./prototipo-responsive.html):
panel, campañas en tarjetas, diseño de caso, ejecución y registro de defecto.
Abrir archivo directamente en navegador. Sin framework, JavaScript, API, auth ni
almacenamiento. Enlaces/desplegables operan localmente; botones/campos deshabilitados.
Estados y datos son ejemplos, no respuestas de un servidor ni estadísticas reales.

- [Diseño responsive y evidencia](./diseno-responsive.md).
- [Prioridades, trazabilidad y hallazgos atendidos](./prioridades-pantalla.md).
- [RF/RNF integrados](../semana-02/rf-rnf-criterios.md).

Se consultaron diseños de Semana 8 (#1403) y hallazgos/backlog de Semana 9 (#1408)
desde sus ramas, ambos PR abiertos/no fusionados; no están en origin/develop.
No se copian commits ni se modifican archivos anteriores. Solo RF-01 tiene núcleo
CLI; la maqueta no lo convierte en frontend funcional.

## Validaciones y límites

Se comprueban cinco secciones, estructura/etiquetas/enlaces, diseño a 320/375/390/
768/1280 px, foco de navegación y correspondencia con trece RF/RNF y seis UX.
Resultados reales y método registrados en diseno-responsive.md: cinco tamaños
sin desborde horizontal, cinco pantallas y doce controles etiquetados; navegación
Tab/Enter y un desplegable comprobados. Enlaces y diff revisados antes del commit.
No se certifica WCAG ni se simulan usuarios, lector de pantalla o backend.
UX-01/02/03 se atienden en presentación; UX-04/05/06 solo parcialmente: faltan
validación asistiva, contrato de evidencia y consulta real de resultado incierto.
Las mejoras no cierran automáticamente los hallazgos en la entrega anterior.
Semana 11: prototipo interactivo autorizado, reglas de seguridad/propiedad y pruebas
de tareas, zoom y tecnología asistiva. Madelin mantiene diseño/evidencia; revisión
y aprobación pendientes de asignación. Sin cambios PHP, PostgreSQL, .env o CI.
