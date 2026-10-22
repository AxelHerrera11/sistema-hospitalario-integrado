/**
 * Menú principal del layout.
 *
 * `route` apunta solo a rutas registradas en `resources/js/router/index.js`.
 * Los ítems con `route: null` se muestran como «próximamente» (sin enlace)
 * hasta que su módulo registre la ruta.
 */
export const navigationItems = [
    { id: 'inicio', label: 'Inicio', route: '/', permission: null },
    { id: 'pacientes', label: 'Pacientes', route: '/patients', permission: 'pacientes.ver' },
    { id: 'ocupacion', label: 'Ocupación', route: '/ocupacion', permission: 'ocupacion.ver' },
    { id: 'medicamentos', label: 'Medicamentos', route: '/medicamentos', permission: 'medicamentos.ver' },
    { id: 'usuarios', label: 'Usuarios', route: '/admin/users', permission: 'usuarios.ver' },
    { id: 'rbac', label: 'Catálogo RBAC', route: '/rbac', permission: 'documentacion.ver' },
    { id: 'citas', label: 'Citas', route: null, permission: 'citas.ver' },
    { id: 'admision', label: 'Admisión y camas', route: null, permission: 'admision.ver' },
    { id: 'expediente', label: 'Expediente', route: null, permission: 'expediente.ver' },
    { id: 'signos-vitales', label: 'Signos vitales', route: null, permission: 'signos_vitales.ver' },
    { id: 'notas-soap', label: 'Notas SOAP', route: null, permission: 'notas_soap.ver' },
    { id: 'alergias', label: 'Alergias', route: null, permission: 'alergias.ver' },
    { id: 'prescripciones', label: 'Prescripciones', route: null, permission: 'prescripciones.ver' },
    { id: 'laboratorio', label: 'Laboratorio', route: null, permission: 'laboratorio.ver' },
    { id: 'reportes', label: 'Reportes', route: null, permission: 'reportes.ver' },
    { id: 'auditoria', label: 'Auditoría', route: null, permission: 'auditoria.ver' },
    { id: 'documentacion', label: 'Documentación', route: null, permission: 'documentacion.ver' },
];
