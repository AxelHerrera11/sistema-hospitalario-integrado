# Declaración de uso de inteligencia artificial — Semana 11

**Herramienta:** OpenCode con el modelo `deepseek-v4.1-flash-free`.
**Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`).
**Módulo:** Órdenes de laboratorio desde EMR.

## Propósito

Construir el prototipo navegable de escritorio y móvil, documentar su mapa de navegación y generar
las capturas de evidencia del flujo feliz y del error crítico.

## Aportes y límites

- La IA escribió el HTML, CSS y JavaScript del prototipo, el mapa Mermaid y el script de capturas.
- Los datos de pacientes, órdenes y pruebas son ficticios y viven en memoria; el prototipo no llama
  a la API del micro-HIS ni persiste información.
- La emisión simula latencia y éxito; no ejecuta idempotencia real ni control de permisos.
- Las capturas se generaron con Chrome headless a 2× y se normalizaron a JPEG 4:4:4 para evitar el
  texto duplicado por antialiasing subpíxel.
- No se realizó una auditoría formal de accesibilidad ni pruebas con usuarios; solo se aplicaron
  revisiones heurísticas basadas en la semana 9.

## Validación humana

**Pendiente.** Javier Alexander Fajardo López debe recorrer el prototipo en escritorio y móvil,
revisar que las capturas correspondan a las rutas documentadas y confirmar los textos antes de
publicar el PR.
