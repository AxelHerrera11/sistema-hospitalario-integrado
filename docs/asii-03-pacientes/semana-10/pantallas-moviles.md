# Pantallas móviles

Diseños conceptuales para 320–430 px, derivados de Semana 8. No son vistas implementadas ni contienen datos reales.

## 1. Búsqueda

**Ancho:** 320 px (misma estructura hasta 430 px). **Objetivo:** consulta exacta.

```text
┌────────────────────────────┐
│ ‹  Pacientes               │
│ Buscar por código          │
│ Código del paciente        │
│ [________________________] │
│ Ingresa el código completo.│
│ [     Buscar paciente     ]│
│                            │
│ Estado de búsqueda aquí    │
└────────────────────────────┘
```

- **Contenido/acciones:** etiqueta, ayuda, campo y botón a ancho completo.
- **Permiso:** `pacientes.ver`; ofrecer alta solo con `pacientes.registrar`.
- **Responsive:** una columna; teclado no tapa botón/estado.
- **Estados:** inicial, carga anunciada, error temporal con código conservado o vacío.
- **Accesibilidad/privacidad:** etiqueta asociada, foco visible y teclado; sin sugerencias de nombres ni código en errores.

## 2. Detalle encontrado

**Ancho:** 360 px. **Objetivo:** confirmar resultado con datos mínimos.

```text
┌────────────────────────────────┐
│ ‹  Resultado de búsqueda       │
│ Paciente encontrado            │
│ Nombre                         │
│ [dato autorizado]              │
│ Fecha de nacimiento            │
│ [dato autorizado]              │
│ Estado                         │
│ [dato autorizado]              │
│ [Editar datos]                 │
│ [Nueva búsqueda]               │
└────────────────────────────────┘
```

- **Contenido/acciones:** pares etiqueta/valor; contacto solo si hace falta. Omitir `patient_id`/`global_id`.
- **Permiso:** `pacientes.ver`; editar requiere `pacientes.editar`.
- **Responsive:** envolver valores largos, apilar acciones; sin tabla ni scroll lateral.
- **Estados:** carga contextual, error con retorno/reintento, solo lectura sin permiso de edición.
- **Accesibilidad/privacidad:** orden lógico, nombres descriptivos y sin datos en notificaciones.

## 3. Registro

**Ancho:** 320 px. **Objetivo:** alta sin pérdida evitable.

```text
┌────────────────────────────┐
│ ‹  Registrar paciente      │
│ Código *                   │
│ [________________________] │
│ Global ID *                │
│ [________________________] │
│ Formato/procedencia        │
│ Nombres *                  │
│ [________________________] │
│ Apellidos *                │
│ [________________________] │
│ Nacimiento *               │
│ [AAAA-MM-DD______________] │
│ Sexo *                     │
│ [Seleccionar_____________] │
│ Contacto                   │
│ [________________________] │
│ Estado                     │
│ [Activo__________________] │
│ [    Guardar paciente    ] │
│ [       Cancelar         ] │
└────────────────────────────┘
```

- **Contenido/acciones:** campos etiquetados en una columna; ayuda de formato/procedencia para `global_id`; botones apilados.
- **Permiso:** `pacientes.registrar`; no requiere `pacientes.ver`.
- **Responsive:** scroll vertical; teclado no tapa control/error; no plegar campos requeridos.
- **Estados:** validar junto al campo; anunciar/bloquear envío duplicado; conservar valores ante conflicto o error temporal.
- **Accesibilidad/privacidad:** requeridos en texto, errores asociados, no persistir datos localmente.

## 4. Edición

**Ancho:** 390 px. **Objetivo:** cambiar solo campos permitidos.

```text
┌──────────────────────────────────┐
│ ‹  Editar paciente               │
│ Código (solo lectura)            │
│ [valor autorizado]               │
│ Nombres *                        │
│ [______________________________] │
│ Apellidos *                      │
│ [______________________________] │
│ Nacimiento *                     │
│ [AAAA-MM-DD____________________] │
│ Sexo *  [Seleccionar___________] │
│ Contacto [_____________________] │
│ Estado   [Seleccionar__________] │
│ [       Guardar cambios        ] │
│ [            Cancelar          ] │
└──────────────────────────────────┘
```

- **Contenido/acciones:** una columna; código de solo lectura; omitir `patient_id`/`global_id`.
- **Permiso:** `pacientes.ver` para cargar; `pacientes.editar` para guardar. Servidor reautoriza.
- **Responsive:** campos/errores al ancho disponible; evitar ocultarlos con teclado; confirmar descarte si hay cambios.
- **Estados:** conservar cambios ante error recuperable.
- **Accesibilidad/privacidad:** errores accionables, foco restaurado y datos mínimos.

## 5. Sin resultados / acceso restringido

**Ancho:** 430 px (igual en 320 px). **Objetivo:** comunicar resultado/restricción sin exponer datos.

```text
┌────────────────────────────────────┐
│ ‹  Pacientes                       │
│ No encontramos un paciente con ese │
│ código.                            │
│ [       Revisar código           ] │
│ [       Registrar paciente       ] │
│                                    │
│ Sin permiso:                       │
│ No tienes permiso para esta acción.│
│ [        Volver                   ]│
└────────────────────────────────────┘
```

- **Contenido/acciones:** estados alternativos; no-resultados permite corregir y, si autorizado, registrar. Restringido no muestra datos.
- **Permiso:** `pacientes.ver` para buscar, `pacientes.registrar` para alta; no asumir permisos no verificados.
- **Responsive:** texto envuelve y acciones ocupan ancho completo a 320 px; sin truncar contenido esencial.
- **Estados:** diferenciar vacío, error recuperable y denegación.
- **Accesibilidad/privacidad:** mensaje anunciado, no solo color; sin código, nombre o detalle RBAC.
