# Plan de revisión técnica formal

**Módulo:** Órdenes de laboratorio desde EMR · **Semana 13**
**Autor:** Fajardo López, Javier Alexander (`Javifa2324`, ASII-09).
**Duración:** 90 minutos · **Modalidad:** revisión por pares con moderador.

## 1. Preguntas que debe responder la revisión

1. ¿Puede crearse una orden incompleta, incoherente o con una prueba inactiva?
2. ¿Puede una orden saltarse una transición o cancelarse después de validada?
3. ¿Puede un usuario de otro hospital leer o modificar una orden?
4. ¿Puede un usuario sin permiso emitir, consultar, avanzar o cancelar?
5. ¿La orden, sus ítems y su primer evento se guardan juntos o pueden quedar a medias?
6. ¿El historial es reconstruible y append-only?
7. ¿El contrato OpenAPI describe lo que realmente devuelve la implementación?
8. ¿Las dependencias son estrictamente necesarias para este alcance?

## 2. Estrategia

| Técnica | Uso | Salida |
|---|---|---|
| Inspección por checklist | 15 ítems con evidencia y criterio | Resultado por ítem |
| Recorrido del flujo | Emisión, consulta, avance, cancelación | Confirmación o hallazgo |
| Casos adversariales | Tenant ajeno, sin permiso, replay, motivo inválido, transición saltada | Evidencia de rechazo seguro |
| Muestreo de pruebas | Una feliz, una de error, una de aislamiento, una de persistencia | Registro de comandos y resultados |
| Contraste contrato–código | OpenAPI vs. `ApiKernel`/controlador | Deriva de contrato |
| Revisión de dependencias | `composer.json`, imports y extensiones | Dependencia innecesaria o justificada |

## 3. Muestreo mínimo de pruebas

| # | Caso | Comando o archivo | Qué se espera |
|---|---|---|---|
| M1 | Emisión válida | `php tests/run.php` | Orden `ISSUED` con ítems y evento |
| M2 | Transición inválida | `php tests/run.php` | `INVALID_STATUS_TRANSITION` |
| M3 | Replay idempotente | `php tests/api.php` | 200 con la misma orden, sin duplicar |
| M4 | Sin permiso | `php tests/api.php` | 403 `FORBIDDEN` |
| M5 | Tenant ajeno | `php tests/run.php` / `api.php` | 404 sin revelar existencia |
| M6 | Fallo de persistencia | `php tests/run.php` | 503/rollback sin orden parcial |
| M7 | Cancelación con motivo | `php tests/mvc.php` | 200 `CANCELLED` con evento |
| M8 | Cliente sin red | `php tests/client.php` | Error controlado, sin reintento de escritura |

## 4. Materiales

- Repositorio en el commit congelado y cuatro baterías de pruebas.
- `docs/ordenes-laboratorio/semana-05/openapi.yaml`.
- `docs/ordenes-laboratorio/semana-12/matriz_decisiones.md`.
- `database/schema.sql` y migraciones si existen.
- Checklist y formato de hallazgos impresos o compartidos.
- Reloj visible y temporizador por bloque.

## 5. Reglas de la sesión

- El autor presenta; no defiende con opiniones sin evidencia.
- Cada ítem se marca **Cumple**, **No cumple**, **Parcial** o **No verificado**.
- Un ítem sin evidencia no se aprueba por confianza.
- Los hallazgos se clasifican al final, no se discuten uno por uno sin límite.
- El moderador puede cortar una discusión que no cambie la decisión.
- No se revisan módulos ajenos ni se proponen cambios de alcance no aprobados.

## 6. Clasificación y decisión

| Severidad | Criterio | Efecto |
|---|---|---|
| Bloqueante | Pérdida de integridad, fuga entre tenants, cancelación de una orden validada o dato clínico expuesto | Rechazo hasta corregir |
| Mayor | Contrato incumplido, permiso ausente, historial no append-only o prueba crítica faltante | Aprobado con cambios |
| Menor | Documentación incompleta, mensaje confuso o deuda de mantenibilidad | Aprobado con acciones |
| Observación | Mejora sin impacto inmediato | Backlog |

**Decisión:** se aprueba solo si no hay bloqueantes y todos los mayores tienen responsable y fecha.

## 7. Seguimiento

- Los hallazgos mayores se cierran antes de publicar el PR.
- Los menores se registran en el backlog con fecha.
- La fecha de seguimiento se acuerda al cierre (máximo 5 días hábiles).
- El autor actualiza el checklist con la evidencia de la corrección.

## 8. Riesgos de la propia revisión

| Riesgo | Mitigación |
|---|---|
| Revisión demasiado amplia | Limitar a emisión, seguimiento, permisos, integridad y trazabilidad |
| Sesgo del autor | Moderador y dos revisores; el autor no cierra solo los hallazgos |
| Pruebas verdes sin cobertura real | Muestreo adversarial y contraste con RF/RNF |
| Discusión de estilos | El checklist separa corrección de preferencia |
| Falta de tiempo | Timebox por bloque; lo no visto queda «No verificado» |
