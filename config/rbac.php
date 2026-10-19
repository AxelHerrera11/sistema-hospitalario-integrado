<?php

return [
    // Pendiente acordar con los dueños de módulos; sin asignaciones implícitas ni comodines.
    'roles' => [],
    'catalog' => [
        'rbac.permisos.ver', 'rbac.asignar', 'rbac.roles.ver', 'rbac.roles.asignar',
        'rbac.autorizar', 'rbac.denegaciones.ver',
        'prescripciones.ver', 'prescripciones.crear', 'prescripciones.firmar',
        'prescripciones.excepcion_alergia', 'muestras.recibir', 'muestras.ver', 'muestras.etiquetar',
        'laboratorio.catalogo.ver', 'laboratorio.catalogo.administrar',
        'alergias.ver', 'alergias.registrar', 'admision.crear', 'camas.ver',
        'alertas.ver', 'alertas.acusar', 'alertas.cerrar',
    ],
];
