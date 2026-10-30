# Semana 6 — Caso práctico Laravel

API protegida por tenant, JWT y permisos muestras.*. Dominio y persistencia en app/Modules/Reception, independientes del micro académico. Pruebas unitarias en tests/Unit/Reception. Configurar RECEPTION_HOSPITAL_ID y sincronizar el actor en local_user_refs.


## Caso práctico y guía de defensa (matriz y plan, semana 6)

**Caso:** un técnico de laboratorio recibe una muestra de sangre contra un ítem de orden pendiente. Debe quedar aceptada con código o rechazada con motivo, sin duplicar el registro si la red falla.

| Decisión arquitectónica | Dónde está | Por qué |
|---|---|---|
| Dominio sin framework | `app/Modules/Reception/Domain` y `Application` | Las reglas se prueban sin HTTP ni base de datos |
| Puertos `OrderGateway` y `ReceptionRepository` | `Application/` | Permiten sustituir la fuente de órdenes y la persistencia (LSP/DIP) |
| Controlador delgado | `ReceptionController` | Valida entrada, resuelve tenant y actor, traduce errores |
| Seguridad por capas | middleware `tenant`, `jwt.auth`, `rbac:muestras.*` | Hospital, identidad y permiso se comprueban antes del caso de uso |
| Idempotencia | `request_key` + hash del contenido | Un reintento tras un corte no duplica la muestra |

**Permisos por rol** (`config/rbac.php`): `muestras.recibir` y `muestras.etiquetar` los tienen superadmin, enfermería y técnico de laboratorio; recepción tiene recibir y ver; bioquímico solo ver; `muestras.rechazar` queda reservado al técnico y al superadmin y todavía no protege ningún endpoint, porque el rechazo se decide por reglas.

**Preguntas de defensa previstas**
1. ¿Por qué un rechazo devuelve 201? Porque es un resultado de negocio registrado, no un fallo del servidor.
2. ¿Qué pasa si se repite el POST? Misma clave y contenido devuelven el resultado original; contenido distinto da 409.
3. ¿Qué evita mezclar hospitales? El header de tenant debe coincidir con `RECEPTION_HOSPITAL_ID` y cada despliegue usa su base HOSPITAL.
4. ¿Qué se probó? Dominio en `tests/Unit/Reception`; la API con JWT/RBAC se prueba en semanas posteriores.
