<?php

/*
 * Catálogo del centro de documentación (ASII-24). Cada entrada apunta a un archivo real de docs/ y declara
 * los roles de config/rbac.php que pueden verlo. Agregar un documento es agregar una entrada aquí.
 */
return [
    'catalogo' => [
        ['id' => 'openapi-v1', 'titulo' => 'Especificacion OpenAPI del API v1', 'tipo' => 'contrato', 'version' => '0.1.0',
            'ruta' => 'docs/asii-24-centro-documentacion/openapi.yaml', 'roles' => ['superadmin', 'admin_hospital']],
        ['id' => 'formato-errores', 'titulo' => 'Formato comun de respuestas y errores', 'tipo' => 'guia tecnica', 'version' => '1.0',
            'ruta' => 'docs/asii-24-centro-documentacion/formato-respuestas-errores.md', 'roles' => ['superadmin', 'admin_hospital']],
        ['id' => 'contrato-citas', 'titulo' => 'Contrato de citas y agenda', 'tipo' => 'contrato', 'version' => '1.0',
            'ruta' => 'docs/asii-05-citas/contrato-api.md', 'roles' => ['superadmin', 'admin_hospital', 'recepcion', 'medico']],
        ['id' => 'contrato-reportes', 'titulo' => 'Filtros y exportacion de reportes', 'tipo' => 'contrato', 'version' => '1.0',
            'ruta' => 'docs/asii-27-reportes/contrato-filtros-export.md', 'roles' => ['superadmin', 'admin_hospital', 'medico', 'tecnico_laboratorio', 'bioquimico']],
        ['id' => 'guia-interfaz', 'titulo' => 'Guia de la interfaz base y navegacion por rol', 'tipo' => 'manual', 'version' => '1.0',
            'ruta' => 'docs/asii-26-dashboard-base/contrato-ui-base.md',
            'roles' => ['superadmin', 'admin_hospital', 'medico', 'enfermeria', 'recepcion', 'tecnico_laboratorio', 'bioquimico', 'jefatura_medica']],
        ['id' => 'guia-ocupacion', 'titulo' => 'Ocupacion hospitalaria', 'tipo' => 'manual', 'version' => '1.0',
            'ruta' => 'docs/ocupacion-hospitalaria/README.md', 'roles' => ['superadmin', 'admin_hospital', 'medico', 'enfermeria', 'jefatura_medica']],
    ],
];
