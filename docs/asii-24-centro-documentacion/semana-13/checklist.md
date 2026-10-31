# Checklist de revision tecnica formal - ASII-24

Cada revisor marca **S** (cumple), **N** (no cumple -> registrar hallazgo) o **NA** en la ultima columna.

| ID | Categoria | Item | S/N/NA |
|---|---|---|---|
| C-01 | Arquitectura | El Dominio no importa PDO, HTTP ni superglobales (`$_GET`, `header`). | |
| C-02 | Arquitectura | El controlador no contiene SQL ni reglas de negocio. | |
| C-03 | Correccion | Las entradas se validan en el borde: obligatorios, tipo y formato. | |
| C-04 | Correccion | Los errores de dominio tienen tipo/codigo propio; el status no se deduce de un texto. | |
| C-05 | Seguridad | Todo SQL usa sentencias preparadas; no hay concatenacion de entradas. | |
| C-06 | Seguridad | Toda consulta y tabla aplica el tenant (multitenancy). | |
| C-07 | Seguridad | Datos de identidad/rol vienen de la sesion (JWT), no del cliente; las salidas se sanean. | |
| C-08 | Correccion | Comparaciones estrictas (`===`, `in_array(..., true)`). | |
| C-09 | Correccion | Fechas y periodos con formato validado y zona horaria explicita. | |
| C-10 | Pruebas | Hay prueba del camino feliz y de al menos un error por regla; son deterministas. | |
| C-11 | Mantenibilidad | Nombres expresan intencion; no hay numeros ni supuestos magicos. | |
| C-12 | Mantenibilidad | No hay logica duplicada ni validaciones repetidas inconsistentes. | |
| C-13 | Trazabilidad | El codigo cumple los RF y el contrato API vigentes (status y `code`). | |
| C-14 | Arquitectura | Las dependencias apuntan hacia el Dominio (DIP). | |
