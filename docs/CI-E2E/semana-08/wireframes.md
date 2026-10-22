# Wireframes anotados (6)

Baja fidelidad, en texto para que sean editables. Los números `①` remiten a las anotaciones de cada pantalla. Los datos son ficticios.

## W1 — Lista de ejecuciones (inicio, vacío, carga, éxito)

```text
┌──────────────────────────────────────────────────────────────┐
│ Calidad de versiones                 Perfil [Líder QA · H1 ▾]① │
│ Planificación, E2E, evidencia y gate  Simular falla [Ninguna▾]│
├──────────────────────────────────────────────────────────────┤
│ Ejecuciones                              [Planificar ejecución]②│
│ ③ Cargando ejecuciones…                                        │
│ ┌ Versión 1.4.0-rc.1  [Aprobado]④ ┐ ┌ Versión 1.3.2 [Sin evaluar]┐│
│ │ Completada · ci · commit 4f2c9a1be07d│ │ En ejecución · ci · …   ││
│ └─────────────────────────────┘ └──────────────────────────┘│
└──────────────────────────────────────────────────────────────┘
```

① El perfil define permisos (solo para mostrar u ocultar; el servidor vuelve a verificar). ② Solo visible con permiso de registro. ③ Estado de carga anunciado por `aria-live`. Vacío: «Todavía no hay ejecuciones en este hospital» + qué hacer. ④ Insignia con texto, no solo color.

## W2 — Planificar ejecución (decisión y validaciones)

```text
Planificar ejecución
Versión (SemVer) [1.4.0-rc.1   ]①  Commit (40 hex) [4f2c…7a8b]②  Ambiente [ci ▾]③
┌ Suites del plan ───────────────────────────────────────────┐
│ Nombre [phpunit-unit ] Tipo [unit ▾] [x] Obligatoria [Quitar]│
│ Nombre [e2e-auth-rbac] Tipo [e2e  ▾] [x] Obligatoria [Quitar]│
│ [Agregar suite]                     ④ error del grupo aquí  │
└────────────────────────────────────────────────────────────┘
▸ ¿Qué suites necesita el plan?⑤
[Cancelar]                                     [Guardar plan]
```

① Mensaje: «Use SemVer, por ejemplo 1.4.0 o 1.4.0-rc.1». ② Sin autocompletar ni corrector; error «Pegue el SHA completo de 40 caracteres en minúsculas». ③ Solo `ci`, `staging`, `local`: producción no se ofrece (protección de datos). ④ Una unit y una E2E obligatorias; nombres únicos. ⑤ Ayuda contextual plegable. Al fallar, el foco va al primer campo con error y nada se envía.

## W3 — Detalle en ejecución (registrar resultado)

```text
← Volver   Ejecución de la versión 1.4.0-rc.1
Estado En ejecución · Commit 4f2c9a1be07d3c6a… · Ambiente ci · Planificó qa-lider
[Completar ejecución]① [Abortar]②
Suites y evidencia
| Suite         | Tipo | Oblig. | Resultado                 | Evidencia               |
| phpunit-unit  | unit | Sí     | 107/107 aprobadas · 0 …   | phpunit/unit.xml (a3f…)③ |
| e2e-auth-rbac | e2e  | Sí     | Pendiente                 | —                        |
Registrar resultado④  Suite [e2e-auth-rbac▾] Total [ ] Fallidas [0] Omitidas [0] ms [0]
Evidencia [e2e/auth-rbac.xml]  SHA-256 [………]                  [Registrar resultado]
```

① y ② Botones solo según estado y permiso (`allowedActions`). ③ La huella se muestra abreviada; la ruta es relativa al paquete (nunca datos clínicos). ④ Solo lista suites sin resultado; «Fallidas más omitidas no puede superar el total».

## W4 — Confirmación (completar / abortar)

```text
┌───────────── Confirmar ─────────────┐
│ La ejecución quedará abortada y el  │
│ gate la bloqueará.                  │
│ Motivo (obligatorio) [            ]①│
│        [Cancelar]   [Confirmar]②    │
└─────────────────────────────────────┘
```

① Abortar exige motivo (≤ 200). ② Diálogo modal nativo: `Esc` cancela; sin motivo no se ejecuta nada. Completar avisa: «Al completar ya no se aceptan más resultados».

## W5 — Decisión del quality gate (éxito / rechazo / bloqueo)

```text
┌ Quality gate: Bloqueado ───────────────────────────────────┐
│ Falta evidencia para decidir (suite ausente, vacía o sin   │
│ archivo). No es un rechazo: complete la ejecución.①        │
│ Política qg-0.1-propuesta · evaluó qa-lider · 2026-10-21…② │
│ • Bloquea: e2e-auth-rbac: No se registró resultado.③       │
│ • Aviso: pint: No se registró resultado.                   │
└────────────────────────────────────────────────────────────┘
```

① Texto de ayuda distinto por veredicto (aprobado / rechazado / bloqueado). ② Versión de la política visible: el veredicto es reproducible. ③ Cada hallazgo dice severidad, suite y regla en lenguaje claro.

## W6 — Error recuperable y error crítico

```text
┌───────────────────────────────────────────────────────────┐
│ Servicio no disponible  El registro de ejecuciones no     │
│ responde. No se guardó nada; intente de nuevo.  [Reintentar] [×] │
└───────────────────────────────────────────────────────────┘
```

503 (almacén o RBAC) y «sin conexión»: recuperables, con «Reintentar» y los datos del formulario conservados. 403: «Su perfil no tiene permiso para esta acción» sin reintento. 404: «La ejecución no existe en su hospital» (no se revela si existe en otro). Nunca se muestran trazas técnicas; solo la referencia `trace_id` en errores inesperados.
