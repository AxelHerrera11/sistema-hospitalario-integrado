# Evidencia verificable — Médicos, especialidades y disponibilidad

## Matriz RF/RNF hacia el diseño

| Requerimiento | Componente que lo cubre |
|---|---|
| RF-MED-01, RF-MED-02 | `DoctorRegistrar` + `DoctorProfileStore` (índices `doctor_profiles_license_number_unique` y `doctor_profiles_active_open_user_unique`) |
| RF-MED-03 | `SpecialtyCatalog`, compartida por los tres clientes |
| RF-MED-04, RF-MED-05 | `SpecialtyAssociator` + `DoctorSpecialtyStore` (restricciones `EXCLUDE` de `doctor_specialties`) |
| RF-MED-06, RF-MED-07 | `AvailabilityService` + `DoctorsBySpecialty` + `DoctorScheduleProvider` + `AppointmentReader` |
| RNF-MED-01 | Middlewares JWT, tenant y RBAC |
| RNF-MED-02 | Transacción única en la conexión `hospital` (perfil + especialidad + `local_access_audits`) |
| RNF-MED-03 | `AppointmentReader` solo expone intervalos ocupados |
| RNF-MED-05 | Seis interfaces pequeñas; métrica de métodos sin usar en la demo |

## Validaciones ejecutadas

```bash
php -l docs/medicos-especialidades/semana-02/evidencia/demo-isp.php   # No syntax errors detected
# comando (PHP 8.3.33, CLI):
php docs/medicos-especialidades/semana-02/evidencia/demo-isp.php
# resultado (código de salida 0):
== Diseño después reproduce el comportamiento del diseño antes ==
PASS  registrar dr.ana con CARD principal → 201
PASS  licencia repetida → 409
PASS  especialidad inactiva → 422
PASS  asociar MED-INT adicional → 201
PASS  misma especialidad otra vez → 409
PASS  NEURO como segunda principal → 409
PASS  disponibilidad CARD sin la franja ocupada → 200

== ISP: ningún cliente depende de métodos que no usa ==
PASS  antes: AvailabilityServiceAntes recibe 4 métodos de escritura que no usa
PASS  antes: DoctorServiceAntes recibe 3 métodos de lectura de agenda que no usa
PASS  después: DoctorRegistrar usa todos los métodos de sus interfaces
PASS  después: SpecialtyAssociator usa todos los métodos de sus interfaces
PASS  después: AvailabilityService usa todos los métodos de sus interfaces
PASS  antes: el adaptador de solo lectura lanza LogicException en createDoctor()
PASS  después: AgendaReadModel no implementa escritura y sirve a AvailabilityService

OK: todas las aserciones pasaron
```

## Evidencia para la defensa

- **¿Qué cambia con ISP?** Antes, todos los clientes dependían de `DoctorDirectory` (8 métodos). Después, cada cliente recibe solo las interfaces que usa: el registro, 2 interfaces; la asociación, 2; la disponibilidad, 4 interfaces de lectura.
- **¿Cómo lo demuestro?** La función `unusedMethods()` de la demo compara los métodos de las interfaces que recibe cada cliente con los que realmente llama: 4 y 3 sin usar antes, 0 después. Además, el adaptador de solo lectura del diseño antes lanza `LogicException`, como `EstablecerDesplazamiento` en el ejemplo de la fuente.
