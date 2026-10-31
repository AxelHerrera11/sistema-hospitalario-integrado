import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

test('failed refresh clears the session before the login guard runs', async () => {
    let listener;
    let store;
    let destination;
    let guardResult;
    let bearer = 'expired-token';
    const context = vm.createContext({
        localStorage: { getItem: () => 'hospital-a', removeItem: () => {} },
        setAccessToken: (value) => { bearer = value; },
        registerRefreshHandler: () => {},
        defineStore: (_name, definition) => {
            store = definition.state();
            for (const [name, action] of Object.entries(definition.actions)) {
                store[name] = action.bind(store);
            }
            return () => store;
        },
        createApp: () => ({ use() {}, mount() {} }),
        createPinia: () => ({}), App: {},
        window: { addEventListener: (_event, handler) => { listener = handler; } },
    });
    const source = (path) => readFileSync(new URL(`../../resources/js/${path}`, import.meta.url), 'utf8')
        .replace(/^import .*;\r?$/gm, '').replace(/^export /gm, '');
    vm.runInContext(source('modules/tenants/responsive/formRules.js'), context);
    vm.runInContext(source('stores/logoutPolicy.js'), context);
    vm.runInContext(source('stores/auth.js'), context);
    vm.runInContext(source('router/navigation.js'), context);
    vm.runInContext(source('router/guards.js'), context);
    context.router = {
        push: async (route) => {
            destination = route.name;
            guardResult = await vm.runInContext("authGuard({ meta: { guest: true } })", context);
        },
    };
    vm.runInContext(source('app.js'), context);
    Object.assign(store, { token: 'expired-token', user: { user_id: 'u' }, roles: ['medico'], initialized: true });
    await listener();
    await Promise.resolve();
    assert.equal(destination, 'login');
    assert.equal(store.token, null);
    assert.equal(store.user, null);
    assert.equal(store.roles.length, 0);
    assert.equal(bearer, null);
    assert.equal(guardResult, undefined);
});
