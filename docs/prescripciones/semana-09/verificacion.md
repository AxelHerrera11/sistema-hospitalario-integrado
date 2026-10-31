# Lista de verificación de usabilidad y accesibilidad

Estado de esta lista: inspección documental/código; `PENDIENTE` significa que
no se verificó en ejecución.

| Criterio | Estado | Evidencia o siguiente comprobación |
|---|---|---|
| Hay punto de entrada real a Prescripciones | Hallazgo H-01 | El item actual no tiene destino y falta ruta |
| Etiquetas y mensajes se asocian con cada campo | Hallazgo H-02 en UI análoga | Probar el componente objetivo cuando exista |
| Flujo completo operable solo con teclado | `PENDIENTE` | Requiere UI navegable |
| Orden y retorno del foco en diálogos | `PENDIENTE` | Requiere diálogo implementado y prueba manual |
| Contraste de texto y estados | `PENDIENTE` | Medir colores reales con herramienta; no inferir de wireframes |
| Reflujo y ampliación de contenido | `PENDIENTE` | Probar en navegador con contenido real |
| Mensajes de rechazo y advertencia tienen texto no dependiente del color | `PROPUESTO` en wireframes S8 | Validar en UI y tecnología asistiva |
| Se informa claramente que un 422 no guardó | `PROPUESTO` en wireframes S8 | Comprobar respuesta y anuncio en integración |

No se declara conformidad WCAG ni una evaluación de usuarios. La falta de
pantalla implementada limita el resultado a inspección de fuente y diseño.
