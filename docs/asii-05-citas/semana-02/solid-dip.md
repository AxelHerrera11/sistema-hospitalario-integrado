# Aplicación de DIP a la gestión de citas

## Principio

El principio de inversión de dependencias (DIP) propone que los módulos de alto nivel no dependan de los de bajo nivel, sino de **abstracciones**, y que ambas dependan de la abstracción. En este módulo, el dominio de citas (reglas de estado y caso de uso) no debe depender del ORM Eloquent ni de la base de datos concreta.

## Diseño antes de DIP: dependencia concreta

El siguiente esquema es conceptual. Representa una implementación problemática, no código existente:

```text
AppointmentController (alto nivel)
  └─ Appointment (Eloquent, bajo nivel)
       ├─ reglas de estado
       ├─ consultas de solape (GiST)
       └─ SQL

AppointmentServiceAntes
  └─ new EloquentAppointmentRepository()   // se instancia la concreta
```

La lógica de alto nivel crea y usa directamente el modelo/ORM, de modo que el dominio queda acoplado a la persistencia.

### Consecuencias

- Probar el caso de uso exige una base de datos real.
- Cambiar de mecanismo de persistencia obliga a tocar el dominio.
- Las reglas de estado quedan mezcladas con consultas del ORM.
- No puede sustituirse la persistencia por un doble de prueba sin editar el servicio.

## Diseño después de DIP: abstracción y adaptadores

```text
AppointmentController (alto nivel)
  ├─ AppointmentRepositoryContract  (abstracción / puerto)
  └─ AppointmentStatusPolicy        (reglas de estado, sin ORM)
        ▲
        │ implementa
  ┌─────┴───────────────────────────┐
  │ EloquentAppointmentRepository   │ (adaptador real, GiST/Eloquent)
  │ InMemoryAppointmentRepository   │ (adaptador de prueba)
  └─────────────────────────────────┘
        ▲
  AppServiceProvider (bind del contrato al adaptador)
```

| Componente conceptual | Responsabilidad única | Razón principal para cambiar |
|---|---|---|
| `AppointmentRepositoryContract` | Definir las operaciones de citas que el dominio necesita. | Cambia el conjunto de operaciones requeridas por el caso de uso. |
| `AppointmentStatusPolicy` | Decidir transiciones de estado válidas. | Cambian las reglas de estado acordadas. |
| `EloquentAppointmentRepository` | Implementar el contrato con Eloquent y las exclusiones GiST. | Cambia la persistencia o el esquema. |
| `InMemoryAppointmentRepository` | Implementar el contrato en memoria para pruebas. | Cambian las necesidades de prueba. |
| `AppointmentController` | Coordinar el caso de uso sobre las abstracciones. | Cambia el flujo del caso de uso. |
| `AppServiceProvider` | Vincular el contrato a su adaptador. | Cambia la implementación activa. |

## Ejemplo concreto de DIP

Si cambia el esquema de `appointments` (por ejemplo, se ajusta la exclusión GiST), el cambio corresponde a `EloquentAppointmentRepository`; el controlador y la política de estado no se modifican mientras el contrato siga igual. Si cambian las reglas de transición, cambia `AppointmentStatusPolicy`, sin tocar la persistencia.

La evidencia (`evidencia/demo-dip.php`) demuestra que el **mismo `AppointmentController`** funciona con `InMemoryAppointmentRepository` sin cambiar su código, y que en el diseño "antes" el servicio no puede instanciarse sin la base de datos.

## Justificación de dependencias

- El controlador depende de `AppointmentRepositoryContract` y de `AppointmentStatusPolicy`, no de Eloquent ni de `Appointment::`.
- `EloquentAppointmentRepository` es el único punto que conoce el ORM y las exclusiones GiST; las garantías de la base (solape, inmutabilidad) permanecen.
- `AppServiceProvider` (composición) es donde se decide el adaptador, invirtiendo el control de la dependencia.
- La separación es una propuesta de diseño para el flujo descrito, no una afirmación de que estas clases estén implementadas.
