const MENU_PERMISSIONS = [
    'pacientes.ver',
    'citas.ver',
    'admision.ver',
    'expediente.ver',
    'signos_vitales.ver',
    'notas_soap.ver',
    'alergias.ver',
    'medicamentos.ver',
    'prescripciones.ver',
    'laboratorio.ver',
    'reportes.ver',
    'ocupacion.ver',
    'auditoria.ver',
    'usuarios.ver',
    'documentacion.ver',
];

const ROLE_FALLBACK = {
    SuperAdmin: MENU_PERMISSIONS,
    AdminHospital: MENU_PERMISSIONS,
    Medico: [
        'pacientes.ver',
        'citas.ver',
        'expediente.ver',
        'signos_vitales.ver',
        'notas_soap.ver',
        'alergias.ver',
        'medicamentos.ver',
        'prescripciones.ver',
        'reportes.ver',
        'documentacion.ver',
    ],
    Enfermeria: [
        'pacientes.ver',
        'citas.ver',
        'expediente.ver',
        'signos_vitales.ver',
        'alergias.ver',
        'documentacion.ver',
    ],
    TecnicoLab: ['laboratorio.ver', 'reportes.ver', 'documentacion.ver'],
    Bioquimico: ['laboratorio.ver', 'reportes.ver', 'documentacion.ver'],
    Recepcionista: ['pacientes.ver', 'citas.ver', 'admision.ver', 'documentacion.ver'],
};

function toNames(entries) {
    if (!Array.isArray(entries)) {
        return [];
    }

    return entries
        .map((entry) => (typeof entry === 'string' ? entry : entry?.name))
        .filter((name) => typeof name === 'string' && name !== '');
}

export function resolvePermissions(user) {
    if (Array.isArray(user?.permissions)) {
        return toNames(user.permissions);
    }

    const roleNames = toNames(user?.roles);

    for (const roleName of roleNames) {
        if (Array.isArray(ROLE_FALLBACK[roleName])) {
            return ROLE_FALLBACK[roleName];
        }
    }

    return [];
}
