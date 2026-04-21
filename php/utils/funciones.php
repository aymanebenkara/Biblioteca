<?php

/**
 * FUNCIONES AUXILIARES MEJORADAS
 * NOTA EDUCATIVA: Este archivo contiene mejoras de seguridad y validación
 * Incluye las funciones originales más las nuevas funciones de validación
 */

// ============================================
// CONFIGURACIÓN DE SESIÓN CON TIMEOUT
// ============================================
// NOTA EDUCATIVA: Las sesiones permiten recordar quién está conectado
// Pero es importante que expiren por seguridad

if (session_status() === PHP_SESSION_NONE) {
    // SEGURIDAD: Configurar tiempo de vida de la sesión
    // 1800 segundos = 30 minutos de inactividad
    ini_set('session.gc_maxlifetime', 1800);

    // Cookie de sesión expira al cerrar el navegador
    session_set_cookie_params(0);

    session_start();

    // IMPORTANTE: Verificar tiempo de última actividad
    if (isset($_SESSION['ultima_actividad'])) {
        $tiempoInactivo = time() - $_SESSION['ultima_actividad'];
        if ($tiempoInactivo > 1800) { // 30 minutos
            // Sesión expirada por inactividad
            session_unset();
            session_destroy();
            session_start();
        }
    }

    // Actualizar tiempo de última actividad
    $_SESSION['ultima_actividad'] = time();
}

// ============================================
// FUNCIONES DE RESPUESTA
// ============================================

/**
 * Enviar respuesta JSON al cliente
 * NOTA EDUCATIVA: Esta función estandariza las respuestas de la API
 * Siempre devuelve el mismo formato: success, mensaje, data
 * 
 * @param bool $success Indica si la operación fue exitosa
 * @param mixed $data Datos a enviar
 * @param string $mensaje Mensaje descriptivo
 * @param int $codigo Código HTTP de respuesta
 */
function enviarRespuesta($success, $data = null, $mensaje = '', $codigo = 200)
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');

    $respuesta = [
        'success' => $success,
        'mensaje' => $mensaje,
        'data' => $data
    ];

    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================
// FUNCIONES DE AUTENTICACIÓN
// ============================================

/**
 * Verificar si el usuario está autenticado
 * @return bool True si está autenticado, false si no
 */
function estaAutenticado()
{
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Obtener el ID del usuario actual
 * @return int|null ID del usuario o null si no está autenticado
 */
function obtenerUsuarioId()
{
    return $_SESSION['usuario_id'] ?? null;
}

/**
 * Verificar si el usuario actual es administrador
 * @return bool True si es admin, false si no
 */
function esAdmin()
{
    return isset($_SESSION['es_admin']) && $_SESSION['es_admin'] === true;
}

/**
 * Requerir autenticación - termina el script si no está autenticado
 */
function requerirAutenticacion()
{
    if (!estaAutenticado()) {
        enviarRespuesta(false, null, 'No autenticado. Por favor inicia sesión.', 401);
    }
}

/**
 * Requerir permisos de administrador
 */
function requerirAdmin()
{
    requerirAutenticacion();

    if (!esAdmin()) {
        enviarRespuesta(false, null, 'Acceso denegado. Se requieren permisos de administrador.', 403);
    }
}

// ============================================
// FUNCIONES DE SANITIZACIÓN Y VALIDACIÓN
// ============================================

/**
 * Sanitizar entrada de texto
 * NOTA EDUCATIVA: Esto limpia el texto para prevenir ataques XSS
 * XSS = Cross-Site Scripting (inyección de código malicioso)
 * 
 * @param string $data Datos a sanitizar
 * @return string Datos sanitizados
 */
function sanitizar($data)
{
    $data = trim($data); // Quitar espacios al inicio y final
    $data = stripslashes($data); // Quitar barras invertidas
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); // Convertir caracteres especiales
    return $data;
}

/**
 * Validar email
 * @param string $email Email a validar
 * @return bool True si es válido, false si no
 */
function validarEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validar año
 * NOTA EDUCATIVA: Permitimos años desde 1000 hasta el año siguiente al actual
 * Esto permite registrar libros que saldrán el próximo año
 * 
 * @param int $anio Año a validar
 * @return bool True si es válido, false si no
 */
function validarAnio($anio)
{
    $anioActual = date('Y');
    return is_numeric($anio) && $anio >= 1000 && $anio <= $anioActual + 1;
}

/**
 * Validar campos requeridos (MEJORADO)
 * NOTA EDUCATIVA: Esta versión mejorada maneja correctamente valores como 0 o false
 * que pueden ser válidos en algunos casos
 * 
 * @param array $datos Datos a validar
 * @param array $camposRequeridos Campos que deben estar presentes
 * @return array Array con 'valido' (bool) y 'mensaje' (string)
 */
function validarCamposRequeridos($datos, $camposRequeridos)
{
    $camposFaltantes = [];

    foreach ($camposRequeridos as $campo) {
        // Verificar si el campo existe
        if (!isset($datos[$campo])) {
            $camposFaltantes[] = $campo;
        }
        // Si es string, verificar que no esté vacío
        elseif (is_string($datos[$campo]) && trim($datos[$campo]) === '') {
            $camposFaltantes[] = $campo;
        }
        // IMPORTANTE: No rechazamos valores 0 o false porque pueden ser válidos
    }

    if (!empty($camposFaltantes)) {
        return [
            'valido' => false,
            'mensaje' => 'Campos requeridos faltantes: ' . implode(', ', $camposFaltantes)
        ];
    }

    return ['valido' => true, 'mensaje' => ''];
}

// ============================================
// FUNCIONES DE VALIDACIÓN DE ARCHIVOS (NUEVO)
// ============================================

/**
 * Validar tamaño de archivo
 * NOTA EDUCATIVA: Limitar el tamaño previene ataques de denegación de servicio (DoS)
 * donde alguien intenta saturar el servidor con archivos gigantes
 * 
 * @param array $archivo Array de $_FILES
 * @param int $tamañoMaximoMB Tamaño máximo en megabytes (por defecto 5MB)
 * @return bool True si es válido
 */
function validarTamañoArchivo($archivo, $tamañoMaximoMB = 5)
{
    // Convertir MB a bytes (1 MB = 1024 * 1024 bytes)
    $tamañoMaximoBytes = $tamañoMaximoMB * 1024 * 1024;

    return $archivo['size'] <= $tamañoMaximoBytes;
}

/**
 * Validar tipo MIME de archivo
 * NOTA EDUCATIVA: El tipo MIME es la identificación "real" del archivo
 * No confiar solo en la extensión porque alguien puede renombrar virus.exe a documento.csv
 * 
 * @param array $archivo Array de $_FILES
 * @param array $tiposPermitidos Array de tipos MIME permitidos
 * @return bool True si es válido
 */
function validarTipoMIME($archivo, $tiposPermitidos)
{
    $tipoMIME = $archivo['type'];
    return in_array($tipoMIME, $tiposPermitidos);
}

// ============================================
// FUNCIONES AUXILIARES
// ============================================

/**
 * Obtener datos del cuerpo de la petición (para PUT, DELETE, etc.)
 * @return array Datos parseados
 */
function obtenerDatosBody()
{
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    return $data ?? [];
}

/**
 * Calcular días entre dos fechas
 * @param string $fechaInicio Fecha de inicio
 * @param string $fechaFin Fecha de fin (opcional, por defecto hoy)
 * @return int Número de días
 */
function calcularDias($fechaInicio, $fechaFin = null)
{
    $inicio = new DateTime($fechaInicio);
    $fin = $fechaFin ? new DateTime($fechaFin) : new DateTime();
    $diferencia = $inicio->diff($fin);
    return $diferencia->days;
}

/**
 * Registrar actividad en log (opcional, para debugging)
 * @param string $mensaje Mensaje a registrar
 */
function registrarLog($mensaje)
{
    $fecha = date('Y-m-d H:i:s');
    $logFile = __DIR__ . '/../../logs/app.log';

    // Crear directorio de logs si no existe
    $logDir = dirname($logFile);
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $logMensaje = "[{$fecha}] {$mensaje}" . PHP_EOL;
    file_put_contents($logFile, $logMensaje, FILE_APPEND);
}

// ============================================
// FUNCIONES DE GOOGLE BOOKS API
// ============================================

/**
 * Buscar portada de libro en APIs (OpenLibrary y Google Books)
 * NOTA EDUCATIVA: Esta función busca la imagen de portada de un libro
 * usando APIs gratuitas
 * 
 * @param string $titulo Título del libro
 * @param string $autor Autor del libro
 * @return string|null URL de la imagen o null si no se encuentra
 */
function buscarPortadaGoogleBooks($titulo, $autor)
{
    // Estrategia 1: Búsqueda en OpenLibrary (título y autor)
    $query = urlencode($titulo . ' ' . $autor);
    $imagenUrl = buscarEnOpenLibraryAPI($query);
    if ($imagenUrl) {
        return $imagenUrl;
    }

    // Estrategia 2: Búsqueda en OpenLibrary (solo título)
    $query = urlencode($titulo);
    $imagenUrl = buscarEnOpenLibraryAPI($query);
    if ($imagenUrl) {
        return $imagenUrl;
    }

    // Estrategia 3: Fallback a Google Books (puede fallar por cuota)
    $query = 'intitle:' . urlencode($titulo) . '+inauthor:' . urlencode($autor);
    $imagenUrl = buscarEnGoogleBooksAPI($query);
    
    return $imagenUrl;
}

/**
 * Función auxiliar para consultar OpenLibrary API
 * 
 * @param string $query Query de búsqueda
 * @return string|null URL de la imagen o null
 */
function buscarEnOpenLibraryAPI($query)
{
    $url = "https://openlibrary.org/search.json?q={$query}&limit=1";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'BibliotecaApp/1.0');

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // curl_close is no longer required in PHP 8+ and can cause deprecation notices

    if ($httpCode !== 200 || !$response) {
        return null;
    }

    $data = json_decode($response, true);

    if (isset($data['docs'][0]['cover_i'])) {
        $coverId = $data['docs'][0]['cover_i'];
        return "https://covers.openlibrary.org/b/id/{$coverId}-L.jpg";
    }

    return null;
}

/**
 * Función auxiliar para consultar Google Books API
 * 
 * @param string $query Query de búsqueda
 * @return string|null URL de la imagen o null
 */
function buscarEnGoogleBooksAPI($query)
{
    $url = "https://www.googleapis.com/books/v1/volumes?q={$query}&maxResults=1";

    // Usar cURL para hacer la petición
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // curl_close is no longer required in PHP 8+ and can cause deprecation notices

    if ($httpCode !== 200 || !$response) {
        return null;
    }

    $data = json_decode($response, true);

    if (isset($data['items'][0]['volumeInfo']['imageLinks'])) {
        $imageLinks = $data['items'][0]['volumeInfo']['imageLinks'];
        $imagenUrl = $imageLinks['thumbnail'] ?? $imageLinks['smallThumbnail'] ?? null;

        if ($imagenUrl) {
            // Convertir a HTTPS por seguridad
            return str_replace('http:', 'https:', $imagenUrl);
        }
    }

    return null;
}

// ============================================
// FUNCIONES DE VALIDACIÓN DE URLS
// ============================================

/**
 * Validar y limpiar URL de imagen
 * NOTA EDUCATIVA: No usamos sanitizar() (htmlspecialchars) en URLs
 * porque convierte & en &amp; y rompe los parámetros de la URL.
 * En su lugar, validamos que sea una URL HTTPS legítima.
 * 
 * @param string $url URL a validar
 * @return string|null URL limpia o null si no es válida
 */
function validarURLImagen($url)
{
    if (empty($url)) {
        return null;
    }

    // Limpiar espacios
    $url = trim($url);

    // Validar que sea una URL válida con filtro de PHP
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }

    // Solo permitir HTTPS por seguridad
    if (strpos($url, 'https://') !== 0) {
        return null;
    }

    // Lista blanca de dominios de imágenes permitidos
    $dominiosPermitidos = [
        'books.google.com',
        'lh3.googleusercontent.com',
        'covers.openlibrary.org',
        'images-na.ssl-images-amazon.com',
        'i.gr-assets.com',
        'googleapis.com'
    ];

    $host = parse_url($url, PHP_URL_HOST);
    $dominioValido = false;

    foreach ($dominiosPermitidos as $dominio) {
        if ($host === $dominio || str_ends_with($host, '.' . $dominio)) {
            $dominioValido = true;
            break;
        }
    }

    if (!$dominioValido) {
        return null;
    }

    return $url;
}
