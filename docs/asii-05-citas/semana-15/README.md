# Gestion de citas medicas y agenda - Semana 15

> Tema guia: **Pruebas unitarias, caja blanca y caja negra**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-05` | **Issue:** [#207](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/207)
- **Proceso:** registro y consulta de citas medicas validando disponibilidad
- **Principio SOLID aplicado:** Dependency Inversion (DIP)
## Entregables

- [Estrategia de pruebas](./01-estrategia-pruebas.svg) | [fuente](./fuentes/01-estrategia-pruebas.puml)
- Suite ejecutable [`tests/pruebas_semana15.php`](./tests/pruebas_semana15.php): **3 unitarias, 2 de caja blanca y 4 de caja negra**.
- Salida registrada en [`evidencia/salida-pruebas.txt`](./evidencia/salida-pruebas.txt).

La suite prueba el Micro-HIS de la Semana 4 y su API REST de la Semana 5 (rama apilada), sin
PHPUnit: un runner minimo con `assert` activado, igual que en semanas anteriores.

## Matriz RF/RNF -> Prueba

| Requisito | Pruebas | Tipo | Resultado |
|---|---|---|---|
| RF-01 (S4) agendar cita | UNIT-01, BB-01 | Unitaria / caja negra | PASA |
| RF-02 (S4) rechazar rango invertido | UNIT-02, BB-04 | Unitaria / caja negra | PASA |
| RF-03 (S4) rechazar solape paciente/doctor | WB-01, BB-02, BB-03 | Caja blanca / caja negra (limite) | PASA |
| RF-04 (S4) persistir via Repository | UNIT-03 | Unitaria | PASA |
| RF-02/03 (S5) validacion y sobre comun | WB-02, BB-03, BB-04 | Caja blanca / caja negra | PASA |
| RNF-01 (S5) errores sin trazas ni SQL | BB-03, BB-04 (solo `code`/`message`) | Caja negra | PASA |

## Pruebas de caja blanca

| ID | Unidad | Criterio de cobertura |
|---|---|---|
| WB-01 | `Cita::seSolapaCon` | Cobertura de condiciones: `paciente===`, `doctor===` y los dos extremos del intervalo `[desde, hasta)` toman valor verdadero y falso al menos una vez (5 casos). |
| WB-02 | `Router::despachar` | Cobertura de caminos: V(G) = 6 (5 decisiones + 1); los 5 casos cubren todas las ramas, incluido el `foreach` con y sin coincidencia, y terminan en 404, 405, 422, error de regla y exito. |

## Pruebas de caja negra (contra el contrato de la API)

| ID | Tecnica | Entrada | Salida esperada |
|---|---|---|---|
| BB-01 | Particion valida | cita completa sin conflicto | 201 |
| BB-02 | Valor limite | `desde` = `hasta` de otra cita del mismo doctor | 201 (intervalo semiabierto) |
| BB-03 | Particion invalida | solape de 1 minuto con el mismo paciente | 409 `appointments_overlap` |
| BB-04 | Particion invalida | `desde` > `hasta` | 409 `cita_rango_invalido` |

## Comandos y salida

```bash
cd docs/asii-05-citas/semana-15
php -d zend.assertions=1 -d assert.exception=1 tests/pruebas_semana15.php
```

```text
  [OK] [UNIT-01] Cita::agendar crea la cita en estado scheduled
  [OK] [UNIT-02] Cita::agendar rechaza rango invertido
  [OK] [UNIT-03] AgendarCita persiste y ListarCitas filtra por paciente
  [OK] [WB-01] Cita::seSolapaCon cubre todas sus condiciones
  [OK] [WB-02] Router::despachar recorre sus 5 caminos (404, 405, 422, error, exito)
  [OK] [BB-01] particion valida: POST /citas responde 201
  [OK] [BB-02] valor limite: cita contigua (inicia cuando termina otra) se acepta
  [OK] [BB-03] particion invalida: solape de un minuto responde 409
  [OK] [BB-04] particion invalida: rango invertido responde 409 cita_rango_invalido

Resumen: 9 pruebas, 0 fallidas.
```

Las 9 pruebas pasan (codigo de salida `0`). El runner devuelve `1` si alguna falla, por lo que
puede usarse como paso de CI.

## Dependencias

Rama apilada sobre la Semana 5 (que a su vez depende de la Semana 4, PR #1182). El PR de esta
semana se abre cuando ambas esten fusionadas en `develop`. `Refs #207`.
