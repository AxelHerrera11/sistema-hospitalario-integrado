# Semana 8 — Flujo UX por rol, wireframes y reglas de interacción

**Módulo:** ASII-18, recepción de muestras y códigos identificadores.
**Autor:** María de los Ángeles López Fajardo, Mar-03.
**Issue:** [#6](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/6).
**Objetivo de la matriz:** User flow, wireframes iniciales y reglas de interacción vinculados al formulario real.

## Índice

1. Introducción y problema.
2. Desarrollo del entregable.
3. Correspondencia con Laravel 12 y Vue.
4. Verificación y conclusión.
5. Fuentes y evidencia Git.

## Introducción y problema

El técnico debe comparar la muestra observada con una orden confiable, sin que la interfaz complete
automáticamente la identidad que debe verificarse. El bioquímico consulta muestras y no recibe.
La UI muestra acciones según permisos efectivos; el servidor vuelve a autorizarlas.

## Matriz de roles y tareas

| Perfil | Permisos relevantes | Tareas visibles |
|---|---|---|
| Técnico de laboratorio | muestras.recibir, muestras.ver, muestras.etiquetar | Buscar orden, registrar observaciones, consultar e imprimir. |
| Recepción | muestras.recibir, muestras.ver según configuración | Recibir y consultar; imprimir requiere permiso propio. |
| Bioquímico/lector | muestras.ver | Consultar código; sin búsqueda de recepción ni botón de registrar. |
| Sin sesión/permiso | Ninguno | Login o respuesta 401/403; nunca confiar en campos de rol enviados por cliente. |

## User flow de recepción y consulta

~~~mermaid
flowchart TD
  Inicio --> Sesion{Sesión y permiso}
  Sesion -->|recibir| Buscar[Buscar ítem o código de paciente]
  Buscar --> Coincidencias{Pendientes del hospital}
  Coincidencias -->|ninguna| Vacio[Estado vacío y nueva búsqueda]
  Coincidencias -->|sí| Elegir[Seleccionar ítem y leer requisitos]
  Elegir --> Observar[Escribir identidad y condiciones observadas]
  Observar --> Enviar[POST con clave de solicitud]
  Enviar --> Resultado{Respuesta}
  Resultado -->|accepted| Etiqueta[Mostrar código e imprimir si autorizado]
  Resultado -->|rejected| Motivo[Mostrar causa y nueva toma]
  Resultado -->|red incierta| Recuperar[Repetir mismo payload y clave]
  Sesion -->|solo ver| Consultar[GET por código]
  Consultar --> Lectura[Detalle de muestra]
  Etiqueta --> Nueva[Iniciar otra muestra y limpiar identidad]
  Motivo --> Nueva
~~~

## Wireframes editables

~~~text
DESKTOP
[ Recepción de muestras                              ]
[ Buscar: ítem/código paciente ______ ] [Buscar orden]
[ Ítem I1 | identidad de referencia | requisitos    ]
[ Seleccionar ítem ]  (no copia identidad observada)
[ Código observado ____ ] [ Nombre observado _____ ]
[ Tipo / contenedor / volumen / temperatura / toma ]
[ Condiciones de muestra ] [Registrar recepción]
[ Resultado + causa O código + etiqueta autorizada ]
[ Consultar por código ______________ ] [Consultar]
~~~

~~~text
MÓVIL
[ Título e instrucción ]
[ Buscar orden          ]
[ Referencia y requisitos ]
[ Identificadores observados ]
[ Condiciones y fecha   ]
[ Registrar             ]
[ Resultado textual     ]
[ Código / imprimir     ]
[ Consulta independiente ]
~~~

## Reglas de interacción implementadas

| Evento | Comportamiento |
|---|---|
| Seleccionar una orden | Solo selecciona item_id; código y nombre observados siguen vacíos. |
| Enviar recepción | Deshabilita campos y botón mientras busy; servidor bloquea la fila de orden. |
| Error 422 | Conserva observaciones, muestra errores y enfoca resumen. |
| Timeout/5xx al recibir | Bloquea otra recepción y permite recuperar payload/clave originales. |
| Falla de GET | Muestra error sin fingir una recepción incierta ni bloquear un registro nuevo. |
| Aceptación | Presenta código persistido; impresión requiere lectura de etiqueta autorizada. |
| Rechazo | Presenta motivo y conserva registro sin código; no permite forzar aceptación. |
| Nueva muestra | Limpia identidad, condiciones, búsqueda y resultado; enfoca primer campo. |

## Aplicación al HIS y evidencia

ReceptionPage.vue consume los permisos de useAuthStore; no mantiene una lista de roles inventada.
FindPendingOrders y las rutas filtran y autorizan en servidor.
El harness de navegador usa Pinia y permisos ficticios explícitos, y comprueba el modo lector.
Los tests HTTP reales prueban búsqueda por tenant y denegación de recepción/etiqueta al lector.

## Conclusión

El flujo se puede recorrer por rol y cada decisión crítica tiene un estado visible.
Los wireframes son editables en este Markdown y se contrastan con las capturas reales de semana 11/12.

## Fuentes y evidencia Git

- [README original y matriz ASII-18](../../../README.md).
- [Declaración de IA](../DECLARACION_IA.md) y [trazabilidad final](../semana-15/README.md).
- [Índice de ramas y PR a develop](../semana-15/ramas-prs.md).
- Rama: feature/asii-18-semana-08-recepcion-muestras-mar-03.
- Los archivos de código enlazados pertenecen al HIS real; el micro conserva su carpeta independiente.
