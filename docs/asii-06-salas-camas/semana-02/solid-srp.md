# Aplicación de SRP al catálogo de wards y beds

## Principio

SRP propone que un componente tenga una responsabilidad cohesiva y, por tanto, una razón principal para cambiar. No significa crear una clase por cada línea de código; significa evitar que una misma unidad mezcle motivos de cambio independientes, como validación de entrada, política del estado operativo y persistencia.

## Diseño antes de SRP: responsabilidades mezcladas

El siguiente esquema es conceptual. Representa una implementación problemática, no código existente:

```text
Controlador de catálogo
  └─ Servicio único de catálogo
       ├─ valida campos de sala y cama
       ├─ decide valores y reglas de estado
       ├─ comprueba códigos duplicados
       ├─ crea wards y beds en la base
       ├─ asocia la cama con ward_id
       └─ traduce fallos de persistencia a respuestas
```

El servicio único cambia por motivos distintos: modificaciones del formato de entrada, ajustes a los dominios de estado, reglas de unicidad o cambios del mecanismo de almacenamiento. También concentra en un mismo lugar la creación de dos entidades relacionadas.

### Consecuencias

- Una modificación en la validación puede afectar el flujo de escritura aunque no cambie la persistencia.
- Las reglas de estado quedan acopladas al mecanismo que crea los registros.
- Las pruebas del servicio necesitan cubrir a la vez validaciones, asociación y almacenamiento.
- La creación de una cama puede ser difícil de razonar si su asociación obligatoria con la sala se mezcla con la interpretación de los datos de entrada.

## Diseño después de SRP: propuesta separada

```text
Entrada de creación
  └─ Validador de datos
       └─ Flujo de catálogo
            ├─ Política de estado operativo
            ├─ WardRepository
            └─ BedRepository
```

La separación propuesta define componentes conceptuales; sus nombres no afirman clases existentes. El flujo coordina el caso de uso. La política de estado valida que se usen los valores admitidos por cada tabla; no inventa transiciones. Los repositorios encapsulan la lectura y escritura de cada entidad. La creación de una cama recibe una sala identificada y persiste el vínculo obligatorio `ward_id`.

| Componente conceptual | Responsabilidad única | Razón principal para cambiar |
|---|---|---|
| Validador de datos | Comprobar presencia y formato de los datos de entrada requeridos por la operación. | Cambia el contrato de entrada. |
| Política de estado operativo | Reconocer los estados permitidos para ward y bed según sus dominios independientes. | Cambian los valores o reglas de estado acordados y reflejados en el esquema. |
| Flujo de catálogo | Coordinar la creación de la sala o la cama y sus pasos dependientes. | Cambia el proceso del caso de uso. |
| `WardRepository` | Crear y consultar wards, incluyendo la comprobación de código único según persistencia. | Cambia la persistencia o consulta de wards. |
| `BedRepository` | Crear y consultar beds manteniendo la asociación a `ward_id` y la unicidad por sala. | Cambia la persistencia o consulta de camas. |
| Adaptador de respuesta | Convertir el resultado del flujo a la forma requerida por su consumidor. | Cambia el protocolo de salida. |

## Ejemplo concreto de SRP

Supóngase que se añade una validación de formato al dato `location` de la sala. En el diseño mezclado, esa modificación cae en el mismo servicio que decide estados y ejecuta inserciones. En la propuesta, el cambio corresponde al validador de datos: la política de estado, `WardRepository` y `BedRepository` no deberían cambiar mientras sus contratos permanezcan iguales.

De forma análoga, si el esquema cambia el conjunto permitido para `beds.status`, cambia la responsabilidad de política de estado y el soporte de persistencia que debe aceptar ese dominio. No se modifica el validador de `location` ni se reasigna la responsabilidad de coordinar la creación.

La creación de la cama también ofrece un límite claro: el flujo coordina; `BedRepository` persiste el `ward_id` y aplica las restricciones de almacenamiento. El `NOT NULL`, la FK y la unicidad `(ward_id, code)` son garantías de base; una comprobación previa en la aplicación puede mejorar el mensaje, pero no debe sustituir esas garantías.

## Justificación de dependencias

- El flujo depende de contratos de validación, política y persistencia, no de sentencias SQL ni detalles del motor.
- El validador depende de la forma de los datos recibidos, no de la base de datos.
- La política de estados depende exclusivamente de los dominios diferenciados: ward usa `active/inactive`; bed usa `available/occupied/maintenance/inactive`.
- `BedRepository` depende de la identidad y existencia de `ward_id` para persistir la asociación. La referencia restrictiva impide crear camas sin una sala existente.
- Los adaptadores de persistencia implementan los contratos y traducen las operaciones al esquema relacional; la unicidad y la integridad referencial permanecen protegidas también por la base.
- La propuesta no añade una regla que impida crear camas en wards inactivos ni prescribe transiciones de estado: el esquema revisado no contiene esas restricciones.

Así, un cambio en un único motivo no obliga a modificar componentes ajenos y cada componente puede probarse según su contrato. La separación es una propuesta de diseño para el flujo descrito, no una afirmación de que estas clases estén implementadas.

