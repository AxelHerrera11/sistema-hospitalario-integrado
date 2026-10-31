# Plan SQA aplicable a ASII-28

Versión inicial para revisión; autora documental: Madelin Jazmín Cerón Molina. Refs #12.
Fuentes/commits y nuevas ejecuciones en [README](./README.md); umbrales en [métricas](./metricas-calidad.md).

## Objetivos y prácticas

SQA define el proceso preventivo: criterios, revisiones, responsables y evidencias.
Control de calidad inspecciona productos y desviaciones; ejecutar pruebas es una de
sus actividades, no el aseguramiento completo. Objetivo: requisitos verificables,
reglas en dominio, contratos estables, datos aislados e historia/evidencia conservadas.
El alcance productivo futuro no se declara implementado por completar documentos.
Prácticas propuestas: mantener cuatro capas y dependencias hacia Domain; contratos
Repository con pre/postcondiciones, SQL parametrizado, validación en frontera y dominio,
revisión de PR, trazabilidad versionada y datos de prueba ficticios. Se siguen plantilla
del repositorio y límite de 400 líneas por PR, sin afirmar certificación ISO o WCAG.
No fusionar PR pendientes para mejorar artificialmente mediciones.

## Roles y separación de responsabilidades

| Rol | Responsable / atribución real |
|---|---|
| Documentación SQA y evidencia ejecutada en Semana 14 | Madelin Jazmín Cerón Molina; mantiene su entrega y corrige sus documentos |
| Desarrollo backend QA | Pendiente de asignación; rol propuesto para dominio/Repository/API/persistencia |
| Desarrollo frontend QA | Pendiente de asignación; rol propuesto para UI/errores/navegación |
| Pruebas y medición futura | Pendiente de asignación; rol propuesto para casos, ejecuciones y registro de métricas |
| Seguridad | Pendiente de asignación; rol propuesto para autorización, propiedad tenant y revisión de datos |
| Revisor independiente / verificador de cierre | Pendiente de asignación; distinto de autor/corrector; sin dictamen de terceros inventado |

No hay sesión formal, aprobación de compañeros o compromiso de fechas acreditados.

## Estrategia de pruebas y preparación de Semana 15

Backend actual: RF-01; suites existentes son línea base de regresión, no cobertura total.
HTML: comprobaciones de simulación. API, permisos, tenant y flujo persistente requerirán
implementación aprobada y ambiente aislado antes de poder probarse funcionalmente.

| Tipo / objetivo | Material o casos propuestos | Evidencia y situación actual |
|---|---|---|
| Unitarias (mínimo 3 en Semana 15) | ManualCase válido; textos vacíos; normalización trim, sin SQL | Proponer pruebas unitarias aisladas con expected/observed; no atribuir automáticamente el mínimo a la suite mixta actual |
| Caja blanca (mínimo 2) | Ramas éxito/rechazo de dominio y rama StorageFailure de Controller con doble controlado | Enumerar ramas recorridas y salida; sin porcentaje de cobertura hasta instrumentar/medir |
| Caja negra (mínimo 4) | CLI caso completo→201, tipo incorrecto→400, vacío→422 y JSON inválido→400 | Ejecutar como entradas/salidas externas en Semana 15 con registro individual; aquí solo demo válida/vacía |
| Funcionales/regresión backend | Reejecutar 17 actuales de casos en ambos adaptadores después de cambios; nuevas invariantes de campaña/ejecución cuando existan | Suite actual ejecutada ahora; backend RF-02–08 aún pendiente |
| API/contrato | POST/GET, forma JSON, status, límites y errores propuestos #1400 | Pendiente de adaptador real; resultados CLI no sirven como respuesta HTTP |
| Autorización | Sin identidad, identidad inválida, permiso ausente y permitido | Pendiente de controles y cuenta de pruebas autorizada; sin credenciales en evidencia |
| Tenant | Ámbitos A/B: denegar lectura/escritura cruzada, contexto ausente y manipulado | Pendiente de modelo/implementación; solo datos ficticios en entorno aislado |
| Integridad e historia | Defecto solo desde fallo; evidencia del origen; segunda ejecución preserva primera; contrato común de registro | Pendiente backend; evaluar campos y vínculos, no solo contadores HTML |
| UX/usabilidad | Tareas campaña→caso→ejecución→defecto, recuperación incierta y descarte con cambios | Parcial técnico; estudio con participantes pendiente de protocolo y autorización |
| Accesibilidad/responsive | Teclado completo, foco, anuncios/lector, zoom y 1280/390/320 px | Reflow y foco parcial medidos; resto pendiente; sin certificación |

Cada registro de prueba incluirá ID local, versión, RF/RNF o control técnico vinculado,
tipo, precondición, datos ficticios, pasos, esperado, observado, resultado, comando/
entorno, evidencia y defecto si corresponde. Separar casos del sistema bajo prueba,
tests PHP y comprobaciones HTML. No renombrar pruebas históricas para inflar mínimos.

## Criterios de entrada y salida propuestos

Entrada a una campaña de pruebas: requisitos/contratos identificados, versión fijada,
ambiente aislado disponible, casos con esperado, datos controlados y responsable designado.
Si falta responsable o implementación, registrar bloqueo; permite planificar pero no
declarar verificación funcional de ese alcance. Solo los comandos acotados actuales fueron ejecutados.
Salida de la revisión documental: siete fichas reproducibles, trece requisitos trazados,
riesgos/controles con dueño propuesto, fuentes existentes y diff validado; requiere revisión externa.
Salida de una versión funcional: M01=100% por suite aplicable, M02=100% de los requisitos
del alcance aprobado (los trece para flujo completo), M05=100%, M06=100% del catálogo
aplicable incluyendo pruebas asistivas antes no verificables y M07=100%; sin bloqueantes abiertos.
Acciones críticas/altas del alcance cerradas con corrección y verificación independiente;
otros riesgos altos requieren aceptación documentada por autoridad designada. Esa aceptación
no dispensa controles de autorización/tenant ni acredita requisitos faltantes. M04 y M03
se evalúan con contexto, no autorizan liberación por sí solos. **Salida funcional no alcanzada.**

## Gestión propuesta de defectos y acciones

Flujo de proceso: Detectado → Registrado → Clasificado → Asignado → En corrección →
En verificación → Cerrado; si no cumple criterio, Reabierto y vuelta a corrección.
No es un workflow backend implementado. Registro: ID, descripción, versión, evidencia,
severidad (impacto), prioridad (orden), responsable, estado y criterio de cierre.
REV-01–08 y AC-01–08 mantienen identidad de Semana 13: no se crean nuevos hallazgos
por repetirlos aquí ni se cierran al redactar el plan. Corregir mediante PR acotado;
verificador independiente repite escenario, conserva resultados y dictamen; sin evidencia,
asignación o dictamen, permanece pendiente. Reabrir si reaparece en regresión.

## Seguimiento y mejora continua propuestos

Frecuencia: por semana académica/iteración y antes de proponer integración; M01 en cada
cambio relevante. Registrar fecha, versión, universo/denominador, valor, meta, desviación,
rol, evidencia y decisión, sin mezclar versiones o suites. Revisar REV/AC, riesgos y
pendientes; cambios de catálogo requieren justificar comparabilidad y recalcular baseline.
Riesgo Alto sin mitigación: registrar bloqueo en evidencia/PR del módulo y solicitar
asignación/revisión por canal autorizado; no continuar la liberación ni inventar aceptación.
Madelin prepara el informe de sus mediciones; seguimiento operativo/revisión siguen sin asignar.
La revisión externa de este plan y el diseño de casos de Semana 15 son los siguientes pasos.
