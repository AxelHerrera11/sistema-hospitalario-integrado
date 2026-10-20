# Backlog priorizado — usabilidad y accesibilidad (Semana 9)

Doce correcciones *propuestas* para el flujo de la [Semana 8](../semana-08/wireframes.md), ordenadas por impacto en nota SOAP, diagnóstico, autoría, versionado y expediente. Cada una tiene un **criterio verificable**: una prueba que alguien más puede repetir y que da un resultado de sí o no. La evidencia está en [hallazgos.md](./hallazgos.md).

**Prioridad:** P0 antes de implementar el editor · P1 primera versión · P2 segunda versión · P3 deseable. **Esfuerzo:** S (≤ 0.5 día), M (≤ 2 días), L (más).

## Cómo se verifica

- **Manual con teclado:** recorrer el flujo solo con `Tab`, `Shift+Tab`, `Enter`, `Espacio`, flechas y `Esc`.
- **Lector de pantalla:** NVDA con Firefox (Windows) y VoiceOver en Safari (si hay equipo).
- **Automática:** axe-core (`@axe-core/playwright`) sobre cada estado, sin violaciones de impacto *serious* o *critical*.
- **Contraste:** razón calculada con la fórmula WCAG; la herramienta es un script o el panel de contraste del navegador.
- **Zoom:** 200 % y 400 % (equivale a 320 px de ancho) en Chrome.

## Backlog

| ID | Prio | Hallazgo | Corrección propuesta | Criterio verificable | Esf. |
|---|---|---|---|---|---|
| B-01 | **P0** | H-07 paciente ausente en la confirmación | Repetir en el encabezado del paso de confirmación el nombre completo, el expediente y un segundo identificador (fecha de nacimiento) del paciente. Verificar que coincide con el del contexto antes de habilitar «Registrar» | El diálogo de confirmación contiene nombre, expediente y fecha de nacimiento; una prueba de componente falla si falta cualquiera de los tres. Con dos pestañas de pacientes distintos, cada una confirma solo el suyo | S |
| B-02 | **P0** | H-11 orden nota → diagnóstico | Quitar «Buscar diagnóstico» del editor sin registrar. El botón aparece tras el `201`; mientras tanto, el panel dice «Registre la nota para asociar diagnósticos». Unificar el botón primario como «Registrar nota» en WF1 y WF3 | En el editor sin nota registrada no existe ningún control de diagnóstico (prueba de componente). El texto «Continuar» no aparece en la pantalla. Flujo extremo a extremo: registrar → buscar → asociar | S |
| B-03 | **P0** | H-01 foco en diálogos | Los diálogos usan `role="dialog"` + `aria-modal="true"`, atrapan el foco, cierran con `Esc`, devuelven el foco al disparador y abren con el foco en «Cancelar» (nunca en «Descartar»). El fondo queda inerte | Prueba con teclado: al abrir, `document.activeElement` es «Cancelar»; con `Tab` el foco no sale del diálogo; `Esc` cierra y el foco vuelve al botón que lo abrió. axe: 0 violaciones en el diálogo | M |
| B-04 | **P0** | H-08 pérdida del borrador | Avisar al salir con texto sin enviar (`beforeunload` y guarda de ruta), advertir 2 minutos antes de que caduque la sesión y permitir extenderla. Proponer a ASII-02/Javier un borrador en servidor (entidad nueva, a coordinar con Javier) como solución definitiva | Con texto escrito, recargar o navegar dispara el aviso del navegador. Con la sesión a 2 minutos de vencer aparece el aviso con «Extender» y el texto se conserva tras extender. Revisión: no hay texto clínico en `localStorage`/`sessionStorage` | M |
| B-05 | **P1** | H-02 selector de tipo | Un único grupo de radios `fieldset`/`legend` «Tipo de diagnóstico» con «Principal», «Secundario», «Diferencial» en español; el valor enviado sigue siendo `primary`/`secondary`/`differential`. Un resultado se selecciona y luego se tipifica | Un solo grupo de radios por pantalla (prueba de componente). El lector anuncia «Tipo de diagnóstico, grupo, Secundario, seleccionado». Ninguna cadena `primary`/`secondary`/`diff` es visible | S |
| B-06 | **P1** | H-03 botón deshabilitado | «Registrar nota» siempre está habilitado salvo durante el envío. Al pulsarlo con secciones vacías, se muestran los errores junto a cada campo, un resumen de errores arriba y el foco pasa al primer campo con problema | Con la sección «Plan» vacía, `Enter` en «Registrar nota» muestra «Plan: escriba el plan (obligatorio)», enlazado con `aria-describedby`, y mueve el foco a «Plan». axe: 0 violaciones | S |
| B-07 | **P1** | H-06 mensajes sin anuncio | Región `role="status"` para éxito, carga y resultados («3 resultados») y `role="alert"` para errores. El `trace_id` va en 14 px mínimo, con botón «Copiar referencia». El código técnico se muestra tras el mensaje, no entre corchetes | Con NVDA, registrar una nota anuncia «Nota registrada, borrador» sin mover el foco; un error se anuncia al instante. El texto del `trace_id` mide ≥ 14 px y escala al 200 % | M |
| B-08 | **P1** | H-09 camino de corrección | La nota firmada muestra «Crear versión corregida» (solo con `notas_soap.corregir`) que pide el motivo. Una cinta «Versión 2 de 2 · vigente» o «Versión 1 · reemplazada por la versión 2» con enlace entre versiones | Prueba: nota firmada con permiso → botón visible y abre el formulario de motivo (obligatorio). Sin permiso → el botón no existe. Una versión anterior muestra «reemplazada» con enlace a la vigente | M |
| B-09 | **P1** | H-12 segundo principal | Si la nota ya tiene principal, la opción «Principal» queda deshabilitada con la razón visible («Ya hay un diagnóstico principal: J18.9») y `aria-describedby`. El `409` sigue manejándose por carreras | Con un principal asociado, «Principal» está `disabled` y su descripción se lee. Con un `409` simulado, el panel de error aparece sin perder la selección | S |
| B-10 | **P2** | H-04 contador y color | Contador ámbar → `#B45309` (5.02:1) o más oscuro, con texto «quedan 400 caracteres» y un ícono. Estado de nota con ícono + texto («Borrador», «Firmada»), no solo punto de color. Esqueleto con borde ≥ 3:1 | Razón de contraste del texto ≥ 4.5:1 y del borde ≥ 3:1 (medida). Con una simulación de daltonismo en el navegador, el estado sigue distinguiéndose | S |
| B-11 | **P2** | H-05 etiquetas | `<label>` visible por sección («Subjetivo (obligatorio)»), pista en `aria-describedby`, leyenda «* obligatorio» al inicio. Disposición de una columna con reflujo a 320 px | axe: 0 violaciones de etiquetas. A 400 % de zoom (320 px) no hay desplazamiento horizontal. La lectura anuncia «Subjetivo, obligatorio, 0 de 4000» | S |
| B-12 | **P3** | H-10 autoría | Mostrar el nombre sin tratamiento de género («Dr./Dra.» solo si el dato existe en el perfil del médico) y el rol («Médico tratante»). «Autor» → «Autoría:» | Ninguna cadena infiere género del nombre (revisión de plantillas). La autoría muestra nombre y rol en la nota propia, ajena y firmada | S |

## Orden de ejecución

1. **P0 (B-01 a B-04):** protegen contra el daño clínico inmediato: paciente equivocado, nota fuera de orden, borrado accidental y pérdida de la nota.
2. **P1 (B-05 a B-09):** hacen utilizable el flujo con teclado y lector de pantalla, y cierran el versionado.
3. **P2/P3 (B-10 a B-12):** contraste, etiquetas y pulido de autoría.

## Dependencias y pendientes

| Con quién | Qué se necesita |
|---|---|
| Javier (base de datos, ASII-09) | Entidad nueva para el borrador en servidor (B-04). **No se crea sin su coordinación** |
| María (ASII-02) | Permiso `notas_soap.corregir` en el catálogo (B-08) y duración de la sesión (B-04) |
| Perfil del médico | Dato de tratamiento opcional si se quiere mostrar «Dr./Dra.» (B-12) |
| Equipo QA (ASII-28) | Incluir axe y la lista de recorrido con teclado en la regresión de la Semana 12 |
