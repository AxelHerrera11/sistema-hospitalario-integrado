# Declaración de uso de inteligencia artificial — Semana 4

**Herramienta:** OpenCode con el modelo `deepseek-v4.1-flash-free`.
**Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`).
**Módulo:** Órdenes de laboratorio desde EMR.

## Propósito

Formalizar la entrada MVC sobre el micro-HIS de la semana 3, documentar el contrato del Repository
con sus dos adaptadores y analizar cómo se integraría un repositorio de datos compartido.

## Aportes y límites

- La IA propuso el controlador, la vista HTML y el análisis de integración del repositorio compartido.
- El contrato del Repository y los adaptadores ya existían en la semana 3; esta semana los formaliza y
  los prueba a través del controlador, sin inventar tablas ni contratos de otros módulos.
- Las 12 verificaciones MVC se ejecutaron realmente con `php tests/mvc.php`; la batería de la semana 3
  sigue pasando con InMemory, SQLite y PostgreSQL 16.
- Los permisos y la autenticación real llegan con la API de la semana 5; aquí el controlador recibe un
  usuario ya resuelto y representa el 401.

## Validación humana

**Pendiente.** Javier Alexander Fajardo López debe revisar el controlador, la vista y el análisis antes
de publicar el PR.
