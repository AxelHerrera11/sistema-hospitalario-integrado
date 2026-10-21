# Escenarios móviles

Escenarios de diseño, no observaciones de usuarios reales.

## E1 - Enfermera verifica la vigencia en planta (lectura)

**Contexto:** teléfono de 360 px, señal intermitente en el pasillo, una sola mano. Rol `enfermeria` con `medicamentos.ver`.

| Paso | Pantalla | Decisión de contenido | Decisión ante error |
|---|---|---|---|
| 1 | M1 | El hospital activo se ve bajo el título: confirma que consulta su catálogo | Sin sesión: va al inicio de sesión (R1) |
| 2 | M1 | Escribe "amox" y pulsa Enter (RM7) | Con 1 carácter: mensaje junto al campo, sin enviar |
| 3 | M2 | Lo primero que ve es el **código y la vigencia en texto** (RM2, RM6) | Sin red: "No se pudo consultar el catálogo" + [Reintentar]; los resultados anteriores no se presentan como actuales |
| 4 | M3 | El detalle muestra el grupo y la vigencia; no ve acciones de escritura | Un 404: "El medicamento ya no está disponible" y vuelta a M2 |

**Resultado esperado:** distingue un medicamento inactivo sin abrir el detalle y nunca confunde "sin coincidencias" con "sin conexión".

## E2 - Gestor inactiva un medicamento con conexión lenta (escritura propuesta)

**Contexto:** teléfono de 412 px, red 3G lenta. Rol `admin_hospital` con el permiso propuesto `medicamentos.cambiar_vigencia`.

| Paso | Pantalla | Decisión de contenido | Decisión ante error |
|---|---|---|---|
| 1 | M2 → M3 | Llega al detalle desde la tarjeta "Ver detalle de AMX-500" (nombre accesible con el código) | — |
| 2 | M3 | La hoja de confirmación repite el código y el nombre y dice que no borra ni afecta lotes | Atrás o Esc cierran sin cambios (RM5) |
| 3 | M3 | Confirma con "Inactivar AMX-500"; el botón se desactiva mientras espera | Timeout: "No sabemos si el cambio se guardó" y se vuelve a consultar la vigencia real antes de ofrecer repetir |
| 4 | M3 | Con 200: "Vigencia actualizada" en `role="status"` y la vigencia pasa a "Inactivo" según la respuesta | Un 503: "No se confirmó el cambio" y se muestra el estado real; nunca un éxito optimista |

**Resultado esperado:** el gestor no inactiva el medicamento equivocado ni cree que se guardó un cambio no confirmado.
