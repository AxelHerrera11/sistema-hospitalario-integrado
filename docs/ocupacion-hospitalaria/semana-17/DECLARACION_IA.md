# Declaración de uso de IA — Semana 17 (ASII-09)

- **Herramienta:** OpenCode (modelo `minimax-m3.1-flash-free`), por indicación expresa de Javier
  Fajardo López, para redactar la matriz de amenazas, la bitácora de despliegue y las pruebas de
  permisos y datos sensibles de la semana 17.
- **Propósito:** levantar las amenazas del endpoint `GET /api/v1/occupancy` y de su pantalla,
  documentar los pasos de despliegue con evidencia real y escribir las pruebas que comprueban que
  la respuesta no filtra datos de pacientes ni detalles internos.
- **Contenido aceptado:** la estructura de la matriz (amenaza, vector, control, estado y
  evidencia), los pasos de despliegue y la bitácora con el resultado literal del intento de
  levantar el servidor.
- **Contenido verificado con comandos reales:** el listado de rutas, las pruebas, la compilación
  de Vite, el valor de `config('app.debug')` con `APP_DEBUG=false` y la respuesta `HTTP 500` con
  `Class "App\Models\Tenant" not found`, que confirma la dependencia con ASII-25.
- **Verificación humana pendiente:** Javier revisa esta declaración, la matriz de amenazas y la
  bitácora antes de publicar.
- **Datos usados:** únicamente metadatos de esquema y respuestas de prueba con datos ficticios; no
  hay datos de pacientes, credenciales ni tokens en el repositorio.
