# Signos vitales y alertas por valores anormales — Semana 12: segunda evaluación parcial

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-13 — Signos vitales y alertas por valores anormales · **Entrega:** presentación breve, recorrido navegable, matriz decisión → RF/RNF → evidencia y respuesta a un cambio solicitado.

## Ficha de la asignación

- **Semana:** Semana 12 — Segunda evaluación parcial
- **Tarea individual:** Defienda las decisiones de componentes, UX, accesibilidad y movilidad de Signos vitales y alertas por valores anormales. Demuestre con el prototipo cómo «captura de signos vitales, evaluación de umbrales y generación de alerta» trata roles, estados, errores y riesgos asociados con signos vitales, unidades, umbrales, valores anormales y alertas clínicas.
- **Entregable / evidencia:** presentación breve, recorrido navegable, matriz decisión→RF/RNF→evidencia y respuesta documentada a un cambio solicitado.
- **Issue:** Refs #23
- **Alcance:** defensa del diseño. El prototipo es el de la [Semana 11](../semana-11/prototipo.html), con **un cambio**: la unidad recordada de la [respuesta al cambio solicitado](./cambio-solicitado.md). No toca el HIS ni el micro-HIS.

## Tesis

**En un flujo que decide si hay una alerta, el diseño de la pantalla es parte de la regla clínica**: una unidad mal elegida inventa una alerta crítica, una coma mal leída cambia la severidad y una conexión caída puede dejar a un paciente crítico sin aviso. Por eso cada decisión de componentes, UX, accesibilidad y movilidad protege una de tres garantías: *ninguna alerta se inventa*, *ninguna se pierde sin que la persona lo sepa* y *ningún dato clínico sale de quien tiene derecho a verlo*.

## Recorrido navegable

Abrir [`../semana-11/prototipo.html`](../semana-11/prototipo.html) en un navegador (sin servidor). Cada parada indica qué demuestra. Para el móvil, ventana de 390 px o modo dispositivo.

| # | Parada | Cómo llegar | Qué demuestra |
|---|---|---|---|
| 1 | Médico: mediciones | `prototipo.html` | Rol médico: «Nueva medición». Tabla en desktop, tarjetas en móvil |
| 2 | Unidades | «Nueva medición» → Siguiente → escribir `5,5` en glucosa sin elegir unidad → Siguiente | **Riesgo unidades:** «Elija la unidad.» No hay valor por defecto |
| 3 | Aviso suave | Elegir `mg/dL` con `5,5` | **Riesgo valores:** «5,5 es raro en mg/dL: ¿es mmol/L?» |
| 4 | Coma decimal | Escribir `1.234,5` en FC y Siguiente; luego `138,5` | **Riesgo valores:** rechazo de lo ambiguo y aceptación de la coma |
| 5 | Confirmación | `?demo=glu#/confirmar` | Paciente completo y conversión «5,5 mmol/L → 99,1 mg/dL» |
| 6 | Alerta | Registrar | **Umbrales y alertas:** «ALERTA CRÍTICA», FC crítica, SpO2 advertencia, `role="alert"` |
| 7 | Estados: no evaluado | `?edad=9&demo=glu#/resultado` | **Estado:** «NO EVALUADO», distinto de «sin valores fuera de umbral» |
| 8 | Error crítico | Marcar «Simular error crítico» → Registrar | **Error:** «No sabemos si se guardó»; sin reintento automático |
| 9 | Recuperación | «Revisar mediciones» → «Registrar de nuevo» | La verificación antes de repetir |
| 10 | Rol enfermería | `?rol=enfermeria#/episodio` | **Rol:** solo lectura, sin controles ni deshabilitados |
| 11 | Cambio solicitado | `?ultima#/episodio` → «Nueva medición» → paso 2 | Unidad recordada, marcada «la última que usó: confírmela» |

## Archivos

- [Presentación breve](./presentacion.md): seis diapositivas con el guion y las preguntas preparadas.
- [Matriz decisión → RF/RNF → evidencia](./matriz.md): 14 decisiones ligadas a su requisito y a una prueba repetible.
- [Respuesta al cambio solicitado](./cambio-solicitado.md): decisión, impacto por área y criterios verificables.
- [Declaración de uso de IA](./DECLARACION_IA.md)
- Base: [Semana 5](../semana-05/contrato-api.md), [7](../semana-07/componentes.md), [8](../semana-08/README.md), [9](../semana-09/backlog.md), [10](../semana-10/README.md) y [11](../semana-11/README.md).

## Lo implementado y lo propuesto

- **En el prototipo:** unidad sin valor por defecto, aviso suave, coma o punto, confirmación con paciente, los cuatro resultados, error sin respuesta con revisión, rol enfermería y la unidad recordada de esta semana.
- **Solo diseño:** corrección de mediciones, lista del episodio por la API, `Idempotency-Key`, borrador en servidor, fecha con selector nativo (B-09) y aviso de sesión que vence (B-08).

## Cómo verificar

```bash
php micro-his/signos-vitales/tests/run.php
php micro-his/signos-vitales/tests/api.php
php micro-his/signos-vitales/tests/client.php
```

Las tres terminan con código de salida 0 (PHP 8.3.33). El comportamiento del prototipo se comprobó en Chrome (390 × 844) con un guion temporal que no se incluye: 19 comprobaciones, las 16 de la Semana 11 y 3 de la unidad recordada (primera captura sin unidad, segunda con `mmol/L` sugerida y la sugerencia que desaparece al cambiar).

## Límites

- El prototipo no llama al servidor: el evaluador replica las bandas de `adulto-2026.1`, que son ilustrativas y no están validadas clínicamente.
- No se evaluó con usuarios clínicos, lector de pantalla ni axe.
- La unidad recordada es una decisión de diseño sin validar con el servicio de urgencias.
