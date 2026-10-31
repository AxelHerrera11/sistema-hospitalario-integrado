# Respuesta documentada a un cambio solicitado

## Solicitud

Tras la evaluación de la Semana 9: «La pantalla nunca debe mostrar el expediente de un paciente junto al código de
otro, y las horas de la línea de tiempo no pueden ser ambiguas.» Corresponde a los hallazgos H-01 (Alta) y H-02
(Media) del backlog; se aprovechó para corregir H-03 y H-04 (Baja), que tocan los mismos componentes.

## Cambios realizados

| Hallazgo | Archivo | Cambio |
|---|---|---|
| H-01 | `public/ui/estados.js` | Nueva función pura `debeOcultar(codigoMostrado, valorCampo)` |
| H-01 | `public/ui/app.js` | Se recuerda el código mostrado; al editar el campo o al fallar la validación se quita el expediente y se muestra «Código no válido» |
| H-02 | `public/ui/estados.js` | `formatearFecha(iso, zona)` muestra `DD/MM/AAAA, hh:mm` + zona (`timeZoneName: 'short'`) |
| H-03 | `public/ui/estados.js`, `vista.js` | `formatearNacimiento()` → `DD/MM/AAAA` sin convertir zona |
| H-04 | `public/ui/vista.js`, `estilos.css` | Títulos de episodio `h4` |

No cambió el backend ni el contrato API.

## Verificación

| Criterio (de `semana-09/hallazgos-backlog.md`) | Resultado | Evidencia |
|---|---|---|
| Consultar P-0001, escribir «P 0002», Consultar → expediente oculto y «Código no válido» | Cumple | [`evidencias/verificacion-despues.json`](evidencias/verificacion-despues.json) `trasCodigoInvalido` |
| Editar el código tras una consulta oculta los datos | Cumple | `trasEditarCodigo`; prueba V-10 |
| Fechas con zona | Cumple: «02/03/2026, 03:00 a. m. GMT-6» | `fechas`; prueba V-11 (UTC fijo) |
| Nacimiento «14/05/1980» | Cumple | `fechas`; V-11 |
| Episodios en `h4` | Cumple | `encabezados` |

Antes del cambio: [`../semana-09/evidencias/auditoria-navegador.json`](../semana-09/evidencias/auditoria-navegador.json).

## Impacto

Ninguna prueba previa cambió de resultado (`tests/run.php` y `ui-estados.test.mjs` en verde). Las capturas de la
Semana 11 se regeneraron después del cambio.
