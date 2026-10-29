# Gestion de citas medicas y agenda - Semana 7

> Tema guia: **Diseno de componentes y refactorizacion**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-05` | **Issue:** [#207](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/207)
- **Proceso:** registro y consulta de citas medicas validando disponibilidad
- **Principio SOLID aplicado:** Dependency Inversion (DIP)
## Entregables

- [Diagrama de componentes backend/frontend](./01-componentes.svg) | [fuente](./fuentes/01-componentes.puml)
- Contratos de entrada/salida por componente (tabla abajo).
- Refactor ejecutable: [`antes.php`](./refactor/antes.php), [`despues.php`](./refactor/despues.php) y
  [`verificar.php`](./refactor/verificar.php), que prueba que ambos producen la misma salida.

## Componentes

El frontend se organiza en vista -> componentes -> store (Pinia) -> cliente API; el backend en
controlador -> casos de uso -> entidad -> Repository -> adaptador. La unica dependencia entre ambos
es el contrato REST de la Semana 5, por lo que cada lado puede cambiar sin romper al otro.

## Contratos de entrada/salida

| Componente | Entrada | Salida | Errores |
|---|---|---|---|
| `CitaForm.vue` | paciente, doctor, desde, hasta (formulario) | evento `guardar(nuevaCita)` | mensajes de validacion en linea |
| `useCitasStore` | `agendar(nuevaCita)` | estado `citas[]`, `cargando`, `error` | `error.code` del sobre comun |
| `citasApi.js` | `POST /api/v1/citas` + JWT + X-Tenant-ID | `{data}` | `{code, message}` (409/422) |
| `CitaController` | Request HTTP validada | 201 `{data}` | 409 `appointments_overlap`, 409 `cita_rango_invalido` |
| `AgendarCita` | `(paciente, doctor, desde, hasta)` | `Cita` | `SolapeCitaException`, `RangoFechaInvalidoException` |
| `CitaRepository` | `guardar(Cita)` / `todas()` | `void` / `list<Cita>` | `ErrorPersistenciaException` |

## Refactorizacion antes/despues

| Tecnica | Aplicacion |
|---|---|
| Extract Value Object | `Horario` encapsula el rango y su invariante (`desde < hasta`). |
| Move Method | el calculo de solape pasa de la funcion a `Horario::seSolapaCon`. |
| Decompose Conditional | tres `if` anidados se reemplazan por `CitaAgendada::bloquea` con nombre de intencion. |
| Replace loose with strict comparison | `==` pasa a `===` e `in_array(..., true)`. |
| Separate Query from Modifier | la validacion de entrada queda fuera de la regla de negocio (`Agenda`). |

**Justificacion:** el codigo *antes* mezclaba decisiones en condicionales anidados y duplicados, lo
que hace dificil agregar casos sin romper otros. El codigo *despues* deja cada regla en un lugar con
nombre propio y aplica Dependency Inversion (DIP). El refactor no cambia comportamiento: `verificar.php` ejecuta los
mismos escenarios contra ambas versiones y compara la salida.



## Evidencia

```bash
cd docs/asii-05-citas/semana-07
php refactor/verificar.php
```

```text
  [IGUAL] camino feliz -> {"status":201,"error":null}
  [IGUAL] solape paciente -> {"status":409,"error":"appointments_overlap"}
  [IGUAL] solape doctor -> {"status":409,"error":"appointments_overlap"}
  [IGUAL] contigua (limite) -> {"status":201,"error":null}
  [IGUAL] rango invertido -> {"status":409,"error":"cita_rango_invalido"}
  [IGUAL] faltan campos -> {"status":422,"error":"validation_failed"}

Comportamiento: 6/6 escenarios iguales.
Antes:   22 lineas, anidamiento max. 5, 5 sentencias if.
Despues: 57 lineas, anidamiento max. 4, 3 sentencias if.
```

## Regeneracion de diagramas

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-05-citas/semana-07/fuentes/*.puml -o ..
```
