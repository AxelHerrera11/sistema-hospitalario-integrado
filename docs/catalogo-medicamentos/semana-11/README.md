# ASII-14 - Catálogo de medicamentos - Semana 11

- **Tema:** prototipo navegable desktop y móvil
- **Issue:** [#165](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/165)

| Requisito | Evidencia |
|---|---|
| Archivo del prototipo | [prototipo/index.html](./prototipo/index.html): ábralo en un navegador; no requiere servidor ni dependencias |
| Mapa de navegación | [01-mapa-navegacion.svg](./01-mapa-navegacion.svg) |
| Camino feliz (desktop y móvil) | `capturas/{desktop,movil}-01-resultados-clinico.png`, `-02-confirmar-vigencia.png` y `-03-vigencia-actualizada.png` |
| Error crítico (desktop y móvil) | `capturas/{desktop,movil}-05-error-catalogo.png`; error de alta en `-04-alta-duplicada.png` |
| Navegación e interacciones comprobadas | `node docs/catalogo-medicamentos/semana-11/evidencia/verificar-prototipo.mjs`: 24/24 comprobaciones reales en Chrome sin interfaz (exit 0) |

## Qué es y qué no es

- **Simulado:** datos ficticios en memoria, rol elegido en un selector y caída del catálogo activada con una casilla. **No consume backend** ni el micro-HIS, y no guarda nada. No es una integración productiva.
- **Real:** las interacciones verificadas, es decir, teclado (Enter, Esc), foco de los diálogos, nombres accesibles por código, `role="status"`, ausencia de desplazamiento horizontal a 360 px, datos mostrados como texto y error sin datos parciales.
- **Corregido gracias a la verificación:** el título de la tabla comprimido en móvil y un estado "1 resultado." que contradecía la alerta de catálogo caído.
- **No verificado:** lector de pantalla real y pruebas con usuarios.

[Declaración de uso de IA](./DECLARACION_IA.md)
