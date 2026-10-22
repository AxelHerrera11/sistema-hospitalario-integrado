# Matriz de validación del catálogo de errores (Semana 8)

Cada fila indica quién aplica la regla y cómo se comprueba. Sirve para que el
acuerdo de formato común sea verificable y no una buena intención.

| Regla | Responsable | Cómo se comprueba |
|---|---|---|
| El SQLSTATE no se expone al cliente | Todos los módulos | Revisión de código: ninguna respuesta incluye `SQLSTATE` ni el texto de la excepción |
| Existe un código de negocio por violación | Cada módulo | Búsqueda del código en las respuestas de error |
| El mensaje está en español y es accionable | Cada módulo | Prueba funcional que revisa el mensaje |
| El log registra `request_id`, código de negocio, SQLSTATE y restricción | Cada módulo | Prueba que lee el log y comprueba que no hay datos clínicos |
| El `422` se reserva para datos inválidos | Cada módulo | Prueba con un cuerpo inválido y con un conflicto |
| El `409` se reserva para conflicto de estado | Cada módulo | Prueba de cama ocupada y de horario solapado |
| El `404` solo para recurso inexistente en el hospital | Cada módulo | Prueba con identificador de otro tenant |
| El `503` nunca devuelve ceros ni listas vacías | Cada módulo | Prueba con la base detenida |
| Los permisos faltantes devuelven `403` con el permiso esperado | Módulo de RBAC | Prueba con usuario sin permiso |

## Trampa conocida

Un `422` con el mensaje "el registro no existe" confunde al usuario: no puede
corregirlo desde el formulario. Por eso el `404` se reserva para el recurso inexistente
y el `422` para el valor que no cumple la regla. El catálogo de la semana 8 mantiene
esa separación en las 21 filas.
