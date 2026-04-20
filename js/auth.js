/**
 * AUTENTICACIÓN
 * Manejo de login, registro y sesión de usuario
 */

// Usuario actual en sesión
let usuarioActual = null;

/**
 * Inicializar módulo de autenticación
 */
function inicializarAuth() {
    // Event listeners para formularios
    document.getElementById('form-login').addEventListener('submit', manejarLogin);
    document.getElementById('form-registro').addEventListener('submit', manejarRegistro);

    // Event listeners para pestañas
    document.querySelectorAll('.tab[data-tab]').forEach(tab => {
        tab.addEventListener('click', () => cambiarTab(tab.dataset.tab));
    });

    // Verificar si hay sesión guardada
    verificarSesionGuardada();
}

/**
 * Cambiar entre tabs de login y registro
 */
function cambiarTab(tab) {
    // Actualizar tabs activas
    document.querySelectorAll('.tab[data-tab]').forEach(t => {
        t.classList.toggle('activa', t.dataset.tab === tab);
    });

    // Actualizar formularios activos
    document.querySelectorAll('.auth-form').forEach(form => {
        form.classList.remove('activa');
    });

    if (tab === 'login') {
        document.getElementById('form-login').classList.add('activa');
    } else {
        document.getElementById('form-registro').classList.add('activa');
    }
}

/**
 * Manejar login
 */
async function manejarLogin(e) {
    e.preventDefault();

    const email = document.getElementById('login-email').value.trim();
    const password = document.getElementById('login-password').value;

    // Validar campos
    if (!email || !password) {
        mostrarToast('Por favor completa todos los campos', 'error');
        return;
    }

    if (!validarEmail(email)) {
        mostrarToast('El email no es válido', 'error');
        return;
    }

    // Realizar login
    const respuesta = await AuthAPI.login(email, password);

    if (respuesta.success) {
        usuarioActual = respuesta.data;
        // Guardar sesión en localStorage
        guardarSesion(usuarioActual);
        mostrarToast('¡Bienvenido! ' + usuarioActual.nombre, 'success');
        mostrarVistaApp();
    } else {
        mostrarToast(respuesta.mensaje || 'Error al iniciar sesión', 'error');
    }
}

/**
 * Manejar login con Google
 * Esta función es llamada automáticamente por el script de Google
 */
window.handleGoogleLogin = async function(response) {
    if (!response || !response.credential) {
        mostrarToast('Error de autenticación con Google', 'error');
        return;
    }

    // Mostrar loader o toast de info
    mostrarToast('Iniciando sesión con Google...', 'info');

    const respuesta = await AuthAPI.loginGoogle(response.credential);

    if (respuesta.success) {
        usuarioActual = respuesta.data;
        guardarSesion(usuarioActual);
        mostrarToast('¡Bienvenido! ' + usuarioActual.nombre, 'success');
        mostrarVistaApp();
    } else {
        mostrarToast(respuesta.mensaje || 'Error al iniciar sesión con Google', 'error');
    }
};



/**
 * Manejar registro
 */
async function manejarRegistro(e) {
    e.preventDefault();

    const nombre = document.getElementById('registro-nombre').value.trim();
    const apellidos = document.getElementById('registro-apellidos').value.trim();
    const email = document.getElementById('registro-email').value.trim();
    const password = document.getElementById('registro-password').value;

    // Validar campos
    if (!nombre || !apellidos || !email || !password) {
        mostrarToast('Por favor completa todos los campos', 'error');
        return;
    }

    if (!validarEmail(email)) {
        mostrarToast('El email no es válido', 'error');
        return;
    }

    if (password.length < 6) {
        mostrarToast('La contraseña debe tener al menos 6 caracteres', 'error');
        return;
    }

    // Realizar registro
    const respuesta = await AuthAPI.registrar(nombre, apellidos, email, password);

    if (respuesta.success) {
        mostrarToast('¡Cuenta creada! Ahora puedes iniciar sesión', 'success');

        // Cambiar a tab de login y prellenar email
        cambiarTab('login');
        document.getElementById('login-email').value = email;
        document.getElementById('form-registro').reset();
    } else {
        mostrarToast(respuesta.mensaje || 'Error al registrar usuario', 'error');
    }
}

/**
 * Cerrar sesión
 */
async function cerrarSesion() {
    const respuesta = await AuthAPI.logout();

    if (respuesta.success) {
        usuarioActual = null;
        // Limpiar sesión de localStorage
        limpiarSesion();

        // CRÍTICO: Recargar la página para limpiar TODA la información
        // Esto previene que el siguiente usuario vea datos del anterior
        window.location.reload();
    } else {
        mostrarToast('Error al cerrar sesión', 'error');
    }
}

/**
 * Mostrar vista de autenticación
 */
function mostrarVistaAuth() {
    document.getElementById('vista-auth').classList.add('activa');
    document.getElementById('vista-app').classList.remove('activa');

    // Limpiar formularios
    document.getElementById('form-login').reset();
    document.getElementById('form-registro').reset();
}

/**
 * Mostrar vista de aplicación
 */
function mostrarVistaApp() {
    document.getElementById('vista-auth').classList.remove('activa');
    document.getElementById('vista-app').classList.add('activa');

    // Actualizar nombre de usuario en navbar
    document.getElementById('usuario-nombre').textContent =
        `${usuarioActual.nombre} ${usuarioActual.apellidos}`;

    // Mostrar/ocultar opciones de admin
    if (usuarioActual.es_admin) {
        document.querySelectorAll('.admin-only').forEach(el => {
            el.style.display = '';
        });
    } else {
        document.querySelectorAll('.admin-only').forEach(el => {
            el.style.display = 'none';
        });
    }

    // Restaurar sección guardada o cargar catálogo por defecto
    let seccionGuardada = localStorage.getItem('seccionActiva') || 'catalogo';
    
    // Si intenta acceder a admin y no es admin, redirigir a catálogo
    if (seccionGuardada === 'admin' && !usuarioActual.es_admin) {
        seccionGuardada = 'catalogo';
    }
    
    cambiarSeccion(seccionGuardada);
}

/**
 * Verificar si el usuario es admin
 */
function esAdmin() {
    return usuarioActual && usuarioActual.es_admin === true;
}

/**
 * Obtener ID del usuario actual (siempre como Número)
 */
function obtenerUsuarioId() {
    return usuarioActual ? Number(usuarioActual.id) : null;
}

/**
 * Guardar sesión en localStorage
 */
function guardarSesion(usuario) {
    try {
        localStorage.setItem('biblioteca_usuario', JSON.stringify(usuario));
        localStorage.setItem('biblioteca_sesion_activa', 'true');
    } catch (error) {
        console.error('Error al guardar sesión:', error);
    }
}

/**
 * Limpiar sesión de localStorage
 */
function limpiarSesion() {
    try {
        localStorage.removeItem('biblioteca_usuario');
        localStorage.removeItem('biblioteca_sesion_activa');
        localStorage.removeItem('seccionActiva');
    } catch (error) {
        console.error('Error al limpiar sesión:', error);
    }
}

/**
 * Verificar si hay sesión guardada
 */
function verificarSesionGuardada() {
    try {
        const sesionActiva = localStorage.getItem('biblioteca_sesion_activa');
        const usuarioGuardado = localStorage.getItem('biblioteca_usuario');

        if (sesionActiva === 'true' && usuarioGuardado) {
            usuarioActual = JSON.parse(usuarioGuardado);
            mostrarVistaApp();
        } else {
            // No hay sesión guardada, mostrar vista de autenticación
            mostrarVistaAuth();
        }
    } catch (error) {
        console.error('Error al verificar sesión:', error);
        limpiarSesion();
        mostrarVistaAuth();
    }
}
