# Capas, responsabilidades y objetos reutilizables

## Responsabilidades

| Capa | Objeto | Responsabilidad |
|---|---|---|
| Presentation | PatientController | Recibir la entrada y coordinar los casos de uso |
| Presentation | PatientView | Preparar la salida del módulo |
| Application | PatientService | Ejecutar registro, búsqueda y actualización |
| Application | PatientRepository | Definir el contrato de persistencia |
| Domain | Patient | Proteger reglas e invariantes del paciente |
| Persistence | PdoPatientRepository | Persistir datos mediante PDO |
| Persistence | InMemoryPatientRepository | Persistir en memoria para pruebas |

## MVC

Flujo principal:

Usuario / Entrada -> PatientController -> PatientService

PatientService -> Patient

PatientService -> PatientRepository

PatientRepository -> PdoPatientRepository

PatientRepository -> InMemoryPatientRepository

PatientController -> PatientView -> Usuario / Salida

El Controller coordina la entrada y no contiene SQL ni reglas de dominio.

Las reglas se mantienen en Domain y Application.

PatientView transforma el resultado a una representación de salida.

## Patrón Repository

PatientService depende de PatientRepository y no de PDO directamente.

Esto permite sustituir la infraestructura sin modificar los casos de uso.

PatientService
      |
      v
PatientRepository
     / \
    /   \
 PDO   InMemory

## Objetos reutilizables

PatientService puede utilizarse desde una API HTTP, una CLI o pruebas automatizadas.

PatientRepository permite agregar otros adaptadores sin modificar Application.

PatientView centraliza la representación de los datos del paciente.

Patient conserva las reglas relacionadas con identidad y datos demográficos.

## Repositorio de datos compartido

Si el HIS incorpora posteriormente un repositorio compartido, se puede crear:

SharedPatientRepository implements PatientRepository

De esta manera no sería necesario modificar PatientService, PatientController ni Patient.

La integración deberá respetar aislamiento por hospital, permisos, trazabilidad y propiedad de los datos.

Los detalles del contrato de integración se definirán en la Semana 05.
