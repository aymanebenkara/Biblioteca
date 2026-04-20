/**
 * APLICACIÓN PRINCIPAL
 * Punto de entrada y coordinación de la aplicación
 */

/**
 * Inicializar aplicación cuando el DOM esté listo
 */
document.addEventListener('DOMContentLoaded', () => {
    inicializarApp();
});

/**
 * Inicializar toda la aplicación
 */
function inicializarApp() {
    console.log('library_books Iniciando Sistema de Préstamo de Libros...');

    // Inicializar módulos
    inicializarAuth(); // Esto llamará a verificarSesionGuardada() que mostrará la vista correcta
    inicializarLibros();
    inicializarPrestamos();
    inicializarNavegacion();
    inicializarModales();

    console.log('✓ Aplicación iniciada correctamente');
}

/**
 * Inicializar sistema de navegación
 */
function inicializarNavegacion() {
    // Event listeners para navegación principal
    document.querySelectorAll('.nav-link[data-seccion]').forEach(link => {
        link.addEventListener('click', () => {
            cambiarSeccion(link.dataset.seccion);
        });
    });

    // Event listener para cerrar sesión
    document.getElementById('btn-logout').addEventListener('click', cerrarSesion);
}

/**
 * Cambiar sección activa
 */
function cambiarSeccion(seccion) {
    // Actualizar links activos
    document.querySelectorAll('.nav-link[data-seccion]').forEach(link => {
        link.classList.toggle('activa', link.dataset.seccion === seccion);
    });

    // Actualizar secciones activas
    document.querySelectorAll('.seccion').forEach(sec => {
        sec.classList.remove('activa');
    });

    document.getElementById(`seccion-${seccion}`).classList.add('activa');

    // Guardar sección activa en localStorage
    localStorage.setItem('seccionActiva', seccion);

    // Cargar datos según la sección
    switch (seccion) {
        case 'catalogo':
            cargarCatalogo();
            break;
        case 'mis-libros':
            cargarMisLibros();
            break;
        case 'mis-prestamos':
            cargarMisPrestamos();
            break;
        case 'admin':
            cargarPanelAdmin();
            break;
    }
}

/**
 * Inicializar modales
 */
function inicializarModales() {
    // Cerrar modales al hacer click en el botón de cerrar
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', cerrarTodosModales);
    });

    // Cerrar modales al hacer click fuera del contenido
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                cerrarTodosModales();
            }
        });
    });

    // Cerrar modales con tecla ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            cerrarTodosModales();
        }
    });
}

/**
 * Cerrar todos los modales
 */
function cerrarTodosModales() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.classList.remove('activo');
    });
}

/**
 * Manejo de errores global
 */
window.addEventListener('error', (e) => {
    console.error('Error global:', e.error);
    mostrarToast('Ha ocurrido un error inesperado', 'error');
});

/**
 * Manejo de promesas rechazadas
 */
window.addEventListener('unhandledrejection', (e) => {
    console.error('Promesa rechazada:', e.reason);
    mostrarToast('Error en la comunicación con el servidor', 'error');
});

/**
 * Prevenir envío de formularios por defecto
 */
document.addEventListener('submit', (e) => {
    // Los formularios ya tienen sus propios handlers
    // Esta es una capa extra de seguridad
}, true);

/**
 * Utilidades globales para debugging (solo en desarrollo)
 */
if (window.location.hostname === 'localhost') {
    window.debugApp = {
        usuarioActual: () => usuarioActual,
        cargarCatalogo,
        cargarMisLibros,
        cargarMisPrestamos,
        cargarPanelAdmin,
        mostrarToast
    };

    console.log('🔧 Modo desarrollo activado. Usa window.debugApp para debugging.');
}
