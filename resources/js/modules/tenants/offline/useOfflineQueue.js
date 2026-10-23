import { onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import api from '@/plugins/axios';
import { OfflineQueue } from './offlineQueue';

// Una sola cola por pestaña. El envío usa la instancia axios de /api/v1, que agrega el
// Bearer y X-Tenant-ID vigentes: la cola nunca guarda tokens.
let queue = null;

export function offlineQueue() {
    queue ??= new OfflineQueue({ storage: window.localStorage });

    return queue;
}

export function useOfflineQueue() {
    const cola = offlineQueue();
    const online = ref(navigator.onLine);
    const estado = reactive(cola.summary());
    let unsubscribe = () => {};

    const sincronizar = () => cola.process((request) => api.request(request));
    const alCambiarConexion = () => {
        online.value = navigator.onLine;
        if (online.value) sincronizar();
    };

    onMounted(() => {
        unsubscribe = cola.subscribe((snapshot) => Object.assign(estado, snapshot));
        window.addEventListener('online', alCambiarConexion);
        window.addEventListener('offline', alCambiarConexion);
    });

    onBeforeUnmount(() => {
        unsubscribe();
        window.removeEventListener('online', alCambiarConexion);
        window.removeEventListener('offline', alCambiarConexion);
    });

    return {
        online,
        estado,
        sincronizar,
        reintentar: (id) => cola.retry(id),
        limpiarFallidas: () => cola.clearFailed(),
    };
}
