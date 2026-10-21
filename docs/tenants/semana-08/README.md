# Semana 08 — Diseño de Experiencia de Usuario (UX)
## Entregables Generados (`docs/tenants/semana-08/`)
### Documentación Principal
| Archivo                         | Tipo     | Descripción                                                                                                                                                                                             |
|---------------------------------|----------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `ux-user-flows.md`              | Markdown | **Documento Principal**: 5 User Flows (Mermaid), 6 Wireframes anotados, Taxonomía errores (40+ códigos), Ayuda contextual, Matriz visibilidad datos (6 roles), Checklist Accesibilidad WCAG 2.1 AA      |
| `ux-accessibility-checklist.md` | Markdown | **Checklist WCAG 2.1 AA Detallado**: 50+ criterios por principio (Perceivable, Operable, Understandable, Robust), checklist por 6 pantallas, testing automatizado (axe-core + jest-axe), testing manual |
| `ux-error-patterns.md`          | Markdown | **Patrones de Error y Microcopy**: Taxonomía 5 categorías, 40+ códigos error con microcopy, Patrones feedback (Toast, Inline, Modal), Guidelines microcopy, Testing errores, Métricas KPIs              |
| `README.md`                     | Markdown | Este índice                                                                                                                                                                                             |

### Wireframes (`wireframes/`)
| Archivo                   | Pantalla                   | Estados Cubiertos                                                             |
|---------------------------|----------------------------|-------------------------------------------------------------------------------|
| `01-login-form.md`        | Login Form                 | Vacío, Carga, Error Credenciales, Cuenta Bloqueada, Éxito + Redirect          |
| `02-mfa-challenge.md`     | MFA Challenge              | TOTP Challenge, Verificando, Error Código, Bloqueo MFA, WebAuthn, Éxito       |
| `03-admin-create-user.md` | Admin Alta Usuario         | Vacío, Validando Tiempo Real, Error Campo, Error Servidor, Éxito              |
| `04-admin-user-list.md`   | Admin Lista + Force-Revoke | Lista + Filtros, Menú Acciones, Modal Force-Revoke, Toast Resultados          |
| `05-audit-logs.md`        | Auditoría                  | Tabla Filtros, Fila Expandida, Detalle Crítico Modal, Exportación             |
| `06-patient-portal.md`    | Paciente Portal            | Dashboard Resumen, Detalle Cita, Seguridad/MFA, Onboarding MFA, Mobile/Tablet |

## Resumen de User Flows Modelados
| #     | Flujo                   | Actor(es)                               | Pasos Clave                                                 | Estados Cubiertos                                    |
|-------|-------------------------|-----------------------------------------|-------------------------------------------------------------|------------------------------------------------------|
| **1** | Login Estándar          | MEDICO, ENFERMERO, PACIENTE             | Form → Validación → Success/Error/MFA                       | Vacío, Carga, Error, Bloqueo, Éxito                  |
| **2** | Login + MFA Obligatorio | TENANT_ADMIN, AUDITOR                   | Login → MFA Policy → Challenge TOTP/WebAuthn → Verify       | Challenge, Verificando, Error, Bloqueo, Éxito        |
| **3** | Alta Usuario por Admin  | TENANT_ADMIN                            | Modal → Validación Tiempo Real → Submit → Email → Auditoría | Vacío, Validando, Error Campo, Error Servidor, Éxito |
| **4** | Revocación Sesión       | Usuario (Logout) / Admin (Force-Revoke) | Click → Confirm → Revoke → Audit → Event                    | Propio, Force-Revoke Admin, Toast Resultados         |
| **5** | Refresh Automático      | Background                              | Interceptor → Refresh → Rotación/Reuse Detection → Retry    | Silencioso, Error Reuse, Expiración Total            |

## Wireframes Generados (6 Pantallas Clave)
| #     | Pantalla                       | Wireframe                            | Estados Documentados                                                                 |
|-------|--------------------------------|--------------------------------------|--------------------------------------------------------------------------------------|
| **1** | **Login Form**                 | `wireframes/01-login-form.md`        | Vacío, Carga, Error Credenciales, Cuenta Bloqueada, Éxito + Mobile                   |
| **2** | **MFA Challenge**              | `wireframes/02-mfa-challenge.md`     | TOTP Challenge, Verificando, Error, Bloqueo MFA, WebAuthn, Éxito                     |
| **3** | **Admin Alta Usuario**         | `wireframes/03-admin-create-user.md` | Vacío, Validando Tiempo Real, Error Campo, Error Servidor, Éxito + Password Strength |
| **4** | **Admin Lista + Force-Revoke** | `wireframes/04-admin-user-list.md`   | Lista + Filtros, Menú Acciones, Modal Force-Revoke, Toast Resultados + Mobile Cards  |
| **5** | **Auditoría**                  | `wireframes/05-audit-logs.md`        | Tabla Filtros, Fila Expandida, Detalle Crítico Modal, Exportación                    |
| **6** | **Paciente Portal**            | `wireframes/06-patient-portal.md`    | Dashboard Resumen, Detalle Cita, Seguridad/MFA, Onboarding MFA, Mobile/Tablet        |

## Taxonomía de Errores (40+ Códigos)
| Categoría              | Códigos             | Recuperabilidad      | Ejemplo                                    |
|------------------------|---------------------|----------------------|--------------------------------------------|
| **Validación Cliente** | VAL-001 a VAL-003   | Inmediata            | "Email obligatorio"                        |
| **Credenciales**       | AUTH-001 a AUTH-006 | Inmediata/Temporal   | "Credenciales inválidas. Intento 2/5"      |
| **MFA**                | MFA-001 a MFA-005   | Inmediata/Bloqueo    | "Código inválido. Intento 2/3"             |
| **Refresh Token**      | REF-001 a REF-004   | Re-login/Forzado     | "Actividad sospechosa. Sesiones revocadas" |
| **Revocación**         | REV-001 a REV-004   | Éxito/Error Permisos | "3 sesiones revocadas"                     |
| **Admin**              | ADM-001 a ADM-006   | Inmediata/Externa    | "Email ya registrado"                      |
| **Infraestructura**    | INF-001 a INF-004   | Reintento            | "Servicio temporalmente no disponible"     |

## Accesibilidad WCAG 2.1 AA - Cobertura
| Principio          | Criterios Cubiertos                                                                          | Testing                   |
|--------------------|----------------------------------------------------------------------------------------------|---------------------------|
| **Perceivable**    | 1.1.1, 1.3.1, 1.3.2, 1.3.5, 1.4.1, 1.4.3, 1.4.4, 1.4.11, 1.4.12, 1.4.13                      | axe-core + Manual         |
| **Operable**       | 2.1.1, 2.1.2, 2.2.1, 2.3.1, 2.4.1, 2.4.2, 2.4.3, 2.4.4, 2.4.5, 2.4.6, 2.4.7, 2.4.11, 2.5.1-4 | axe-core + Keyboard Test  |
| **Understandable** | 3.1.1, 3.2.1, 3.2.2, 3.2.3, 3.2.4, 3.3.1, 3.3.2, 3.3.3, 3.3.4                                | axe-core + Screen Reader  |
| **Robust**         | 4.1.1, 4.1.2, 4.1.3                                                                          | axe-core + HTML Validator |

**Testing Automatizado**: `axe-core` + `jest-axe` en CI/CD
**Testing Manual**: NVDA/VoiceOver, Zoom 200%, High Contrast, Reduced Motion, Color Blindness

## Microcopy Guidelines (Principios)
| Principio             | Mal Ejemplo                   | Bien Ejemplo                                 |
|-----------------------|-------------------------------|----------------------------------------------|
| **Claro, no técnico** | "Error 401: Unauthorized"     | "Credenciales inválidas"                     |
| **Accionable**        | "Error en el servidor"        | "Intente nuevamente en unos minutos"         |
| **Empático**          | "Usted escribió mal el email" | "El email parece incorrecto"                 |
| **Específico**        | "Error inesperado"            | "Su sesión expiró. Inicie sesión nuevamente" |
| **Consistente**       | "Error" / "Falló" / "Fallido" | Siempre "Error" para errores                 |

## Matriz Visibilidad Datos (6 Roles)
| Dato                | MEDICO       | ENFERMERO      | TENANT_ADMIN | AUDITOR    | PACIENTE  | PLATFORM_ADMIN |
|---------------------|--------------|----------------|--------------|------------|-----------|----------------|
| Expediente Completo | ✅ R/W        | ✅ R (limitado) | ✅ R (audit)  | ✅ R        | ✅ Resumen | ✅ R/W          |
| Notas Psiquiátricas | ✅ (consent.) | ❌              | ❌            | ✅ (audit)  | ❌         | ✅ (audit)      |
| Auditoría Sistema   | ❌            | ❌              | ✅ (tenant)   | ✅ (global) | ❌         | ✅ (global)     |
| Gestión Usuarios    | ❌            | ❌              | ✅ CRUD       | ❌          | ❌         | ✅ CRUD         |
| Config Tenant       | ❌            | ❌              | ✅ R/W        | ❌          | ❌         | ✅ R/W          |

## Responsive Breakpoints
| Breakpoint  | Ancho      | Login                          | Admin                      | Auditoría                          | Paciente                     |
|-------------|------------|--------------------------------|----------------------------|------------------------------------|------------------------------|
| **Mobile**  | 320-480px  | Stack 100%, botones full-width | Cards apiladas, hamburger  | Tabla → Cards, filtros colapsables | Stack vertical, nav inferior |
| **Tablet**  | 481-768px  | Centrado 400px                 | 2 cols, sidebar colapsable | Tabla responsive (scroll H)        | 2 columnas grid              |
| **Desktop** | 769-1024px | Centrado 400px max             | Layout completo sidebar    | Tabla completa, filtros            | 3-4 cols grid                |
| **Wide**    | 1025px+    | Centrado 400px max             | Dashboard paneles          | Tabla ancha, panel detalle         | 4 cols, sidebar fija         |

## Métricas UX Objetivo (KPIs)
| Métrica                          | Objetivo         | Medición                                 |
|----------------------------------|------------------|------------------------------------------|
| **Tiempo Login (p50)**           | < 15s            | Analytics: login_start → dashboard_load  |
| **Tasa Éxito Login**             | > 98%            | logins_exitosos / intentos_totales       |
| **Tasa Éxito MFA (1er intento)** | > 95%            | mfa_success_1er_intento / mfa_challenges |
| **Tasa Abandono Login**          | < 2%             | usuarios_abandonan / inician             |
| **Tasa Éxito MFA Onboarding**    | > 80%            | mfa_completado / mfa_iniciado            |
| **Satisfacción (NPS)**           | > 50             | Encuesta trimestral post-login           |
| **Accesibilidad (axe-core)**     | 0 violaciones AA | CI pipeline automatizado                 |

## Verificación Rápida
```bash
ls docs/tenants/semana-08/wireframes/
ls docs/tenants/semana-08/*.md
for f in docs/tenants/semana-08/wireframes/*.md; do
  echo "=== $f ==="
  grep -c "```mermaid" "$f"
done
# Verificar accesibilidad en CI
# npm run test:a11y  (requiere configuración axe-core + jest-axe)
```
## Referencias Cruzadas
| Semana | Artefactos Relacionados                                                                   |
|--------|-------------------------------------------------------------------------------------------|
| **01** | UML: Casos de Uso, Actividad, Secuencia (`docs/cindy/week-01/gestion-usuarios-tenants/`)  |
| **02** | RF/RNF, Criterios Aceptación, SRP Analysis (`docs/tenants/semana-02/`)                    |
| **03** | C4 Diagrams, Secuencias, Código Base (`docs/tenants/semana-03/` + `app/Modules/Tenants/`) |
| **04** | Arquitectura Capas, Repository, MVC, Shared Repo (`docs/tenants/semana-04/`)              |
| **05** | OpenAPI 3.1, Evaluación Microservicio, Patrones Comm (`docs/tenants/semana-05/`)          |
| **06** | Evaluación Parcial, Matriz Trazabilidad, Cambio Práctico (`docs/tenants/semana-06/`)      |
| **07** | Componentes, Refactor AuthenticateUserUseCase, Contratos (`docs/tenants/semana-07/`)      |
| **08** | **UX Completo** (Esta entrega)                                                            |
