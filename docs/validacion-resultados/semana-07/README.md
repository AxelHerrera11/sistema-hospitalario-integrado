# Semana 7 — Diseño de componentes backend/frontend

**Módulo:** ASII-20 — Validación de resultados por bioquímico

## Componentes

- Frontend: bandeja de resultados, visor de valores/unidades, acciones validar/rechazar y captura de motivo.
- Presentation: controlador HTTP con acciones explícitas.
- Application: casos de uso separados para validar y rechazar.
- Domain: entidad revisable, estados, política de rol y reglas de unidad.
- Persistence: interfaz Repository y adaptadores PDO/InMemory con control optimista.
- Audit: puerto para decisiones trazables.

El diagrama de componentes mantiene la dependencia del backend hacia interfaces.

## Propuesta de refactorización

1. Extraer un componente de decisión que reciba política de rol y estado esperado.
2. Mantener validar y rechazar como comandos separados para evitar condicionales ambiguos.
3. Compartir en frontend un componente de confirmación que exija motivo solo al rechazar.
4. Encapsular estado/version esperados en el Repository y traducir conflictos a 409.
5. Registrar la decisión con un evento de auditoría inmutable.

## Criterios de aceptación

- Solo un bioquímico autorizado puede decidir.
- La unidad incompatible impide validar.
- Rechazar sin motivo responde 422 y conserva el estado pendiente.
- Acción HTTP desconocida no ejecuta ningún caso de uso.

