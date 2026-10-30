# Semana 11 — Prototipo navegable y API real

Vue consume la API Laravel. ReceptionApiTest ejecuta middleware JWT/RBAC real, con identidad CENTRAL de prueba SQLite y base clínica PostgreSQL en esquemas aislados. No utiliza HTTP fake para la API. Para ejecutar: php artisan test tests/Feature/Reception/ReceptionApiTest.php. Las credenciales se configuran por entorno de prueba; solo se aceptan bases terminadas en _test.


## Mockup del prototipo (plan, semana 11)

Prototipo navegable real en `/recepcion-muestras` (Vue 3), con la API Laravel. Capturas de escritorio y móvil en `docs/recepcion-muestras/evidencias/` (`recepcion-desktop.png`, `recepcion-movil.png`, generadas en la entrega final de la pila).

```
Escritorio (1440x900)                         Móvil (390x844)
+--------------------------------------+      +--------------------+
| Recepción de muestras                |      | Recepción          |
| [Buscar ítem pendiente.........][Q]  |      | [Buscar ítem...][Q]|
| Resultados:  item · paciente · tipo  |      | > item · tipo      |
|--------------------------------------|      |--------------------|
| Paciente (código, nombre observados) |      | Código / nombre    |
| Tipo [blood v] Contenedor [.......]  |      | Tipo / contenedor  |
| Volumen [..] Temp [..] Recolección   |      | Volumen / temp.    |
| [ ] hemolizada [ ] coagulada [ ] ... |      | [ ] flags          |
| [Recibir muestra]                    |      | [Recibir muestra]  |
|--------------------------------------|      |--------------------|
| Resultado: ACEPTADA · código  [Imprimir]    | Resultado · [Imprimir]
+--------------------------------------+      +--------------------+
```

Reglas de interacción: el rol lector ve consulta y oculta recepción; la identidad observada no se autocompleta al seleccionar un ítem; ante respuesta incierta se conserva la clave para reintentar; las pruebas de navegador cubren cuatro tamaños de pantalla.
