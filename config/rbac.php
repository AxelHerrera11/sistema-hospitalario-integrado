<?php

return [
    /*
     * Matriz explícita de integración de módulos. Cada permiso se
     * enumera literalmente: no hay comodines,
     * herencia implícita ni concesión automática por el nombre del rol. Las claves
     * deben coincidir exactamente con hospital_memberships.role_code (distingue
     * mayúsculas).
     */
    'roles' => [
        'superadmin' => [
            'citas.ver', 'citas.crear', 'citas.confirmar',
            'citas.cancelar', 'reportes.ver', 'reportes.exportar',
            'documentacion.ver', 'admision.ver', 'expediente.ver', 'expediente.abrir', 'expediente.asignar',
            'medicamentos.ver', 'laboratorio.ver', 'auditoria.ver',
            'signos_vitales.corregir', 'alertas.supervisar',
            'admisiones.registrar', 'admisiones.ver', 'camas.asignar',
            'alergias.resolver', 'muestras.rechazar', 'notas_soap.ver',
            'notas_soap.crear', 'notas_soap.asociar_diagnostico', 'notas_soap.firmar',
            'notas_soap.corregir', 'signos_vitales.ver', 'signos_vitales.registrar',
            'ocupacion.ver', 'laboratorio.ordenes.crear', 'laboratorio.ordenes.ver',
            'laboratorio.ordenes.cancelar', 'lab-results.read', 'lab-results.create',
            'lab-results.correct', 'lab-results.validate',
            'medicos.registrar', 'medicos.asociar_especialidad', 'medicos.ver_disponibilidad',
            'pacientes.ver', 'pacientes.registrar', 'pacientes.editar',
            'rbac.roles.ver', 'rbac.asignar', 'rbac.roles.asignar', 'rbac.autorizar',
            'rbac.permisos.ver', 'rbac.denegaciones.ver',
            'usuarios.ver', 'usuarios.crear', 'usuarios.desactivar', 'tenants.crear', 'sesiones.revocar_ajenas',
            'qa.ejecuciones.ver', 'qa.ejecuciones.registrar', 'qa.gates.evaluar',
            'prescripciones.ver', 'prescripciones.crear', 'prescripciones.firmar',
            'muestras.recibir', 'muestras.ver', 'muestras.etiquetar',
            'laboratorio.catalogo.ver', 'laboratorio.catalogo.administrar',
            'alergias.ver', 'alergias.registrar', 'admision.crear', 'camas.ver',
            'alertas.ver', 'alertas.acusar', 'alertas.cerrar',
        ],
        'admin_hospital' => [
            'medicamentos.registrar', 'medicamentos.cambiar_vigencia',
            'citas.ver', 'citas.crear', 'citas.confirmar',
            'citas.cancelar', 'reportes.ver', 'reportes.exportar',
            'documentacion.ver', 'medicamentos.ver', 'laboratorio.ver',
            'ocupacion.ver',
            'medicos.registrar', 'medicos.asociar_especialidad', 'medicos.ver_disponibilidad',
            'rbac.roles.ver', 'rbac.asignar', 'rbac.roles.asignar', 'rbac.permisos.ver', 'rbac.denegaciones.ver',
            'usuarios.ver', 'usuarios.crear', 'usuarios.desactivar', 'sesiones.revocar_ajenas',
            'expediente.asignar',
            // ASII-25: calidad de versiones (ejecuciones automatizadas y quality gate), decisión del 30/10/2026.
            'qa.ejecuciones.ver', 'qa.ejecuciones.registrar', 'qa.gates.evaluar',
        ],
        'medico' => [
            // ASII-08: el médico da el alta y consulta la estancia.
            'altas.registrar', 'traslados.ver',
            'citas.ver', 'citas.confirmar', 'citas.cancelar',
            'reportes.ver', 'documentacion.ver', 'admision.ver',
            // ASII-25: el médico abre el expediente longitudinal; enfermería solo lo consulta.
            'expediente.ver', 'expediente.abrir', 'medicamentos.ver', 'laboratorio.ver',
            'signos_vitales.corregir',
            'admisiones.ver', 'alergias.resolver', 'notas_soap.ver',
            'notas_soap.crear', 'notas_soap.asociar_diagnostico', 'notas_soap.firmar',
            'notas_soap.corregir', 'signos_vitales.ver', 'signos_vitales.registrar',
            'laboratorio.ordenes.crear', 'laboratorio.ordenes.ver', 'laboratorio.ordenes.cancelar',
            'medicos.ver_disponibilidad',
            'pacientes.ver',
            'rbac.autorizar',
            'prescripciones.ver', 'prescripciones.crear', 'prescripciones.firmar',
            'laboratorio.catalogo.ver', 'alergias.ver', 'alergias.registrar',
            'alertas.ver', 'alertas.acusar', 'alertas.cerrar',
        ],
        'enfermeria' => [
            // ASII-08: enfermería traslada y consulta la estancia.
            'traslados.registrar', 'traslados.ver',
            'citas.ver', 'documentacion.ver', 'admision.ver',
            'expediente.ver', 'medicamentos.ver',
            'admisiones.ver', 'notas_soap.ver', 'signos_vitales.ver',
            'ocupacion.ver',
            'pacientes.ver',
            'rbac.autorizar', 'prescripciones.ver',
            'muestras.recibir', 'muestras.ver', 'muestras.etiquetar',
            'alergias.ver', 'alergias.registrar',
        ],
        'recepcion' => [
            'citas.ver', 'citas.crear', 'citas.confirmar',
            'citas.cancelar', 'documentacion.ver', 'admision.ver',
            'admisiones.registrar', 'admisiones.ver', 'camas.asignar',
            'ocupacion.ver',
            'medicos.ver_disponibilidad',
            'pacientes.ver', 'pacientes.registrar', 'pacientes.editar',
            'rbac.autorizar', 'muestras.recibir', 'muestras.ver',
            'admision.crear', 'camas.ver',
        ],
        'tecnico_laboratorio' => [
            'reportes.ver', 'documentacion.ver', 'laboratorio.ver',
            'rbac.autorizar', 'muestras.recibir', 'muestras.ver', 'muestras.etiquetar',
            'muestras.rechazar', 'laboratorio.catalogo.ver', 'laboratorio.ordenes.ver',
            'laboratorio.ordenes.actualizar',
            'lab-results.read', 'lab-results.create', 'lab-results.correct',
        ],
        'bioquimico' => [
            'reportes.ver', 'documentacion.ver', 'laboratorio.ver',
            'rbac.autorizar', 'muestras.ver', 'laboratorio.catalogo.ver', 'laboratorio.ordenes.ver',
            'lab-results.read', 'lab-results.validate',
        ],
        'jefatura_medica' => [
            'documentacion.ver', 'alertas.ver', 'alertas.acusar', 'alertas.supervisar',
            'expediente.asignar',
        ],
    ],
    /*
     * Concesiones propuestas que NO están activas. Se documentan para no
     * confundirlas con las activas; ConfiguredPermissions solo lee 'roles'.
     * Formato: permiso => roles que lo recibirían si se aprobara.
     */
    'pending' => [
        'notas_soap.transferir_autoria' => ['medico', 'superadmin'],
        'prescripciones.excepcion_alergia' => ['medico', 'superadmin'],
        'laboratorio.catalogo.administrar' => ['admin_hospital'],
        'alertas.ver' => ['enfermeria'],
        'alertas.acusar' => ['enfermeria'],
    ],
    'catalog' => [
        // ASII-16: transición de órdenes la ejecuta el técnico de laboratorio.
        'laboratorio.ordenes.actualizar',
        'medicamentos.registrar', 'medicamentos.cambiar_vigencia',
        'citas.ver', 'citas.crear', 'citas.confirmar',
        'citas.cancelar', 'reportes.ver', 'reportes.exportar',
        'documentacion.ver', 'admision.ver', 'expediente.ver', 'expediente.abrir', 'expediente.asignar',
        'medicamentos.ver', 'laboratorio.ver', 'auditoria.ver',
        'signos_vitales.corregir', 'alertas.supervisar',
        'admisiones.registrar', 'admisiones.ver', 'camas.asignar',
        'alergias.resolver', 'muestras.rechazar', 'notas_soap.ver',
        'notas_soap.crear', 'notas_soap.asociar_diagnostico', 'notas_soap.firmar',
        'notas_soap.corregir', 'signos_vitales.ver', 'signos_vitales.registrar',
        'ocupacion.ver', 'laboratorio.ordenes.crear', 'laboratorio.ordenes.ver',
        'laboratorio.ordenes.cancelar', 'lab-results.read', 'lab-results.create',
        'lab-results.correct', 'lab-results.validate', 'notas_soap.transferir_autoria',
        'medicos.registrar', 'medicos.asociar_especialidad', 'medicos.ver_disponibilidad',
        'pacientes.ver', 'pacientes.registrar', 'pacientes.editar',
        'rbac.roles.ver', 'rbac.asignar', 'rbac.roles.asignar', 'rbac.autorizar',
        'rbac.permisos.ver', 'rbac.denegaciones.ver',
        'usuarios.ver', 'usuarios.crear', 'usuarios.desactivar', 'tenants.crear', 'sesiones.revocar_ajenas',
        'prescripciones.ver', 'prescripciones.crear', 'prescripciones.firmar',
        'prescripciones.excepcion_alergia', 'muestras.recibir', 'muestras.ver', 'muestras.etiquetar',
        'laboratorio.catalogo.ver', 'laboratorio.catalogo.administrar',
        'alergias.ver', 'alergias.registrar', 'admision.crear', 'camas.ver',
        'alertas.ver', 'alertas.acusar', 'alertas.cerrar',
        'traslados.registrar', 'altas.registrar', 'traslados.ver',
        'qa.ejecuciones.ver', 'qa.ejecuciones.registrar', 'qa.gates.evaluar',
    ],
];
