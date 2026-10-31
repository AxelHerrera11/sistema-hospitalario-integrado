# Órdenes de laboratorio desde EMR — Semana 13: revisión técnica formal

**Módulo:** Órdenes de laboratorio desde EMR (ASII-16) · **Entrega académica:** Fajardo López, Javier Alexander (`Javifa2324`, ASII-09)
**Alcance:** reglas de dominio, contrato API, permisos/tenant, integridad de la orden, estados,
trazabilidad y dependencias estrictamente necesarias para «emisión de orden desde el expediente y
seguimiento de su estado».
**Artefactos:** [`plan_revision.md`](./plan_revision.md) · [`checklist.md`](./checklist.md) · [`formato_hallazgos.md`](./formato_hallazgos.md) · [`hallazgos_iniciales.md`](./hallazgos_iniciales.md)

## 1. Propósito

Preparar una revisión técnica formal, no una demo. La revisión debe responder una pregunta concreta:
**¿el módulo puede emitir y seguir una orden sin perder integridad, aislamiento por hospital,
trazabilidad ni claridad de contrato?**

La revisión es **previa a la ejecución**: el checklist está listo, los roles y criterios están
definidos y los hallazgos iniciales se registran como candidatos que deben confirmarse con evidencia
en la sesión.

## 2. Artefactos bajo revisión

| Artefacto | Ubicación | Qué se revisa |
|---|---|---|
| Dominio | `micro-his/ordenes-laboratorio/src/Domain/` | Invariantes, transiciones, cancelación, ítems |
| Aplicación | `src/Application/` | Casos de uso, idempotencia, transacción, errores |
| Persistencia | `src/Persistence/` y `database/schema.sql` | Tenant, índices, eventos append-only, unicidad |
| Entrada HTTP | `src/Presentation/` y `public/index.php` | Permisos, errores, contrato, cabeceras |
| Contrato | `docs/ordenes-laboratorio/semana-05/openapi.yaml` | Rutas, códigos, esquemas, seguridad |
| Pruebas | `micro-his/ordenes-laboratorio/tests/` | Cobertura de reglas, fallos y adaptadores |
| Dependencias | `composer.json` / código | Solo PHP 8.2+, PDO y extensiones necesarias |

## 3. Roles

| Rol | Responsabilidad | Asignación propuesta |
|---|---|---|
| Autor | Presenta el diseño, responde preguntas y corrige hallazgos | Javier Alexander Fajardo López |
| Moderador | Conduce la sesión, controla el tiempo y declara la decisión | Docente o par designado |
| Revisor de dominio/API | Verifica invariantes, estados y contrato | Par 1 |
| Revisor de seguridad/datos | Verifica permisos, tenant, privacidad y dependencias | Par 2 |
| Secretario | Registra hallazgos, acuerdos y responsables | Moderador o par 1 |

En una revisión individual, los roles de revisores se simulan con dos pares; el autor no aprueba sus
propios hallazgos.

## 4. Criterios de entrada

1. El código y la documentación están congelados para la revisión (sin cambios durante la sesión).
2. `php tests/run.php`, `php tests/mvc.php`, `php tests/api.php` y `php tests/client.php` terminan sin
   fallos en el entorno acordado.
3. El OpenAPI corresponde a la versión revisada.
4. El autor entregó una guía de 1 página con los casos de uso y las decisiones discutibles.
5. Los revisores tienen acceso a los artefactos y al checklist.
6. No hay incidentes abiertos que impidan la sesión.

## 5. Criterios de salida

La revisión termina con una de estas decisiones:

- **Aprobado:** no hay hallazgos bloqueantes ni mayores abiertos.
- **Aprobado con cambios:** hay hallazgos mayores con responsable y fecha; no se publica hasta
  cerrarlos.
- **Rechazado:** existe un hallazgo bloqueante sin mitigación aceptada.

Además, deben quedar:

1. checklist completo (15/15 ítems revisados);
2. hallazgos con severidad, evidencia, responsable y fecha;
3. minuta con decisiones y acciones;
4. fecha de seguimiento para cerrar hallazgos;
5. confirmación de que no se revisaron módulos ajenos (muestras, resultados, facturación).

## 6. Agenda propuesta (90 minutos)

| Minuto | Actividad |
|---:|---|
| 0–5 | Apertura, alcance y reglas |
| 5–15 | Recorrido del flujo y del contrato |
| 15–50 | Checklist C-01…C-15 con evidencia |
| 50–70 | Discusión y clasificación de hallazgos |
| 70–80 | Decisión y plan de acción |
| 80–90 | Minuta y cierre |

## 7. Método de revisión

- **Inspección basada en checklist:** cada ítem se marca con evidencia concreta, no con opinión.
- **Trazabilidad requisito→artefacto:** se usa la matriz de la semana 12 y los RF/RNF de la semana 2.
- **Prueba de lectura:** el revisor intenta refutar la decisión con un caso límite.
- **Muestreo de pruebas:** se ejecutan al menos una prueba feliz, una de error y una de aislamiento.
- **Regla de no invención:** si no hay evidencia, el ítem queda «No verificado», no «Aprobado».

## 8. Hallazgos iniciales

Antes de la sesión se identificaron cuatro candidatos que deben confirmarse o descartarse con
evidencia: longitud del motivo de cancelación, respuestas del contrato en lecturas, confianza en
`X-Permissions` y reutilización de `Idempotency-Key` con otro payload. Están en
[`hallazgos_iniciales.md`](./hallazgos_iniciales.md), con severidad provisional y sin declararlos
corregidos.

## 9. Fuera de alcance

- Resultados de laboratorio, toma de muestras y facturación.
- Integración real con el expediente clínico.
- Auditoría de accesibilidad con usuarios.
- Rendimiento a escala hospitalaria real.
- Revisión de módulos de otros equipos.

## 10. Declaración de IA

[`DECLARACION_IA.md`](./DECLARACION_IA.md), con validación humana pendiente.
