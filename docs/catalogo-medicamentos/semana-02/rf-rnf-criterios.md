# RF, RNF y criterios de aceptación - ASII-14

Flujo: **alta, búsqueda y control de vigencia de un medicamento**. Cada requisito se traza a los casos de uso de [Semana 1](../semana-01/narrativa-alcance-trazabilidad.md). La columna *Base* distingue lo verificado en el repositorio (**R**) de lo propuesto por este módulo (**P**).

## Requisitos funcionales

| ID | Requisito | UC | Base |
|---|---|---|---|
| RF-01 | Registrar un medicamento con código, nombre, principio activo y grupo farmacológico; el registro nace con vigencia `active`. | UC-MED-01 | P |
| RF-02 | Rechazar el alta si falta un dato obligatorio o el código no cumple el formato definido en Semana 3. | UC-MED-06 | P |
| RF-03 | Rechazar el alta si el código normalizado ya existe en la base HOSPITAL activa (identidad provisional). | UC-MED-06 | P |
| RF-04 | Buscar por texto o código y devolver cada resultado con su vigencia; una lista vacía se distingue de un fallo de consulta. | UC-MED-02 | P |
| RF-05 | Consultar la vigencia de un medicamento por identificador; si no existe se informa "Medicamento no encontrado". | UC-MED-03 | P |
| RF-06 | Cambiar la vigencia entre `active` e `inactive` sin borrar el registro ni modificar sus demás datos. | UC-MED-04 | P |
| RF-07 | Permitir alta y cambio de vigencia solo al gestor; búsqueda y consulta al gestor o al usuario clínico, ambos con hospital activo. | UC-MED-05 | P |
| RF-08 | Comunicar éxito de alta o de cambio solo cuando la persistencia confirme la operación. | UC-MED-01/04 | P |

La vigencia es el estado del **registro de catálogo**. No representa la fecha de vencimiento de un lote: lotes, existencias y vencimientos quedan fuera de alcance, como en Semana 1.

## Requisitos no funcionales

| ID | Atributo | Requisito medible | Base |
|---|---|---|---|
| RNF-01 | Seguridad | Una operación sin autorización se rechaza antes de llamar a cualquier contrato del catálogo (0 llamadas registradas). | P |
| RNF-02 | Aislamiento | Cada operación actúa sobre una sola base HOSPITAL; ningún contrato recibe un hospital distinto del activo. | R: README, base clínica por hospital |
| RNF-03 | Integridad | Un fallo de lectura o escritura nunca se presenta como éxito ni como lista vacía. | P |
| RNF-04 | Mantenibilidad (ISP) | Cada cliente depende de un contrato sin métodos que no invoca: 0 métodos no usados, verificado con [la evidencia](./EVIDENCIA.md). | P |
| RNF-05 | Seguridad de datos | Los valores externos se envían a la base como parámetros de sentencias preparadas (se verificará en Semana 3). | P |
| RNF-06 | Privacidad | La documentación y las pruebas usan solo datos ficticios; el catálogo no guarda datos de pacientes. | P |

## Criterios de aceptación

| ID | Dado | Cuando | Entonces | RF/RNF |
|---|---|---|---|---|
| CA-01 | un gestor autorizado y un código libre | registra datos completos | recibe el medicamento confirmado con vigencia `active` | RF-01, RF-08 |
| CA-02 | un alta sin principio activo | se valida | se rechaza indicando el campo y no se escribe nada | RF-02 |
| CA-03 | el código `AMX-500` ya registrado | se registra `amx-500` | se rechaza como duplicado | RF-03 |
| CA-04 | medicamentos que coinciden con "amox" | un usuario clínico busca | recibe los resultados con su vigencia | RF-04 |
| CA-05 | ningún medicamento coincide | se busca | recibe una lista vacía, no un error | RF-04 |
| CA-06 | la consulta de búsqueda falla | se busca | recibe un error de consulta, no una lista vacía | RF-04, RNF-03 |
| CA-07 | un identificador inexistente | se consulta o cambia la vigencia | recibe "Medicamento no encontrado" | RF-05 |
| CA-08 | un medicamento `active` | el gestor lo cambia a `inactive` | el registro sigue consultable con sus datos y vigencia `inactive` | RF-06 |
| CA-09 | un usuario clínico | intenta registrar o cambiar vigencia | se rechaza sin invocar el catálogo | RF-07, RNF-01 |
| CA-10 | la escritura falla | se registra o cambia vigencia | se informa error y nunca "Medicamento registrado" ni "Vigencia actualizada" | RF-08, RNF-03 |
| CA-11 | cada cliente del diseño después | se analiza su dependencia | depende de 0 métodos que no invoca | RNF-04 |

## Supuestos según la evidencia del repositorio

- **Permisos:** al redactar Semana 2, `config/rbac.php` no tenía permisos `medicamentos.*`. Desde entonces RBAC concede `medicamentos.ver` a `superadmin`, `admin_hospital`, `medico` y `enfermeria`. Los permisos de alta y vigencia siguen sin existir: Semana 5 propone `medicamentos.registrar` y `medicamentos.cambiar_vigencia` ([contrato API](../semana-05/contrato-api.md)).
- **Alcance por hospital:** el README define una base clínica HOSPITAL por hospital. El catálogo no añade una columna de hospital y queda limitado a la base activa.
- **Unicidad:** el slice H5 (`000500_create_medication_tables.php`) sigue pendiente en `openspec/.../tasks.md`. El código único por base HOSPITAL es una identidad provisional, no una restricción existente.
