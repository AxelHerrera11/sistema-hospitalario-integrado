# Semana 9 | ASII-29: Evaluación UX y accesibilidad NativePHP

## Alcance

Se evalúa el flujo acotado de NativePHP para capturar y sincronizar resultados.
La revisión considera viabilidad NativePHP, capacidades nativas, sincronización,
riesgos de plataforma y protección de datos clínicos.

## Checklist de evaluación

| Criterio | Evidencia | Prioridad |
|---|---|---|
| Teclado y foco | Bandeja, captura y cola son navegables en escritorio. | Alta |
| Tamaño y legibilidad | Controles táctiles y texto siguen siendo utilizables en móvil. | Alta |
| Conexión | La interfaz distingue online, offline, cola y error. | Alta |
| Mensajes | Cada fallo explica si el dato se guardó localmente y cómo reintentar. | Alta |
| Prevención de duplicados | La sincronización usa una clave idempotente visible en soporte. | Alta |
| Privacidad | La caché local minimiza datos y respeta tenant y rol. | Alta |
| Plataforma | Permisos, almacenamiento y diferencias de sistema están documentados. | Media |

## Hallazgos priorizados

1. **N1 Alta:** el estado offline debe ser persistente y no depender del color.
2. **N2 Alta:** guardar localmente debe confirmar que el dato quedó en cola.
3. **N3 Alta:** reintentar debe reutilizar la misma clave y no crear duplicados.
4. **N4 Alta:** los datos clínicos en caché deben tener expiración y mínimo necesario.
5. **N5 Media:** permisos de almacenamiento deben explicar motivo y alternativa.
6. **N6 Media:** una incompatibilidad de plataforma debe mostrar ruta de soporte.

## Criterios verificables

- El técnico captura un resultado con o sin conexión sin perder valor ni unidad.
- La cola muestra estado, fecha del intento y acción de reintento.
- Al recuperar conexión, un envío idempotente termina en `Sincronizado` o `Error`.
- La vista no almacena ni expone datos de otro tenant.
- La documentación separa simulación de ejecución real: la viabilidad NativePHP queda
  condicionada a validar el entorno habilitado y sus permisos de plataforma.

## Evidencia y trazabilidad

- Requisito Odoo: `Semana 9: Evaluación del diseño, usabilidad y accesibilidad`.
- Flujo evaluado: evaluación y prototipo acotado de un flujo HIS mediante NativePHP.
- Verificación: checklist de accesibilidad, estados offline/online y riesgos.
- No se declara ejecución real de NativePHP desde esta evidencia documental.
