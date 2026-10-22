# Semana 11 - Prototipo navegable desktop/móvil

## Portada

- **Estudiante:** Javier Alexander Fajardo López (`Javifa2324`)
- **Módulo:** ASII-09 - Dashboard de ocupación hospitalaria
- **Issue:** [#36](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/36)
- **Entrega:** prototipo navegable equivalente a mockup desktop/móvil

## Alcance

El prototipo convierte las decisiones de Semanas 8 a 10 en una página local responsive. Usa valores ficticios y no consume la API ni sustituye el futuro módulo Vue. Permite revisar jerarquía, foco, semántica y estados antes de integrar componentes productivos.

- [Abrir prototipo](./prototipo/index.html)
- [Evaluación de Semana 9](../semana-09/README.md)
- [Propuesta móvil de Semana 10](../semana-10/README.md)
- [Declaración de IA](./DECLARACION_IA.md)

## Recorridos navegables

| ID | Recorrido | Resultado observable |
|---|---|---|
| P-01 | Seleccionar `UCI` | Recalcula la vista ficticia y muestra una sola sala |
| P-02 | Activar `Actualizar` | Anuncia carga y vuelve a un resultado válido |
| P-03 | Activar `Simular dato desactualizado` | Conserva cifras rotuladas, antigüedad, reintento y request ID |
| P-04 | Activar `Simular acceso restringido` | Elimina todas las cifras y mueve foco al mensaje de acceso |
| P-05 | Navegar a 320-430 px | Cambia la tabla por tarjetas sin perder métricas críticas |

Los botones de simulación son herramientas de la maqueta. No forman parte de la interfaz productiva ni representan autorización del lado servidor.

## Mapa de navegación

```text
Inicio (prototipo)
├── Escenarios de demostración
│   ├── Resultado válido ──► Corte, resumen global, inconsistencias, tabla por sala
│   ├── Dato desactualizado ─► Misma fotografía rotulada como desactualizada
│   └── Acceso restringido ──► Estado 403 sin cifras previas
└── Resultado válido
    ├── Filtro por sala ──► Recalcula indicadores y tabla de la sala elegida
    ├── Actualizar ──► Anuncia la actualización y conserva el foco
    └── Ver explicación ──► Detalle de estados y discrepancias
```

Vistas y estados que la pantalla real de la semana 16 reproduce en
`resources/js/modules/ocupacion/pages/OcupancyPage.vue`.


## Decisiones aplicadas

- Orden estable: contexto, frescura, filtros, resumen, advertencia y salas.
- Región `aria-live="polite"` para carga, actualización y falla recuperable.
- Foco programático solo cuando un estado sustituye el contenido.
- Texto, icono y cantidad para discrepancias; no se usa solo color.
- Tabla semántica en escritorio y tarjetas equivalentes en móvil.
- Objetivos táctiles de al menos 44 px y foco visible.
- `N/A`, error y vacío nunca se reemplazan por `0 %` o cifras inventadas.
- No aparecen cama, paciente, episodio, token ni identificadores clínicos.

## Uso local

```bash
cd docs/ocupacion-hospitalaria/semana-11/prototipo
php -S 127.0.0.1:8080
```

Abrir `http://127.0.0.1:8080`, recorrer todos los controles con Tab y probar anchos de 320, 390, 430 y 1024 px. El archivo también funciona directamente mediante `file://`.

## Verificación realizada

- `node --check` valida la sintaxis de `app.js`.
- La página se sirvió localmente y respondió HTTP `200`.
- Las 19 verificaciones heredadas del Micro-HIS continúan aprobadas.
- `git diff --check` no detectó errores de espacios.

Los recorridos P-01 a P-05 son el guion de aceptación manual y permanecen pendientes de ejecución en un navegador gráfico. Tampoco se ejecutaron pruebas con lector de pantalla, usuarios reales ni auditoría automática de contraste; son validaciones previas a producción.

## Capturas de escritorio y móvil

Generadas el 21 de octubre de 2026 con Chromium (Puppeteer) sobre el prototipo servido con
`php -S`, en 1280 x 900 (escritorio) y 390 x 844 (móvil):

| Captura | Escenario | Qué demuestra |
|---|---|---|
| [`capturas/escritorio-camino-feliz.png`](capturas/escritorio-camino-feliz.png) | Camino feliz | Corte visible, resumen global, tasa 66.7 %, inconsistencias señaladas y tabla por sala. |
| [`capturas/escritorio-error-critico.png`](capturas/escritorio-error-critico.png) | Error crítico | Acceso restringido: el estado 403 no conserva ninguna cifra previa. |
| [`capturas/movil-camino-feliz.png`](capturas/movil-camino-feliz.png) | Camino feliz móvil | La misma información reorganizada en tarjetas, sin scroll horizontal. |
| [`capturas/movil-error-critico.png`](capturas/movil-error-critico.png) | Error crítico móvil | El estado 403 en móvil mantiene el mensaje y la acción de retorno. |

Comandos usados para producirlas:

```bash
php -S 127.0.0.1:8099 -t docs/ocupacion-hospitalaria/semana-11/prototipo
node capturas.mjs http://127.0.0.1:8099/ docs/ocupacion-hospitalaria/semana-11/capturas
```

Estas capturas son evidencia de interfaz del prototipo, no de la pantalla integrada: el script de
Puppeteer no se versiona porque depende de un Chromium local y no es parte del módulo.


## Criterios de aceptación

- Los cinco recorridos tienen un control y resultado distinguible.
- El estado restringido no conserva cifras en el DOM visible.
- El estado obsoleto nunca presenta la fotografía como actual.
- La vista móvil conserva corte, totales, discrepancias y recuperación.
- El prototipo declara de forma visible que todos sus datos son ficticios.

## Evidencia reproducible

```bash
$ node --check docs/ocupacion-hospitalaria/semana-11/prototipo/app.js
(sin salida)

$ php micro-his/ocupacion-hospitalaria/tests/run.php
OK: 12 verificaciones aprobadas

$ php -S 127.0.0.1:8099 -t docs/ocupacion-hospitalaria/semana-11/prototipo &
$ curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8099/
200

$ git diff --check
(sin salida)
```

Las cuatro capturas se produjeron con Chromium (Puppeteer) sobre ese servidor local, con viewport
de 1280 x 900 y de 390 x 844, pulsando los escenarios «Resultado válido» y «Acceso restringido».

## Conclusión

El prototipo permite defender y modificar el flujo sin acoplar la experiencia a datos reales. Su propósito es validar decisiones de interacción antes de construir el cliente Vue y conectar el contrato `/api/v1/occupancy`.
