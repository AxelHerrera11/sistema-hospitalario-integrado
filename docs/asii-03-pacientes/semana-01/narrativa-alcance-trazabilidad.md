# Narrativa de alcance y trazabilidad — ASII-03 Pacientes

## Propósito

El módulo de pacientes proporciona la base para identificar a las personas que reciben atención dentro del Sistema Hospitalario Integrado.

Durante la Semana 1 se modela el proceso de **registro, búsqueda y actualización segura de un paciente**.

El objetivo no es implementar todavía la funcionalidad, sino establecer de manera clara los actores, operaciones, validaciones, excepciones y resultados que deberán mantenerse durante las siguientes semanas.

---

## Alcance funcional

### Incluido

El proceso contempla:

- Registrar un nuevo paciente.
- Validar la información necesaria para el registro.
- Buscar pacientes existentes.
- Mostrar resultados de búsqueda.
- Consultar el detalle de un paciente encontrado.
- Verificar la existencia de un paciente antes de modificarlo.
- Actualizar información de un paciente.
- Validar los datos antes de realizar una actualización.
- Informar errores de validación.
- Informar cuando no se encuentra un paciente.

### Fuera de alcance en Semana 1

No se implementan todavía:

- Endpoints API.
- Controllers.
- Services.
- Requests.
- Pantallas Vue.
- Permisos definitivos.
- Integración con expediente médico.
- Integración con citas.
- Integración con admisiones.
- Sincronización funcional con el índice global de pacientes.

Estos elementos corresponden a semanas posteriores.

---

## Datos considerados

La estructura actual del proyecto contiene `local_patients` en la base HOSPITAL.

Entre los datos existentes se encuentran:

- `local_patient_id`
- `global_id`
- `code`
- `given_name`
- `family_name`
- `birth_date`
- `sex`
- `contact`
- `status`
- `mpi_link_status`

El modelo conceptual de esta semana toma esta estructura como referencia para evitar documentar información que contradiga al proyecto actual.

---

## Reglas consideradas

### RN-PAC-01

Un paciente debe contar con los datos necesarios antes de poder registrarse.

### RN-PAC-02

La fecha de nacimiento no puede corresponder a una fecha futura.

### RN-PAC-03

Los valores utilizados en campos controlados deben pertenecer a las opciones permitidas por el sistema.

### RN-PAC-04

No debe actualizarse un paciente que no exista.

### RN-PAC-05

Una actualización inválida no debe reemplazar la información previamente almacenada.

### RN-PAC-06

Una búsqueda sin coincidencias debe producir una respuesta comprensible y no un registro ficticio.

---

## Actores

### Usuario autorizado

Es el actor principal.

Puede iniciar:

- Registro.
- Búsqueda.
- Consulta.
- Actualización.

La definición exacta de los roles que podrán realizar cada operación se realizará cuando corresponda diseñar permisos y contrato API.

### Sistema Hospitalario Integrado

Actor de soporte encargado de:

- Recibir la solicitud.
- Validar información.
- Consultar registros.
- Persistir información válida.
- Rechazar operaciones incorrectas.
- Retornar un resultado.

---

## Casos de uso

### UC-PAC-01 · Registrar paciente

**Actor:** Usuario autorizado.

**Objetivo:** crear el registro de un nuevo paciente con información válida.

**Flujo principal:**

1. El usuario selecciona registrar paciente.
2. Ingresa la información solicitada.
3. El sistema valida los datos.
4. La información es válida.
5. El sistema registra el paciente.
6. Se devuelve una confirmación.

**Excepción:**

Si algún dato no es válido, el sistema no registra al paciente y devuelve los errores encontrados.

---

### UC-PAC-02 · Buscar paciente

**Actor:** Usuario autorizado.

**Objetivo:** localizar uno o más pacientes existentes.

**Flujo principal:**

1. El usuario proporciona un criterio de búsqueda.
2. El sistema consulta los registros.
3. Se encuentran coincidencias.
4. El sistema devuelve los resultados.

**Excepción:**

Si no existen coincidencias, el sistema informa que no se encontraron pacientes.

---

### UC-PAC-03 · Consultar detalle

**Actor:** Usuario autorizado.

**Objetivo:** visualizar la información disponible de un paciente seleccionado.

**Precondición:**

El paciente debe existir.

**Resultado:**

El sistema presenta la información correspondiente al paciente.

---

### UC-PAC-04 · Actualizar paciente

**Actor:** Usuario autorizado.

**Objetivo:** modificar información válida de un paciente existente.

**Flujo principal:**

1. El usuario selecciona el paciente.
2. El sistema verifica su existencia.
3. El usuario modifica los datos.
4. El sistema valida la nueva información.
5. Los cambios son válidos.
6. El sistema actualiza el registro.
7. Se devuelve una confirmación.

**Excepciones:**

- El paciente no existe.
- Los nuevos datos contienen errores.

En ambos casos no se modifica el registro.

---

### UC-PAC-05 · Validar datos

Caso utilizado por registro y actualización.

Valida que la información requerida cumpla las reglas definidas.

---

### UC-PAC-06 · Verificar existencia

Caso utilizado antes de consultar o actualizar un paciente.

Evita ejecutar operaciones sobre registros inexistentes.

---

## Trazabilidad

| Caso de uso | Actividad | Secuencia |
|---|---|---|
| UC-PAC-01 Registrar | Rama Registrar → ingresar datos → validar → guardar | Usuario → Interfaz → Gestión → HOSPITAL |
| UC-PAC-02 Buscar | Rama Buscar → ingresar criterio → consultar | Usuario → Interfaz → Gestión → HOSPITAL |
| UC-PAC-03 Detalle | Resultado encontrado → seleccionar paciente | Interfaz → Gestión → HOSPITAL |
| UC-PAC-04 Actualizar | Verificar existencia → validar → actualizar | Usuario → Interfaz → Gestión → HOSPITAL |
| UC-PAC-05 Validar | Decisión ¿datos válidos? | Gestión realiza validación |
| UC-PAC-06 Existencia | Decisión ¿paciente existe? | Gestión consulta HOSPITAL |

---

## Correspondencia de resultados

### Registro válido

Actividad:

`Validar datos → Registrar paciente → Confirmación`

Secuencia:

`Usuario → UI → Gestión → HOSPITAL → Gestión → UI`

Resultado:

Paciente registrado.

### Registro inválido

Actividad:

`Validar datos → Error`

Secuencia:

`Gestión → UI: errores de validación`

Resultado:

No se registra información.

### Búsqueda con resultados

Actividad:

`Buscar → Existen coincidencias → Mostrar resultados`

Secuencia:

`Gestión → HOSPITAL → resultados → UI`

Resultado:

Lista de pacientes.

### Búsqueda sin resultados

Actividad:

`Buscar → Sin coincidencias`

Secuencia:

`Gestión → UI: no encontrado`

Resultado:

Mensaje informativo.

### Actualización correcta

Actividad:

`Verificar existencia → Validar nuevos datos → Actualizar`

Secuencia:

`Gestión → HOSPITAL: verificar → actualizar`

Resultado:

Información actualizada.

### Actualización inválida

Actividad:

`Validar nuevos datos → Rechazar`

Secuencia:

`Gestión → UI: errores`

Resultado:

No se modifican los datos existentes.

---

## Dependencias conceptuales

El módulo de pacientes será utilizado posteriormente por otros procesos del sistema.

Entre ellos:

- Expediente médico.
- Citas médicas.
- Admisiones.
- Alergias.
- Signos vitales.
- Órdenes de laboratorio.

Semana 1 únicamente reconoce estas dependencias; no implementa todavía integración técnica.

---

## Resultado final

Los diagramas deben representar el mismo proceso desde tres perspectivas:

**Casos de uso:** qué quiere lograr el actor.

**Actividad:** qué decisiones y excepciones ocurren.

**Secuencia:** qué participantes intercambian mensajes para obtener el resultado.

La correspondencia documentada permite mantener trazabilidad entre los tres artefactos.
