# Siete métricas SQA y línea base

Medición documental/técnica del 29/10/2026; versiones y comandos exactos en [README](./README.md).
Metas/frecuencias son **propuestas del Plan SQA**, no estándares obligatorios ni acuerdos aprobados.
Porcentajes redondeados a dos decimales; denominador cero o evidencia insuficiente:
**Sin línea base verificable**, nunca convertir desconocido en éxito o cero fallos.
Madelin Cerón elabora estas mediciones/documentos. Responsables futuros T=pruebas,
B=backend, F=frontend, S=seguridad; todos **Pendiente de asignación**. Verificación
independiente de mediciones/cierres: revisor técnico, **Pendiente de asignación**.

## M01 — Porcentaje de pruebas aprobadas

- Objetivo/unidad/fórmula: detectar regresión; % = aprobadas / ejecutadas × 100.
- Fuente/evidencia: tests/run.php en micro-his/qa-regresion y verificador de #1492; ejecución nueva: PHP 17/17=100%, HTML 17/17=100%, cero fallidas en cada conjunto.
- Frecuencia/responsable: por cambio relevante y preintegración; T/B para PHP, T/F para HTML.
- Meta propuesta: 100% por suite, sin omitir pruebas fallidas. Desviación: bloquear alcance afectado, registrar defecto, corregir/repetir y verificar.
- Límite: dos naturalezas distintas; no sumar como 34 pruebas de cobertura integral; ningún porcentaje de código derivable.

## M02 — Cobertura de requisitos verificables

- Objetivo/unidad/fórmula: evitar cobertura aparente; % = requisitos con verificación completa en alcance backend / requisitos aplicables × 100.
- Fuente/evidencia: trece criterios reales de Semana 2, matriz de Semana 12 y PHP actual. RF-01 verificado en CLI durante proceso; RF-02–08 y RNF-01–05 pendientes en backend: 1/13=7.69%.
- Frecuencia/responsable: cada iteración e incremento de alcance; T/B. Meta propuesta: 100% para los trece antes de liberar flujo completo.
- Desviación: priorizar REV-03 y pruebas de aceptación faltantes; no excluir requisitos pendientes para mejorar cifra.
- Límite: 13/13 trazados documentalmente no son 13/13 verificados; HTML parcial no incrementa numerador; RF-01 no acredita durabilidad ni API.

## M03 — Densidad de hallazgos por criterio revisado

- Objetivo/unidad/fórmula: orientar inspección; hallazgos/criterio = hallazgos identificados / criterios evaluados.
- Fuente/evidencia documental: #1512 checklist de 15 RT y 8 REV distintos: 8/15=0.5333 hallazgos/criterio; no es una nueva revisión ni defectos/KLOC.
- Frecuencia/responsable: cada revisión técnica comparable; T y revisor por asignar. Meta propuesta orientativa: ≤0.5333 en revisiones posteriores equivalentes, subordinada al riesgo/severidad.
- Desviación: analizar causas, ampliar revisión y priorizar correcciones; nunca ocultar hallazgos, reducir catálogo o tratar más detección como peor desempeño personal.
- Límite: un hallazgo puede corresponder a varios RT; se cuenta una vez. No mide cierres; comparar misma versión/catálogo o declarar ruptura de serie.

## M04 — Porcentaje de acciones correctivas cerradas

- Objetivo/unidad/fórmula: medir mejora verificada; % = acciones cerradas con corrección y verificación / acciones registradas × 100.
- Fuente/evidencia documental: AC-01–08 de #1512, todas Pendiente, sin cierre acreditado en el material revisado: 0/8=0%.
- Frecuencia/responsable: semanal y antes de integración; T/correctores designados y revisor independiente.
- Meta propuesta: 100% de acciones del alcance a liberar; priorizar AC-01/02/03. Desviación: mantener abiertos REV y bloqueo pertinente; exigir diff, reejecución y dictamen.
- Límite: solo línea base de ese plan fijado, no una consulta a tablero operativo inexistente; aprobar documentación no cierra AC.

## M05 — Cumplimiento de controles de seguridad

- Objetivo/unidad/fórmula: no exponer sin controles; % = controles aplicables completamente verificados / controles aplicables evaluados × 100.
- Fuente/evidencia: inspección de Controller, PDO, esquema/demo y tests actuales; catálogo S1–S5 abajo: 1/5=20%; Parcial no suma como conforme.
- Frecuencia/responsable: iteración y cada cambio de frontera/datos; S/B/T. Meta propuesta: 100% antes de exposición compartida.
- Desviación: bloquear exposición, priorizar REV-01/02/06/07 y pruebas de seguridad aisladas.
- Límite: evalúa cinco controles del plan para futura integración; no es auditoría de todo el HIS ni tasa de vulnerabilidades; SQL parametrizado por sí solo no demuestra protección integral.

| Control evaluado | Estado / evidencia concreta |
|---|---|
| S1 Validación de entradas CLI | Conforme: Controller comprueba tipo/campos, dominio no vacío, suite/demos pasan; límites HTTP no cubiertos |
| S2 Autorización | No conforme para integración: no identidad/permisos en núcleo; REV-02 |
| S3 Protección de datos/evidencias | Parcial: PDO parametrizado, pero sin almacenamiento privado, control de acceso/retención demostrados; REV-02/07 |
| S4 Aislamiento tenant | No conforme para integración: esquema sin ámbito y find(id) sin contexto; REV-01 |
| S5 Errores seguros de extremo a extremo | Parcial: Controller normaliza 400/422/503 y tests de fallo; arranque PDO/esquema fuera de try en demo; REV-06 |

## M06 — Cumplimiento de criterios UX/accesibilidad

- Objetivo/unidad/fórmula: detectar barreras; % = criterios Conformes / criterios efectivamente evaluados × 100.
- Fuente/evidencia: nueva ejecución S11 sobre catálogo local U1–U7 abajo: 4 Conforme, 1 Parcial, 0 No conforme, 2 No verificable; 4/(4+1+0)=80%.
- Frecuencia/responsable: cada cambio de interacción y revisión semanal; F/T. Meta propuesta: 100% del catálogo aplicable, completando además U6/U7 antes de salida funcional.
- Desviación: mantener REV-08/AC-08, diseñar pruebas pendientes y corregir barreras con evidencia.
- Límite: los dos No verificable se excluyen del denominador observado y se publican, no se consideran aprobados. No comparable con los 26 criterios de Semana 9 ni certificación WCAG.

| Criterio local | Estado y evidencia de la sesión |
|---|---|
| U1 Navegación y destinos internos | Conforme: seis vistas/una visible; cero destinos rotos en inspección DOM del script |
| U2 Etiquetas de formularios | Conforme: cero controles sin label; alcance estructura, no nombres accesibles completos |
| U3 Errores y foco de recuperación | Conforme: envíos vacíos de caso/ejecución/defecto enfocan resumen; no prueba anuncios por lector |
| U4 Reflow del panel | Conforme: scrollWidth ≤ viewport en 1280/390/320 CSS px; no zoom real |
| U5 Operación completa por teclado | Parcial: Tab/Enter de navegación y outline medidos; resto de acciones usa automatización DOM |
| U6 Lector/anuncios dinámicos | No verificable: no se ejecutó tecnología asistiva |
| U7 Zoom real y tareas con participantes | No verificable: sesión headless sin estudio humano ni zoom real |

## M07 — Porcentaje de trazabilidad de pruebas

- Objetivo/unidad/fórmula: relacionar evidencia con aceptación; % = casos/comprobaciones con vínculo directo explícito a RF/RNF / casos/comprobaciones revisados × 100, por conjunto.
- Fuente/evidencia: lectura de los 17 tests nombrados PHP y 17 check del script HTML, contrastados con Semana 2; mapa conservador nuevo abajo: PHP 8/17=47.06%; HTML 8/17=47.06%.
- Frecuencia/responsable: cada alta/cambio de casos y semanal; T/B/F. Meta propuesta: 100% de casos con RF/RNF identificable; auxiliares deben documentar vínculo sustentado sin inventar requisitos.
- Desviación: completar mapa o separar controles técnicos justificados en catálogo futuro, conservando denominador original para comparar; no reclasificar para inflar cifra.
- Límite: trazabilidad directa definida aquí no equivale a cumplimiento del requisito. Casos manuales de una campaña productiva: Sin línea base verificable; no existe catálogo real persistente.

| Universo revisado | Vínculos directos contados una sola vez | Resto no contado como vínculo directo RF/RNF en esta medición |
|---|---|---|
| PHP: 17 invocaciones nombradas de tests/run.php | Camino feliz/lectura por cada adaptador (2) y vacíos purpose/steps/expected por adaptador (6) → RF-01, total 8 | Formato inválido (2), inexistente (2), duplicado (2), fallo seguro doble/PDO (2), fallo consulta PDO (1): 9 controles técnicos de contrato/error; no se les atribuye RNF-04 de ejecuciones |
| HTML: 17 check del verificador | Abrir caso asociado→RF-03; error de caso→RF-01; alta/asociación→RF-01/02; ejecución incompleta→RF-03/RNF-02 parcial; evidencia→RF-05; fallido→RF-04; defecto ligado→RF-06; aprobado bloquea defecto→RF-06: 8 | Estructura, teclado, búsqueda, defecto incompleto, cancelar, contadores, reflow, recarga y excepciones: 9 auxiliares sin correspondencia directa al criterio completo; pueden apoyar objetivos UX/proceso |

M07 mide existencia de vínculos directos explícitos con esa regla conservadora, no
afirma que los nueve auxiliares sean irrelevantes o carezcan de todo vínculo indirecto.
Datos pendientes: cobertura de código, eficacia con personas/asistencia, seguridad,
tenant, API y persistencia integrada. Metas y frecuencias no son mediciones realizadas.
