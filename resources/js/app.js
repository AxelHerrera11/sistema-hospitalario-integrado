import './bootstrap';
import '../css/app.css';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import { useAuthStore } from '@/stores/auth';

const app = createApp(App);

app.use(createPinia());
app.use(router);
app.mount('#app');

window.addEventListener('auth:session-expired', () => {
    useAuthStore().clearSession();
    router.push({ name: 'login' });
});
