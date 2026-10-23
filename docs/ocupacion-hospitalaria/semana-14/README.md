# Semana 14 - Plan de aseguramiento de calidad (SQA)

## Portada

- **Estudiante:** Javier Alexander Fajardo López (`Javifa2324`)
- **Módulo:** ASII-09 - Dashboard de ocupación hospitalaria
- **Issue:** [#36](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/36)
- **Entrega:** plan de calidad, métricas, riesgos y estrategia de control

## Propósito y alcance

Definir cómo prevenir, detectar, medir y corregir defectos de ASII-09 desde requisitos hasta operación. El plan cubre el Micro-HIS existente, contrato HTTP, futura interfaz Vue, integración con PostgreSQL y evidencia de entrega. No asigna calidad de escritura sobre datos propiedad de ASII-06/07/08.

- [Catálogo de métricas](./metricas.md)
- [Calendario de seguimiento y acciones](./calendario-seguimiento.md)
- [Matriz de riesgos](./matriz-riesgos.md)
- [Plan de revisión formal](../semana-13/README.md)
- [Declaración de IA](./DECLARACION_IA.md)

## Objetivos de calidad

| Atributo | Objetivo | Control principal |
|---|---|---|
| Exactitud | Cada cama pertenece a una sola categoría y los agregados cuadran | Pruebas de dominio e invariantes |
| Confiabilidad | Una falla explícita nunca se representa como ocupación cero | Pruebas de error y observabilidad |
| Seguridad/privacidad | Solo tenant y permiso válidos consultan agregados sin PII | Pruebas negativas y revisión de payload |
| Rendimiento | Consulta útil dentro del umbral acordado para 10 000 camas | Medición p95 en entorno documentado |
| Accesibilidad | Tareas principales operables con teclado, reflow y contraste AA | Checklist y evidencia de navegador |
| Mantenibilidad | Capas y puertos preservan dependencias y permiten dobles | Pruebas de arquitectura y revisión |
| Trazabilidad | Cada RF/RNF enlaza prueba, evidencia y hallazgo | Matriz y acta de revisión |

## Organización SQA

| Responsabilidad | Responsable propuesto | Evidencia |
|---|---|---|
| Propietario del módulo | Javier Fajardo | Código, documentos, ejecución y correcciones |
| Aprobación de alcance | Líder del equipo | Issue, revisión y decisión del PR |
| Calidad técnica | Revisor backend/QA ajeno al autor | Checklist Semana 13 y hallazgos |
| Contratos de datos | Responsables ASII-06/07/08 | Confirmación de tablas y semántica |
| UX/accesibilidad | Revisor frontend/QA | Recorridos desktop/móvil y reporte |
| Automatización | CI del repositorio | Logs inmutables de pruebas y build |

Los nombres concretos se acuerdan antes de la revisión; la tabla define responsabilidades, no aceptaciones ya obtenidas.

## Estrategia de control

### Prevención

- RF/RNF y criterios de aceptación antes de implementar.
- Regla temporal e invariantes concentrados en Domain.
- Repository como frontera de persistencia y consultas preparadas.
- Contrato API versionado, mínimo privilegio y ausencia de identificadores clínicos.
- Cambios semanales menores de 400 líneas y fuentes editables.
- Diseño explícito de carga, vacío, obsolescencia, error y denegación.

### Detección

- Pruebas unitarias de clasificación, fronteras temporales, tasa y capacidad cero.
- Pruebas de integración PostgreSQL para fotografía, aislamiento y falla.
- Pruebas HTTP de método, ruta, parámetros, autenticación, tenant y `503`.
- Revisión técnica formal sobre un SHA fijo.
- Recorridos de teclado y responsive a 320, 390, 430 y 1024 px.
- Monitoreo de latencia, errores, edad de fotografía e inconsistencias.

### Corrección

- Hallazgo con severidad, responsable, fecha y evidencia de cierre.
- Corrección en commit trazable; no reescribir historia compartida.
- Prueba de regresión obligatoria para cada defecto confirmado.
- Nueva revisión cuando cambie dominio, seguridad o contrato.
- Rollback del despliegue si se rompe una puerta crítica.

## Ciclo por entrega

1. **Planificar:** fijar alcance, criterios, riesgos y métricas afectadas.
2. **Construir:** aplicar capas, pruebas y documentación en el mismo cambio.
3. **Verificar:** ejecutar suite, integración disponible, build y checklist.
4. **Revisar:** celebrar revisión formal y clasificar hallazgos.
5. **Corregir:** cerrar defectos con evidencia y repetir regresión.
6. **Liberar:** aprobar puerta de calidad y registrar versión/entorno.
7. **Observar:** medir en ejecución y retroalimentar el siguiente ciclo.

## Puerta de calidad para PR

Un cambio puede proponerse para integración cuando:

- no existen hallazgos bloqueantes o mayores abiertos;
- el 100 % de pruebas obligatorias pasa en los entornos declarados;
- exactitud, suma global, falla segura y ausencia de PII cumplen el umbral;
- rutas negativas de autenticación, permiso y tenant tienen evidencia;
- `git diff --check`, sintaxis y fuentes de diagramas pasan;
- documentación, declaración de IA y límites están actualizados;
- cualquier métrica no ejecutada tiene responsable, fecha y riesgo aceptado.

No alcanzar rendimiento o accesibilidad crítica bloquea producción. Una prueba PostgreSQL no ejecutada puede permitir revisión documental, pero no la entrega funcional de Semana 16.

## Línea base actual

| Evidencia | Estado medido el 21/10/2026 |
|---|---|
| Suite Micro-HIS | `OK: 12 verificaciones aprobadas` (camino feliz, error de persistencia y arquitectura) |
| Contrato HTTP e integridad de ocupación | `Tests: 22 passed (125 assertions)` |
| Utilidades de la pantalla Vue | `OK: 14 aserciones` |
| Semántica de ocupación en PostgreSQL 16 | 6 pruebas de integridad en verde |
| Prototipo | HTML/CSS/JS local; sintaxis JS verificada con `node --check` |
| Frontend Vue | **Implementado** en la semana 16 (`resources/js/modules/ocupacion/`) |
| Accesibilidad | roles `status`/`alert` presentes; contraste y reflow **sin medir** |
| Rendimiento 10 000 camas | Umbral propuesto, medición pendiente (sin datos) |

## Auditorías y conservación

- Conservar logs de comandos y CI asociados al SHA.
- Mantener actas y hallazgos en el repositorio sin datos sensibles.
- Revisar métricas en cada PR y riesgos al menos una vez por semana activa.
- Retener evidencia del curso durante la vigencia académica o política del equipo.
- Nunca almacenar tokens, DSN con contraseña ni datos reales de pacientes.

## Evidencia de esta entrega

Salida real del 21 de octubre de 2026 sobre `develop` en `75bd69ce`:

```bash
$ php micro-his/ocupacion-hospitalaria/tests/run.php
OK: 12 verificaciones aprobadas

$ php artisan test tests/Feature/Ocupacion tests/Database/Hospital/OccupancySnapshotIntegrityTest.php
Tests:    22 passed (125 assertions)

$ node --test resources/js/modules/ocupacion/ocupacion.test.mjs
OK: 14 aserciones de la pantalla de ocupación aprobadas

$ git diff --check
```

La línea de base completa, con responsable, fuente y acción por métrica, está en el
[calendario de seguimiento](./calendario-seguimiento.md).

## Conclusión

El plan SQA hace verificables las garantías del dashboard y evita confundir una suite verde con calidad total. Exactitud, falla segura, privacidad, accesibilidad y rendimiento requieren controles y evidencia propios antes de integrar.
