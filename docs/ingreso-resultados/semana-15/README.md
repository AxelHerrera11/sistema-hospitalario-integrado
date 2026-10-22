# Semana 15 Pruebas del modulo

**Modulo:** ASII-19 Ingreso de resultados de laboratorio

## Alcance de pruebas

Se preparan pruebas unitarias de reglas, caja blanca de decisiones y caja negra del contrato del flujo de captura y correccion controlada.

## Matriz

| Tipo | Caso | Resultado esperado |
|---|---|---|
| Unitaria | Motivo obligatorio | Regla rechaza entrada incompleta |
| Unitaria | Estado pendiente | Solo pendientes pueden corregirse |
| Caja blanca | Ramas de permiso, tenant y version | Cada decision tiene salida verificable |
| Caja negra | Solicitud valida | Respuesta de exito con version nueva |
| Caja negra | Solicitud invalida | Error explicito sin cambio parcial |

## Limite

Este PR documenta la matriz y los criterios. La ejecucion requiere que el flujo funcional y las dependencias de integracion esten disponibles en develop.
