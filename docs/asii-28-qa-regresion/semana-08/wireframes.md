# Wireframes iniciales — seis pantallas propuestas

Abrir [wireframes.html](./wireframes.html) directamente en un navegador, sin levantar
Laravel/Vite ni introducir datos. HTML/CSS es fuente editable, sin librerías o API.
Distribución de dos columnas en escritorio y una bajo 850 px; cada sección es una
pantalla de baja fidelidad. Los enlaces saltan entre diseños, no ejecutan casos de uso.
Vacío, error y éxito se muestran juntos como catálogo de estados alternativos;
en una implementación futura solo se mostrará el estado pertinente.

| Wireframe / enlace | Objetivo, contenido y estados |
|---|---|
| [WF-01 Panel QA](./wireframes.html#wf-1) | Navegación, campañas/casos pendientes/defectos [n], ejecuciones recientes; vacío orienta al diseño de caso y error de carga ofrece reintento. |
| [WF-02 Campañas](./wireframes.html#wf-2) | Búsqueda, filtro de estado por definir, tarjeta con cantidad [n], detalle; estado vacío y error sin borrar filtros. |
| [WF-03 Caso manual](./wireframes.html#wf-3) | ID generado; título es etiqueta UX de purpose, no un campo nuevo del núcleo; steps/expected obligatorios. Requisito y precondiciones son extensiones propuestas sin persistencia actual. Guardar/cancelar y validación por campo. |
| [WF-04 Detalle](./wireframes.html#wf-4) | Campaña, casos, último resultado si existe, responsable sin asignar propuesto; consultar/ejecutar, vacío y trazabilidad. |
| [WF-05 Ejecución](./wireframes.html#wf-5) | Pasos/esperado, observaciones, comparación aprobado/fallido y referencia privada; sin evidencia no confirma; cancelar y confirmación con ID nuevo. |
| [WF-06 Defecto](./wireframes.html#wf-6) | Origen fallido, descripción, severidad/estado por definir, referencia de evidencia y guardar/cancelar; resultado con vínculo y error de origen inválido. |

No se inventan catálogos de campaña/defecto ni severidades: [por definir] es
placeholder. Aprobado/fallido sí proceden de RF-04. Responsable de campaña no
está en contratos actuales: se muestra como propuesta sin asignar a una persona.
No hay estadísticas reales, pacientes, credenciales ni contenido clínico.
El wireframe de caso no demuestra UI existente: únicamente tres campos tienen
respaldo en el alta CLI actual. Los demás recorridos requieren implementar el flujo.
