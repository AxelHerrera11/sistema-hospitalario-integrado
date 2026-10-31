# Hallazgos y backlog priorizado

Prioridad = impacto sobre expediente longitudinal, episodios, acceso autorizado, integridad y datos sensibles
(Alta: puede mostrar o atribuir mal datos clínicos; Media: puede inducir a error o exponer datos; Baja: calidad).

## Hallazgos con evidencia

| Id | Hallazgo | Evidencia | Severidad |
|---|---|---|---|
| H-01 | Tras consultar P-0001, escribir un código inválido deja visible el expediente de P-0001 junto al campo con «P 0002». | `auditoria-navegador.json` → `trasCodigoInvalido.expedienteVisible: true` | Alta |
| H-02 | Las fechas se muestran en la hora local del navegador sin indicar zona: apertura 09:00 UTC aparece «3:00 a. m.». En una línea de tiempo clínica, la hora es ambigua. | `fechaNacimientoMostrada` → «2/03/2026, 3:00 a. m.»; `demo.sql` usa `09:00:00+00:00` | Media |
| H-03 | Fecha de nacimiento en formato ISO (`1980-05-14`) y el resto en formato local: inconsistencia. | Mismo registro de la evidencia | Baja |
| H-04 | Cada episodio es `h3`, igual que el título «Episodios clínicos»: la estructura no refleja la jerarquía. | `encabezados` en la evidencia | Baja |
| H-05 | «Abrir expediente…» se ofrece a todos los roles aunque enfermería, administración y (hoy) todos reciban 403. | `index.html`; N-08 en caja negra | Media |
| H-06 | El anillo amarillo del foco tiene 1.64:1 sobre blanco; cumple solo gracias al contorno oscuro. | `contraste.txt` | Baja |
| H-07 | El expediente queda en pantalla indefinidamente si la persona se aleja (no hay ocultado por inactividad). | `app.js` no tiene temporizador | Media |
| H-08 | «Sin vínculo asistencial» no indica a quién acudir para obtener la asignación. | `estados.js` (`MENSAJES.SIN_VINCULO_ASISTENCIAL`) | Baja |

## Backlog priorizado

| Orden | Id | Corrección propuesta | Criterio verificable | Estado |
|---|---|---|---|---|
| 1 | H-01 | Al fallar la validación (o al editar el código), limpiar el expediente mostrado y el estado anterior. | En el navegador: consultar P-0001, escribir «P 0002», pulsar Consultar → `#expediente` oculto y estado «Código no válido». Prueba node de la función que decide limpiar. | **Corregido en Semana 12** |
| 2 | H-02 | Mostrar la zona horaria en cada fecha y hora (por ejemplo «GMT-6»). | `formatearFecha` incluye la zona; prueba node con una fecha fija. | **Corregido en Semana 12** |
| 3 | H-07 | Ocultar los datos tras N minutos sin actividad. | Prueba con temporizador simulado. **Bloqueado:** N es una política sin fuente; definir con el grupo/Gobernanza. | Pendiente de decisión |
| 4 | H-05 | Mostrar «Abrir expediente…» solo si la sesión trae el permiso (`can()` de `resources/js/router/permissions.js`). | En la SPA, sin el permiso el botón no existe en el DOM. **Bloqueado:** el prototipo no recibe permisos; depende de la ruta SPA y del permiso de apertura. | Pendiente (SPA + RBAC) |
| 5 | H-04 | Bajar los títulos de episodio a `h4`. | El script de encabezados muestra `H4` para cada episodio. | **Corregido en Semana 12** |
| 6 | H-03 | Formatear la fecha de nacimiento con `toLocaleDateString('es-GT')` sin convertir zona. | Ficha muestra «14/05/1980». | **Corregido en Semana 12** |
| 7 | H-08 | Añadir «Solicite la asignación al servicio o a la jefatura médica». | Prueba node del mensaje. Requiere confirmar el procedimiento real con el grupo. | Pendiente de confirmar texto |
| 8 | H-06 | Mantener el doble anillo (no quitar el contorno oscuro). | `contraste.mjs` mide el contorno oscuro ≥ 3:1. | Aceptado (mitigado) |
