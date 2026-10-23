# Checklist de revisión técnica ASII-09

Marcar `Sí`, `No` o `N/A`; todo `No` requiere un hallazgo. Un `N/A` exige justificación.

| ID | Pregunta de control | Resultado | Evidencia/hallazgo |
|---|---|---|---|
| REQ-01 | ¿El cambio está dentro del issue #36 y tiene criterio de aceptación? |  |  |
| REQ-02 | ¿Distingue requisitos confirmados, propuestas y limitaciones? |  |  |
| DOM-01 | ¿La estancia activa se evalúa con `started_at <= T` y `ended_at > T` o nulo? |  |  |
| DOM-02 | ¿Cada cama se clasifica exactamente una vez? |  |  |
| DOM-03 | ¿La tasa excluye mantenimiento, inactivas y no verificadas? |  |  |
| DOM-04 | ¿Capacidad cero produce `N/A` y no `0 %`? |  |  |
| DOM-05 | ¿Las discrepancias permanecen visibles y no se corrigen aquí? |  |  |
| ARQ-01 | ¿Presentation carece de SQL y reglas de clasificación? |  |  |
| ARQ-02 | ¿Application depende del puerto y no de PDO? |  |  |
| ARQ-03 | ¿ASII-09 solo lee datos propiedad de ASII-06/07/08? |  |  |
| API-01 | ¿El contrato v1 conserva campos, errores y fecha canónica? |  |  |
| API-02 | ¿Método/ruta/parámetros inválidos reciben estado coherente? |  |  |
| REL-01 | ¿La lectura es una fotografía coherente para un único corte? |  |  |
| REL-02 | ¿Una falla devuelve `503` sin cuerpo de ocupación cero? |  |  |
| SEC-01 | ¿JWT, tenant y permiso se comprueban antes de acceder a persistencia? |  |  |
| SEC-02 | ¿La respuesta omite paciente, episodio, token, DSN y datos clínicos? |  |  |
| SEC-03 | ¿Un `401/403` elimina cualquier dato previamente visible? |  |  |
| UX-01 | ¿Éxito, vacío, obsoleto, falla y denegación son distinguibles? |  |  |
| UX-02 | ¿Desktop y móvil mantienen corte, frescura y discrepancias? |  |  |
| A11Y-01 | ¿Todo control funciona por teclado y muestra foco visible? |  |  |
| A11Y-02 | ¿Semántica, nombres y mensajes de estado son programáticos? |  |  |
| A11Y-03 | ¿Contraste y reflow tienen evidencia, no solo declaración? |  |  |
| TST-01 | ¿Pruebas cubren camino feliz, bordes, errores y arquitectura? |  |  |
| TST-02 | ¿Los comandos y el entorno permiten reproducir el resultado? |  |  |
| DOC-01 | ¿README, diagramas, prototipo y código describen lo mismo? |  |  |
| DOC-02 | ¿La declaración de IA fue revisada por el estudiante? |  |  |
| GIT-01 | ¿El diff es enfocado, menor de 400 líneas y sin secretos/binarios ajenos? |  |  |

## Resultado

- **SHA revisado:**
- **Fecha:**
- **Revisores:**
- **Bloqueantes / mayores / menores / mejoras:**
- **Decisión:** aprobado / aprobado con correcciones / nueva revisión
- **Firma de moderación:**
