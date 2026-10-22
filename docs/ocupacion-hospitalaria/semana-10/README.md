# Semana 10 - Propuesta responsive y escenarios móviles

## Portada

- **Estudiante:** Javier Alexander Fajardo López (`Javifa2324`)
- **Módulo:** ASII-09 - Dashboard de ocupación hospitalaria
- **Issue:** [#36](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/36)
- **Entrega:** adaptación responsive del flujo principal

## Objetivo

Adaptar el dashboard para consultar ocupación desde 320 px sin perder corte, frescura, capacidad, discrepancias ni recuperación ante fallas. Es una propuesta de diseño; no se presenta como frontend Vue implementado.

- [Hallazgos de Semana 9](../semana-09/README.md)
- [Flujo móvil editable](./fuentes/01-flujo-movil.mmd)
- [Declaración de IA](./DECLARACION_IA.md)

## Jerarquía de contenido

El orden permanece estable en todos los tamaños:

1. Título, hospital y fecha de corte con zona horaria.
2. Estado de actualización y antigüedad.
3. Filtro de sala y acción `Actualizar`.
4. Ocupadas, disponibles, capacidad operativa y tasa o `N/A`.
5. Advertencia y cantidad de inconsistencias.
6. Salas, una por una, con métricas completas.
7. Ayuda contextual y request ID solo cuando existe un error.

No se ocultan datos críticos detrás de hover, gestos ni un carrusel. Mantenimiento y no verificadas pueden usar detalle expandible, pero sus conteos permanecen visibles.

## Breakpoints y comportamiento

| Rango | Estructura | Navegación y controles |
|---|---|---|
| 320-430 px | Una columna; métricas en cuadrícula 2x2; cada sala es tarjeta | Controles de ancho completo y objetivo táctil mínimo 44x44 px |
| 431-768 px | Una columna amplia; métricas 2x2; tarjetas de sala en dos columnas si caben | Filtro y actualizar comparten fila solo sin truncar etiquetas |
| 769-1024 px | Resumen en cuatro columnas; tabla de salas con encabezados | Filtros alineados al inicio; foco conserva orden visual |
| Más de 1024 px | Resumen, advertencia y tabla dentro de un contenedor legible | No se estira texto indefinidamente; ancho máximo de contenido |

Reglas comunes:

- No hay scroll horizontal de página a 320 px ni con zoom al 200 %.
- El cambio tabla-tarjetas ocurre por espacio disponible, no por agente de usuario.
- Los valores no se cortan: si crecen, la tarjeta aumenta de alto.
- Icono, texto y cantidad expresan advertencias sin depender del color.
- El foco visible tiene al menos 2 px y contraste `>= 3:1`.
- Texto normal usa contraste `>= 4.5:1`.

## Wireframe móvil - Resultado válido

```text
┌ Ocupación hospitalaria ┐
│ Hospital Central       │
│ Corte 16/10 10:00 -06  │
│ Actualizado hace 1 min │
│ Sala [Todas         ▼] │
│ [     Actualizar     ] │
├──────────┬─────────────┤
│ Ocup. 12 │ Dispon. 6   │
│ Cap.  18 │ Tasa 66.7 % │
├──────────┴─────────────┤
│ ⚠ 2 inconsistencias   │
│ Sala MED               │
│ 8 ocup. · 4 dispon.    │
│ 1 mant. · 1 no verif.  │
│ Tasa 66.7 %            │
│ [Ver explicación]      │
└────────────────────────┘
```

## Wireframe móvil - Falla segura

```text
┌ No pudimos actualizar ┐
│ Último dato: 09:45    │
│ Hace 15 min           │
│ DATOS DESACTUALIZADOS │
│ [      Reintentar    ]│
│ Código: req-demo-503  │
│ Conserve el código    │
└───────────────────────┘
```

Si no existe fotografía previa, no se dibujan tarjetas numéricas. Un `401/403` limpia las cifras y mueve el foco al título del mensaje de acceso.

## Navegación por teclado y lector

1. Entra al `h1` y al texto de hospital/corte.
2. Recorre sala y `Actualizar` en el mismo orden visual.
3. Lee el resumen dentro de una región `Resumen global`.
4. Recibe la advertencia en texto, sin interrupción agresiva.
5. Recorre las salas en orden del backend; cada tarjeta tiene el nombre como encabezado.
6. En error, el foco va al título y después a `Reintentar`.

Los mensajes breves de carga, éxito y obsolescencia usan una región `aria-live="polite"`. Los cambios no mueven el foco salvo denegación o error que sustituye todo el contenido.

## Escenarios móviles

| ID | Contexto | Recorrido | Resultado esperado |
|---|---|---|---|
| M-01 | Coordinación, 320 px | Abre, filtra UCI y revisa discrepancias | Completa la tarea con teclado o tacto y sin scroll horizontal |
| M-02 | Dirección, 390 px | Compara tasa global y salas | Ve corte, capacidad y tasas antes del detalle secundario |
| M-03 | Conexión intermitente | La recarga falla con dato previo | Conserva datos rotulados, antigüedad y reintento |
| M-04 | Falla sin caché | Primera solicitud devuelve `503` | No aparecen ceros; muestra explicación, reintento y request ID |
| M-05 | Permiso revocado | Una recarga devuelve `403` | Limpia cifras, anuncia acceso restringido y permite volver |
| M-06 | Capacidad cero | Todas las camas quedan fuera de capacidad operativa | Presenta `N/A` y explicación, no una tasa falsa |

## Trazabilidad con Semana 9

| Hallazgo | Respuesta de diseño |
|---|---|
| H-01 Teclado | Orden de foco y activación estándar documentados |
| H-02 Contraste | Umbrales y foco visible definidos como criterio del prototipo |
| H-03 Reflow | Jerarquía y patrón tabla-tarjetas desde 320 px |
| H-04 Terminología | `No verificada` conserva enlace de explicación |
| H-05 Estados | Mensajes `aria-live` y reglas de movimiento de foco |
| H-06 Semántica | Regiones, encabezados y tarjeta por sala |

## Criterios de aceptación

- A 320, 390 y 430 px no se pierde información crítica ni aparece scroll horizontal.
- Todos los controles táctiles miden al menos 44x44 px.
- Teclado, tacto y lectura lineal permiten completar M-01 y M-03.
- Falla, vacío, obsolescencia y capacidad cero siguen siendo estados distintos.
- La adaptación no agrega escritura de camas ni identificadores clínicos.

## Evidencia

```bash
npx --yes @mermaid-js/mermaid-cli -i docs/ocupacion-hospitalaria/semana-10/fuentes/01-flujo-movil.mmd -o /tmp/semana-10-flujo-movil.svg
php micro-his/ocupacion-hospitalaria/tests/run.php
git diff --check
```

## Conclusión

La propuesta prioriza decisiones seguras en pantallas pequeñas y convierte los principales hallazgos de Semana 9 en reglas comprobables para el prototipo de Semana 11.
