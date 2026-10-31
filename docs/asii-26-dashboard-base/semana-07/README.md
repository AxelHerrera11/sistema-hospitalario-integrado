# Tablero de indicadores (dashboard base) - Semana 7

> Tema guia: **Diseno de componentes y refactorizacion**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-26` | **Issue:** [#209](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/209)
- **Proceso:** consulta del tablero de indicadores por periodo
- **Principio SOLID aplicado:** Single Responsibility (SRP)
## Entregables

- [Diagrama de componentes backend/frontend](./01-componentes.svg) | [fuente](./fuentes/01-componentes.puml)
- Contratos de entrada/salida por componente (tabla abajo).
- Refactor ejecutable: [`antes.php`](./refactor/antes.php), [`despues.php`](./refactor/despues.php) y
  [`verificar.php`](./refactor/verificar.php), que prueba que ambos producen la misma salida.

## Componentes

El frontend se organiza en vista -> componentes -> store (Pinia) -> cliente API; el backend en
controlador -> casos de uso -> entidad -> Repository -> adaptador. La unica dependencia entre ambos
es el contrato REST de la Semana 5, por lo que cada lado puede cambiar sin romper al otro.

## Contratos de entrada/salida

| Componente | Entrada | Salida | Errores |
|---|---|---|---|
| `MenuLateral.vue` | `auth.user.permissions[]` | items visibles | sin permisos: solo `Inicio` (falla cerrada) |
| `TarjetaIndicador.vue` | `{titulo, texto, color}` | tarjeta accesible (`role=status`) | - |
| `EstadoPagina.vue` | `estado: cargando|vacio|error`, `mensaje` | bloque visual uniforme | boton Reintentar en error |
| `useTableroStore` | `cargar(periodo)` | `tablero`, `cargando`, `error` | `error.code` |
| `TableroController` | `GET /tablero?periodo=` | 200 read model | 422 `periodo_requerido` |
| `ObtenerTablero` | `ejecutar(periodo)` | `{periodo,total,cumplen_meta,indicadores[]}` | - |

## Refactorizacion antes/despues

| Tecnica | Aplicacion |
|---|---|
| Replace Magic Number with Symbolic Constant | `100` y `90` pasan a `Semaforo::UMBRAL_VERDE` / `UMBRAL_AMARILLO`. |
| Extract Method | el semaforo (copiado 3 veces) queda en `Semaforo::color`. |
| Replace Conditional with Table | las ramas por clave se vuelven datos en `DEFINICIONES`. |
| Introduce Explaining Variable | `menorEsMejor` hace explicita la inversion que antes estaba oculta en la formula. |

**Justificacion:** el codigo *antes* mezclaba decisiones en condicionales anidados y duplicados, lo
que hace dificil agregar casos sin romper otros. El codigo *despues* deja cada regla en un lugar con
nombre propio y aplica Single Responsibility (SRP). El refactor no cambia comportamiento: `verificar.php` ejecuta los
mismos escenarios contra ambas versiones y compara la salida.

**Riesgo detectado:** con `espera = 0` ambas versiones dividen entre cero. Se deja igual para no cambiar comportamiento en el refactor y se registra para la revision tecnica (Semana 13).

## Evidencia

```bash
cd docs/asii-26-dashboard-base/semana-07
php refactor/verificar.php
```

```text
  [IGUAL] ocupacion verde -> [{"titulo":"Ocupacion de camas","texto":"85 %","color":"verde"}]
  [IGUAL] ocupacion amarillo (limite 90%) -> [{"titulo":"Ocupacion de camas","texto":"72 %","color":"amarillo"}]
  [IGUAL] espera roja (menor es mejor) -> [{"titulo":"Tiempo de espera","texto":"30 min","color":"rojo"}]
  [IGUAL] citas verdes en el limite 100% -> [{"titulo":"Citas atendidas","texto":"90 citas","color":"verde"}]
  [IGUAL] clave desconocida se omite -> []

Comportamiento: 5/5 escenarios iguales.
Antes:   22 lineas, anidamiento max. 3, 9 sentencias if.
Despues: 38 lineas, anidamiento max. 3, 1 sentencias if.
```

## Regeneracion de diagramas

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-26-dashboard-base/semana-07/fuentes/*.puml -o ..
```
