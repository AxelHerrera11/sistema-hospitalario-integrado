# RF/RNF y criterios de aceptacion

## Requerimientos funcionales

| Codigo | Requerimiento funcional | Criterios de aceptacion |
| --- | --- | --- |
| RF-NP-01 | El prototipo debe permitir autenticar un usuario de laboratorio en un entorno controlado. | Dado un usuario de prueba, cuando inicia sesion, entonces el prototipo registra una sesion local sin guardar credenciales en texto plano. |
| RF-NP-02 | El prototipo debe consultar una lista acotada de resultados pendientes o tareas HIS simuladas. | Dado datos semilla, cuando se abre la vista principal, entonces se muestran solo registros del tenant activo. |
| RF-NP-03 | El prototipo debe permitir marcar una accion local como preparada para sincronizacion. | Dado un registro disponible, cuando el usuario confirma una accion, entonces queda en cola local con estado `pendiente_sincronizar`. |
| RF-NP-04 | El prototipo debe simular sincronizacion con respuesta exitosa y fallida. | Dado una cola local, cuando se ejecuta sincronizacion, entonces el sistema marca elementos exitosos y conserva fallidos con razon. |
| RF-NP-05 | El prototipo debe mostrar estado de conectividad y ultimo intento de sincronizacion. | Dado el prototipo abierto, cuando cambia el modo online/offline simulado, entonces la interfaz muestra el estado vigente. |
| RF-NP-06 | El prototipo debe generar evidencia tecnica de viabilidad. | Dado una ejecucion de prueba, cuando termina el flujo, entonces se registra una matriz de hallazgos, riesgos y decision. |

## Requerimientos no funcionales

| Codigo | Requerimiento no funcional | Criterios de aceptacion |
| --- | --- | --- |
| RNF-NP-01 | Seguridad local. | Tokens, configuraciones y cache se tratan como datos sensibles y no se documentan con valores reales. |
| RNF-NP-02 | Aislamiento del HIS principal. | El prototipo no escribe sobre servicios productivos ni requiere credenciales reales. |
| RNF-NP-03 | Portabilidad evaluable. | La evidencia indica limites para escritorio y movil sin asumir soporte productivo automatico. |
| RNF-NP-04 | Observabilidad minima. | Cada intento de sincronizacion deja estado, fecha y resultado tecnico. |
| RNF-NP-05 | Mantenibilidad por contratos. | La vista, almacenamiento local, sincronizacion y seguridad pueden probarse por separado. |
| RNF-NP-06 | Proteccion de datos clinicos. | La evidencia usa datos ficticios y minimizados. |

## Escenarios de aceptacion

### Consulta local acotada

```gherkin
Dado que existen datos semilla del tenant activo
Cuando el usuario abre la vista principal del prototipo
Entonces ve una lista acotada de registros simulados
Y no observa datos de otro tenant
```

### Accion pendiente de sincronizar

```gherkin
Dado que un registro esta disponible en modo offline
Cuando el usuario marca una accion para sincronizar
Entonces el prototipo crea un item en cola local
Y el item queda con estado pendiente_sincronizar
```

### Falla de sincronizacion

```gherkin
Dado que existe un item en cola local
Cuando la sincronizacion simulada falla
Entonces el item conserva su estado pendiente
Y registra la razon tecnica del fallo
```
