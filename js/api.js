/**
 * API - COMUNICACIÓN CON EL BACKEND PHP
 * Funciones para hacer peticiones HTTP al servidor
 */

// URL base de la API
const API_BASE = 'php';

/**
 * Realizar petición HTTP
 * @param {string} endpoint - Endpoint de la API
 * @param {object} opciones - Opciones de fetch
 * @returns {Promise} Respuesta de la API
 */
async function peticionAPI(endpoint, opciones = {}) {
    try {
        const url = `${API_BASE}/${endpoint}`;

        const config = {
            ...opciones,
            credentials: 'include', // Garantizar que se envíen las cookies de sesión (PHPSESSID)
            headers: {
                'Content-Type': 'application/json',
                ...opciones.headers
            }
        };

        const respuesta = await fetch(url, config);
        const datos = await respuesta.json();

        return datos;

    } catch (error) {
        console.error('Error en petición API:', error);
        return {
            success: false,
            mensaje: 'Error de conexión con el servidor'
        };
    }
}

/**
 * API de Autenticación
 */
const AuthAPI = {
    /**
     * Registrar nuevo usuario
     */
    async registrar(nombre, apellidos, email, password) {
        return await peticionAPI('auth/registro.php', {
            method: 'POST',
            body: JSON.stringify({ nombre, apellidos, email, password })
        });
    },

    /**
     * Iniciar sesión
     */
    async login(email, password) {
        return await peticionAPI('auth/login.php', {
            method: 'POST',
            body: JSON.stringify({ email, password })
        });
    },

    /**
     * Iniciar sesión con Google
     */
    async loginGoogle(credential) {
        return await peticionAPI('auth/google.php', {
            method: 'POST',
            body: JSON.stringify({ credential })
        });
    },



    /**
     * Cerrar sesión
     */
    async logout() {
        return await peticionAPI('auth/logout.php', {
            method: 'POST'
        });
    }
};

/**
 * API de Usuarios
 */
const UsuariosAPI = {
    /**
     * Obtener todos los usuarios (admin)
     */
    async obtenerTodos() {
        return await peticionAPI('api/usuarios.php');
    },

    /**
     * Obtener usuario por ID
     */
    async obtenerPorId(id) {
        return await peticionAPI(`api/usuarios.php?id=${id}`);
    },

    /**
     * Eliminar usuario
     */
    async eliminar(id) {
        return await peticionAPI(`api/usuarios.php?id=${id}`, {
            method: 'DELETE'
        });
    }
};

/**
 * API de Libros
 */
const LibrosAPI = {
    /**
     * Obtener todos los libros
     */
    async obtenerTodos() {
        return await peticionAPI('api/libros.php');
    },

    /**
     * Obtener libro por ID
     */
    async obtenerPorId(id) {
        return await peticionAPI(`api/libros.php?id=${id}`);
    },

    /**
     * Buscar libros
     */
    async buscar(termino, campo) {
        return await peticionAPI(`api/libros.php?buscar=${encodeURIComponent(termino)}&campo=${campo}`);
    },

    /**
     * Obtener libros disponibles
     */
    async obtenerDisponibles() {
        return await peticionAPI('api/libros.php?disponibles=1');
    },

    /**
     * Obtener libros de un propietario
     */
    async obtenerPorPropietario(propietarioId) {
        return await peticionAPI(`api/libros.php?propietario=${propietarioId}`);
    },

    /**
     * Crear nuevo libro
     */
    async crear(titulo, autor, genero, anio, imagenUrl = null) {
        return await peticionAPI('api/libros.php', {
            method: 'POST',
            body: JSON.stringify({ titulo, autor, genero, anio, imagen_url: imagenUrl })
        });
    },

    /**
     * Importar libros desde CSV
     */
    async importarCSV(archivo) {
        const formData = new FormData();
        formData.append('archivo', archivo);

        try {
            const respuesta = await fetch(`${API_BASE}/api/libros.php?importar=csv`, {
                method: 'POST',
                body: formData
            });

            return await respuesta.json();
        } catch (error) {
            console.error('Error al importar CSV:', error);
            return {
                success: false,
                mensaje: 'Error al importar el archivo'
            };
        }
    },

    /**
     * Eliminar libro
     */
    async eliminar(id) {
        return await peticionAPI(`api/libros.php?id=${id}`, {
            method: 'DELETE'
        });
    },

    /**
     * Buscar portada de libro en APIs (OpenLibrary y Google Books) con múltiples estrategias
     */
    async buscarPortada(titulo, autor) {
        try {
            // Estrategia 1: Búsqueda exacta con título y autor
            let query = `title:${encodeURIComponent(titulo)} author:${encodeURIComponent(autor)}`;
            let imagenUrl = await this._buscarPortadaAPI(query);

            if (imagenUrl) return imagenUrl;

            // Estrategia 2: Solo título (para casos donde el autor no coincide exactamente)
            query = `title:${encodeURIComponent(titulo)}`;
            imagenUrl = await this._buscarPortadaAPI(query);

            if (imagenUrl) return imagenUrl;

            // Estrategia 3: Búsqueda general (título + autor sin restricciones)
            query = encodeURIComponent(`${titulo} ${autor}`);
            imagenUrl = await this._buscarPortadaAPI(query);

            return imagenUrl;
        } catch (error) {
            console.error('Error al buscar portada:', error);
            return null;
        }
    },

    /**
     * Función auxiliar para buscar en APIs de libros
     */
    async _buscarPortadaAPI(query) {
        try {
            // Intentar con OpenLibrary primero (es libre y no requiere API key)
            const openLibraryUrl = `https://openlibrary.org/search.json?q=${query}&limit=1`;
            const respuestaOL = await fetch(openLibraryUrl);
            const datosOL = await respuestaOL.json();

            if (datosOL.docs && datosOL.docs.length > 0) {
                const libroOL = datosOL.docs[0];
                if (libroOL.cover_i) {
                    return `https://covers.openlibrary.org/b/id/${libroOL.cover_i}-L.jpg`;
                }
            }

            // Fallback: Google Books API (limitado)
            const googleUrl = `https://www.googleapis.com/books/v1/volumes?q=${query}&maxResults=1`;
            const respuestaGB = await fetch(googleUrl);
            const datosGB = await respuestaGB.json();

            if (datosGB.items && datosGB.items.length > 0) {
                const libroGB = datosGB.items[0];
                if (libroGB.volumeInfo && libroGB.volumeInfo.imageLinks) {
                    const imagenUrl = libroGB.volumeInfo.imageLinks.thumbnail || 
                                      libroGB.volumeInfo.imageLinks.smallThumbnail;
                    return imagenUrl ? imagenUrl.replace('http:', 'https:') : null;
                }
            }

            return null;
        } catch (error) {
            console.error('Error en búsqueda de portadas:', error);
            return null;
        }
    }
};

/**
 * API de Préstamos
 */
const PrestamosAPI = {
    /**
     * Obtener todos los préstamos (admin)
     */
    async obtenerTodos() {
        return await peticionAPI('api/prestamos.php');
    },

    /**
     * Obtener préstamos de un usuario
     */
    async obtenerPorUsuario(usuarioId) {
        return await peticionAPI(`api/prestamos.php?usuario=${usuarioId}`);
    },

    /**
     * Obtener préstamos activos
     */
    async obtenerActivos() {
        return await peticionAPI('api/prestamos.php?activos=1');
    },

    /**
     * Crear nuevo préstamo
     */
    async crear(libroId) {
        return await peticionAPI('api/prestamos.php', {
            method: 'POST',
            body: JSON.stringify({ libro_id: libroId })
        });
    },

    /**
     * Devolver libro
     */
    async devolver(prestamoId) {
        return await peticionAPI(`api/prestamos.php?id=${prestamoId}&devolver=1`, {
            method: 'PUT'
        });
    }
};
