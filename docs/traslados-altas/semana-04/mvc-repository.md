# MVC y Repository — Semana 4

Continúa el mismo micro-HIS de la Semana 3 ([`micro-his/traslados-altas`](../../../micro-his/traslados-altas/README.md)); no se creó otra implementación.

## MVC en el micro-HIS

| Rol MVC | Clase | Responsabilidad | Lo que NO hace |
|---|---|---|---|
| Controller | `Presentation/ConsolaTraslados` | Convierte argumentos en `SolicitudTraslado` o `SolicitudAlta`, pide las dependencias solo si la solicitud es válida y ejecuta la operación | SQL, reglas de dominio, formato de salida |
| Model | `Domain/*` y `Application/*` | Reglas (`ReglasLiberacion`), planes, casos de uso (`TrasladoInterno`, `AltaHospitalaria`) y el protocolo transaccional | Conocer la consola, el JSON o PDO |
| View | `Presentation/VistaResultado` | Traducir cada uno de los cuatro resultados a un código de salida y un JSON con identificadores | Decidir si la operación tuvo éxito |
| Raíz de composición | `bin/consola.php` | Crear los adaptadores PDO y conectarlos al controller | Lógica de negocio |

`tests/arquitectura.php` lo verifica en cada ejecución: Presentation no puede contener PDO, Persistence ni sentencias SQL, y Domain no depende de ninguna otra capa.

## Contrato Repository

`Application/RegistroInternacion` es el Repository de la internación y, a la vez, delimita la unidad de trabajo: una transacción HOSPITAL por operación (RNF-TA-03).

| Operación | Contrato |
|---|---|
| `leerPorEstancia(id)` | Episodio y su estancia abierta, con bloqueo; `null` si la estancia no existe |
| `leerCama(id, T)` | Estado y libertad desde T, con bloqueo; `null` si la cama no existe |
| `ahora()` | T tomado del reloj de HOSPITAL |
| `cerrarEstancia`, `abrirEstancia`, `cerrarEpisodio` | Escrituras dentro de la transacción |
| `confirmar()` | `FalloPersistencia` si se sabe que no se confirmó; `ConfirmacionIncierta` si no se sabe |
| `revertir()` | Nunca lanza |

## Adaptadores

| Adaptador | Uso | Garantías |
|---|---|---|
| `Persistence/PdoRegistroInternacion` | PostgreSQL real | Sentencias preparadas, `FOR UPDATE`, restricciones y triggers de HOSPITAL |
| `Persistence/InMemoryRegistroInternacion` | Demostraciones y pruebas sin base | Instantánea por transacción; **sin** atomicidad entre procesos, concurrencia ni restricciones |
| `tests/RegistroFalso` (extiende InMemory) | Pruebas de flujo | Agrega el registro de llamadas y las fallas inyectables |

Ambos adaptadores productivos cumplen la misma especificación ejecutable, `tests/contrato-registro.php`: 8/8 con InMemory y 8/8 con PostgreSQL. Un adaptador nuevo es correcto si pasa esa batería; Application y Domain no cambian.

## Reutilización

- **Dentro del módulo:** `ProtocoloLiberacion` es el único flujo transaccional y lo reutilizan el traslado y el alta (LSP). `VistaResultado` se reutilizará en la API (Semana 5).
- **Entre adaptadores:** el InMemory comparte el contrato y la batería con el PDO, y `RegistroFalso` lo reutiliza en lugar de duplicar el almacenamiento.
- **Con otros módulos:** ver [repositorio-compartido.md](./repositorio-compartido.md).
