# Catálogo de violaciones de integridad y su traducción (Semana 8)

Traducción entre la violación que produce la base de datos y lo que ve el usuario.
Los SQLSTATE se **ejecutaron** sobre PostgreSQL 16 para obtener el código real; el
mensaje que PostgreSQL imprime no se muestra al usuario final.

## 1. Principio de la traducción

1. La base de datos lanza un **SQLSTATE**; nunca debe viajar hasta el cliente.
2. La capa de aplicación (servicio o middleware de errores) convierte el SQLSTATE en un
   **código de negocio** estable, con el formato `ENTIDAD_MOTIVO` en mayúsculas.
3. El código de negocio se traduce a un **código HTTP** según la tabla 2.
4. El mensaje visible al usuario está en español, describe qué hacer y **no** incluye
   nombres de tabla, nombres de restricción ni texto de la excepción.
5. En el log se registra el SQLSTATE, la restricción y el `request_id`; nunca datos
   clínicos.

## 2. SQLSTATE verificados en ejecución

| Caso reproducido | SQLSTATE | Mensaje interno de PostgreSQL (no se expone) |
|---|---|---|
| Columna obligatoria sin valor | `23502` | `null value in column "status" of relation "beds" violates not-null constraint` |
| Referencia a un registro inexistente | `23503` | `insert or update on table "beds" violates foreign key constraint "beds_ward_id_fkey"` |
| Duplicado en una clave única | `23505` | `duplicate key value violates unique constraint "beds_ward_code_unique"` |
| Valor fuera del dominio permitido | `23514` | `new row for relation "beds" violates check constraint "beds_status_check"` |

El solapamiento de intervalos (exclusión GiST) produce `23P01`; en esta auditoría no se
reprodujo de forma aislada porque las tablas estaban vacías, pero el repositorio lo
cubre en `SchedulingAndBedIntegrityTest`, que pasa dentro de la suite ejecutada.

## 3. Catálogo violación → mensaje → HTTP

| Violación | SQLSTATE | Código de negocio | HTTP | Mensaje para el usuario |
|---|---|---|---|---|
| Obligatoriedad de un campo del paciente | `23502` | `PACIENTE_CAMPO_OBLIGATORIO` | 422 | "Falta un dato obligatorio del paciente. Revisá el formulario e intentá de nuevo." |
| Obligatoriedad en la atención | `23502` | `ATENCION_CAMPO_OBLIGATORIO` | 422 | "Falta un dato obligatorio de la atención. Revisá el formulario e intentá de nuevo." |
| Referencia a un paciente inexistente o de otro hospital | `23503` | `PACIENTE_NO_ENCONTRADO` | 404 | "El paciente indicado no existe en este hospital." |
| Referencia a una cama inexistente | `23503` | `CAMA_NO_ENCONTRADA` | 404 | "La cama indicada no existe en este hospital." |
| Referencia a un profesional inexistente | `23503` | `PROFESIONAL_NO_ENCONTRADO` | 404 | "El profesional indicado no existe en este hospital." |
| Código de cama duplicado en la sala | `23505` | `CAMA_CODIGO_DUPLICADO` | 409 | "Ya existe una cama con ese código en la sala." |
| Identificador de paciente duplicado | `23505` | `PACIENTE_CODIGO_DUPLICADO` | 409 | "Ya existe un paciente con ese código. Verificá los datos." |
| Episode de cuidado ya cerrado | `23505` | `EPISODIO_DUPLICADO` | 409 | "Ese episodio de cuidado ya tiene una estancia abierta." |
| Estado fuera de dominio (cama, paciente, episodio) | `23514` | `ESTADO_INVALIDO` | 422 | "El estado indicado no es válido. Elegí una de las opciones disponibles." |
| Rango clínico inválido (signos vitales) | `23514` | `VALOR_FUERA_DE_RANGO` | 422 | "Algún valor capturado está fuera del rango válido. Revisá la toma." |
| Período inválido (fin antes del inicio) | `23514` | `PERIODO_INVALIDO` | 422 | "La fecha de fin no puede ser anterior a la de inicio." |
| Slot que no divide el bloque horario | `23514` | `BLOQUE_INVALIDO` | 422 | "La duración del bloque no es múltiplo del tamaño de turno." |
| Solapamiento de horarios del mismo médico | `23P01` | `HORARIO_SOLAPADO` | 409 | "Ese horario se cruza con otro ya registrado para el mismo profesional." |
| Cama ocupada en el momento de asignar | `23P01` | `CAMA_OCUPADA` | 409 | "La cama ya está ocupada en ese momento. Elegí otra cama o liberá la existente." |
| Doble authorship de una nota en la misma versión | `23505` | `NOTA_VERSION_DUPLICADA` | 409 | "Ya existe una nota con esa versión. Actualizá la anterior." |
| Inconsistencia episodio-paciente-autor | `23514` | `CONTEXTO_INCONSISTENTE` | 422 | "La nota no corresponde al paciente o al profesional del episodio." |
| Operación sobre registro inmutable (nota firmada) | `23514` | `REGISTRO_INMUTABLE` | 409 | "Este registro ya está firmado. Creá una corrección en lugar de editarlo." |
| Borrado de registro con retención | `23514` | `REGISTRO_PROTEGIDO` | 409 | "Este registro no se puede eliminar porque forma parte del historial clínico." |
| Violación de rango o formato en el motor | `22003` | `RANGO_FUERA_DE_LIMITE` | 422 | "El valor enviado está fuera del rango permitido." |
| Violación de exclusividad de la sesión | `40P01` | `CONFLICTO_DE_CONCURRENCIA` | 409 | "Otra operación se está ejecutando sobre el mismo registro. Reintentá en un momento." |

## 4. Códigos HTTP derivados

| Situación | HTTP |
|---|---|
| El usuario no está autenticado | 401 |
| El usuario no tiene permiso `modulo.accion` | 403 |
| El recurso no existe en el hospital del usuario | 404 |
| El cuerpo o los parámetros no son válidos | 422 |
| La operación choca con el estado actual del dato | 409 |
| La base de datos o una dependencia no está disponible | 503 |

Regla adicional: una caída de la base de datos **nunca** se traduce a "dato vacío". Si
la base no responde, la respuesta es `503` con un mensaje de indisponibilidad, para no
mostrar ceros ni listas vacías que parezcan "no hay datos".

## 5. Qué debe registrar cada módulo

| Dato | Dónde |
|---|---|
| `request_id` | Log y cuerpo de la respuesta |
| Código de negocio | Log y cuerpo de la respuesta |
| SQLSTATE y nombre de la restricción | Solo en el log |
| Nombre de tabla y columna | Solo en el log |
| Datos del paciente | **Nunca** en el log ni en el mensaje |

## 6. Validación ejecutada

```bash
# comando: bloque DO con inserciones que violan NOT NULL, FK, UNIQUE y CHECK
# resultado: SQLSTATE 23502 (not-null), 23503 (foreign key), 23505 (unique), 23514 (check)

# comando: SELECT conname, pg_get_constraintdef(oid) FROM pg_constraint WHERE conrelid='beds'::regclass
# resultado: beds_ward_code_unique (UNIQUE), beds_status_check (CHECK), beds_ward_id_fkey (FK RESTRICT)

# comando: podman run --rm --network host -v $PWD:/app -w /app localhost/php85-pgsql-full php vendor/bin/phpunit tests/Database/Hospital/SchedulingAndBedIntegrityTest.php
# resultado: OK — el solapamiento de estancias (23P01) esta cubierto por las pruebas del repositorio
```

## 7. Riesgos y pendientes

- Los códigos de negocio son **propuesta** del módulo de BD: el acuerdo del formato
  común de respuestas y errores sigue pendiente entre los módulos.
- La traducción vive en la capa de aplicación; el repositorio todavía no tiene un
  middleware de errores común, así que cada módulo repetirá la tabla hasta que se
  acuerde el compartido.
- La tabla no cubre los SQLSTATE de trigger de dominio del slice H8, que todavía no
  existe.
