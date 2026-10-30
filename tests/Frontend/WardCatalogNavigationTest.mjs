import test from 'node:test';
import assert from 'node:assert/strict';
import { NAVIGATION, ROUTE_PERMISSIONS, allowsRoute, visibleNavigation } from '../../resources/js/router/permissions.js';

test('ward catalog route and navigation use the existing camas.ver permission', () => {
    assert.equal(ROUTE_PERMISSIONS.wards, 'camas.ver');
    assert.equal(allowsRoute({ permissions: ['camas.ver'] }, { permission: ROUTE_PERMISSIONS.wards }), true);
    assert.equal(allowsRoute({ permissions: [] }, { permission: ROUTE_PERMISSIONS.wards }), false);

    const item = NAVIGATION.find(({ to }) => to === '/wards');
    assert.deepEqual(item, { label: 'Salas y camas', to: '/wards', permission: 'camas.ver' });
});

test('ward catalog is only visible to users with the effective permission', () => {
    const userWithoutPermission = visibleNavigation({ permissions: [] });
    const authorizedUser = visibleNavigation({ permissions: ['camas.ver'] });

    assert.equal(userWithoutPermission.some(({ to }) => to === '/wards'), false);
    assert.equal(authorizedUser.some(({ to }) => to === '/wards'), true);
});
