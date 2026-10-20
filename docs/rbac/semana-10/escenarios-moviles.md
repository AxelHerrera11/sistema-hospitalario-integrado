# Escenarios móviles — decisiones de contenido y error

## Escenario A — Asignar rol con conexión limitada (320 px)

Contexto: la administradora asigna un rol desde el celular en una zona con señal intermitente.

- Decisión de contenido: se muestran primero Usuario, Rol y la acción; los filtros quedan colapsados.
- Al pulsar «Asignar» la petición tarda; el botón pasa a «Enviando…» y se deshabilita para no duplicar.
- Error (timeout/sin red): banner recuperable «No se pudo enviar. Lo capturado permanece.» con `Reintentar`.
- Recuperación: el formulario conserva usuario, rol y hospital; al reintentar no se crea una segunda membresía (idempotencia por usuario+rol+tenant).
- Falla segura: si no hay confirmación del servidor, no se asume asignado; el estado queda «pendiente de confirmación».

## Escenario B — Denegación trazable en pantalla pequeña (320 px)

Contexto: un médico solicita autorizar una operación y es denegado.

- Decisión de contenido: el resultado «⛔ DENEGADO» y el motivo ocupan la parte superior sin scroll; el `trace_id` con botón `Copiar` debajo.
- Los datos de contexto (usuario, hospital) se truncan con «ver más» porque no son críticos para entender la denegación.
- Privacidad: el mensaje no muestra datos de otros tenants ni de otros usuarios.
- Error de red al cargar la auditoría: se muestra «No se pudo cargar la auditoría» con `Reintentar`, sin perder el `trace_id` ya mostrado.
- Accesibilidad: el cambio a estado denegado se anuncia por `aria-live` y no depende solo del color.
