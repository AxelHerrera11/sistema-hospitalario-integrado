# Tablero de indicadores (dashboard base) - Semana 15

> Tema guia: **Pruebas unitarias, caja blanca y caja negra**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-26` | **Issue:** [#209](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/209)
- **Proceso:** consulta del tablero de indicadores por periodo
- **Principio SOLID aplicado:** Single Responsibility (SRP)
## Entregables

- [Estrategia de pruebas](./01-estrategia-pruebas.svg) | [fuente](./fuentes/01-estrategia-pruebas.puml)
- Suite ejecutable [`tests/pruebas_semana15.php`](./tests/pruebas_semana15.php): **3 unitarias, 2 de caja blanca y 4 de caja negra**.
- Salida registrada en [`evidencia/salida-pruebas.txt`](./evidencia/salida-pruebas.txt).

La suite prueba el Micro-HIS de la Semana 4 y su API REST de la Semana 5 (rama apilada), sin
PHPUnit: un runner minimo con `assert` activado, igual que en semanas anteriores.

## Matriz RF/RNF -> Prueba

| Requisito | Pruebas | Tipo | Resultado |
|---|---|---|---|
| RF-01 (S4) consultar tablero por periodo | UNIT-03, BB-01, BB-02 | Unitaria / caja negra | PASA |
| RF-02 (S4) rechazar consulta sin periodo | BB-03 | Caja negra | PASA |
| RF-03 (S4) valor, meta y cumplimiento | UNIT-01, UNIT-02, WB-01 | Unitaria / caja blanca (MC/DC) | PASA |
| RF-03 (S5) tablero de solo lectura | WB-02, BB-04 | Caja blanca / caja negra | PASA |
| RNF estados vacios sin error | BB-02 | Caja negra | PASA |

## Pruebas de caja blanca

| ID | Unidad | Criterio de cobertura |
|---|---|---|
| WB-01 | `Indicador::registrar` | MC/DC: cada condicion de `clave === '' || periodo === ''` cambia por si sola el resultado (3 casos). |
| WB-02 | `Router::despachar` | Cobertura de caminos: V(G) = 6 (5 decisiones + 1); los 5 casos cubren todas las ramas, incluido el `foreach` con y sin coincidencia, y terminan en 404, 405, 422, error de regla y exito. |

## Pruebas de caja negra (contra el contrato de la API)

| ID | Tecnica | Entrada | Salida esperada |
|---|---|---|---|
| BB-01 | Particion valida | periodo con indicadores | 200 con 2 indicadores |
| BB-02 | Particion valida sin datos | periodo sin indicadores | 200 con `total: 0` (estado vacio) |
| BB-03 | Particion invalida | sin `periodo` | 422 `periodo_requerido` |
| BB-04 | Particion invalida | metodo de escritura | 405 `method_not_allowed` |

## Comandos y salida

```bash
cd docs/asii-26-dashboard-base/semana-15
php -d zend.assertions=1 -d assert.exception=1 tests/pruebas_semana15.php
```

```text
  [OK] [UNIT-01] Indicador cumple la meta en el valor limite (valor = meta)
  [OK] [UNIT-02] Indicador::registrar sin clave lanza excepcion 422
  [OK] [UNIT-03] ObtenerTablero agrega total y cumplen_meta
  [OK] [WB-01] Indicador::registrar: MC/DC de (clave vacia || periodo vacio)
  [OK] [WB-02] Router::despachar recorre sus 5 caminos (404, 405, 422, error, exito)
  [OK] [BB-01] particion valida: GET /tablero con datos responde 200
  [OK] [BB-02] particion valida sin datos: periodo vacio de datos devuelve total 0
  [OK] [BB-03] particion invalida: sin periodo responde 422
  [OK] [BB-04] particion invalida: DELETE /tablero responde 405

Resumen: 9 pruebas, 0 fallidas.
```

Las 9 pruebas pasan (codigo de salida `0`). El runner devuelve `1` si alguna falla, por lo que
puede usarse como paso de CI.

## Dependencias

Rama apilada sobre la Semana 5 (que a su vez depende de la Semana 4, PR #1184). El PR de esta
semana se abre cuando ambas esten fusionadas en `develop`. `Refs #209`.
