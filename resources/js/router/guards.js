import { useAuthStore } from '@/stores/auth';
import { authorizeNavigation } from './navigation.js';

export async function authGuard(to) {
    return authorizeNavigation(useAuthStore(), to);
}
