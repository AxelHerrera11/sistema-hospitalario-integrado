# Dos escenarios móviles

## E-1 · Médica en ronda de sala con señal débil (teléfono, 375 px)

**Contexto:** la Dra. López revisa a la paciente P-0001 junto a la cama, con una mano ocupada y red móvil lenta.

| Paso | Contenido mostrado | Decisión de contenido | Error posible y decisión |
|---|---|---|---|
| 1 | Búsqueda (M-1) | Código con ayuda y ejemplos; teclado sin autocorrector | Código mal escrito → error bajo el campo, no se envía (V-01) |
| 2 | Cargando… | Texto visible, botones deshabilitados (evita toques dobles) | 10 s sin respuesta → «Sin conexión», «Reintentar» (intento 1 de 3) |
| 3 | Expediente (M-2) | Estado y ficha primero; resumen en chips; episodio abierto arriba | 503 de auditoría → «Datos protegidos», ningún dato (N-12) |
| 4 | Motivo | Plegado; se abre solo el episodio de interés | — |
| 5 | Salir | «Ocultar datos del paciente» antes de guardar el teléfono | Si cambia de usuario/hospital, los datos se quitan solos |

## E-2 · Recepción abre el expediente y la conexión se corta (320 px)

**Contexto:** Diego Mora registra la primera atención de P-0002 en un teléfono de 320 px; la red cae justo después
de confirmar.

| Paso | Contenido mostrado | Decisión de contenido | Error posible y decisión |
|---|---|---|---|
| 1 | Búsqueda + «Abrir expediente…» | Botón secundario a todo el ancho | — |
| 2 | Diálogo (M-4) | Código y hospital en negrita; consecuencia en una frase | Toque accidental → foco y primer botón es «Cancelar» |
| 3 | Sin respuesta | «Sin conexión. Reintentar es seguro: si la apertura ya se hizo, se mostrará el mismo expediente.» | Reintento → 200 `created=false` con el mismo número (A3, N-09) |
| 4 | Resultado | «El paciente ya tenía el expediente EXP-P-0002; no se creó otro.» + «Consultar expediente» | Recepción pulsa «Consultar» → 403 (no tiene `expediente.ver`): mensaje de acceso denegado sin datos |

## Evidencia

- Lógica de ambos escenarios: `tests/ui-estados.test.mjs` (V-01, V-05, V-06, V-07) y caja negra N-09/N-12.
- Pantallas reales: capturas `m-*` en `../semana-11/evidencias/`.
- La caída de red real en un teléfono **no** se simuló en un dispositivo físico (pendiente manual).
