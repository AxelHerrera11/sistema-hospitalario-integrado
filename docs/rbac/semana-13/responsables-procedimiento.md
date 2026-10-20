# Responsables y procedimiento de revisión — RBAC (Semana 13)

## 1. Responsables

Los roles de módulo provienen de `README.md:34-65`.

| Rol | Responsable | Responsabilidad en la revisión |
|---|---|---|
| Autor del módulo | `Luis890D` (designado en `README.md:37`); autora de esta rama: `Mar-03` | Mantiene checklist, hallazgos e informe; corrige lo que se le apruebe. |
| Revisor par | Otro integrante del equipo HIS | Revisa código, pruebas y trazabilidad; firma la aprobación del checklist. |
| Responsable de usuarios/tenants | `AquinoB2090` (`README.md:36`) | Valida identidad, `central_users`, membresías y resolución de `X-Tenant-ID`. |
| Responsable de gobernanza/auditoría | `bquiroao` (`README.md:57`) | Valida interfaz del `DenialRecorder` y la consulta de bitácora append-only. |
| Docente / integrador | Rol de integración (`README.md:99`) | Resuelve discrepancias entre módulos y aprueba criterios de salida. |

Si un responsable no está disponible, firma el revisor par y se registra el reemplazo en la
decisión, sin omitir la aprobación de la frontera afectada (usuarios/tenants o auditoría).

## 2. Procedimiento

1. **Preparar.** El autor abre el PR hacia `develop` desde su worktree y adjunta al menos:
   checklist `checklist-revision.md`, hallazgos `registro-hallazgos.md` y comandos ejecutados.
2. **Revisión estática.** El revisor par recorre el checklist por secciones y marca
   `aprobado` / `con observación` / `bloqueado`, citando `archivo:línea`.
3. **Revisión de fronteras.** Usuarios/tenants y gobernanza revisan solo sus interfaces
   (`X-Tenant-ID`, `hospital_memberships`, `DenialRecorder`, bitácora).
4. **Revisión en ejecución.** Se corren los comandos declarados; se pega la salida real.
   Si un comando no puede ejecutarse, se declara como **no ejecutado**, nunca como aprobado.
5. **Registro de hallazgos.** Todo hallazgo se anota con severidad, evidencia y estado.
   Bloqueo = impide aprobar; riesgo = permite aprobar con plan; hecho = verificado.
6. **Decisión.** Se aprueba solo si no quedan bloqueos abiertos y el checklist queda en verde
   o con justificación registrada.
7. **Cierre.** Los hallazgos cerrados se anotan con el commit o la evidencia que los cierra.

## 3. Evidencia de decisión

Cada revisión deja, como mínimo:

- salida real de los comandos ejecutados (texto, no resumen);
- checklist firmado con `archivo:línea` por ítem observado;
- lista de hallazgos con severidad y estado;
- nombre del aprobador y fecha.

## 4. Reglas de la revisión

- **No** se declara ejecutada una prueba que no se corrió.
- **No** se modifica código de producción sin autorización del responsable del módulo.
- **No** se hace `push`, PR, merge ni cambios en otras ramas durante la revisión.
- El diff del PR contra `develop` se mantiene **por debajo de 400 líneas modificadas**; si se
  excede, se divide por documento o por capacidad, no por líneas artificiales.

## 5. Cadencias

- Revisión de preparación al abrir el PR.
- Revisión de fronteras cuando cambie `X-Tenant-ID`, membresías o auditoría.
- Revisión de cierre antes de aprobar el PR hacia `develop`.
