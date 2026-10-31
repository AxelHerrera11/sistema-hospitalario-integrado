# Reportes exportables - Semana 15

> Tema guia: **Pruebas unitarias, caja blanca y caja negra**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-27` | **Issue:** [#210](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/210)
- **Proceso:** generacion y consulta de reportes operativos por periodo
- **Principio SOLID aplicado:** Open/Closed (OCP)
## Entregables

- [Estrategia de pruebas](./01-estrategia-pruebas.svg) | [fuente](./fuentes/01-estrategia-pruebas.puml)
- Suite ejecutable [`tests/pruebas_semana15.php`](./tests/pruebas_semana15.php): **3 unitarias, 2 de caja blanca y 4 de caja negra**.
- Salida registrada en [`evidencia/salida-pruebas.txt`](./evidencia/salida-pruebas.txt).

La suite prueba el Micro-HIS de la Semana 4 y su API REST de la Semana 5 (rama apilada), sin
PHPUnit: un runner minimo con `assert` activado, igual que en semanas anteriores.

## Matriz RF/RNF -> Prueba

| Requisito | Pruebas | Tipo | Resultado |
|---|---|---|---|
| RF-01 (S4) generar reporte por periodo | UNIT-01, BB-01 | Unitaria / caja negra | PASA |
| RF-02 (S4) rechazar periodo vacio | UNIT-02, WB-01, BB-02 | Unitaria / caja blanca / caja negra | PASA |
| RF-03 (S4) listar por periodo | UNIT-03, BB-03, BB-04 | Unitaria / caja negra | PASA |
| RF-02 (S5) sobre comun de errores | WB-02, BB-02, BB-04 | Caja blanca / caja negra | PASA |
| RNF reproducibilidad: el contenido no se altera | UNIT-01, UNIT-03 | Unitaria | PASA |

## Pruebas de caja blanca

| ID | Unidad | Criterio de cobertura |
|---|---|---|
| WB-01 | `ReporteController` | Cobertura de caminos: los 2 caminos de `generar` (excepcion / exito) y los 2 de `listar` (periodo vacio / exito). |
| WB-02 | `Router::despachar` | Cobertura de caminos: V(G) = 6 (5 decisiones + 1); los 5 casos cubren todas las ramas, incluido el `foreach` con y sin coincidencia, y terminan en 404, 405, 422, error de regla y exito. |

## Pruebas de caja negra (contra el contrato de la API)

| ID | Tecnica | Entrada | Salida esperada |
|---|---|---|---|
| BB-01 | Particion valida | periodo y datos | 201 |
| BB-02 | Particion invalida | POST sin `periodo` | 422 `reporte_invalido` |
| BB-03 | Particion valida sin datos | periodo sin reportes | 200 con lista vacia |
| BB-04 | Particion invalida | GET sin `periodo` | 422 `periodo_requerido` |

## Comandos y salida

```bash
cd docs/asii-27-reportes/semana-15
php -d zend.assertions=1 -d assert.exception=1 tests/pruebas_semana15.php
```

```text
  [OK] [UNIT-01] Reporte::generar conserva periodo y contenido
  [OK] [UNIT-02] Reporte::generar sin periodo lanza excepcion 422
  [OK] [UNIT-03] GenerarReporte::listar filtra por periodo
  [OK] [WB-01] ReporteController recorre sus 4 caminos
  [OK] [WB-02] Router::despachar recorre sus 5 caminos (404, 405, 422, error, exito)
  [OK] [BB-01] particion valida: POST /reportes responde 201
  [OK] [BB-02] particion invalida: POST sin periodo responde 422 reporte_invalido
  [OK] [BB-03] particion valida sin datos: periodo sin reportes devuelve lista vacia
  [OK] [BB-04] particion invalida: GET sin periodo responde 422 periodo_requerido

Resumen: 9 pruebas, 0 fallidas.
```

Las 9 pruebas pasan (codigo de salida `0`). El runner devuelve `1` si alguna falla, por lo que
puede usarse como paso de CI.

## Dependencias

Rama apilada sobre la Semana 5 (que a su vez depende de la Semana 4, PR #1185). El PR de esta
semana se abre cuando ambas esten fusionadas en `develop`. `Refs #210`.
