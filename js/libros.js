/**
 * GESTIÓN DE LIBROS
 * Funciones para manejar el catálogo y gestión de libros
 */

/**
 * Inicializar módulo de libros
 */
function inicializarLibros() {
    // Event listeners para búsqueda
    document.getElementById('btn-buscar').addEventListener('click', buscarLibros);
    document.getElementById('btn-limpiar-busqueda').addEventListener('click', limpiarBusqueda);

    // Búsqueda al presionar Enter
    document.getElementById('buscar-input').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') buscarLibros();
    });

    // Event listeners para agregar libro
    document.getElementById('btn-agregar-libro').addEventListener('click', () => {
        abrirModalLibro();
    });

    document.getElementById('form-libro').addEventListener('submit', guardarLibro);

    // Event listener para importar CSV
    document.getElementById('btn-importar-csv').addEventListener('click', () => {
        abrirModalCSV();
    });

    document.getElementById('form-csv').addEventListener('submit', importarCSV);
}

/**
 * Cargar catálogo de libros
 */
async function cargarCatalogo() {
    const contenedor = document.getElementById('catalogo-libros');
    mostrarLoader(contenedor);

    const respuesta = await LibrosAPI.obtenerTodos();

    if (respuesta.success) {
        renderizarLibros(respuesta.data, contenedor, true);
    } else {
        mostrarEstadoVacio(contenedor, '📚', 'No hay libros', 'Aún no hay libros en el catálogo');
    }
}

/**
 * Buscar libros
 */
async function buscarLibros() {
    const termino = document.getElementById('buscar-input').value.trim();
    const campo = document.getElementById('buscar-campo').value;
    const contenedor = document.getElementById('catalogo-libros');

    if (!termino) {
        cargarCatalogo();
        return;
    }

    mostrarLoader(contenedor);

    const respuesta = await LibrosAPI.buscar(termino, campo);

    if (respuesta.success) {
        if (respuesta.data.length === 0) {
            mostrarEstadoVacio(contenedor, '🔍', 'Sin resultados',
                `No se encontraron libros con "${termino}" en ${campo}`);
        } else {
            renderizarLibros(respuesta.data, contenedor, true);
        }
    } else {
        mostrarToast(respuesta.mensaje || 'Error al buscar', 'error');
    }
}

/**
 * Limpiar búsqueda
 */
function limpiarBusqueda() {
    document.getElementById('buscar-input').value = '';
    cargarCatalogo();
}

/**
 * Cargar mis libros
 */
async function cargarMisLibros() {
    const contenedor = document.getElementById('mis-libros-grid');
    mostrarLoader(contenedor);

    const usuarioId = obtenerUsuarioId();
    const respuesta = await LibrosAPI.obtenerPorPropietario(usuarioId);

    if (respuesta.success) {
        if (respuesta.data.length === 0) {
            mostrarEstadoVacio(contenedor, '📚', 'No tienes libros',
                'Agrega tus primeros libros para compartir');
        } else {
            renderizarLibros(respuesta.data, contenedor, false);
        }
    } else {
        mostrarEstadoVacio(contenedor, '❌', 'Error', 'No se pudieron cargar los libros');
    }
}

/**
 * Renderizar libros en el DOM
 */
function renderizarLibros(libros, contenedor, mostrarBotonPrestamo) {
    if (libros.length === 0) {
        mostrarEstadoVacio(contenedor, '📚', 'No hay libros', 'No se encontraron libros');
        return;
    }

    // URL de la imagen placeholder - SVG simple sin caracteres problemáticos
    const placeholderUrl = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(`
        <svg xmlns="http://www.w3.org/2000/svg" width="128" height="192" viewBox="0 0 128 192">
            <rect fill="#f5f5f5" width="128" height="192"/>
            <rect fill="#e5e5e5" x="20" y="30" width="88" height="132" rx="4"/>
            <rect fill="#d4d4d4" x="30" y="40" width="68" height="4"/>
            <rect fill="#d4d4d4" x="30" y="50" width="68" height="4"/>
            <rect fill="#d4d4d4" x="30" y="60" width="50" height="4"/>
            <text x="64" y="140" font-family="Arial" font-size="11" fill="#737373" text-anchor="middle">Sin portada</text>
        </svg>
    `);

    contenedor.innerHTML = libros.map(libro => {
        const esMiLibro = libro.propietario_id === obtenerUsuarioId();
        const disponible = libro.disponible;
        const imagenUrl = libro.imagen_url || placeholderUrl;

        return `
            <div class="libro-card-modern">
                <div class="libro-cover">
                    <img src="${imagenUrl}" alt="${sanitizarHTML(libro.titulo)}" 
                         onerror="this.src='${placeholderUrl}'">
                </div>
                
                <div class="libro-content">
                    <div class="libro-header">
                        <h3 class="libro-titulo">${sanitizarHTML(libro.titulo)}</h3>
                        <p class="libro-autor">por ${sanitizarHTML(libro.autor)}</p>
                    </div>
                    
                    <div class="libro-meta">
                        <span class="libro-meta-item">
                            <span class="meta-icon">📖</span>
                            ${sanitizarHTML(libro.genero)}
                        </span>
                        <span class="libro-meta-item">
                            <span class="meta-icon">📅</span>
                            ${libro.anio}
                        </span>
                        <span class="libro-meta-item">
                            <span class="meta-icon">👤</span>
                            ${sanitizarHTML(libro.propietario_nombre)}
                        </span>
                    </div>
                    
                    <div class="libro-status">
                        <span class="libro-badge ${disponible ? 'badge-disponible' : 'badge-prestado'}">
                            ${disponible ? '✓ Disponible' : '✗ Prestado'}
                        </span>
                    </div>
                    
                    <div class="libro-actions">
                        ${mostrarBotonPrestamo && !esMiLibro && disponible ?
                `<button class="btn btn-primary btn-block" onclick="pedirPrestado(${libro.id})">
                                📖 Pedir Prestado
                            </button>` : ''}
                        
                        ${!mostrarBotonPrestamo && esMiLibro ?
                `<button class="btn btn-danger btn-block" onclick="eliminarLibro(${libro.id})">
                                🗑️ Eliminar
                            </button>` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

/**
 * Pedir libro prestado
 */
async function pedirPrestado(libroId) {
    if (!confirmar('¿Deseas pedir prestado este libro?')) {
        return;
    }

    const respuesta = await PrestamosAPI.crear(libroId);

    if (respuesta.success) {
        mostrarToast('¡Libro prestado exitosamente!', 'success');
        cargarCatalogo();
    } else {
        mostrarToast(respuesta.mensaje || 'Error al pedir prestado', 'error');
    }
}

/**
 * Eliminar libro
 */
async function eliminarLibro(libroId) {
    if (!await confirmar('¿Estás seguro de eliminar este libro?')) {
        return;
    }

    const respuesta = await LibrosAPI.eliminar(libroId);

    if (respuesta.success) {
        mostrarToast('Libro eliminado', 'success');
        cargarMisLibros();
    } else {
        mostrarToast(respuesta.mensaje || 'Error al eliminar', 'error');
    }
}

/**
 * Abrir modal para agregar libro
 */
function abrirModalLibro() {
    const modal = document.getElementById('modal-libro');
    modal.classList.add('activo');
    document.getElementById('form-libro').reset();
}

/**
 * Cerrar modal de libro
 */
function cerrarModalLibro() {
    const modal = document.getElementById('modal-libro');
    modal.classList.remove('activo');
}

/**
 * Guardar libro
 */
async function guardarLibro(e) {
    e.preventDefault();

    const titulo = document.getElementById('libro-titulo').value.trim();
    const autor = document.getElementById('libro-autor').value.trim();
    const genero = document.getElementById('libro-genero').value.trim();
    const anio = parseInt(document.getElementById('libro-anio').value);

    // Buscar portada automáticamente
    mostrarToast('Buscando portada...', 'info', 2000);
    const imagenUrl = await LibrosAPI.buscarPortada(titulo, autor);

    if (imagenUrl) {
        mostrarToast('¡Portada encontrada!', 'success', 1500);
    } else {
        mostrarToast('No se encontró portada', 'warning', 1500);
    }

    // Crear libro con la URL de la imagen (o null si no se encontró)
    const respuesta = await LibrosAPI.crear(titulo, autor, genero, anio, imagenUrl);

    if (respuesta.success) {
        mostrarToast('Libro agregado exitosamente', 'success');
        cerrarModalLibro();
        cargarMisLibros();
    } else {
        mostrarToast(respuesta.mensaje || 'Error al agregar libro', 'error');
    }
}

/**
 * Abrir modal de importación CSV
 */
function abrirModalCSV() {
    const modal = document.getElementById('modal-csv');
    modal.classList.add('activo');
    document.getElementById('form-csv').reset();
}

/**
 * Cerrar modal CSV
 */
function cerrarModalCSV() {
    const modal = document.getElementById('modal-csv');
    modal.classList.remove('activo');
}

/**
 * Importar libros desde CSV
 */
async function importarCSV(e) {
    e.preventDefault();

    const archivo = document.getElementById('csv-archivo').files[0];

    if (!archivo) {
        mostrarToast('Por favor selecciona un archivo', 'error');
        return;
    }

    mostrarToast('Importando libros...', 'info');

    const respuesta = await LibrosAPI.importarCSV(archivo);

    if (respuesta.success) {
        const { importados, errores } = respuesta.data;

        if (errores && errores.length > 0) {
            mostrarToast(`Importados ${importados} libros con ${errores.length} errores`, 'warning', 5000);
        } else {
            mostrarToast(`${importados} libros importados exitosamente`, 'success');
        }

        cerrarModalCSV();
        cargarMisLibros();
    } else {
        mostrarToast(respuesta.mensaje || 'Error al importar', 'error');
    }
}
