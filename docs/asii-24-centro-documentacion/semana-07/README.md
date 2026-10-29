# Centro de documentacion y manuales por rol - Semana 7

> Tema guia: **Diseno de componentes y refactorizacion**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-24` | **Issue:** [#208](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/208)
- **Proceso:** publicacion y consulta de documentacion y manuales diferenciados por rol
- **Principio SOLID aplicado:** Interface Segregation (ISP)
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
| `PublicarDocumentoForm.vue` | titulo, roles[] (checkboxes) | evento `publicar(documento)` | aviso si no hay roles |
| `useDocumentosStore` | `cargar()` (rol de la sesion) | `documentos[]`, `cargando`, `error` | `error.code` |
| `documentosApi.js` | `GET /api/v1/documentos` + JWT | `{data:{documentos}}` | `{code, message}` |
| `DocumentoController` | Request HTTP | 200/201 `{data}` | 422 `documento_sin_roles` |
| `ConsultarDocumentos` | `listar(rol)` | `list<{id,titulo,roles}>` | - |
| `DocumentoRepository` | `guardar(Documento)` / `todos()` | `void` / `list<Documento>` | `ErrorPersistenciaException` |

## Refactorizacion antes/despues

| Tecnica | Aplicacion |
|---|---|
| Replace Conditional with Polymorphism | la cadena `if/elseif` por rol se reemplaza por `PoliticaVisibilidad`. |
| Remove Duplicated Code | el filtro `in_array(rol, roles)` existia 3 veces; queda una vez en `VeSusRoles`. |
| Introduce Factory Method | `politicaPara($rol)` concentra la eleccion de politica. |
| Replace loose with strict comparison | `==` e `in_array` sin modo estricto pasan a `===` y `true`. |

**Justificacion:** el codigo *antes* mezclaba decisiones en condicionales anidados y duplicados, lo
que hace dificil agregar casos sin romper otros. El codigo *despues* deja cada regla en un lugar con
nombre propio y aplica Interface Segregation (ISP). El refactor no cambia comportamiento: `verificar.php` ejecuta los
mismos escenarios contra ambas versiones y compara la salida.

**Defecto detectado durante el refactor:** en la version *antes*, un rol nuevo (p. ej. `laboratorio`) no ve ningun documento aunque este en su lista de roles, porque no tiene rama `elseif`. El refactor lo corrige de forma natural (`VeSusRoles`), pero como cambia comportamiento se registra aparte y no forma parte de los escenarios de equivalencia.

## Evidencia

```bash
cd docs/asii-24-centro-documentacion/semana-07
php refactor/verificar.php
```

```text
  [IGUAL] admin -> ["Manual de enfermeria","Guia de prescripcion","Protocolo de alergias"]
  [IGUAL] medico -> ["Guia de prescripcion","Protocolo de alergias"]
  [IGUAL] enfermeria -> ["Manual de enfermeria","Protocolo de alergias"]
  [IGUAL] farmacia -> ["Guia de prescripcion"]

Comportamiento: 4/4 escenarios iguales.
Antes:   24 lineas, anidamiento max. 4, 7 sentencias if.
Despues: 34 lineas, anidamiento max. 2, 0 sentencias if.
```

## Regeneracion de diagramas

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-24-centro-documentacion/semana-07/fuentes/*.puml -o ..
```
