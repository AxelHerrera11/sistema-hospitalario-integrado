# Semana 6 — Primera evaluación parcial

**Módulo:** ASII-20 — Validación de resultados por bioquímico  
**Estudiante:** ORELLANA VÉLIZ, GERSON GIOVANNI

## Tesis arquitectónica

La decisión de validación o rechazo pertenece al dominio y se ejecuta mediante casos de uso separados. El rol, tenant, unidad, estado pendiente y motivo se validan antes de persistir; el registro de auditoría no puede quedar separado de la decisión lógica.

## Matriz decisión→evidencia

| Decisión defendida | Evidencia | Criterio verificable |
|---|---|---|
| Separar política del controlador | BiochemistReviewPolicy y entidad revisable | Un rol incorrecto no decide |
| Usar casos de uso explícitos | ValidateReviewedResult y RejectReviewedResult | Cada acción tiene reglas y respuesta propia |
| Proteger concurrencia | Repository con estado y versión esperados | Una decisión obsoleta falla |
| Mantener atomicidad observable | auditoría antes de guardar y copias defensivas | Falla de auditoría conserva pendiente |
| Respetar contrato HTTP | lista explícita validate/reject | Acción desconocida no valida |

## Cambio práctico defendido

Para un resultado de 95 mg/dL, el bioquímico con rol autorizado puede validarlo si la unidad coincide. Si decide rechazarlo, el motivo es obligatorio. Una segunda decisión sobre la versión anterior no puede reemplazar la decisión ya registrada.

## Validación

    php micro-his/validacion-resultados-bioquimico/tests/run.php
    OK: validacion, reglas de dominio y error de persistencia verificados.

## Límite

La revisión real requiere identidad y permisos del HIS; el prototipo recibe el rol como entrada controlada y no representa autenticación productiva.

