# Semana 10 — Diseño para movilidad (responsive)

**Módulo:** ASII-02 — RBAC: roles, permisos y protección de rutas  
**Estudiante:** LÓPEZ FAJARDO, MARÍA DE LOS ÁNGELES · @Mar-03

## Objetivo

Adaptar el flujo «asignación de rol, autorización de una operación y denegación trazable» a móvil (320–430 px): jerarquizar datos y acciones de roles, permisos, rutas protegidas, decisiones de autorización y denegaciones auditables, reducir carga cognitiva y definir navegación, tablas/formularios, confirmaciones y recuperación ante conexión limitada.

## Jerarquía en móvil

Prioridad descendente de lo que se muestra primero en pantalla pequeña:

1. La decisión y su estado (Permitido / Denegado) con `trace_id` cuando aplica.
2. La acción primaria (Asignar / Solicitar autorización), fija y alcanzable con el pulgar.
3. Los datos del contexto (usuario, rol, hospital), apilados y truncables.
4. Lo secundario (filtros, ayuda, historial), detrás de un acordeón o pantalla aparte.

## Pantallas responsive (4)

### 1. Asignar rol (móvil, campos apilados)
```text
≡  Asignar rol            H1 ▾
---------------------------------
Usuario
[ buscar...                    ]
Rol
[ Enfermeria                  ▾ ]
Permiso requerido: rbac.roles.asignar
---------------------------------
(sticky)        [ Validar ][ Asignar ]
```

### 2. Resultado de autorización
```text
←  Autorizar operacion
---------------------------------
Accion: prescripciones.crear
Usuario: med1   Hospital: H1
[ Solicitar autorizacion ]
---------------------------------
⛔ DENEGADO · permiso_faltante
trace_id: a1b2c3d4e5f6   [Copiar]
[ Ver auditoria ]
```

### 3. Bitácora de denegaciones (tabla → tarjetas)
```text
←  Denegaciones            Filtro ▾
---------------------------------
- med1 · prescripciones.crear
  403 · 22:14 · trace a1b2c3
- enf1 · muestras.etiquetar
  403 · 22:10 · trace 9f8e7d
---------------------------------
[ Cargar mas ]
```

### 4. Confirmación (hoja inferior / bottom sheet)
```text
---------------------------------
Confirmar asignacion
Usuario med1 · Rol SuperAdmin · H1
Habra trazabilidad de esta accion.
[ Cancelar ]        [ Confirmar ]
---------------------------------
```

## Reglas de interacción móvil

- Objetivos táctiles ≥ 44×44 px; acción primaria fija abajo (sticky), alcanzable con el pulgar.
- Las tablas se convierten en tarjetas apiladas; nunca scroll horizontal de datos críticos.
- El motivo de la denegación y el `trace_id` siempre visibles sin scroll; datos no esenciales se truncan con «ver más».
- Confirmaciones destructivas o sensibles como hoja inferior, no como diálogo pequeño.
- Un solo foco de acción por pantalla para reducir carga cognitiva.

## Evidencia

- Reglas de breakpoint: `breakpoints.md`.
- Escenarios móviles con decisiones de contenido y error: `escenarios-moviles.md`.
- Navegación móvil: `fuentes/01-navegacion-movil.mmd`.
- Issue relacionado: `Refs #4`. PR con más de 3 commits y menos de 400 líneas.
