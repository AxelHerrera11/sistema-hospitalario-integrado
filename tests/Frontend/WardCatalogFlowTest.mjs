import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { computed, ref } from 'vue';

// Ejecuta el script real de la vista con Vue reactivo y un doble HTTP.
// No monta DOM ni sustituye una comprobación autenticada en navegador.
function page(get) {
    const source = readFileSync(new URL('../../resources/js/modules/salas-camas/pages/WardCatalogPage.vue', import.meta.url), 'utf8');
    const script = source.match(/<script setup>([\s\S]*?)<\/script>/)[1]
        .replace(/^import .*;\r?$/gm, '');
    const context = vm.createContext({ ref, computed, api: { get },
        can: () => true, useAuthStore: () => ({ user: {} }), onMounted: () => {} });
    vm.runInContext(`${script}\nglobalThis.page = { loadWards, selectWard, loadBeds,
        wards, beds, selectedWard, wardsLoading, bedsLoading, wardsError, bedsError,
        bedsCanRetry, accessError };`, context);
    return context.page;
}
const ward = { ward_id: 'ward-1', code: 'W-1' };
const response = (data) => ({ data: { data } });
const failure = (status, code) => ({ response: { status, data: { error: { code } } } });

test('consulta, carga, selección y ward sin camas conservan el contrato', async () => {
    let finish;
    const calls = [];
    const view = page((url) => {
        calls.push(url);
        return url === '/wards' ? new Promise((resolve) => { finish = resolve; }) : Promise.resolve(response([]));
    });
    const loading = view.loadWards();
    assert.equal(view.wardsLoading.value, true);
    finish(response([ward]));
    await loading;
    assert.equal(view.wardsLoading.value, false);
    assert.equal(view.wards.value[0].ward_id, ward.ward_id);
    await view.selectWard(ward);
    assert.equal(view.beds.value.length, 0);
    assert.equal(view.bedsLoading.value, false);
    assert.deepEqual(calls, ['/wards', '/wards/ward-1/beds']);
});

test('503 muestra español legible y permite reintentar sin conservar el error', async () => {
    let attempts = 0;
    const view = page(async () => {
        if (++attempts === 1) throw failure(503, 'WARD_CATALOG_UNAVAILABLE');
        return response([]);
    });
    await view.loadWards();
    assert.equal(view.wardsError.value, 'No se pudo consultar el catálogo de wards. Intenta nuevamente.');
    await view.loadWards();
    assert.equal(view.wardsError.value, '');
    assert.equal(view.wards.value.length, 0);
    assert.equal(attempts, 2);
});

test('respuesta tardía de otra ward no reemplaza sus camas', async () => {
    let finish;
    const view = page((url) => url.includes('ward-1')
        ? new Promise((resolve) => { finish = resolve; })
        : Promise.resolve(response([{ bed_id: 'bed-2', ward_id: 'ward-2' }])));
    const first = view.selectWard(ward);
    await view.selectWard({ ward_id: 'ward-2' });
    finish(response([{ bed_id: 'bed-1', ward_id: 'ward-1' }]));
    await first;
    assert.equal(view.beds.value[0].bed_id, 'bed-2');
    assert.equal(view.bedsLoading.value, false);
});

test('404 impide reintento de camas y 403 limpia el catálogo por acceso', async () => {
    let status = 404;
    const view = page(async () => { throw failure(status, 'WARD_NOT_FOUND'); });
    await view.selectWard(ward);
    assert.equal(view.bedsCanRetry.value, false);
    assert.ok(view.bedsError.value);
    status = 403;
    await view.loadWards();
    assert.ok(view.accessError.value);
    assert.equal(view.selectedWard.value, null);
    assert.equal(view.beds.value.length, 0);
});
