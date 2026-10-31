# ASII-28 - QA Funcional - Semana 11 - Prototipo navegable

Madelin Jazmín Cerón Molina (`MadelinCeron`). Refs #12.
Rama feature/asii-28-semana-11-qa-regresion-madelinceron; base develop.
HEAD inicial ca324b6d99b72d1aa6db59533172ccaf25e56308, igual a origin/develop
tras fetch; árbol inicialmente limpio. Sin worktrees, pull ni commits previos incorporados.

## Abrir y demostrar

Abrir [prototipo-navegable.html](./prototipo-navegable.html) directamente en Edge.
No requiere servidor ni dependencias. Recorrido: panel → campañas → detalle →
nuevo caso → ejecución → evidencia de ejemplo → defecto → panel actualizado.
Seis vistas, menú/hash, búsqueda/filtro, errores de formulario, foco de destino,
diálogo de evidencia y confirmaciones **simuladas**. Cada registro existe solo en
memoria JS de esta página: recargar/cerrar elimina cambios. No hay API, PHP,
login, localStorage, archivos adjuntos ni conexión al núcleo CLI de Semana 4.

- [Recorrido, pruebas y trazabilidad](./recorrido-pruebas.md).
- [Capturas reales y procedimiento](./evidencia-visual.md).
- [Prueba automatizada ligera, sin librerías](./verificar-prototipo.mjs).

## Fuentes y estado

[RF/RNF de Semana 2](../semana-02/rf-rnf-criterios.md) y diseños pendientes:
#1403 (Semana 8), #1408 (Semana 9), #1452 (Semana 10), todos abiertos/no fusionados
según GitHub. Se consultaron desde sus ramas sin copiar historial ni modificar
sus archivos. El prototipo conserva la apariencia de baja fidelidad e incorpora
consulta distinta de creación, cancelación separada, obligatoriedad y ayuda de evidencia.

Validación nueva del 29/10/2026: **17 comprobaciones del prototipo aprobadas,
0 fallidas**, no son las 17 pruebas PHP históricas. Reflow a 1280/390/320 px,
teclado, validación, registro en memoria, bloqueo de defecto desde aprobado y reset
tras recargar comprobados. Capturas desktop/móvil en capturas/; sin prueba humana
ni integración backend. Las referencias de evidencia y clasificación son ficticias.

Pendientes para Semana 12: revisión con participantes autorizados, zoom real/lector
de pantalla, recuperación incierta e idempotencia, seguridad/tenant, catálogos y
almacenamiento acordados. No se afirma WCAG certificado ni campañas productivas.
Madelin Cerón mantiene código/evidencia; revisión y aprobación pendientes de asignación.
