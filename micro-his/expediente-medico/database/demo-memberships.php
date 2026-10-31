<?php

declare(strict_types=1);

/**
 * Membresías FICTICIAS usuario → hospital → rol para el prototipo y las pruebas (database/demo.sql).
 * PROVISIONAL: en el sistema real salen de hospital_memberships (CENTRAL) y las resuelve Auth/RBAC; el micro-HIS
 * no tiene sesión JWT. La dra. López también es miembro de hospital-norte, que no tiene base configurada (503).
 *
 * @return list<array{0: string, 1: string, 2: string}>
 */
return [
    ['11111111-1111-4111-8111-111111111111', 'hospital-demo', 'medico'],
    ['22222222-2222-4222-8222-222222222222', 'hospital-demo', 'medico'],
    ['33333333-3333-4333-8333-333333333333', 'hospital-demo', 'enfermeria'],
    ['44444444-4444-4444-8444-444444444444', 'hospital-demo', 'recepcion'],
    ['55555555-5555-4555-8555-555555555555', 'hospital-demo', 'admin_hospital'],
    ['11111111-1111-4111-8111-111111111111', 'hospital-norte', 'medico'],
];
