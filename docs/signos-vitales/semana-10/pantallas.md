# Pantallas responsive — Signos vitales y alertas (Semana 10)

Cinco pantallas en baja fidelidad, dibujadas a 360 px. La barra superior (paciente) y la inferior (acción) son fijas. Cada pantalla indica su variante de escritorio (≥ 768 px), los estados y las reglas de contenido. Datos ficticios.

## M1. Captura, paso 2 de 3

```text
┌────────────────────────────────────┐
│ María Gómez · 45 a · EP-1          │  ← barra de paciente (fija)
├────────────────────────────────────┤
│ Paso 2 de 3 · Glucosa y temperatura│
│                                    │
│ Glucosa                            │
│ [ 5,5               ]  teclado num.│
│ Unidad  [ mg/dL ] [ mmol/L ]       │
│ ⚠ 5,5 es raro en mg/dL: ¿es mmol/L?│
│                                    │
│ Temperatura                        │
│ [                   ]              │
│ Unidad  [  °C  ] [  °F  ]          │
│                                    │
│ ▸ Ayuda: unidades                  │
├────────────────────────────────────┤
│ [ Atrás ]            [ Siguiente ] │  ← barra de acción (fija)
└────────────────────────────────────┘
```

| Aspecto | Decisión |
|---|---|
| **Contenido** | Solo glucosa y temperatura en este paso. Cada signo con su etiqueta visible y su unidad como control segmentado, sin valor por defecto. |
| **Estados** | Vacío (sin valores), con aviso suave de unidad, error de campo, y «Siguiente» siempre habilitado (muestra el error en vez de bloquear). |
| **Validaciones** | Coma o punto decimal; al menos un signo en todo el formulario (se comprueba en el paso 3); unidad elegida si hay valor. |
| **Escritorio** | Los tres pasos se muestran juntos en dos columnas; los controles segmentados pasan a selectores. |

## M2. Confirmación (hoja a pantalla completa)

```text
┌────────────────────────────────────┐
│ ‹ Confirmar registro               │
├────────────────────────────────────┤
│ María Gómez · exp-7734             │
│ Nac. 14/03/1981 · Episodio EP-1    │
│ Medido el 14/10/2026 08:30         │
│                                    │
│ Frec. cardiaca     138 lpm         │
│ Saturación O2       94 %           │
│ Glucosa    5,5 mmol/L → 99,1 mg/dL │
│                                    │
│ Si hay un valor fuera de umbral se │
│ notificará a los médicos tratantes.│
├────────────────────────────────────┤
│ [ Corregir ]            [ Registrar]│
└────────────────────────────────────┘
```

| Aspecto | Decisión |
|---|---|
| **Contenido** | Paciente (nombre, expediente, fecha de nacimiento) antes que los valores; cada valor con su unidad y su conversión. |
| **Estados** | Resumen y en curso («Registrando…», ambos botones deshabilitados). |
| **Reglas** | El foco cae en «Corregir». `Esc` o «Atrás» vuelven a la captura sin perder datos. Se envía una sola vez por pulsación. |
| **Escritorio** | Diálogo centrado de 480 px con los mismos botones. |

## M3. Resultado con alerta

```text
┌────────────────────────────────────┐
│ María Gómez · 45 a · EP-1          │
├────────────────────────────────────┤
│ ■ ALERTA CRÍTICA                   │  ← role="alert"
│ Se notificó a los médicos tratantes│
│                                    │
│ ■ Frec. cardiaca 138 lpm  Crítica  │
│ ▲ Saturación O2   94 %  Advertencia│
│                                    │
│ ▸ Ver los demás signos (1)         │
│ Código ALERT_GENERATED · 3f2a…9c1  │
│ [ Copiar referencia ]              │
├────────────────────────────────────┤
│ [ Ver medición ]  [ Registrar otra]│
└────────────────────────────────────┘
```

| Aspecto | Decisión |
|---|---|
| **Contenido** | Severidad global primero; los signos fuera de umbral por gravedad; los demás, plegados. El código y el `trace_id`, al final. |
| **Estados** | Alerta crítica, advertencia, sin valores fuera de umbral (`✓`) y «no evaluado» (`?`, «revise los valores usted mismo»). Cuatro formas y textos distintos. |
| **Reglas** | La alerta crítica no desaparece sola: queda hasta que se pulsa «Ver medición» o «Registrar otra». El foco va al título. |
| **Escritorio** | Misma jerarquía en un panel de 560 px; los signos plegados se muestran abiertos. |

## M4. Sin respuesta (conexión limitada)

```text
┌────────────────────────────────────┐
│ María Gómez · 45 a · EP-1          │
├────────────────────────────────────┤
│ ⚠ NO SABEMOS SI SE GUARDÓ          │  ← role="alert"
│ No se confirmó que se haya         │
│ notificado a los médicos tratantes.│
│ Si hay un valor crítico, avise     │
│ usted directamente.                │
│ No repita el envío.                │
│                                    │
│ Código: sin respuesta · 8b1…e07    │
├────────────────────────────────────┤
│ [ Revisar mediciones ]             │
└────────────────────────────────────┘
```

| Aspecto | Decisión |
|---|---|
| **Contenido** | Qué se sabe y qué no, qué debe hacer la persona y una sola acción: «Revisar mediciones». |
| **Estados** | Sin respuesta, `500` y `503`. Tras revisar y no encontrar la medición, se habilita «Registrar de nuevo». |
| **Reglas** | Lo escrito se conserva. Nunca se muestra «registrado» ni «sin alertas» sin respuesta. No hay reintento automático. |
| **Escritorio** | Mismo contenido en un panel; el botón junto al texto. |

## M5. Mediciones del episodio (tarjetas)

```text
┌────────────────────────────────────┐
│ María Gómez · 45 a · EP-1          │
├────────────────────────────────────┤
│ Mediciones (2)                     │
│ ┌────────────────────────────────┐ │
│ │ ■ Crítica · 14/10 08:30        │ │
│ │ FC 138 lpm · SpO2 94 %         │ │
│ │ ▸ Ver los demás (1)            │ │
│ └────────────────────────────────┘ │
│ ┌────────────────────────────────┐ │
│ │ ✓ Sin valores fuera de umbral  │ │
│ │ 14/10 06:00                    │ │
│ └────────────────────────────────┘ │
├────────────────────────────────────┤
│ [ Nueva medición ]  (solo médico)  │
└────────────────────────────────────┘
```

| Aspecto | Decisión |
|---|---|
| **Contenido** | Una tarjeta por medición, la más reciente primero; la severidad encabeza la tarjeta. Es la lista del episodio, endpoint propuesto en la [Semana 5](../semana-05/contrato-api.md). |
| **Estados** | Cargando (esqueleto sin valores, `aria-busy`), vacío («Este episodio aún no tiene mediciones registradas»), con datos y `403` («No atiende este episodio»). |
| **Reglas** | Enfermería ve las tarjetas sin «Nueva medición» (ni deshabilitado). La tabla de escritorio tiene las mismas columnas: fecha, severidad, signos fuera de umbral. |
| **Escritorio** | Tabla con encabezados de columna; la fila se abre como la tarjeta. |
