/**
 * GESTIÓN DE PRÉSTAMOS
 * Funciones para manejar préstamos de libros
 */

/**
 * Inicializar módulo de préstamos
 */
function inicializarPrestamos() {
    // Event listeners para tabs de préstamos
    document.querySelectorAll('.tab[data-tab-prestamo]').forEach(tab => {
        tab.addEventListener('click', () => {
            cambiarTabPrestamo(tab.dataset.tabPrestamo);
        });
    });

    // Event listeners para tabs de admin
    document.querySelectorAll('.tab[data-tab-admin]').forEach(tab => {
        tab.addEventListener('click', () => {
            cambiarTabAdmin(tab.dataset.tabAdmin);
        });
    });
}

/**
 * Cambiar tab de préstamos
 */
function cambiarTabPrestamo(tab) {
    // Actualizar tabs activas
    document.querySelectorAll('.tab[data-tab-prestamo]').forEach(t => {
        t.classList.toggle('activa', t.dataset.tabPrestamo === tab);
    });

    // Actualizar contenedores activos
    document.querySelectorAll('.prestamos-container').forEach(container => {
        container.classList.remove('activa');
    });

    if (tab === 'pedidos') {
        document.getElementById('prestamos-pedidos').classList.add('activa');
    } else {
        document.getElementById('prestamos-prestados').classList.add('activa');
    }
}

/**
 * Cargar mis préstamos
 */
async function cargarMisPrestamos() {
    try {
        const usuarioId = obtenerUsuarioId();

        if (!usuarioId) {
            return;
        }

        const respuesta = await PrestamosAPI.obtenerPorUsuario(usuarioId);

        if (respuesta.success) {
            const prestamos = respuesta.data;

            // Separar préstamos:
            // - Pedidos: Libros que YO pedí prestados (soy prestatario Y el dueño NO soy yo)
            // - Prestados: Libros MÍOS que presté a OTROS (soy dueño Y el prestatario NO soy yo)
            const pedidos = prestamos.filter(p =>
                p.prestatario_id === usuarioId &&
                p.libro_propietario_id !== usuarioId &&
                p.activo
            );

            const prestados = prestamos.filter(p =>
                p.libro_propietario_id === usuarioId &&
                p.prestatario_id !== usuarioId &&
                p.activo
            );

            renderizarPrestamosPedidos(pedidos);
            renderizarPrestamosPrestados(prestados);
        } else {
            mostrarToast('Error al cargar préstamos', 'error');
        }
    } catch (error) {
        console.error('Error en cargarMisPrestamos:', error);
        mostrarToast('Error al cargar préstamos', 'error');
    }
}

/**
 * Renderizar préstamos pedidos
 */
function renderizarPrestamosPedidos(prestamos) {
    const contenedor = document.getElementById('lista-prestamos-pedidos');

    if (prestamos.length === 0) {
        mostrarEstadoVacio(contenedor, '📖', 'Sin préstamos activos',
            'No tienes libros pedidos prestados actualmente');
        return;
    }

    contenedor.innerHTML = prestamos.map(prestamo => {
        const dias = prestamo.dias_prestamo;
        const color = obtenerColorDias(dias);

        return `
            <div class="prestamo-card">
                <div class="prestamo-info">
                    <h4 class="prestamo-libro">${sanitizarHTML(prestamo.libro_titulo)}</h4>
                    <div class="prestamo-detalles">
                        <span>📚 ${sanitizarHTML(prestamo.libro_autor)}</span>
                        <span>👤 Propietario: ${sanitizarHTML(prestamo.propietario_nombre)}</span>
                        <span>📅 Desde: ${formatearFechaCorta(prestamo.fecha_prestamo)}</span>
                    </div>
                </div>
                
                <div class="prestamo-dias" style="background: ${color}20; color: ${color};">
                    <span class="dias-numero" style="color: ${color};">${dias}</span>
                    <span>días</span>
                </div>
                
                <div class="prestamo-actions">
                    <button class="btn btn-success" onclick="devolverLibro(${prestamo.id})">
                        ✓ Devolver
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

/**
 * Renderizar libros prestados a otros
 */
function renderizarPrestamosPrestados(prestamos) {
    const contenedor = document.getElementById('lista-prestamos-prestados');

    if (prestamos.length === 0) {
        mostrarEstadoVacio(contenedor, '📚', 'Sin libros prestados',
            'No has prestado libros a otros usuarios');
        return;
    }

    contenedor.innerHTML = prestamos.map(prestamo => {
        const dias = prestamo.dias_prestamo;
        const color = obtenerColorDias(dias);

        return `
            <div class="prestamo-card">
                <div class="prestamo-info">
                    <h4 class="prestamo-libro">${sanitizarHTML(prestamo.libro_titulo)}</h4>
                    <div class="prestamo-detalles">
                        <span>📚 ${sanitizarHTML(prestamo.libro_autor)}</span>
                        <span>👤 Prestado a: ${sanitizarHTML(prestamo.prestatario_nombre)}</span>
                        <span>📅 Desde: ${formatearFechaCorta(prestamo.fecha_prestamo)}</span>
                    </div>
                </div>
                
                <div class="prestamo-dias" style="background: ${color}20; color: ${color};">
                    <span class="dias-numero" style="color: ${color};">${dias}</span>
                    <span>días</span>
                </div>
            </div>
        `;
    }).join('');
}

/**
 * Devolver libro
 */
async function devolverLibro(prestamoId) {
    if (!await confirmar('¿Confirmas la devolución de este libro?')) {
        return;
    }

    const respuesta = await PrestamosAPI.devolver(prestamoId);

    if (respuesta.success) {
        mostrarToast('Libro devuelto exitosamente', 'success');
        cargarMisPrestamos();
        cargarCatalogo(); // Actualizar catálogo para mostrar disponibilidad
    } else {
        mostrarToast(respuesta.mensaje || 'Error al devolver', 'error');
    }
}

/**
 * Cambiar tab de admin
 */
function cambiarTabAdmin(tab) {
    // Actualizar tabs activas
    document.querySelectorAll('.tab[data-tab-admin]').forEach(t => {
        t.classList.toggle('activa', t.dataset.tabAdmin === tab);
    });

    // Actualizar contenedores activos
    document.querySelectorAll('.admin-container').forEach(container => {
        container.classList.remove('activa');
    });

    if (tab === 'prestamos') {
        document.getElementById('admin-prestamos').classList.add('activa');
    } else {
        document.getElementById('admin-usuarios').classList.add('activa');
    }
}

/**
 * Cargar panel de administración
 */
async function cargarPanelAdmin() {
    if (!esAdmin()) {
        mostrarToast('No tienes permisos de administrador', 'error');
        return;
    }

    // Cargar estadísticas
    await cargarEstadisticas();

    // Cargar todos los préstamos
    await cargarTodosPrestamos();

    // Cargar todos los usuarios
    await cargarTodosUsuarios();
}

/**
 * Cargar estadísticas
 */
async function cargarEstadisticas() {
    // Obtener usuarios
    const respUsuarios = await UsuariosAPI.obtenerTodos();
    if (respUsuarios.success) {
        document.getElementById('stat-usuarios').textContent = respUsuarios.data.length;
    }

    // Obtener libros
    const respLibros = await LibrosAPI.obtenerTodos();
    if (respLibros.success) {
        document.getElementById('stat-libros').textContent = respLibros.data.length;
    }

    // Obtener préstamos activos
    const respPrestamos = await PrestamosAPI.obtenerActivos();
    if (respPrestamos.success) {
        document.getElementById('stat-prestamos').textContent = respPrestamos.data.length;
    }
}

/**
 * Cargar todos los préstamos (admin)
 */
async function cargarTodosPrestamos() {
    const contenedor = document.getElementById('admin-lista-prestamos');
    mostrarLoader(contenedor);

    const respuesta = await PrestamosAPI.obtenerTodos();

    if (respuesta.success) {
        const prestamos = respuesta.data;

        if (prestamos.length === 0) {
            mostrarEstadoVacio(contenedor, '🔄', 'Sin préstamos',
                'No hay préstamos registrados en el sistema');
            return;
        }

        contenedor.innerHTML = prestamos.map(prestamo => {
            const dias = prestamo.dias_prestamo;
            const color = obtenerColorDias(dias);
            const activo = prestamo.activo;

            return `
                <div class="prestamo-card">
                    <div class="prestamo-info">
                        <h4 class="prestamo-libro">${sanitizarHTML(prestamo.libro_titulo)}</h4>
                        <div class="prestamo-detalles">
                            <span>📚 ${sanitizarHTML(prestamo.libro_autor)}</span>
                            <span>👤 Prestatario: ${sanitizarHTML(prestamo.prestatario_nombre)}</span>
                            <span>🏠 Propietario: ${sanitizarHTML(prestamo.propietario_nombre)}</span>
                            <span>📅 Préstamo: ${formatearFechaCorta(prestamo.fecha_prestamo)}</span>
                            ${!activo ? `<span>✓ Devuelto: ${formatearFechaCorta(prestamo.fecha_devolucion)}</span>` : ''}
                        </div>
                    </div>
                    
                    ${activo ? `
                        <div class="prestamo-dias" style="background: ${color}20; color: ${color};">
                            <span class="dias-numero" style="color: ${color};">${dias}</span>
                            <span>días</span>
                        </div>
                    ` : `
                        <span class="libro-badge badge-disponible">Devuelto</span>
                    `}
                </div>
            `;
        }).join('');
    } else {
        mostrarEstadoVacio(contenedor, '❌', 'Error', 'No se pudieron cargar los préstamos');
    }
}

/**
 * Cargar todos los usuarios (admin)
 */
async function cargarTodosUsuarios() {
    const contenedor = document.getElementById('admin-lista-usuarios');
    mostrarLoader(contenedor);

    const respuesta = await UsuariosAPI.obtenerTodos();

    if (respuesta.success) {
        const usuarios = respuesta.data;

        contenedor.innerHTML = usuarios.map(usuario => {
            const esAdminUsuario = usuario.es_admin;
            const esUsuarioActual = usuario.id === obtenerUsuarioId();

            return `
                <div class="usuario-card">
                    <div class="usuario-info">
                        <h4>${sanitizarHTML(usuario.nombre)} ${sanitizarHTML(usuario.apellidos)}
                            ${esAdminUsuario ? '<span class="libro-badge badge-disponible">Admin</span>' : ''}
                        </h4>
                        <p>📧 ${sanitizarHTML(usuario.email)}</p>
                        <p>📅 Registrado: ${formatearFechaCorta(usuario.fecha_registro)}</p>
                    </div>
                    
                    ${!esAdminUsuario && !esUsuarioActual ? `
                        <button class="btn btn-danger" onclick="eliminarUsuario(${usuario.id})">
                            🗑️ Eliminar
                        </button>
                    ` : ''}
                </div>
            `;
        }).join('');
    } else {
        mostrarEstadoVacio(contenedor, '❌', 'Error', 'No se pudieron cargar los usuarios');
    }
}

/**
 * Eliminar usuario (admin)
 */
async function eliminarUsuario(usuarioId) {
    if (!await confirmar('¿Estás seguro de eliminar este usuario? Solo se puede eliminar si no tiene préstamos activos.')) {
        return;
    }

    const respuesta = await UsuariosAPI.eliminar(usuarioId);

    if (respuesta.success) {
        mostrarToast('Usuario eliminado', 'success');
        cargarTodosUsuarios();
        cargarEstadisticas();
    } else {
        mostrarToast(respuesta.mensaje || 'Error al eliminar usuario', 'error');
    }
}
