# ASII-14 - Catálogo de medicamentos - Semana 15

- **Tema:** pruebas unitarias, de caja blanca y de caja negra
- **Issue:** [#165](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/165)

| Requisito | Evidencia |
|---|---|
| ≥ 3 unitarias | U1–U3 |
| ≥ 2 de caja blanca con ramas y condiciones | W1 (5 ramas) y W2 (4 ramas) |
| ≥ 4 de caja negra: equivalencias, límites, permisos y errores | B1–B4, más la regresión B5 |
| Matriz RF/RNF → prueba | [matriz-pruebas.md](./matriz-pruebas.md) |
| Comandos, resultados y capturas | [Ejecución](./matriz-pruebas.md#ejecución-20102026-php-8212) y `capturas/` |
| Defectos registrados | [Registro](./matriz-pruebas.md#registro-de-defectos): AV3 y AV1 corregidos esta semana |

**No es una simulación de RBAC:** B3 lee la matriz real de `config/rbac.php`. El micro-HIS no aplica permisos todavía, y esa brecha está declarada.

[Declaración de uso de IA](./DECLARACION_IA.md)
