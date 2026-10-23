# Semana 13 - Plan de revisión técnica formal

## Portada

- **Estudiante:** Javier Alexander Fajardo López (`Javifa2324`)
- **Módulo:** ASII-09 - Dashboard de ocupación hospitalaria
- **Issue:** [#36](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/36)
- **Entrega:** checklist, responsables y evidencia esperada

## Objetivo y alcance

Detectar defectos antes de integrar ASII-09 mediante una revisión estructurada de requisitos, dominio, arquitectura, contrato HTTP, persistencia, seguridad, UX, pruebas y documentación. El plan aplica al cambio acumulado de la semana candidata, pero cada hallazgo señala archivo y versión exactos.

- [Checklist formal](./checklist-revision.md)
- [Formato de acta y hallazgos](./formato-acta.md)
- [Declaración de IA](./DECLARACION_IA.md)

La revisión no sustituye pruebas automatizadas, CI, aprobación del líder ni validación clínica/operativa. Tampoco autoriza cambios de alcance durante la reunión.

## Roles y responsabilidades

| Rol | Responsable propuesto | Responsabilidad | Restricción |
|---|---|---|---|
| Autor | Javier Fajardo | Preparar paquete, responder dudas y corregir | No modera ni cierra sus propios hallazgos |
| Moderador | Líder del equipo o delegado | Verificar entrada, dirigir sesión y decidir salida | No evalúa autoría personal |
| Revisor técnico | Integrante backend ajeno a ASII-09 | Revisar capas, API, SQL y pruebas | Registra evidencia reproducible |
| Revisor de integración | Responsable ASII-06/07/08 | Confirmar propiedad de datos y contratos | No aprueba escrituras desde ASII-09 |
| Revisor UX/accesibilidad | Integrante frontend o QA | Revisar estados, móvil, teclado y semántica | Distingue prototipo de producción |
| Secretario | Persona designada por moderación | Numerar hallazgos y conservar decisiones | No elimina observaciones sin trazabilidad |

Una persona puede asumir dos roles si el equipo es pequeño, excepto autor y moderador. Toda sustitución queda registrada en el acta.

## Criterios de entrada

- Issue y alcance identificados; commit o SHA candidato inmutable.
- Diff menor de 400 líneas para la unidad semanal revisada.
- `README`, contrato y declaración de IA disponibles.
- `git diff --check` y pruebas aplicables ejecutadas con resultado adjunto.
- Diagrama o fuente editable disponible cuando el cambio altera diseño.
- Limitaciones conocidas declaradas, incluido PostgreSQL opcional.
- Paquete enviado a revisores al menos 24 horas antes, cuando el calendario lo permita.

Si falta un criterio obligatorio, moderación pospone la sesión en vez de convertirla en lectura improvisada.

## Procedimiento

1. **Planificación:** moderación fija SHA, alcance, participantes y duración de 45-60 minutos.
2. **Preparación individual:** cada revisor aplica el checklist y registra ubicación, impacto y pregunta.
3. **Apertura:** autor resume objetivo y flujo en cinco minutos, sin defender línea por línea.
4. **Inspección:** se recorren invariantes, interfaces y riesgos; secretaría asigna ID y severidad.
5. **Clasificación:** el grupo acuerda defecto, duda o mejora y designa responsable/plazo.
6. **Salida:** moderación decide aprobado, aprobado con correcciones o requiere nueva revisión.
7. **Retrabajo:** autor corrige en commits posteriores sin reescribir evidencia compartida.
8. **Seguimiento:** cada hallazgo se cierra con enlace a diff, prueba o decisión documentada.

## Clasificación de hallazgos

| Severidad | Definición | Regla de salida |
|---|---|---|
| Bloqueante | Riesgo de dato incorrecto, fuga entre tenants, exposición clínica o pérdida de disponibilidad segura | Impide aprobar y exige nueva verificación |
| Mayor | Incumple RF/RNF, contrato, arquitectura o accesibilidad de tarea principal | Debe corregirse antes del PR |
| Menor | Defecto localizado con alternativa clara | Puede aprobarse con responsable y fecha |
| Mejora | No incumple un criterio actual | Se registra en backlog; no bloquea |
| Duda | Falta información para decidir | Debe resolverse o convertirse en riesgo aceptado |

## Evidencia esperada

| Área | Evidencia mínima |
|---|---|
| Requisitos | Matriz RF/RNF y criterios de aceptación enlazados |
| Dominio | Casos de corte temporal, clasificación única y tasa `N/A` |
| Arquitectura | Diagrama editable y dependencia permitida por capa |
| Persistencia | Consulta preparada y prueba PostgreSQL cuando exista DSN |
| API/seguridad | Respuestas `200/400/401/403/405/503`, tenant y ausencia de PII |
| UX | Estados éxito, vacío, obsoleto, falla y denegación desktop/móvil |
| Accesibilidad | Checklist, teclado, foco, semántica y medición de contraste |
| Pruebas | Comando, entorno, resultado y fallo reproducible si aplica |
| Revisión | Acta firmada, registro de hallazgos y enlaces de cierre |

## Criterios de salida

**Aprobado:** cero bloqueantes/mayores abiertos, checklist completo y evidencia reproducible.

**Aprobado con correcciones:** cero bloqueantes, únicamente menores con responsable y fecha; moderación verifica el cierre.

**Nueva revisión:** cualquier bloqueante o mayor abierto, o un cambio sustancial posterior en dominio, seguridad o contrato API.

El porcentaje de checklist no reemplaza estos criterios: un solo defecto de tenant es suficiente para bloquear.

## Métricas de la revisión

- Hallazgos por severidad y área.
- Tiempo de preparación y reunión.
- Porcentaje cerrado en el plazo acordado.
- Hallazgos reabiertos.
- Defectos encontrados después de una revisión aprobada.

Estas métricas evalúan el proceso, no el desempeño individual, y alimentan el plan SQA de Semana 14.

## Evidencia de esta entrega

```bash
php micro-his/ocupacion-hospitalaria/tests/run.php
node --check docs/ocupacion-hospitalaria/semana-11/prototipo/app.js
git diff --check
```

## Conclusión

La revisión formal convierte opiniones dispersas en decisiones trazables. Separar roles, fijar una versión y exigir evidencia reduce el riesgo de aprobar una interfaz atractiva con reglas de ocupación, seguridad o fallos incorrectos.
