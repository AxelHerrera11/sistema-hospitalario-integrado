# Evidencia verificable

## Fuentes revisadas

Para esta entrega se tomaron como referencia las estructuras disponibles actualmente en el repositorio.

| Fuente | Evidencia encontrada | Uso en esta semana |
|---|---|---|
| `database/migrations/hospital/2026_08_11_000200_create_patient_and_care_tables.php` | La tabla `local_patients` contiene identificadores, nombres, fecha de nacimiento, sexo, contacto, estado y restricciones de integridad. | Se utiliza como base para definir requisitos, validaciones y criterios de aceptación. |
| `database/migrations/central/2026_08_11_000200_create_mpi_tables.php` | Existen estructuras separadas para la información central del paciente y sus vínculos con hospitales. | Permite distinguir responsabilidades entre persistencia local y central. |
| `app/` | No se encuentra implementado todavía el flujo completo de registro, búsqueda y actualización de pacientes. | El ejemplo de LSP se presenta como una propuesta de diseño previa a la implementación. |

## Verificación del diseño LSP

La propuesta puede verificarse posteriormente utilizando diferentes implementaciones del mismo contrato de pacientes.

Todas deberían conservar el mismo comportamiento esperado sin obligar al código cliente a realizar cambios especiales.

Se consideran los siguientes escenarios:

1. Registrar un paciente con información válida.
2. Rechazar identificadores duplicados.
3. Rechazar una fecha de nacimiento futura.
4. Rechazar valores no permitidos.
5. Actualizar un paciente existente conservando su identidad.
6. Buscar pacientes sin producir modificaciones.
7. Manejar una búsqueda sin coincidencias.
8. Evitar cambios parciales cuando ocurre un error durante una actualización.

Una implementación no cumpliría correctamente con LSP si al sustituir a otra:

- exige condiciones adicionales;
- devuelve resultados incompatibles;
- cambia garantías ya establecidas;
- o produce efectos secundarios inesperados.

## Evidencia de Semana 2

La evidencia de esta entrega incluye:

- Requisitos funcionales.
- Requisitos no funcionales.
- Criterios de aceptación.
- Diseño previo a aplicar LSP.
- Diseño propuesto aplicando LSP.
- Justificación de responsabilidades y dependencias.
- Escenarios de verificación del principio.

No se reportan pruebas funcionales ejecutadas porque esta entrega corresponde al análisis y diseño del flujo.
