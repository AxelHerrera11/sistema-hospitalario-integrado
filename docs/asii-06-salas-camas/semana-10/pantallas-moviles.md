# Pantallas móviles

Wireframes conceptuales derivados del flujo de Semana 8 y limitados a 320–430
px. No son páginas Vue, no contienen datos reales y no introducen acciones de
edición.

## 1. Listado de wards

**Ancho de referencia:** 320 px.

```text
┌────────────────────────────┐
│ Menú HIS                   │
│ Catálogo de wards y camas  │
│ Consulta la estructura...  │
│                            │
│ UCI-A                      │
│ Unidad de cuidados intens. │
│ Ubicación: Torre A · Nivel 2│
│ Estado: active             │
│ [Ver camas de esta ward]   │
│                            │
│ Pediatría                  │
│ Ubicación: Torre B · Nivel 1│
│ Estado: active             │
│ [Ver camas de esta ward]   │
└────────────────────────────┘
```

- **Datos:** una tarjeta por ward; código, nombre, ubicación y estado del
  contrato. Los valores largos envuelven.
- **Acción:** seleccionar una ward mediante control con nombre claro.
- **Estado:** mientras carga, anunciar “Consultando wards…”; si `data` está
  vacío, informar que no hay wards y ofrecer reintentar.
- **Permiso:** mostrar el contenido solo si el servidor autoriza `camas.ver`.

## 2. Camas de una ward

**Ancho de referencia:** 360 px.

```text
┌────────────────────────────────┐
│ Menú HIS                       │
│ ‹ Volver a wards               │
│ Camas de UCI-A                 │
│ Unidad de cuidados intensivos  │
│                                │
│ Cama UCI-01                    │
│ Tipo: standard                 │
│ Estado: available              │
│                                │
│ Cama UCI-02                    │
│ Tipo: isolation                │
│ Estado: maintenance            │
└────────────────────────────────┘
```

- **Contexto:** mantener visibles nombre y código de la ward seleccionada.
- **Datos:** cada tarjeta muestra `code`, `type` y `status`; no se interpreta
  `occupied` como información de paciente o estancia.
- **Acción:** “Volver a wards” permite cambiar selección y conserva el foco de
  forma predecible.
- **Estado:** la ward existente sin camas tiene mensaje vacío propio; no
  sugerir creación de registros, pues no hay endpoint de alta en alcance.

## 3. Carga, vacío y error recuperable

**Ancho de referencia:** 390 px.

```text
┌──────────────────────────────────┐
│ ‹ Volver a wards                 │
│ Camas de UCI-A                   │
│                                  │
│ Consultando camas…               │
│                                  │
│ / alternativa sin registros:     │
│ Esta ward no tiene camas         │
│ registradas.                     │
│                                  │
│ / alternativa ante 503:          │
│ No se pudo consultar el catálogo.│
│ [Reintentar camas]               │
└──────────────────────────────────┘
```

- Las alternativas son estados mutuamente excluyentes, no contenido simultáneo.
- El anuncio se asocia a la lista de camas; el reintento conserva la ward.
- No exponer mensajes de excepción, SQL, credenciales ni datos técnicos del
  servidor.

## 4. Acceso denegado o ward no disponible

**Ancho de referencia:** 430 px; misma estructura en 320 px.

```text
┌────────────────────────────────────┐
│ Catálogo de wards y camas          │
│                                    │
│ / 403:                             │
│ No tienes permiso para consultar  │
│ este catálogo en el hospital activo│
│ [Volver]                           │
│                                    │
│ / 404 de ward seleccionada:        │
│ La ward ya no está disponible.     │
│ Actualiza la lista para continuar. │
│ [Volver a wards]                   │
└────────────────────────────────────┘
```

- Mensajes visibles y anunciables, nunca indicados solo por color.
- Un `403` no presenta información ni controles que revelen el catálogo.
- Ante `404 WARD_NOT_FOUND`, volver al listado permite solicitar wards
  actualizadas; no conservar camas potencialmente obsoletas.
