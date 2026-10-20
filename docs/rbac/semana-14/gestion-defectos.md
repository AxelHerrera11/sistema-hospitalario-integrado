# Procedimiento de gestión de defectos y verificación de correcciones

Aplica a todo fallo detectado en el módulo RBAC durante pruebas, revisión o integración. Un defecto no se cierra sin corrección **verificada** por una prueba que lo reproduzca.

## 1. Ciclo de vida

1. **Registro:** se crea un issue con los campos de la sección 3 y el `trace_id` si la falla provino de una denegación.
2. **Triaje:** el responsable del módulo clasifica severidad y prioridad (sección 2) y asigna.
3. **Reproducción:** se reduce a un caso mínimo sobre la suite; sin reproducción no se inicia corrección.
4. **Causa raíz:** se identifica el componente (dominio, persistencia, presentación, middleware, frontend) y la dimensión afectada.
5. **Corrección:** se modifica el código en una rama de corrección, con la prueba de regresión correspondiente.
6. **Verificación:** se ejecuta la prueba que reprodujo el defecto y la suite completa (sección 4).
7. **Cierre:** solo el verificador cierra; se registra la evidencia y el `commit` de corrección.

## 2. Severidad y prioridad

| Severidad | Criterio | Ejemplo |
|---|---|---|
| Crítica | Fuga de tenant, falso permitido, escalación, operación sin traza | `med1` autoriza en H2 (CA-TEN-01) |
| Alta | Denegación errónea de una operación válida, rollback ausente | membresía residual tras 500 (CA-PRS-01) |
| Media | Mensaje o código incorrecto sin efecto de seguridad | motivo de denegación equivocado |
| Baja | Defecto cosmético o de documentación | ruta citada desactualizada |

La prioridad se fija por severidad e impacto en el *gate* activo. Crítica y Alta bloquean el gate y tienen prioridad sobre cualquier mejora.

## 3. Campos obligatorios del registro

- Identificador y título; módulo `ASII-02`; versión/commit base.
- **Dimensión:** permisos | autenticación | tenant | trazabilidad | persistencia.
- Precondiciones, pasos, resultado esperado y resultado observado.
- Caso de prueba y criterio de aceptación relacionados (`CA-*`, `Q-*`).
- Severidad, prioridad, componente, `trace_id` (si aplica).
- Evidencia (salida de consola, captura, filas de base).
- Estado: `Nuevo`, `En análisis`, `En corrección`, `Corregido`, `Verificado`, `Cerrado`, `Reabierto`.

## 4. Verificación de una corrección

1. **Reproducir antes:** el verificador confirma que el defecto se reproduce en el commit base.
2. **Aplicar corrección:** se actualiza la rama; el commit referencia el defecto.
3. **Probar el caso:** la prueba que reproducía el defecto pasa en el commit corregido.
4. **Regresión:** se ejecuta la suite base (`php micro-his/rbac/tests/run.php`) más las adiciones de regresión aplicables; debe quedar 100 % `OK`.
5. **Comprobar la dimensión:** se verifica que la corrección no rompió otra dimensión (p. ej. no debilitó el aislamiento de tenant).
6. **Cerrar:** se adjunta la salida de ambos pasos; `Corregido` sin verificación **no** equivale a `Cerrado`.

Distinción de estados: `Corregido` = existe cambio sin verificar; `Verificado` = la prueba pasa; `Cerrado` = verificado y sin regresiones; `Reabierto` = reaparece (afecta Q-DEF-02).

## 5. Casos especiales

- **Defectos de seguridad (Crítica en permisos, tenant o autenticación):** no se documentan con detalle explotable; se notifican al revisor `@rortizs` y a Gobernanza si involucra la bitácora.
- **Defectos de trazabilidad:** requieren conservar el `trace_id` original como evidencia y comprobar que la corrección no altera la inmutabilidad del registro previo.
- **Defectos de persistencia:** deben incluir el estado de la transacción antes y después y comprobar la ausencia de filas parciales.

## 6. Métricas de defectos

Alimentan `Q-DEF-01` (densidad por dimensión) y `Q-DEF-02` (reincidencia). El tablero de defectos es el registro de issues del módulo; no se reportan cifras hasta que existan issues reales.
