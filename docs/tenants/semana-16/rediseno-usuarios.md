# Semana 16 — Rediseño de la pantalla de usuarios del hospital (parte 7)

## Problema

`/admin/users` se veía vacía: el preflight de Tailwind quita bordes, fondos y tamaños a títulos, campos y
botones, y la página no los restauraba (Médicos y Medicamentos sí lo hacen). El buscador y los botones
parecían texto suelto y la tabla no tenía jerarquía.

## Cambios

- Encabezado con el nombre del hospital de la sesión y tres tarjetas de resumen: usuarios con acceso (total
  del servidor), activos y sin roles (de la página visible, porque la lista está paginada).
- Buscador con estilo, botón «Limpiar» y mensajes de éxito o error con el mismo formato que Médicos.
- Cada fila muestra avatar con iniciales, nombre, correo, la marca «Usted» en la cuenta propia, estado en
  español (Activo, Inactivo) y roles como etiquetas con su botón × de 24 px.
- Estado vacío explicado (sin coincidencias o sin personal) y, bajo 720 px, la tabla pasa a tarjetas.
- Mismos tokens de color, foco visible y alturas de 44 px que el resto de la aplicación.

No cambian la API, los permisos (`usuarios.ver`, `rbac.asignar`, `usuarios.desactivar`) ni las reglas: la
autoridad sigue siendo el backend.

## Validación

```bash
node --test "resources/js/modules/tenants/*.test.mjs"   # pass, fail 0 (27 aserciones en hospitalUsers)
npx vite build                                          # HospitalUsersPage-*.js generado
```

Las funciones nuevas (`iniciales`, `estadoDe`, `resumen`) son puras y se prueban sin navegador.
