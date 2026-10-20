# Semana 8 — Diseño de experiencia de usuario

## Portada

- **Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`)
- **Módulo:** Verificación de Base de Datos PostgreSQL
- **Semana:** Semana 8 — Errores de integridad vistos por el usuario
- **Entregable / evidencia:** Catálogo violación de restricción → mensaje para el usuario → código HTTP.
- **Estructura:** Sistema Hospitalario Integrado 2026 | Semanas 1–15.

## 1. Alcance

Definir cómo se presentan al usuario los errores que produce la base de datos. La
traducción se apoya en los **SQLSTATE medidos** en la auditoría de la semana 7 y
ejecutados de nuevo en esta semana, para que el catálogo no dependa de suposiciones.

No es un rediseño de pantallas: es el contrato de error que los módulos deben
aplicar para que el usuario lea un mensaje útil y el sistema no exponga detalles
internos.

## 2. Entregable

[catálogo-violaciones.md](./catalogo-violaciones.md) contiene:

- los cuatro SQLSTATE reproducidos en ejecución (`23502`, `23503`, `23505`, `23514`) y
  el `23P01` de solapamiento cubierto por las pruebas del repositorio;
- 21 filas de catálogo con SQLSTATE, código de negocio, código HTTP y mensaje en
  español;
- la tabla de códigos HTTP derivados (401, 403, 404, 409, 422, 503);
- la regla de qué se registra en el log y qué nunca sale del servidor.

## 3. Decisiones de diseño

1. **El mensaje dice qué hacer, no qué falló internamente.** "La cama ya está ocupada
   en ese momento. Elegí otra cama o liberá la existente" es accionable;
   `duplicate key value violates unique constraint` no lo es.
2. **El detalle técnico queda en el log.** SQLSTATE, restricción y `request_id` se
   registran con el código de negocio; nunca el contenido clínico.
3. **Conflicto y validación se distinguen.** Un dato inválido es `422`; un choque con
   el estado actual del dato es `409`. El usuario puede corregir un `422`, pero un
   `409` exige resolver el conflicto primero.
4. **Ausencia de datos no es error.** Un hospital sin camas no devuelve `404`: devuelve
   una lista vacía. Solo hay `404` cuando el recurso no existe en el hospital del
   usuario.
5. **Falla segura.** Si la base no responde, la respuesta es `503` con mensaje de
   indisponibilidad; nunca ceros ni listas vacías.

## 4. Validación ejecutada

```bash
# comando: bloque DO con inserciones que violan NOT NULL, FK, UNIQUE y CHECK sobre beds
# resultado: SQLSTATE 23502, 23503, 23505 y 23514 con el mensaje interno de PostgreSQL

# comando: SELECT conname, pg_get_constraintdef(oid) FROM pg_constraint WHERE conrelid='beds'::regclass
# resultado: beds_ward_code_unique, beds_status_check y beds_ward_id_fkey con ON DELETE RESTRICT

# comando: podman run --rm --network host -v $PWD:/app -w /app localhost/php85-pgsql-full php vendor/bin/phpunit tests/Database/Hospital/SchedulingAndBedIntegrityTest.php
# resultado: OK — el rechazo de solapamiento de estancias (23P01) esta cubierto por el repositorio
```

## 5. Riesgos y pendientes

- Los códigos de negocio propuestos necesitan el acuerdo del formato común de
  respuestas y errores, que sigue pendiente entre los módulos.
- No hay todavía un middleware de errores compartido: hasta que exista, cada módulo
  repetirá esta tabla y puede haber divergencias.
- El slice H8 (auditoría local y outbox) no existe, así que los errores de sus
  triggers todavía no tienen traducción.
