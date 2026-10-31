# Semana 12 — Recuperación e impresión

El cliente conserva clave y observaciones ante respuestas inciertas y recupera con el mismo POST idempotente. Advierte antes de cerrar la pestaña durante una operación pendiente. Reimprime mediante GET protegido sin repetir la recepción. El runner de navegador usa playwright y @axe-core/playwright instalados en el entorno de pruebas.


## Guía de la segunda evaluación parcial (plan, semana 12)

**Caso práctico de diseño:** la respuesta del POST se pierde y el técnico ve un error. ¿Cómo se evita duplicar la muestra y cómo se reimprime la etiqueta?

1. El cliente genera `request_key` (UUID) y conserva clave y observaciones.
2. Reintenta el mismo POST: el servidor devuelve la muestra original si el contenido coincide (409 si difiere).
3. La reimpresión usa `GET /lab/specimens/{code}/label` con `muestras.etiquetar`; no repite la recepción.
4. Si el usuario intenta cerrar la pestaña con una operación pendiente, el navegador advierte.

**Preguntas de defensa:** ¿qué garantiza la unicidad del código? (restricción UNIQUE y reintento ante colisión); ¿por qué el GET no es sustituto del POST? (consulta no crea registro); ¿qué decisión de diseño cambió respecto al micro? (adaptador por puerto sin tocar el caso de uso).
