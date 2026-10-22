# Responsables y procedimiento

## Roles

| Rol | Quién | Responsabilidad |
|---|---|---|
| Autor | Axel Herrera (`AxelHerrera11`) | Prepara el paquete de revisión, responde los hallazgos y los corrige en commits nuevos (sin force push) |
| Moderadora | Yamilet Lindo (`Yamilet1235`), responsable de integración | Convoca, controla el tiempo, decide si el módulo pasa los criterios de salida |
| Revisor técnico | Un compañero de un módulo que consume este (ver el README) | Revisa contrato, dominio y persistencia con el checklist |
| Revisor de BD | Javier Fajardo (`Javifa2324`) | Revisa tablas, restricciones y migraciones contra el esquema HOSPITAL |
| Registro | El autor | Anota cada hallazgo en `registro-hallazgos.md` con su severidad y estado |

## Procedimiento

1. **Planificación (autor, día 0):** el autor confirma los criterios de entrada y publica el paquete (PR, enlaces, pruebas).
2. **Preparación individual (revisores, 1 día):** cada revisor aplica el [checklist](./checklist-revision.md) y anota sus hallazgos.
3. **Reunión de revisión (45 min):** se recorren los hallazgos por severidad; no se discuten soluciones largas, solo se acuerda el defecto.
4. **Corrección (autor):** cada hallazgo se cierra con un commit que lo cita (`Refs` + número de hallazgo).
5. **Seguimiento (moderadora):** verifica los cierres y los criterios de salida; si quedan hallazgos altos, se agenda otra revisión.
