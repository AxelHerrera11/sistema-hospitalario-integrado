// Contrato del cierre de sesión: si el servidor ya revocó la sesión, el
// logout local es exitoso aunque el POST responda 401. Funciones puras,
// sin Pinia ni Axios, para probarlas con node:test igual que formRules.js.
import { createSubmitGuard } from '../modules/tenants/responsive/formRules.js';

const RAZON_SESION_REVOCADA = 'SESSION_REVOKED';

// Un 401 con razon SESSION_REVOKED no es un fallo de revocacion: el servidor
// ya cerro esa sesion (p. ej. segundo clic con el token ya invalidado).
export function isSessionAlreadyClosed(error) {
    const status = error?.response?.status;
    const razon = error?.response?.data?.error;

    return status === 401 && razon === RAZON_SESION_REVOCADA;
}

// El logout es una operacion no idempotente en la interfaz: evita que dos
// clics seguidos lancen dos POST y que el segundo marque un aviso falso.
export function createLogoutGuard() {
    return createSubmitGuard();
}
