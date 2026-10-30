# Declaración de uso de inteligencia artificial — Semana 3

**Herramienta:** OpenCode con el modelo `deepseek-v4.1-flash-free`.
**Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`).
**Módulo:** Órdenes de laboratorio desde EMR.

## Propósito

Implementar el micro-HIS por capas en PHP 8.2+ vanilla, con dominio, aplicación, persistencia y
presentación separadas, y ejecutar pruebas reales contra un adaptador en memoria, SQLite y
PostgreSQL 16.

## Aportes y límites

- La IA propuso la estructura de clases, los puertos `ExpedienteGateway` y `LabOrderRepository`, el
  esquema portable y la batería de pruebas.
- Las reglas (ítems únicos, episodio abierto, prueba vigente, transiciones, motivo de cancelación e
  idempotencia) provienen de la semana 2 y del contrato #343; no se inventaron tablas ni contratos
  ajenos.
- Las pruebas y sus salidas fueron **ejecutadas** en esta sesión: 25 verificaciones por adaptador y
  75 en total, incluida la base PostgreSQL 16 temporal.
- El adaptador real del expediente y del catálogo **no existe** todavía; se usa un doble en memoria y
  así se declara.

## Validación humana

**Pendiente.** Javier Alexander Fajardo López debe revisar el código, el esquema y la evidencia antes
de publicar el PR.
