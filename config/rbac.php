<?php

return [
    /*
     * Matriz administrativa aprobada para esta integración. Cada permiso se
     * enumera literalmente: no hay comodines,
     * herencia implícita ni concesión automática por el nombre del rol. Las claves
     * deben coincidir exactamente con hospital_memberships.role_code (distingue
     * mayúsculas).
     */
    'roles' => [
        'superadmin' => [
            'pacientes.ver', 'pacientes.registrar', 'pacientes.editar',
            'rbac.roles.ver', 'rbac.asignar', 'rbac.roles.asignar', 'rbac.autorizar',
            'rbac.permisos.ver', 'rbac.denegaciones.ver',
            'usuarios.ver', 'usuarios.desactivar', 'tenants.crear', 'sesiones.revocar_ajenas',
            'prescripciones.ver', 'prescripciones.crear', 'prescripciones.firmar',
            'muestras.recibir', 'muestras.ver', 'muestras.etiquetar',
            'laboratorio.catalogo.ver', 'laboratorio.catalogo.administrar',
            'alergias.ver', 'alergias.registrar', 'admision.crear', 'camas.ver',
            'alertas.ver', 'alertas.acusar', 'alertas.cerrar', 'ocupacion.ver',
        ],
        'admin_hospital' => [
            'rbac.roles.ver', 'rbac.asignar', 'rbac.roles.asignar', 'rbac.permisos.ver', 'rbac.denegaciones.ver',
            'usuarios.ver', 'usuarios.desactivar', 'sesiones.revocar_ajenas',
            'ocupacion.ver',
        ],
        'medico' => [
            'pacientes.ver',
            'rbac.autorizar',
            'prescripciones.ver', 'prescripciones.crear', 'prescripciones.firmar',
            'laboratorio.catalogo.ver', 'alergias.ver', 'alergias.registrar',
            'alertas.ver', 'alertas.acusar', 'alertas.cerrar',
        ],
        'enfermeria' => [
            'pacientes.ver',
            'rbac.autorizar', 'prescripciones.ver',
            'muestras.recibir', 'muestras.ver', 'muestras.etiquetar',
            'alergias.ver', 'alergias.registrar',
        ],
        'recepcion' => [
            'pacientes.ver', 'pacientes.registrar', 'pacientes.editar',
            'rbac.autorizar', 'muestras.recibir', 'muestras.ver',
            'admision.crear', 'camas.ver',
        ],
    ],
    /*
     * Concesiones propuestas que NO están activas. Se documentan para no
     * confundirlas con las activas; ConfiguredPermissions solo lee 'roles'.
     * Formato: permiso => roles que lo recibirían si se aprobara.
     */
    'pending' => [
        'prescripciones.excepcion_alergia' => ['medico', 'superadmin'],
        'laboratorio.catalogo.administrar' => ['admin_hospital'],
        'alertas.ver' => ['enfermeria'],
        'alertas.acusar' => ['enfermeria'],
    ],
    'catalog' => [
        'pacientes.ver', 'pacientes.registrar', 'pacientes.editar',
        'rbac.roles.ver', 'rbac.asignar', 'rbac.roles.asignar', 'rbac.autorizar',
        'rbac.permisos.ver', 'rbac.denegaciones.ver',
        'usuarios.ver', 'usuarios.desactivar', 'tenants.crear', 'sesiones.revocar_ajenas',
        'prescripciones.ver', 'prescripciones.crear', 'prescripciones.firmar',
        'prescripciones.excepcion_alergia', 'muestras.recibir', 'muestras.ver', 'muestras.etiquetar',
        'laboratorio.catalogo.ver', 'laboratorio.catalogo.administrar',
        'alergias.ver', 'alergias.registrar', 'admision.crear', 'camas.ver',
        'alertas.ver', 'alertas.acusar', 'alertas.cerrar',
        'ocupacion.ver',
    ],
];
