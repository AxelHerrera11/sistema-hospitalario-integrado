# Matriz de pruebas — Semana 15

Todas las pruebas están en `micro-his/traslados-altas/tests/` y se ejecutaron el 21/10/2026 ([salidas](./evidencia/)).

## Casos seleccionados por tipo

| ID | Tipo | Técnica | Caso | Archivo | Resultado |
|---|---|---|---|---|---|
| U1 | Unitaria | Equivalencias | `SolicitudTraslado` rechaza estancia, cama o motivo inválidos | `solicitudes.php` | Pasa |
| U2 | Unitaria | Ramas | `ReglasLiberacion::rechazoDeDestino`: los cuatro rechazos y el orden de evaluación | `destino.php` | Pasa |
| U3 | Unitaria | Contrato | Solo `ExitoConfirmado` declara cambios | `resultados.php` | Pasa |
| U4 | Unitaria | Equivalencias | `interpretar()` de la interfaz: un estado HTTP → un mensaje y una acción | `ui-mensajes.test.mjs` | Pasa |
| W1 | Caja blanca | Cobertura de ramas | `rechazoDeEstancia`: una prueba por rama del `match`, más los límites de T (igual, anterior y +1 µs) | `reglas.php` | Pasa |
| W2 | Caja blanca | Caminos | `ProtocoloLiberacion`: sin autorización, estancia inexistente, falla en iniciar, leer, escribir o confirmar, incierto y éxito | `protocolo.php` | Pasa |
| W3 | Caja blanca | Condición de carrera | `leerCama` bajo dos transacciones simultáneas | `concurrencia.php` C2 | **Falló y se corrigió** (D5) |
| B1 | Caja negra | Equivalencias (HTTP) | 201, 200, 409, 403, 401, 400, 422, 404, 405, 503 y 500 | `http.php` | Pasa |
| B2 | Caja negra | Valores límite | Motivo de 1, 500 y 501 caracteres; T igual al inicio y un microsegundo después | `limites.php`, `solicitudes.php`, `reglas.php` | Pasa |
| B3 | Caja negra | Permisos | Actor activo, revocado, inexistente, inválido o en blanco | `postgres.php`, `limites.php` | Pasa |
| B4 | Caja negra | Errores | FK real después de la primera escritura: se revierte por completo | `postgres.php` | Pasa |
| B5 | Caja negra | Concurrencia | Misma estancia hacia dos camas: como máximo un éxito | `concurrencia.php` C1 | Pasa (10/10) |
| B6 | Caja negra | Interfaz de punta a punta | Traslado y alta desde el navegador contra PostgreSQL | `semana-10/evidencia/recorrido-ui.mjs` | Pasa (9/9) |

Mínimos de la asignación: unitarias 4 de 3, caja blanca 3 de 2 y caja negra 6 de 4.

## Matriz RF/RNF → prueba

| Requisito | Pruebas |
|---|---|
| RF-TA-01, RF-TA-02 solicitudes | U1, B2 |
| RF-TA-03 autorizar antes de la transacción | W2, B3 |
| RF-TA-04 estancia esperada y T | W1, B2 |
| RF-TA-05 destino | U2, W3 |
| RF-TA-06 traslado contiguo en el mismo episodio | `planes.php`, `postgres.php` |
| RF-TA-07 alta con el mismo T | `alta.php`, `postgres.php` |
| RF-TA-08 cuatro resultados | U3, W2, B1 |
| RF-TA-09 éxito con la liberación común | `planes.php`, U4 |
| RF-TA-10 `beds.status` sin cambios | `traslado.php`, `postgres.php` |
| RNF-TA-01 LSP | `alta.php` (batería común) |
| RNF-TA-02 sin cambios ante un rechazo | W2, B4 |
| RNF-TA-03 atomicidad | B4 |
| RNF-TA-04 concurrencia | B5, W3 |
| RNF-TA-06 incierto | W2, `http.php`, `semana-10/evidencia/movil.mjs` |
| RNF-TA-07 solo identificadores | `presentacion.php`, `http.php` |
