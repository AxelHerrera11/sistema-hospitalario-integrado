# Centro de documentacion y manuales por rol - Semana 15

> Tema guia: **Pruebas unitarias, caja blanca y caja negra**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-24` | **Issue:** [#208](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/208)
- **Proceso:** publicacion y consulta de documentacion y manuales diferenciados por rol
- **Principio SOLID aplicado:** Interface Segregation (ISP)
## Entregables

- [Estrategia de pruebas](./01-estrategia-pruebas.svg) | [fuente](./fuentes/01-estrategia-pruebas.puml)
- Suite ejecutable [`tests/pruebas_semana15.php`](./tests/pruebas_semana15.php): **3 unitarias, 2 de caja blanca y 4 de caja negra**.
- Salida registrada en [`evidencia/salida-pruebas.txt`](./evidencia/salida-pruebas.txt).

La suite prueba el Micro-HIS de la Semana 4 y su API REST de la Semana 5 (rama apilada), sin
PHPUnit: un runner minimo con `assert` activado, igual que en semanas anteriores.

## Matriz RF/RNF -> Prueba

| Requisito | Pruebas | Tipo | Resultado |
|---|---|---|---|
| RF-01 (S4) publicar documento | UNIT-01, BB-01 | Unitaria / caja negra | PASA |
| RF-02 (S4) rechazar publicacion sin roles | UNIT-02, WB-01 | Unitaria / caja blanca | PASA |
| RF-03 (S4) listar catalogo visible por rol | UNIT-03, BB-03, BB-04 | Unitaria / caja negra | PASA |
| RF-02 (S5) exigir titulo y rol | WB-02, BB-02 | Caja blanca / caja negra | PASA |
| RNF confidencialidad: un rol no ve documentos ajenos | BB-03, BB-04 | Caja negra | PASA |

## Pruebas de caja blanca

| ID | Unidad | Criterio de cobertura |
|---|---|---|
| WB-01 | `Documento::publicar` / `visiblePara` | Cobertura de ramas: la decision `roles === []` y el retorno de `visiblePara` se evaluan verdadero y falso. |
| WB-02 | `Router::despachar` | Cobertura de caminos: V(G) = 6 (5 decisiones + 1); los 5 casos cubren todas las ramas, incluido el `foreach` con y sin coincidencia, y terminan en 404, 405, 422, error de regla y exito. |

## Pruebas de caja negra (contra el contrato de la API)

| ID | Tecnica | Entrada | Salida esperada |
|---|---|---|---|
| BB-01 | Particion valida | titulo y un rol | 201 |
| BB-02 | Particion invalida | sin `titulo` | 422 `validation_failed` (`errors: [titulo]`) |
| BB-03 | Particion valida sin datos | rol sin documentos publicados | 200 con lista vacia |
| BB-04 | Valor limite | rol con mayuscula inicial (`Medico`) | 200 con lista vacia (comparacion exacta) |

## Comandos y salida

```bash
cd docs/asii-24-centro-documentacion/semana-15
php -d zend.assertions=1 -d assert.exception=1 tests/pruebas_semana15.php
```

```text
  [OK] [UNIT-01] Documento::publicar normaliza roles (sin vacios ni duplicados)
  [OK] [UNIT-02] Documento::publicar sin roles validos lanza excepcion 422
  [OK] [UNIT-03] ConsultarDocumentos lista solo lo visible para el rol
  [OK] [WB-01] Documento::publicar y visiblePara cubren ambas ramas
  [OK] [WB-02] Router::despachar recorre sus 5 caminos (404, 405, 422, error, exito)
  [OK] [BB-01] particion valida: POST /documentos responde 201
  [OK] [BB-02] particion invalida: POST sin titulo responde 422 validation_failed
  [OK] [BB-03] particion valida sin datos: rol sin documentos recibe lista vacia
  [OK] [BB-04] valor limite: el rol se compara exacto (mayusculas no dan acceso)

Resumen: 9 pruebas, 0 fallidas.
```

Las 9 pruebas pasan (codigo de salida `0`). El runner devuelve `1` si alguna falla, por lo que
puede usarse como paso de CI.

## Dependencias

Rama apilada sobre la Semana 5 (que a su vez depende de la Semana 4, PR #1183). El PR de esta
semana se abre cuando ambas esten fusionadas en `develop`. `Refs #208`.
