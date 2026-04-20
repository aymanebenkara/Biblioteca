/**
 * UTILIDADES GENERALES
 * Funciones auxiliares para toda la aplicación
 */

/**
 * Mostrar notificación toast
 * @param {string} mensaje - Mensaje a mostrar
 * @param {string} tipo - Tipo: 'success', 'error', 'info', 'warning'
 * @param {number} duracion - Duración en ms (por defecto 3000)
 */
function mostrarToast(mensaje, tipo = 'info', duracion = 3000) {
    const container = document.getElementById('toast-container');

    const toast = document.createElement('div');
    toast.className = `toast toast-${tipo}`;
    toast.innerHTML = mensaje;

    container.appendChild(toast);

    // Eliminar después de la duración
    setTimeout(() => {
        toast.style.animation = 'slideOut 300ms ease';
        setTimeout(() => toast.remove(), 300);
    }, duracion);
}

/**
 * Formatear fecha a formato legible
 * @param {string} fecha - Fecha en formato ISO
 * @returns {string} Fecha formateada
 */
function formatearFecha(fecha) {
    const date = new Date(fecha);
    const opciones = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return date.toLocaleDateString('es-ES', opciones);
}

/**
 * Formatear fecha corta (solo día)
 * @param {string} fecha - Fecha en formato ISO
 * @returns {string} Fecha formateada
 */
function formatearFechaCorta(fecha) {
    const date = new Date(fecha);
    const opciones = {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    };
    return date.toLocaleDateString('es-ES', opciones);
}

/**
 * Calcular días entre dos fechas
 * @param {string} fechaInicio - Fecha de inicio
 * @param {string} fechaFin - Fecha de fin (opcional, por defecto hoy)
 * @returns {number} Número de días
 */
function calcularDias(fechaInicio, fechaFin = null) {
    const inicio = new Date(fechaInicio);
    const fin = fechaFin ? new Date(fechaFin) : new Date();
    const diferencia = fin - inicio;
    return Math.floor(diferencia / (1000 * 60 * 60 * 24));
}

/**
 * Validar email
 * @param {string} email - Email a validar
 * @returns {boolean} True si es válido
 */
function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Sanitizar HTML para prevenir XSS
 * @param {string} texto - Texto a sanitizar
 * @returns {string} Texto sanitizado
 */
function sanitizarHTML(texto) {
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}

/**
 * Mostrar estado vacío
 * @param {HTMLElement} contenedor - Contenedor donde mostrar
 * @param {string} icono - Emoji del icono
 * @param {string} titulo - Título del mensaje
 * @param {string} descripcion - Descripción
 */
function mostrarEstadoVacio(contenedor, icono, titulo, descripcion) {
    contenedor.innerHTML = `
        <div class="empty-state">
            <div class="empty-state-icon material-symbols-outlined" style="font-size: 3rem;">${icono}</div>
            <h3>${titulo}</h3>
            <p>${descripcion}</p>
        </div>
    `;
}

/**
 * Mostrar loader
 * @param {HTMLElement} contenedor - Contenedor donde mostrar
 */
function mostrarLoader(contenedor) {
    contenedor.innerHTML = `
        <div class="empty-state">
            <div class="empty-state-icon material-symbols-outlined" style="font-size: 3rem;">hourglass_empty</div>
            <h3>Cargando...</h3>
        </div>
    `;
}

/**
 * Confirmar acción con modal personalizado
 * @param {string} mensaje - Mensaje de confirmación
 * @returns {Promise<boolean>} True si confirma
 */
function confirmar(mensaje) {
    return new Promise((resolve) => {
        // Crear modal de confirmación
        const modal = document.createElement('div');
        modal.className = 'modal activo';
        modal.style.zIndex = '10000';

        modal.innerHTML = `
            <div class="modal-content" style="max-width: 450px;">
                <div class="modal-header">
                    <h3><span class="material-symbols-outlined">warning</span> Confirmación</h3>
                </div>
                <div class="modal-body">
                    <p>${mensaje}</p>
                </div>
                <div class="modal-actions" style="padding: 0 var(--spacing-lg) var(--spacing-lg);">
                    <button type="button" class="btn btn-secondary" id="modal-cancel">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="modal-confirm">Confirmar</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Event listeners
        document.getElementById('modal-cancel').onclick = () => {
            modal.remove();
            resolve(false);
        };

        document.getElementById('modal-confirm').onclick = () => {
            modal.remove();
            resolve(true);
        };

        // Cerrar con ESC
        const handleEsc = (e) => {
            if (e.key === 'Escape') {
                modal.remove();
                resolve(false);
                document.removeEventListener('keydown', handleEsc);
            }
        };
        document.addEventListener('keydown', handleEsc);

        // Cerrar al hacer clic fuera del modal
        modal.onclick = (e) => {
            if (e.target === modal) {
                modal.remove();
                resolve(false);
            }
        };
    });
}

/**
 * Obtener color según días de préstamo
 * @param {number} dias - Número de días
 * @returns {string} Color CSS
 */
function obtenerColorDias(dias) {
    if (dias < 7) return 'var(--color-success)';
    if (dias < 14) return 'var(--color-warning)';
    return 'var(--color-danger)';
}

/**
 * Debounce para optimizar búsquedas
 * @param {Function} func - Función a ejecutar
 * @param {number} wait - Tiempo de espera en ms
 * @returns {Function} Función con debounce
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Agregar animación de salida para toast
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
