# Alergias clínicas y alerta visual en expediente — Semana 7: componentes backend/frontend

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-12 · **Entrega:** diagrama de componentes y propuesta de refactorización.

## Objetivo

Diseñar los componentes del flujo «registro de alergia y emisión de alerta al consultar el expediente» dentro del HIS real (Laravel 12 y Vue 3, ya presentes en `develop`), separando UI, aplicación, dominio y persistencia. El diseño reutiliza el dominio y los casos de uso del micro-HIS de las Semanas 3 a 5 sin reescribirlos.

![Componentes backend/frontend](./01-componentes.svg)

[Fuente Mermaid](./fuentes/01-componentes.mmd)

## Componentes y responsabilidades

| Componente | Responsabilidad | Ya existe en `develop` |
|---|---|---|
| Middlewares `tenant` y `jwt.auth` | Resolver el hospital (`X-Tenant-ID`) y el usuario del JWT | Sí: `TenantMiddleware` y `JwtAuth` |
| Middleware `permission:modulo.accion` | Proteger cada ruta con el permiso de RBAC | Parcial: `spatie/laravel-permission` y `config/permission.php` están; falta registrar el alias `permission` en `bootstrap/app.php` y el catálogo de permisos (María, ASII-02) |
| `Api/V1/AllergyController` + `StoreAllergyRequest` | Controller delgado: valida tipos y obligatorios con el FormRequest y llama al caso de uso | No (propuesto) |
| Application: RegisterAllergy · GetAllergyAlert | Orquestan el caso de uso; se copian del micro-HIS sin cambios de lógica | En `micro-his/alergias/src/Application` |
| Domain: Allergy · AllergyAlert | Reglas puras, sin Laravel | En `micro-his/alergias/src/Domain` |
| `AllergyRepository` «interfaz» + `EloquentAllergyRepository` | Puerto de persistencia; el adaptador usa el Query Builder sobre la conexión `hospital` | El puerto y los adaptadores PDO/InMemory existen en el micro-HIS |
| `AuditLogger` | Evidencia en `local_access_audits` | Interfaz común de Cindy (plan de integración) |
| Vue: `PatientAllergiesPage.vue`, `AllergyBanner.vue · AllergyForm.vue` | el banner del expediente con `role="alert"` y el formulario de alta | No (propuesto) |
| `stores/allergies.js` y `api/allergiesApi.js` | Estado de la pantalla y llamadas HTTP con el plugin `axios` existente | Plugin `resources/js/plugins/axios.js` |

`AllergyBanner.vue` trata `unavailable` como un estado propio (gris, «Alergias no disponibles») y nunca lo muestra como «Sin alergias»; si la API no responde, el store pone `unavailable`.

## Rutas protegidas

| Método | Ruta | Permiso |
|---|---|---|
| GET | `/api/v1/patients/{patient}/allergy-alert` | `alergias.ver` |
| POST | `/api/v1/patients/{patient}/allergies` | `alergias.registrar` |
