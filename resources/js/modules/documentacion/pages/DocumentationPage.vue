<template>
    <section class="docs" aria-labelledby="docs-title">
        <header class="docs__header">
            <p class="docs__eyebrow">Referencia técnica del HIS</p>
            <h1 id="docs-title">Documentación de APIs</h1>
            <p>
                Contratos disponibles bajo <code>/api/v1</code>. Todas las rutas protegidas
                requieren JWT, <code>X-Tenant-ID</code> y el permiso indicado.
            </p>
        </header>

        <div class="docs__notice" role="note">
            El frontend oculta acciones sin permiso, pero la API es la autoridad final.
            Los errores deben conservar su código estable y el identificador de solicitud.
        </div>

        <section class="docs__card">
            <h2>Convenciones comunes</h2>
            <dl class="docs__definition-list">
                <div><dt>Base URL</dt><dd><code>/api/v1</code></dd></div>
                <div><dt>Tenant</dt><dd><code>X-Tenant-ID: uuid</code></dd></div>
                <div><dt>Identidad</dt><dd><code>Authorization: Bearer &lt;jwt&gt;</code></dd></div>
                <div><dt>Errores</dt><dd>JSON con <code>message</code>, <code>code</code> y <code>request_id</code> cuando aplica.</dd></div>
            </dl>
        </section>

        <section v-for="group in groups" :key="group.title" class="docs__card">
            <h2>{{ group.title }}</h2>
            <p v-if="group.description" class="docs__description">{{ group.description }}</p>
            <div class="docs__table-wrap">
                <table>
                    <thead><tr><th>Método</th><th>Ruta</th><th>Permiso</th><th>Uso</th></tr></thead>
                    <tbody>
                        <tr v-for="endpoint in group.endpoints" :key="`${endpoint.method}-${endpoint.path}`">
                            <td><code>{{ endpoint.method }}</code></td>
                            <td><code>{{ endpoint.path }}</code></td>
                            <td><code>{{ endpoint.permission || 'público' }}</code></td>
                            <td>{{ endpoint.summary }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="docs__card">
            <h2>Documentos del repositorio</h2>
            <ul class="docs__links">
                <li v-for="link in documents" :key="link.href">
                    <a :href="link.href" target="_blank" rel="noreferrer">{{ link.label }}</a>
                    <span>{{ link.description }}</span>
                </li>
            </ul>
        </section>
    </section>
</template>

<script setup>
const groups = [
    {
        title: 'Autenticación y administración',
        description: 'Identidad central, contexto hospitalario, usuarios y sesiones.',
        endpoints: [
            { method: 'POST', path: '/auth/login', summary: 'Inicia sesión y devuelve JWT, roles, permisos y hospital.' },
            { method: 'POST', path: '/auth/refresh', summary: 'Renueva el token usando la cookie de refresh.' },
            { method: 'GET', path: '/auth/me', permission: 'sesión autenticada', summary: 'Devuelve identidad y permisos vigentes.' },
            { method: 'POST', path: '/auth/logout', permission: 'sesión autenticada', summary: 'Revoca la sesión actual.' },
            { method: 'GET', path: '/admin/users', permission: 'usuarios.ver', summary: 'Lista usuarios del hospital actual.' },
            { method: 'POST', path: '/admin/users', permission: 'usuarios.crear', summary: 'Crea un usuario y asigna su rol inicial.' },
            { method: 'POST', path: '/admin/users/{user}/roles', permission: 'rbac.asignar', summary: 'Asigna un rol al usuario.' },
            { method: 'POST', path: '/admin/users/{user}/deactivate', permission: 'usuarios.desactivar', summary: 'Desactiva un usuario del hospital.' },
        ],
    },
    {
        title: 'Laboratorio',
        description: 'Órdenes, resultados, validación y acciones de extensión.',
        endpoints: [
            { method: 'GET', path: '/lab-orders', permission: 'laboratorio.ordenes.ver', summary: 'Lista órdenes del hospital con sus estados.' },
            { method: 'POST', path: '/lab-orders', permission: 'laboratorio.ordenes.crear', summary: 'Emite una orden para paciente, episodio y pruebas existentes.' },
            { method: 'GET', path: '/lab-orders/{id}', permission: 'laboratorio.ordenes.ver', summary: 'Consulta el detalle de una orden.' },
            { method: 'PATCH', path: '/lab-orders/{id}/status', permission: 'laboratorio.ordenes.actualizar', summary: 'Actualiza el estado permitido de una orden.' },
            { method: 'POST', path: '/lab-orders/{id}/cancellation', permission: 'laboratorio.ordenes.cancelar', summary: 'Cancela una orden según sus reglas de estado.' },
            { method: 'GET', path: '/lab-results', permission: 'lab-results.read', summary: 'Lista resultados pendientes y su auditoría.' },
            { method: 'POST', path: '/lab-results/{resultId}/capture', permission: 'lab-results.create', summary: 'Captura un resultado pendiente.' },
            { method: 'POST', path: '/lab-results/{resultId}/correct', permission: 'lab-results.correct', summary: 'Registra una corrección controlada.' },
            { method: 'GET', path: '/lab-validation/results', permission: 'lab-results.read', summary: 'Consulta resultados para revisión bioquímica.' },
            { method: 'POST', path: '/lab-validation/results/{resultId}/validate', permission: 'lab-results.validate', summary: 'Valida un resultado.' },
            { method: 'POST', path: '/lab-validation/results/{resultId}/reject', permission: 'lab-results.validate', summary: 'Rechaza un resultado con motivo.' },
            { method: 'GET', path: '/nativephp/actions', permission: 'lab-results.read', summary: 'Consulta la cola local de acciones.' },
            { method: 'POST', path: '/nativephp/actions', permission: 'lab-results.create', summary: 'Prepara una acción local.' },
            { method: 'POST', path: '/nativephp/sync', permission: 'lab-results.create', summary: 'Sincroniza acciones autorizadas.' },
        ],
    },
    {
        title: 'Médicos, ocupación y reportes',
        endpoints: [
            { method: 'GET', path: '/availability', permission: 'medicos.ver_disponibilidad', summary: 'Consulta disponibilidad médica.' },
            { method: 'POST', path: '/doctors', permission: 'medicos.registrar', summary: 'Registra un perfil médico.' },
            { method: 'POST', path: '/doctors/{doctor}/specialties', permission: 'medicos.asociar_especialidad', summary: 'Asocia una especialidad.' },
            { method: 'GET', path: '/occupancy', permission: 'ocupacion.ver', summary: 'Obtiene el corte de ocupación hospitalaria.' },
            { method: 'POST', path: '/reports/indicators/IND-RA-01/calculate', permission: 'reportes.ver', summary: 'Calcula el indicador de ocupación.' },
        ],
    },
];

const documents = [
    { label: 'Contrato API de resultados', href: 'https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/blob/develop/docs/ingreso-resultados/semana-05/contrato-api.md', description: 'Contrato del flujo de captura y corrección controlada.' },
    { label: 'README del sistema', href: 'https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/blob/develop/README.md', description: 'Alcance general, módulos, convenciones y ejecución.' },
    { label: 'Contrato de órdenes', href: 'https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/blob/develop/docs/ordenes-laboratorio/contrato-api.md', description: 'Emisión, estados, idempotencia y cancelación.' },
    { label: 'Contrato de NativePHP', href: 'https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/blob/develop/docs/nativephp/semana-05/contrato-api.md', description: 'Acciones locales y límites de sincronización.' },
    { label: 'OpenAPI de recepción', href: 'https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/blob/develop/docs/recepcion-muestras/semana-05/openapi.yaml', description: 'Contrato OpenAPI de recepción de muestras.' },
    { label: 'OpenAPI del catálogo', href: 'https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/blob/develop/micro-his/catalogo-pruebas-lab/openapi.yaml', description: 'Contrato OpenAPI del catálogo de pruebas.' },
    { label: 'README del sistema e instalación', href: 'https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/blob/develop/README.md', description: 'Alcance general, configuración local, base API y tenant.' },
    { label: 'Rutas API del backend', href: 'https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/blob/develop/routes/api.php', description: 'Fuente de verdad de métodos, rutas y middlewares.' },
];
</script>

<style scoped>
.docs { max-width: 1180px; margin: 0 auto; display: grid; gap: 1rem; }
.docs__header, .docs__card, .docs__notice { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; }
.docs__header h1 { margin: 0.25rem 0 0.5rem; font-size: 1.7rem; }
.docs__header p { margin: 0; color: #475569; line-height: 1.55; }
.docs__eyebrow { color: #2563eb !important; font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; }
.docs__notice { background: #eff6ff; border-color: #bfdbfe; color: #1e3a8a; }
.docs__card h2 { margin: 0 0 0.75rem; font-size: 1.15rem; }
.docs__description { color: #475569; margin-top: -0.25rem; }
.docs__definition-list { display: grid; gap: 0.5rem; margin: 0; }
.docs__definition-list div { display: grid; grid-template-columns: 150px 1fr; gap: 0.75rem; }
.docs__definition-list dt { font-weight: 700; color: #334155; }
.docs__definition-list dd { margin: 0; color: #475569; }
.docs__table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
th, td { padding: 0.65rem; text-align: left; vertical-align: top; border-bottom: 1px solid #e2e8f0; }
th { color: #334155; background: #f8fafc; }
code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.88em; }
.docs__links { display: grid; gap: 0.7rem; margin: 0; padding-left: 1.2rem; }
.docs__links li { display: grid; gap: 0.15rem; }
.docs__links a { color: #2563eb; font-weight: 600; }
.docs__links span { color: #475569; font-size: 0.9rem; }
@media (max-width: 640px) { .docs__definition-list div { grid-template-columns: 1fr; gap: 0.15rem; } th, td { min-width: 150px; } }
</style>
