# Refactor justificado: `EjecutorOperaciones`

## Antes (develop `bbb6ac4`)

```text
HttpTraslados ──JSON → argv──► ConsolaTraslados ──argv → solicitud──► operación ──► VistaResultado
```

- Un controller (HTTP) dependía de otro (consola).
- El JSON se convertía en argumentos posicionales con `(string) $valor`. Un `motivo` enviado como arreglo se convertía en `"Array"` y **el traslado se aceptaba (201) con ese motivo**.

## Después (este PR)

```text
HttpTraslados ──campos con nombre──► EjecutorOperaciones ──► operación ──► VistaResultado
ConsolaTraslados ──argv → campos──┘
```

- Cada controller solo traduce su protocolo; `EjecutorOperaciones` es el único que arma la solicitud y la operación.
- Un campo que no es escalar cuenta como ausente, así que el motivo como arreglo ahora responde 422.
- Las dependencias se siguen pidiendo solo si la solicitud es válida.

## Justificación y alcance

| Criterio | Resultado |
|---|---|
| Acoplamiento | Se elimina la dependencia de controller a controller |
| Corrección | Corrige un defecto real, verificado con el código anterior: `motivo: ["x"]` daba 201 antes y da 422 después |
| Comportamiento | Las 88 verificaciones previas siguen pasando sin cambios |
| Alcance | Solo Presentation; Domain, Application y Persistence no cambian ni se agregan funciones |

Evidencia: `tests/ejecutor.php` (7 verificaciones nuevas, 95/95 en total).
