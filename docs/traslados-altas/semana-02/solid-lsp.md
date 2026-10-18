# Aplicación de LSP al flujo de traslado o alta

**Módulo:** `ASII-08` · **Issue:** `Refs #9` · **Requisitos:** [RF/RNF y criterios](./rf-rnf-criterios.md).

## Principio

El principio de sustitución de Liskov (LSP) exige que un cliente que depende de un contrato pueda recibir cualquier implementación de ese contrato sin dejar de funcionar. Una implementación sustituible no puede pedir más que el contrato (precondiciones más fuertes) ni garantizar menos o algo distinto (postcondiciones más débiles o contradictorias), y debe conservar sus invariantes. Compartir el nombre de un método no basta.

## Diseño antes

> **Ejemplo didáctico.** Las clases de esta sección no existen en el repositorio: no hay servicio, modelo ni API de traslados o altas. Se construyen solo para mostrar el incumplimiento.

La idea de partida es que "traslado y alta liberan cama", por lo que `TrasladoInterno` hereda de `AltaHospitalaria` y sobrescribe `registrar()`.

```mermaid
classDiagram
    direction LR
    class CierreDeEpisodio {
        +finalizar(AltaHospitalaria operacion, episodioId, estanciaEsperadaId, T)
    }
    class AltaHospitalaria {
        +registrar(episodioId, estanciaEsperadaId, T) Resultado
    }
    class TrasladoInterno {
        -camaDestinoId
        -reason
        +registrar(episodioId, estanciaEsperadaId, T) Resultado
    }
    CierreDeEpisodio --> AltaHospitalaria : depende del contrato
    AltaHospitalaria <|-- TrasladoInterno : herencia incorrecta
```

### Contrato que promete `AltaHospitalaria::registrar()`

| Elemento | Contenido del contrato del ejemplo |
|---|---|
| Entrada | Episodio, estancia esperada y `T`. |
| Precondiciones | Episodio `inpatient` y abierto; estancia actual = esperada; `T` posterior al inicio del episodio y de la estancia. |
| Rechazos declarados | `EPISODIO_NO_INPATIENT`, `EPISODIO_NO_ABIERTO`, `SIN_ESTANCIA_ABIERTA`, `ESTANCIA_DESACTUALIZADA`, `T_INVALIDA`, sin cambios persistidos. |
| Postcondiciones ante `ExitoConfirmado` | La estancia esperada termina en `T`; el episodio queda `closed` con `ended_at = T`; el episodio no tiene estancias abiertas. |

### Cómo lo incumple `TrasladoInterno`

**Postcondiciones incompatibles.** Un traslado correcto (RF-TA-06) deja el episodio **abierto** y crea una estancia destino `[T, ∞)` **abierta**. Eso contradice dos postcondiciones de la clase base: "episodio `closed` en `T`" y "sin estancias abiertas". No es un caso particular de alta: si el traslado cumpliera esas postcondiciones, dejaría de ser un traslado.

**Requisitos adicionales.** Para ejecutarse, el traslado necesita datos y condiciones que el contrato base no contiene:

- cama destino y `reason` no vacío (RF-TA-01), que `registrar()` no recibe; quedan como estado interno que el cliente del contrato base no conoce ni puede proporcionar;
- condiciones sobre el destino (RF-TA-05): que exista, sea distinto del origen, no tenga solapes con `[T, ∞)` y, según la decisión propuesta, tenga `status = available`.

Tener cama destino y `reason` como datos específicos en el constructor de `TrasladoInterno` no viola LSP por sí solo. El incumplimiento demostrado aquí son las postcondiciones incompatibles y los rechazos de destino (`DESTINO_*`), que exceden los declarados por el contrato de `AltaHospitalaria`; un cliente escrito contra ese contrato no tiene por qué esperarlos.

### Consecuencia para el cliente

`CierreDeEpisodio::finalizar()` (también didáctico) recibe una `AltaHospitalaria`, llama a `registrar()` y, ante `ExitoConfirmado`, confía en las postcondiciones: informa que el episodio quedó cerrado en `T` y que ya no ocupa ninguna cama.

Si recibe un `TrasladoInterno`, el código compila y la llamada termina con éxito, pero el cliente informa un cierre que no ocurrió: el episodio sigue abierto y ocupa la cama destino. Introducir una comprobación del tipo concreto (`instanceof TrasladoInterno`) sería un parche que acoplaría el cliente a las subclases, no una corrección del contrato. La solución estructural se presentará en el diseño después; la herencia falla en el diseño, no solo en la ejecución.
