# Flujo de usuario por rol — Semana 8

Los roles concretos que pueden trasladar o dar de alta **no están definidos** en RBAC (RF-TA-03, pendiente con ASII-02). Esta tabla es una propuesta de la autora para diseñar el flujo, no un permiso implementado.

| Rol propuesto | Objetivo | Operaciones |
|---|---|---|
| Enfermería de sala | Mover al paciente a otra cama del hospital | Consultar y trasladar |
| Médico tratante | Cerrar la internación | Consultar y dar de alta |
| Personal sin permiso | — | La API responde 403 y la interfaz lo explica |

```mermaid
flowchart TD
    I([Inicio]) --> A["1. Indicar usuario y estancia<br/>(ayuda junto a cada campo)"]
    A --> C{"Consultar"}
    C -->|404| E1["Aviso: la estancia no existe"] --> A
    C -->|503| E2["Aviso: reintentar (sin cambios)"] --> A
    C -->|200| S["2. Situación actual"]
    S -->|sin cama asignada| V["Estado vacío: nada que hacer"]
    S -->|estancia no vigente| N["Aviso: los datos cambiaron"] --> A
    S -->|vigente| D{"¿Qué operación?"}
    D -->|Enfermería| T["3. Cama destino y motivo<br/>(validación local)"]
    D -->|Médico| L["3. Alta (sin campos)"]
    T --> K["4. Confirmar resumen"]
    L --> K
    K -->|Volver| T
    K --> P["Procesando… (botón bloqueado)"]
    P -->|201/200| OK["Éxito: cama liberada"]
    P -->|409/403| R["Rechazo explicado, sin cambios"] --> S
    P -->|503| RE["Fallo sin cambios: puede reintentar"] --> K
    P -->|500 o red| IN["Incierto: Consultar estado"] --> C
```

## Reglas de interacción

1. No se puede confirmar sin pasar por el resumen; el botón se bloquea mientras procesa, para evitar un doble envío.
2. Solo se ofrecen acciones si la estancia consultada sigue vigente y el episodio está abierto.
3. Un rechazo explica el motivo y no ofrece reintento automático.
4. Un 503 permite reintentar, porque se sabe que no hubo cambios. Un incierto, o una falla de red en el envío, solo permite consultar el estado.
5. La pantalla muestra solo identificadores y estados, nunca datos personales (RNF-TA-07).
