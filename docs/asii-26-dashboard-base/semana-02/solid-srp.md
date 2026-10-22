# Aplicación de SRP a la base del dashboard

## Principio

SRP propone que un componente tenga una responsabilidad cohesiva y, por tanto, una razón principal para cambiar. No significa crear una clase por cada línea; significa evitar que una misma unidad mezcle motivos de cambio independientes, como validar el acceso, construir el menú por rol, obtener el resumen y decidir el estado de la interfaz.

## Diseño antes de SRP: responsabilidades mezcladas

El siguiente esquema es conceptual. Representa una implementación problemática, no código existente:

```text
DashboardPage (vista única)
  ├─ valida sesión y permisos          (acceso)
  ├─ construye el menú según el rol    (navegación)
  ├─ consulta el resumen a la API      (datos)
  └─ decide carga / vacío / error      (estado de UI)
```

Una sola unidad cambia por motivos distintos: ajustes de permisos, del catálogo del menú, del contrato de la API de resumen o de los criterios de estado. Cualquiera de esos cambios obliga a tocar la misma clase que también valida el acceso.

### Consecuencias

- Cambiar el catálogo del menú puede afectar el flujo de acceso aunque no cambien los permisos.
- Las reglas de estado de la UI quedan acopladas a la consulta de datos.
- Las pruebas de la vista deben cubrir a la vez acceso, menú, carga y estado.
- Reutilizar el menú o el resumen en otra vista exige arrastrar toda la clase.

## Diseño después de SRP: responsabilidades separadas

```text
DashboardController (coordina la vista)
  ├─ ContractGuard            (sesión y permisos → login/403/ok)
  ├─ ContractMenuBuilder      (menú por rol y permisos)
  ├─ ContractResumenLoader    (obtener el resumen)
  └─ ContractEstadoPresenter  (traducir el resultado a estado de UI)
```

| Componente conceptual | Responsabilidad única | Razón principal para cambiar |
|---|---|---|
| `ContractGuard` | Evaluar sesión y permisos de la vista. | Cambian las reglas de acceso (401/403). |
| `ContractMenuBuilder` | Construir el menú según rol y permisos. | Cambia el catálogo del menú por rol. |
| `ContractResumenLoader` | Obtener el resumen del dashboard. | Cambia el contrato de la API de resumen. |
| `ContractEstadoPresenter` | Traducir el resultado a estado de UI (carga, vacío, error, datos). | Cambian los criterios de estado de la interfaz. |
| `DashboardController` | Coordinar el orden de los pasos de la vista. | Cambia el flujo del caso de uso. |

## Ejemplo concreto de SRP

Si se agrega un ítem al menú para un rol, el cambio corresponde al `ContractMenuBuilder`; el guard, el loader y el presenter no deberían cambiar mientras sus contratos sigan iguales. Si cambia el contrato de la API del resumen, cambia el `ContractResumenLoader`; el menú y el estado no se reasignan. Si cambian los criterios de "vacío", cambia solo el `ContractEstadoPresenter`.

La evidencia (`evidencia/demo-srp.php`) demuestra dos extensiones —un menú nuevo y un resumen vacío— **sin modificar `DashboardController`**, y muestra que cambiar el menú no altera la carga del resumen ni las reglas de acceso.

## Justificación de dependencias

- El controlador depende de contratos (`ContractGuard`, `ContractMenuBuilder`, `ContractResumenLoader`, `ContractEstadoPresenter`), no de implementaciones.
- El guard depende de la sesión y de la vista, no de la API de resumen.
- El builder depende del rol y los permisos, no de la consulta de datos.
- La separación es una propuesta de diseño para el flujo descrito, no una afirmación de que estas clases estén implementadas.
