# Gobernanza y Auditoría — Semana 11: prototipo navegable

## Objetivo

Construir un prototipo navegable de la consulta de la bitácora. Debe tener formularios y filtros interactivos; estados de carga, vacío y error; diseño para escritorio y móvil, y datos ficticios. Debe consumir el **mismo contrato** que la API real.

## Entregables

| Entregable | Ubicación |
|---|---|
| Prototipo (HTML, CSS y JS sin frameworks) | [`micro-his/gobernanza-auditoria/public/prototipo/`](../../../micro-his/gobernanza-auditoria/public/prototipo/) (`index.html`, `styles.css`, `app.js`) |
| API de demostración (código real del módulo, InMemory y 64 eventos ficticios) | [`micro-his/gobernanza-auditoria/public/index.php`](../../../micro-his/gobernanza-auditoria/public/index.php) |
| Mapa de navegación | `01-mapa-navegacion.svg`, [fuente](fuentes/01-mapa-navegacion.mmd) |
| Capturas reales | [../evidencias/](../evidencias/) (`s11-01` a `s11-04`, `s13-01`) |

El código del prototipo vive en una sola ubicación, dentro del módulo; aquí solo se enlaza.

### Por qué HTML y JS sin frameworks y no Vue

La SPA del proyecto es Vue 3 + Vite (`resources/js`). Para integrar una página ahí habría que modificar el router y el menú compartidos (`resources/js/router`, archivos de otros módulos) y depender de que `/api/v1` funcione (hoy da HTTP 500 según el README). El prototipo usa tecnología web estándar, **no reemplaza** la SPA y respeta el contrato. Cuando se integre, la pantalla Vue debe reproducir sus estados y reglas.

## Datos de demostración

- Identificadores sintéticos (`aaaaaaaa-0000-4000-8000-00000000000x`, `11111111-…`), recursos `demo-NNN` y trazas `trace-demoNNNN`. **No hay personas, pacientes ni datos clínicos reales.**
- La sesión se simula con la cabecera `X-Demo-Session` (`admin`, `medico` o `admin_h2`). Un banner fijo indica «PROTOTIPO DE DEMOSTRACIÓN».
- Los fallos se simulan con el selector «Simular»: el parámetro `demo_falla=almacen|rbac` y `demo_latencia=1500`. El servidor de demostración los quita antes de llamar al handler real.

## Instrucciones

```bash
php -S 127.0.0.1:8091 -t micro-his/gobernanza-auditoria/public micro-his/gobernanza-auditoria/public/index.php
```

Después, abrir `http://127.0.0.1:8091/prototipo/`.

## Relación con semanas anteriores

Implementa los wireframes W1–W6 (Semana 8) y la propuesta responsive (Semana 10). Durante su construcción se detectaron y corrigieron F-03 y F-04 (Semana 9).

## Pruebas ejecutadas y resultados reales (navegador integrado de Claude Code)

| Escenario | Resultado observado | Evidencia |
|---|---|---|
| Admin H1, sin filtros | «52 eventos encontrados. Página 1 de 3.» | `s11-03` |
| Médico H1 | Estado «Acceso denegado», referencia `trace-denegado`, sin filtros (después de H-GOB-10). | `s11-02` (antes), `s13-01` (después) |
| «Hasta» anterior a «Desde» | Resumen «Revise los filtros: Hasta debe ser igual o posterior a «Desde».», `aria-invalid=true` y foco en el resumen. | `s11-01` |
| Respuesta lenta | «Cargando…» con `aria-busy=true`; después, resultados. | Texto leído del DOM |
| Bitácora no disponible | «Servicio no disponible … Referencia: req-proto-…». | Texto leído del DOM |
| `modulo=inexistente` | «Sin resultados … Amplíe el rango de fechas o quite filtros.» | Texto leído del DOM |
| Detalle | El diálogo se abre con Enter, el foco va a «Cerrar» y Esc lo cierra y devuelve el foco. | Registro de `focusin` |
| Móvil 375 × 812 | Tarjetas, `scrollWidth = 375`. | `s11-04` |
| Consola | Solo los errores HTTP esperados (403, 422 y 503); ningún error de JavaScript. | `read_console_messages` |

## Dependencias pendientes

La pantalla Vue definitiva (y el menú por permisos) requiere RBAC y una API `/api/v1` funcional.

## Limitaciones conocidas

- Los datos se regeneran en cada petición, así que las consultas auditadas no se acumulan entre peticiones.
- La identidad se simula. La seguridad real está en `AuditHttpHandler` y en el middleware de Laravel, no en el prototipo.
- Pendientes del backlog: B-01, B-03, B-04 y B-05.
