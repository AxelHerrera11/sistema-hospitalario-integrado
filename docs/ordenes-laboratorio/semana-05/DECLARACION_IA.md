# Declaración de uso de inteligencia artificial — Semana 5

**Herramienta:** OpenCode con el modelo `deepseek-v4.1-flash-free`.
**Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`).
**Módulo:** Órdenes de laboratorio desde EMR.

## Propósito

Publicar la API HTTP del micro-HIS, documentar su contrato OpenAPI, implementar un cliente con
timeout y reintento seguro y demostrar la comunicación cliente–servidor con evidencia real.

## Aportes y límites

- La IA propuso el `ApiKernel`, el front controller, el cliente HTTP, el contrato OpenAPI y las
  baterías de pruebas.
- El kernel confía en `X-Tenant-ID` y `X-User-Ref`; se declara explícitamente que la validación de
  tokens y roles corresponde al gateway de producción y **no** está implementada aquí.
- Las 17 verificaciones del kernel y las 6 del cliente se ejecutaron realmente, incluida una prueba
  HTTP con `php -S` y base SQLite. No se simularon salidas.
- No se inventaron endpoints de otros módulos ni tablas compartidas; el contrato solo cubre órdenes.

## Validación humana

**Pendiente.** Javier Alexander Fajardo López debe revisar el contrato, el kernel y la evidencia antes
de publicar el PR.
