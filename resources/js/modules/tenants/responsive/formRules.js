// Reglas de los formularios responsive de Tenants (Semana 10). Funciones puras, sin Vue,
// para poder probarlas con node:test igual que permissions.js y ocupacion.js.

const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const UUID = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

export const PASSWORD_CHECKS = [
    { clave: 'longitud', etiqueta: '12+', cumple: (v) => v.length >= 12 },
    { clave: 'mayuscula', etiqueta: 'A-Z', cumple: (v) => /[A-Z]/.test(v) },
    { clave: 'minuscula', etiqueta: 'a-z', cumple: (v) => /[a-z]/.test(v) },
    { clave: 'numero', etiqueta: '0-9', cumple: (v) => /\d/.test(v) },
    { clave: 'simbolo', etiqueta: '!@#', cumple: (v) => /[^A-Za-z0-9]/.test(v) },
];

const NIVELES = ['MUY DÉBIL', 'MUY DÉBIL', 'DÉBIL', 'MEDIA', 'BUENA', 'FUERTE'];

// Misma política que el backend (PasswordPolicy): 12 caracteres y las cuatro clases.
export function passwordStrength(value) {
    const texto = typeof value === 'string' ? value : '';
    const checks = PASSWORD_CHECKS.map(({ clave, etiqueta, cumple }) => ({ clave, etiqueta, ok: cumple(texto) }));
    const puntaje = checks.filter((c) => c.ok).length;

    return { puntaje, porcentaje: puntaje * 20, nivel: NIVELES[puntaje], valida: puntaje === PASSWORD_CHECKS.length, checks };
}

// En el login solo se exige que haya contraseña: la política se aplica al crearla, no al usarla.
export function validateLogin({ tenantId, email, password }) {
    const errores = {};
    if (! tenantId) errores.tenantId = 'Indique el ID del hospital';
    else if (! UUID.test(tenantId.trim())) errores.tenantId = 'El ID del hospital debe ser un UUID';
    if (! email) errores.email = 'El correo es obligatorio';
    else if (! EMAIL.test(email.trim())) errores.email = 'Formato de correo inválido';
    if (! password) errores.password = 'La contraseña es obligatoria';

    return errores;
}

// Evita el doble envío: mientras una acción está en curso, las siguientes llamadas se ignoran
// y devuelven la misma promesa. Lo usan BaseButton y LoginForm (clic, Enter o doble toque).
export function createSubmitGuard() {
    let enCurso = null;

    return {
        get pending() {
            return enCurso !== null;
        },
        run(accion) {
            if (enCurso) return enCurso;
            enCurso = Promise.resolve()
                .then(accion)
                .finally(() => {
                    enCurso = null;
                });

            return enCurso;
        },
    };
}
