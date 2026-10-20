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
| RNF-04 | Mantenibilidad (ISP) | Cada cliente depende de un contrato sin métodos que no invoca: 0 métodos no usados; se verifica con la evidencia ISP de la parte 2 de esta semana. | P |
| RNF-05 | Seguridad de datos | Los valores externos se envían a la base como parámetros de sentencias preparadas (se verificará en Semana 3). | P |
| RNF-06 | Privacidad | La documentación y las pruebas usan solo datos ficticios; el catálogo no guarda datos de pacientes. | P |

## Supuestos según la evidencia del repositorio

- **Permisos:** `config/rbac.php` no define permisos `medicamentos.*`. Los roles "gestor" y "usuario clínico" siguen siendo actores de Semana 1 y su permiso concreto queda pendiente con RBAC.
- **Alcance por hospital:** el README define una base clínica HOSPITAL por hospital. El catálogo no añade una columna de hospital y queda limitado a la base activa.
- **Unicidad:** el slice H5 (`000500_create_medication_tables.php`) sigue pendiente en `openspec/.../tasks.md`. El código único por base HOSPITAL es una identidad provisional, no una restricción existente.
