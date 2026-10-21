# Pendientes de coordinación del contrato de órdenes de laboratorio

Acuerdos que deben cerrarse con el equipo antes de escribir el módulo. El plan
transversal del 18 de octubre da a este contrato fecha de publicación el martes 20
y el PR en `develop` el miércoles 21.

| # | Acuerdo | Depende de | Estado |
|---:|---|---|---|
| 1 | Confirmar quién es el responsable de escribir las migraciones de órdenes y muestras | Javier (BD) con el dueño de ASII-16 | Pendiente |
| 2 | Confirmar los nombres de los permisos `laboratorio.ordenes.*` en el catálogo de RBAC | María (RBAC) | Pendiente |
| 3 | Unificar `X-Correlation-ID` con el `X-Request-ID` del contrato de ocupación | Lis (contratos) | Pendiente |
| 4 | Definir los estados compartidos entre órdenes, muestras y resultados | Mercedes, Gerson | Pendiente |
| 5 | Confirmar el estilo final de ruta: `/api/v1/lab-orders` o `/api/v1/lab/orders` | ARMADO (recepción) | Pendiente |
| 6 | Confirmar la dependencia de `lab_result_versions` para la FK de alertas | ASII-21 (alertas) | Pendiente |
| 7 | Confirmar quién publica la clave de idempotencia por hospital y su vigencia de 24 h | Dueños de órdenes y recepción | Pendiente |

## Riesgos

- El slice H6 del esquema federado sigue sin migraciones en `develop`. Mientras tanto
  el módulo debe trabajar detrás de una interfaz con adaptador InMemory.
- Si la tabla `lab_result_versions` aparece después, el PR de alertas (#337) debe
  apilarse o agregar la FK antes de integrarse.
- Si nadie asume la escritura de `lab_orders`, el PR del miércoles 21 no puede llegar.
