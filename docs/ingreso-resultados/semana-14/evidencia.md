# Evidencia SQA

| Area | Caso minimo | Resultado esperado |
|---|---|---|
| Captura | Resultado pendiente con unidad valida | Se registra sin alterar otros datos |
| Correccion | Motivo vacio | Se rechaza y conserva la version activa |
| Permisos | Rol no autorizado | Respuesta 403 sin persistencia |
| Concurrencia | Version obsoleta | Conflicto explicito y sin sobrescritura |
| Persistencia | Adaptador con fallo | Error recuperable y estado consistente |
