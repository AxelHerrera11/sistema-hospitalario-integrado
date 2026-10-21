# Semana 15 - Pruebas de validacion

**Modulo:** ASII-20 Validacion de resultados por bioquimico
**Tarea Odoo:** Semana 15 - Validacion de resultados por bioquimico

## Casos de prueba

| Tipo | Caso | Resultado esperado |
|---|---|---|
| Unitaria | Decision `validated` | Regla acepta resultado pendiente |
| Unitaria | Rechazo sin motivo | Regla rechaza entrada |
| Caja blanca | Ramas de rol y tenant | Cada decision tiene salida |
| Caja negra | Solicitud autorizada | 200/201 con estado final |
| Caja negra | Rol no autorizado | 403 sin cambio |
| Caja negra | ID inexistente | 404 sin auditoria |
| Integracion | Falla de auditoria | Rollback del estado |

## Ejecucion

La ejecucion debe usar los comandos del pipeline del repositorio, por ejemplo
`php artisan test --filter=Lab`. El resultado del entorno local o integrado se
adjunta al PR con fecha, commit y salida resumida. No se marca como ejecutado
cuando solo se reviso el diseño.

## Datos

Fixtures ficticios, tenant de prueba y usuarios con permisos diferenciados.
Nunca se incluyen resultados de pacientes reales.
