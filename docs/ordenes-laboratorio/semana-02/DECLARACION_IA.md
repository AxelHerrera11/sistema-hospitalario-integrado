# Declaración de uso de inteligencia artificial — Semana 2

**Herramienta:** OpenCode con el modelo `deepseek-v4.1-flash-free`.
**Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`).
**Módulo:** Órdenes de laboratorio desde EMR.

## Propósito

Derivar los requisitos funcionales y no funcionales de la narrativa de la semana 1, redactar sus
criterios de aceptación y proponer el refactor SRP del flujo de emisión de órdenes.

## Aportes y límites

- La IA estructuró los RF/RNF y los criterios en formato Dado/Cuando/Entonces para que sean
  verificables con pruebas.
- El refactor respeta las convenciones del repositorio (`micro-his/` en PHP vanilla, puertos y
  adaptadores) y el contrato ya publicado; no se inventaron tablas, permisos definitivos ni acuerdos
  con otros módulos.
- Los umbrales (200 ms, `per_page` máximo 50) son **propuestas medibles**, no resultados medidos;
  la medición real se hace en la semana 15 y se reporta con su salida.
- Los permisos `laboratorio.ordenes.*` siguen marcados como propuestos.

## Validación humana

**Pendiente.** Javier Alexander Fajardo López debe revisar los requisitos y el refactor antes de
publicar el PR.
