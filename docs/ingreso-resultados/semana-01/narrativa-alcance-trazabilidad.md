# Narrativa de alcance y trazabilidad - Ingreso de resultados de laboratorio

## Proposito

Permitir la **captura segura y trazable de resultados de laboratorio pendientes**, evitando que un resultado se publique con datos incompletos, unidades incorrectas, rangos incompatibles o correcciones sin justificacion. El modulo debe conservar evidencia de quien capturo, que cambio, cuando lo hizo y por que se corrigio.

## Alcance funcional

**Incluido**

- Consulta de ordenes y pruebas de laboratorio pendientes asociadas a una muestra recibida.
- Captura de valores cualitativos o cuantitativos segun el tipo de prueba.
- Validacion de campos obligatorios, unidad esperada, formato, rango tecnico y estado de la orden.
- Registro de resultado preliminar, disponible o pendiente de revision segun reglas del estudio.
- Correccion controlada de un resultado existente con motivo obligatorio.
- Versionamiento: el valor anterior no se sobrescribe; queda asociado a la nueva version.
- Auditoria de captura y correccion con usuario, tenant, fecha, prueba, accion y motivo.
- Notificacion interna al medico solicitante o al bioquimico cuando el resultado queda disponible o requiere revision.

**Fuera de alcance**

- Recepcion fisica de muestras y generacion de codigos identificadores: modulo *Recepcion de muestras*.
- Creacion del catalogo de pruebas, unidades y rangos de referencia: modulo *Catalogo de pruebas de laboratorio*.
- Validacion final por bioquimico como proceso independiente: modulo *Validacion de resultados por bioquimico*.
- Alertas criticas y escalamiento clinico: modulo *Alertas criticas y notificaciones internas*.
- Inicio de sesion, emision de JWT y administracion de usuarios.

## Supuestos y restricciones

- Solo se capturan resultados para pruebas con estado `pendiente_resultado` o equivalente.
- La muestra debe existir, pertenecer al hospital activo y estar en estado apto para procesamiento.
- El tecnico de laboratorio puede capturar resultados preliminares; las correcciones de resultados ya publicados exigen motivo y pueden requerir autorizacion del bioquimico.
- El sistema nunca elimina una version anterior de resultado.
- Los eventos de auditoria no deben incluir mas datos clinicos de los necesarios.
- Si falla la persistencia del resultado o de la auditoria, la operacion se rechaza para evitar resultados sin traza.

## Trazabilidad entre los diagramas

| UC | Objetivo | Actividad (decision o paso) | Secuencia (mensajes) |
|---|---|---|---|
| UC-LAB-01 | Consultar resultados pendientes | "Consultar bandeja de pendientes" y decision "Hay pruebas pendientes" | 1-4 |
| UC-LAB-02 | Capturar resultado de prueba | "Ingresar valor, unidad y observacion" | 5-8 |
| UC-LAB-03 | Validar resultado | Decisiones "Orden/muestra valida", "Unidad esperada", "Valor dentro del formato/rango" | 7-10 |
| UC-LAB-04 | Guardar resultado preliminar/disponible | "Crear resultado o nueva version" y "Confirmar registro" | 11-15 |
| UC-LAB-05 | Solicitar correccion controlada | Decision "Es correccion" y "Motivo obligatorio" | 16-20 |
| UC-LAB-06 | Registrar motivo y version anterior | "Conservar version anterior" y "Registrar auditoria" | 18-22 |
| UC-LAB-07 | Notificar disponibilidad o revision | "Notificar al medico/bioquimico" | 23-24 |
| UC-LAB-08 | Rechazar captura invalida | Ramas de error 401, 403, 404, 409 y 422 | 7, 9, 10, 20 |

## Flujo principal

1. El tecnico abre la bandeja de resultados pendientes.
2. El sistema valida autenticacion, tenant y permisos de laboratorio.
3. El tecnico selecciona una prueba pendiente asociada a una muestra recibida.
4. Ingresa valor, unidad, observacion y, si aplica, indicador de resultado critico.
5. El sistema valida que la orden y la muestra esten activas y correspondan al hospital.
6. El sistema valida formato, unidad esperada y rango permitido por el catalogo.
7. Si la captura es valida, se crea el resultado y queda como preliminar, disponible o pendiente de revision.
8. El sistema registra auditoria y notifica a los actores correspondientes.

## Flujo de correccion

1. Un usuario autorizado abre un resultado existente.
2. El sistema identifica si el resultado ya fue publicado o validado.
3. Si la correccion esta permitida, exige motivo obligatorio.
4. El sistema crea una nueva version con el valor corregido y conserva la version anterior.
5. Se registra auditoria con usuario, motivo, valor anterior referenciado y resultado nuevo.
6. Se notifica al bioquimico o medico solicitante segun el estado del resultado.

## Resultados y errores esperados

- `200 OK`: listado de pendientes o resultado actualizado correctamente.
- `201 Created`: resultado capturado por primera vez.
- `401 Unauthorized`: usuario no autenticado.
- `403 Forbidden`: usuario sin permiso para capturar o corregir.
- `404 Not Found`: orden, muestra o prueba inexistente para el tenant.
- `409 Conflict`: la prueba ya no esta pendiente o el resultado no admite correccion directa.
- `422 Unprocessable Entity`: valor, unidad, rango o motivo invalido.
- `500 Internal Server Error`: fallo de persistencia o auditoria; no se confirma la operacion.

## Dependencias con otros modulos

- **Recepcion de muestras:** provee muestras aptas para procesamiento.
- **Catalogo de pruebas de laboratorio:** define tipo de resultado, unidad, formato y rangos.
- **Validacion de resultados por bioquimico:** toma resultados preliminares o marcados para revision.
- **Alertas criticas:** recibe eventos cuando el resultado supera umbrales definidos.
- **Expediente medico:** consulta resultados disponibles para el medico tratante.

## Archivos relacionados

- [Casos de uso](./01-casos-de-uso.svg)
- [Actividades](./02-actividad.svg)
- [Secuencia](./03-secuencia.svg)
- [README del modulo](./README.md)
