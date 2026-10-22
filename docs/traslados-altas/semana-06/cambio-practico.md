# Cambio práctico documentado — `Retry-After` en el 503

**Tipo:** ejercicio **simulado**, propuesto por la autora como práctica. No responde a ninguna solicitud del docente.

| | Antes (#849) | Después (este PR) |
|---|---|---|
| 503 (fallo confirmado, sin cambios) | Sin indicación de cuándo reintentar | `Retry-After: 5` |
| 500 (resultado incierto) | Sin `Retry-After` | Sin cambios: sigue sin `Retry-After`; primero hay que consultar |
| Conexión imposible en `public/index.php` | 503 sin cabecera | 503 con `Retry-After: 5` |

**Motivo:** RNF-TA-06 distingue entre un fallo confirmado (reintentar es seguro) y un resultado incierto (reintentar a ciegas es peligroso). La cabecera hace visible esa diferencia en el protocolo.

**Impacto:**
- Solo Presentation (`HttpTraslados::conCabeceras`) y el front controller.
- Domain, Application y Persistence no cambian.
- El contrato común LSP no se toca.

**Evidencia:**
- `tests/http.php`: el 503 lleva `Retry-After: 5` y el 500 no lleva cabeceras (88/88).
- `php -S` con HOSPITAL inaccesible: `HTTP/1.1 503`, `Retry-After: 5`, `Cache-Control: no-store`.
