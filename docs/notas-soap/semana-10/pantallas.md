# Pantallas móviles — Notas SOAP y diagnósticos (Semana 10)

Cinco pantallas en baja fidelidad a 360 px (tramo S). Cada una anota **jerarquía**, **estados**, **acciones** y **reglas**. Los textos y códigos vienen de la [Semana 8](../semana-08/wireframes.md); las correcciones, del [backlog de la Semana 9](../semana-09/backlog.md). Reglas de breakpoint: [README](./README.md).

## M1. Lectura de la nota

**Rol:** médico tratante o enfermería con `notas_soap.ver` y asignación activa.

```text
┌──────────────────────────────┐
│ ←  Nota SOAP · EP-1        ⋮ │
│ María Gómez · exp-7734       │
│ Nac. 14/03/1987              │
├──────────────────────────────┤
│ [● Borrador]  Autoría:       │
│ López, A. · Médico tratante  │
│ Versión 1                    │
│                              │
│ ▾ Subjetivo                  │
│   Tos de 3 días, sin fiebre  │
│ ▸ Objetivo                   │
│ ▸ Análisis                   │
│ ▸ Plan                       │
│                              │
│ Diagnósticos (2)             │
│ ┌──────────────────────────┐ │
│ │ J18.9      [Principal]   │ │
│ │ Neumonía, no especificada│ │
│ └──────────────────────────┘ │
│ ┌──────────────────────────┐ │
│ │ E11.9      [Secundario]  │ │
│ │ Diabetes mellitus tipo 2 │ │
│ └──────────────────────────┘ │
├──────────────────────────────┤
│ [ Continuar editando ]       │
└──────────────────────────────┘
```

| Anotación | Detalle |
|---|---|
| **Jerarquía** | Paciente y fecha de nacimiento (fijos) → estado y autoría → secciones → diagnósticos. La barra superior se queda al desplazar |
| **Estados** | Carga (esqueleto sin texto clínico), con nota, vacío («Este episodio aún no tiene nota SOAP»), sin asignación (`NOT_TREATING_PHYSICIAN`), sin permiso (`FORBIDDEN`) |
| **Acciones** | «Continuar editando» solo si es borrador **y** el actor es el autor. Para el resto, la barra inferior no existe: no hay botón deshabilitado |
| **Reglas** | Solo la primera sección va abierta; las demás, plegadas, con el nombre como `<button aria-expanded>`. La autoría es nombre y rol, sin género supuesto (B-12). El estado lleva texto, no solo color (B-10). El menú `⋮` contiene «Versiones» |

## M2. Editor por pasos

**Rol:** médico tratante con `notas_soap.crear`.

```text
┌──────────────────────────────┐
│ ←  Nueva nota · EP-1       ⋮ │
│ María Gómez · exp-7734       │
├──────────────────────────────┤
│ Paso 2 de 4                  │
│ ━━━━━━╸─────                 │
│                              │
│ Objetivo (obligatorio)       │
│ Hallazgos del examen         │
│ ┌──────────────────────────┐ │
│ │ T 38.4 °C, FR 22/min     │ │
│ │                          │ │
│ └──────────────────────────┘ │
│ 25 de 4000 caracteres        │
├──────────────────────────────┤
│ [ Atrás ]      [ Siguiente ] │
└──────────────────────────────┘
```

| Anotación | Detalle |
|---|---|
| **Jerarquía** | Un campo, una etiqueta, una pista y un contador. Los pasos 1–4 son S, O, A y P; el paso 5 es el resumen y lo envía M3 |
| **Estados** | Vacío, con texto, cerca del límite (desde 3600: «Quedan 400 caracteres», texto en `#B45309` y símbolo, B-10), error de validación (junto al campo y en un resumen) |
| **Acciones** | «Siguiente» siempre habilitado: si el campo está vacío, muestra «Escriba el objetivo (obligatorio)» y deja el foco en él (B-06). «Atrás» conserva el texto |
| **Reglas** | El campo crece con el texto, sin desplazamiento interno. Aviso al salir con texto sin enviar (B-04). Banner de conexión fijo bajo la cabecera. Sin guardado local de texto clínico |

## M3. Confirmación de registro

**Rol:** médico tratante, al final del paso 5.

```text
┌──────────────────────────────┐
│ ▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒ │
├──────────────────────────────┤
│ Registrar nota como borrador │
│                              │
│ María Gómez                  │
│ exp-7734 · Nac. 14/03/1987   │
│ Episodio EP-1                │
│                              │
│ ▸ Ver las cuatro secciones   │
│ Sin diagnósticos todavía.    │
│                              │
│ [ Volver a editar ]          │
│ [ Registrar nota ]           │
└──────────────────────────────┘
```

| Anotación | Detalle |
|---|---|
| **Jerarquía** | Paciente completo primero (B-01), luego el episodio y el estado «borrador», luego el resumen plegado |
| **Estados** | Confirmación, enviando («Registrando…», con aviso a los 8 s), éxito («Nota registrada (borrador)» + `trace_id`), sin respuesta («No sabemos si se guardó», botón «Comprobar») |
| **Acciones** | Dos botones a todo el ancho, de 48 px de alto; «Registrar nota» es el principal. Foco inicial en «Volver a editar» |
| **Reglas** | Hoja inferior modal (`role="dialog"`, foco atrapado, `Esc` cierra, B-03). El `422` devuelve al paso con el error; el `409 NOTE_SIGNED` dice «La nota fue firmada mientras la editaba» |

## M4. Buscar y asociar un diagnóstico

**Rol:** médico tratante, autor de la nota en borrador, con `notas_soap.asociar_diagnostico`.

```text
┌──────────────────────────────┐
│ ←  Diagnóstico · EP-1        │
│ María Gómez · exp-7734       │
├──────────────────────────────┤
│ Código o descripción         │
│ ┌────────────────────┐ ┌───┐ │
│ │ J18                │ │ 🔍│ │
│ └────────────────────┘ └───┘ │
│ 3 resultados                 │
│ ┌──────────────────────────┐ │
│ │ J18.9                    │ │
│ │ Neumonía, no especificada│ │
│ └──────────────────────────┘ │
│ ┌──────────────────────────┐ │
│ │ J44.1                    │ │
│ │ EPOC con exacerbación    │ │
│ └──────────────────────────┘ │
└──────────────────────────────┘
Hoja de tipo, al tocar J18.9:
┌──────────────────────────────┐
│ J18.9 · Neumonía             │
│ Tipo de diagnóstico          │
│ ( ) Principal — ya hay uno:  │
│     E11.9                    │
│ (•) Secundario               │
│ ( ) Diferencial              │
│ [ Cancelar ] [ Asociar ]     │
└──────────────────────────────┘
```

| Anotación | Detalle |
|---|---|
| **Jerarquía** | Búsqueda → resultados como tarjetas (código en negrita, descripción debajo) → tipo en una hoja |
| **Estados** | Buscando, «3 resultados» (anunciado con `role="status"`), «Ningún código coincide», catálogo caído (`ICD10_UNAVAILABLE`, búsqueda bloqueada), asociando, duplicado (`DIAGNOSIS_DUPLICATED`), éxito |
| **Acciones** | Tocar una tarjeta abre la hoja. Un único grupo de radios en español (B-05); «Principal» deshabilitado con su razón si ya existe uno (B-09) |
| **Reglas** | Cada tarjeta es un solo control de 64 px de alto. La descripción viene del catálogo. Sin entrada manual de código. Esta pantalla solo existe tras registrar la nota (B-02) |

## M5. Versiones y estado de firma

**Rol:** cualquier rol con `notas_soap.ver`; el botón de corrección, solo con `notas_soap.corregir` (propuesto).

```text
┌──────────────────────────────┐
│ ←  Versiones · EP-1          │
│ María Gómez · exp-7734       │
├──────────────────────────────┤
│ ┌──────────────────────────┐ │
│ │ [✔ Firmada] Versión 2    │ │
│ │ VIGENTE · 15/10/2026     │ │
│ │ Motivo: error en la dosis│ │
│ │ Autoría: López, A.       │ │
│ └──────────────────────────┘ │
│ ┌──────────────────────────┐ │
│ │ [✔ Firmada] Versión 1    │ │
│ │ Reemplazada por la v2    │ │
│ │ 12/10/2026               │ │
│ └──────────────────────────┘ │
├──────────────────────────────┤
│ [ Crear versión corregida ]  │
└──────────────────────────────┘
```

| Anotación | Detalle |
|---|---|
| **Jerarquía** | Versión vigente primero y destacada; las anteriores, atenuadas pero legibles (≥ 4.5:1) |
| **Estados** | Una versión (sin historial), varias, cargando, sin permiso de corrección (barra inferior ausente) |
| **Acciones** | Tocar una versión abre su lectura (M1) con la cinta «Versión 1 · reemplazada por la versión 2» y un enlace a la vigente (B-08). «Crear versión corregida» pide el motivo, obligatorio |
| **Reglas** | La lista es una línea de tiempo, no una tabla. La versión firmada no ofrece editar. El motivo y la autoría salen del servidor, nunca de texto libre del cliente |
