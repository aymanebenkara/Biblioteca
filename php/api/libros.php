<?php

/**
 * API de Gestión de Libros
 * Endpoints:
 * - GET /php/api/libros.php - Obtener todos los libros
 * - GET /php/api/libros.php?id=X - Obtener libro específico
 * - GET /php/api/libros.php?buscar=X&campo=Y - Buscar libros
 * - GET /php/api/libros.php?disponibles=1 - Libros disponibles
 * - GET /php/api/libros.php?propietario=X - Libros de un usuario
 * - POST /php/api/libros.php - Crear nuevo libro
 * - POST /php/api/libros.php?importar=csv - Importación masiva CSV
 * - DELETE /php/api/libros.php?id=X - Eliminar libro
 */

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir archivos necesarios
require_once '../config/database.php';
require_once '../utils/funciones.php';

// Configurar headers CORS y JSON
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Conectar a la base de datos
$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    enviarRespuesta(false, null, 'Error de conexión a la base de datos.', 500);
}

// Manejar diferentes métodos HTTP
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    switch ($metodo) {
        case 'GET':
            // Obtener libros con diferentes filtros

            if (isset($_GET['id'])) {
                // Obtener libro específico
                $id = intval($_GET['id']);

                $query = "SELECT l.*, u.nombre as propietario_nombre, u.apellidos as propietario_apellidos,
                          (SELECT COUNT(*) FROM prestamos p WHERE p.libro_id = l.id AND p.fecha_devolucion IS NULL) as prestado
                          FROM libros l
                          INNER JOIN usuarios u ON l.propietario_id = u.id
                          WHERE l.id = :id";

                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->execute();

                if ($stmt->rowCount() === 0) {
                    enviarRespuesta(false, null, 'Libro no encontrado.', 404);
                }

                $libro = $stmt->fetch();
                $libro['disponible'] = $libro['prestado'] == 0;
                unset($libro['prestado']);

                enviarRespuesta(true, $libro, 'Libro obtenido exitosamente.', 200);
            } elseif (isset($_GET['buscar']) && isset($_GET['campo'])) {
                // Buscar libros por campo
                $termino = '%' . sanitizar($_GET['buscar']) . '%';
                $campo = sanitizar($_GET['campo']);

                // Validar campo de búsqueda
                $camposValidos = ['titulo', 'autor', 'genero'];
                if (!in_array($campo, $camposValidos)) {
                    enviarRespuesta(false, null, 'Campo de búsqueda no válido.', 400);
                }

                $query = "SELECT l.*, u.nombre as propietario_nombre, u.apellidos as propietario_apellidos,
                          (SELECT COUNT(*) FROM prestamos p WHERE p.libro_id = l.id AND p.fecha_devolucion IS NULL) as prestado
                          FROM libros l
                          INNER JOIN usuarios u ON l.propietario_id = u.id
                          WHERE l.$campo LIKE :termino
                          ORDER BY l.titulo";

                $stmt = $db->prepare($query);
                $stmt->bindParam(':termino', $termino);
                $stmt->execute();

                $libros = $stmt->fetchAll();

                foreach ($libros as &$libro) {
                    $libro['disponible'] = $libro['prestado'] == 0;
                    unset($libro['prestado']);
                }

                enviarRespuesta(true, $libros, 'Búsqueda completada.', 200);
            } elseif (isset($_GET['disponibles'])) {
                // Obtener solo libros disponibles
                $query = "SELECT l.*, u.nombre as propietario_nombre, u.apellidos as propietario_apellidos
                          FROM libros l
                          INNER JOIN usuarios u ON l.propietario_id = u.id
                          WHERE NOT EXISTS (
                              SELECT 1 FROM prestamos p 
                              WHERE p.libro_id = l.id AND p.fecha_devolucion IS NULL
                          )
                          ORDER BY l.fecha_alta DESC";

                $stmt = $db->prepare($query);
                $stmt->execute();

                $libros = $stmt->fetchAll();

                foreach ($libros as &$libro) {
                    $libro['disponible'] = true;
                }

                enviarRespuesta(true, $libros, 'Libros disponibles obtenidos.', 200);
            } elseif (isset($_GET['propietario'])) {
                // Obtener libros de un propietario específico
                $propietarioId = intval($_GET['propietario']);

                $query = "SELECT l.*, u.nombre as propietario_nombre, u.apellidos as propietario_apellidos,
                          (SELECT COUNT(*) FROM prestamos p WHERE p.libro_id = l.id AND p.fecha_devolucion IS NULL) as prestado
                          FROM libros l
                          INNER JOIN usuarios u ON l.propietario_id = u.id
                          WHERE l.propietario_id = :propietario_id
                          ORDER BY l.fecha_alta DESC";

                $stmt = $db->prepare($query);
                $stmt->bindParam(':propietario_id', $propietarioId);
                $stmt->execute();

                $libros = $stmt->fetchAll();

                foreach ($libros as &$libro) {
                    $libro['disponible'] = $libro['prestado'] == 0;
                    unset($libro['prestado']);
                }

                enviarRespuesta(true, $libros, 'Libros del propietario obtenidos.', 200);
            } else {
                // Obtener todos los libros
                $query = "SELECT l.*, u.nombre as propietario_nombre, u.apellidos as propietario_apellidos,
                          (SELECT COUNT(*) FROM prestamos p WHERE p.libro_id = l.id AND p.fecha_devolucion IS NULL) as prestado
                          FROM libros l
                          INNER JOIN usuarios u ON l.propietario_id = u.id
                          ORDER BY l.fecha_alta DESC";

                $stmt = $db->prepare($query);
                $stmt->execute();

                $libros = $stmt->fetchAll();

                foreach ($libros as &$libro) {
                    $libro['disponible'] = $libro['prestado'] == 0;
                    unset($libro['prestado']);
                }

                enviarRespuesta(true, $libros, 'Libros obtenidos exitosamente.', 200);
            }
            break;

        case 'POST':
            requerirAutenticacion();

            // Verificar si es importación CSV
            if (isset($_GET['importar']) && $_GET['importar'] === 'csv') {
                // Importación masiva desde CSV
                // NOTA EDUCATIVA: Validamos bien los archivos para evitar problemas de seguridad

                if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
                    enviarRespuesta(false, null, 'No se recibió el archivo CSV o hubo un error.', 400);
                }

                $archivo = $_FILES['archivo']['tmp_name'];
                $extension = pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION);

                // VALIDACIÓN 1: Verificar extensión del archivo
                if (strtolower($extension) !== 'csv') {
                    enviarRespuesta(false, null, 'El archivo debe ser un CSV.', 400);
                }

                // VALIDACIÓN 2: Verificar tamaño del archivo (máximo 5MB)
                // IMPORTANTE: Esto previene que alguien suba archivos muy grandes
                if (!validarTamañoArchivo($_FILES['archivo'], 5)) {
                    enviarRespuesta(false, null, 'El archivo es demasiado grande. Máximo 5MB.', 400);
                }

                // VALIDACIÓN 3: Verificar tipo MIME real del archivo
                // IMPORTANTE: No confiar solo en la extensión, verificar el tipo real
                $tiposCSVPermitidos = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'];
                if (!validarTipoMIME($_FILES['archivo'], $tiposCSVPermitidos)) {
                    enviarRespuesta(false, null, 'El archivo no es un CSV válido.', 400);
                }

                $librosImportados = 0;
                $errores = [];

                if (($handle = fopen($archivo, 'r')) !== false) {
                    // Saltar la primera línea (encabezados)
                    $primeraLinea = fgetcsv($handle, 1000, ',');

                    $usuarioId = obtenerUsuarioId();

                    while (($datos = fgetcsv($handle, 1000, ',')) !== false) {
                        // Validar que tenga 4 columnas: titulo, autor, genero, año
                        if (count($datos) < 4) {
                            $errores[] = "Línea con datos insuficientes: " . implode(',', $datos);
                            continue;
                        }

                        $titulo = sanitizar($datos[0]);
                        $autor = sanitizar($datos[1]);
                        $genero = sanitizar($datos[2]);
                        $anio = intval($datos[3]);

                        // Validar año
                        if (!validarAnio($anio)) {
                            $errores[] = "Año inválido para libro: $titulo";
                            continue;
                        }

                        // Buscar portada en Google Books API
                        $imagenUrl = buscarPortadaGoogleBooks($titulo, $autor);

                        // Insertar libro con portada
                        $query = "INSERT INTO libros (titulo, autor, genero, anio, imagen_url, propietario_id) 
                                  VALUES (:titulo, :autor, :genero, :anio, :imagen_url, :propietario_id)";

                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':titulo', $titulo);
                        $stmt->bindParam(':autor', $autor);
                        $stmt->bindParam(':genero', $genero);
                        $stmt->bindParam(':anio', $anio);
                        $stmt->bindParam(':imagen_url', $imagenUrl);
                        $stmt->bindParam(':propietario_id', $usuarioId);

                        if ($stmt->execute()) {
                            $librosImportados++;
                        } else {
                            $errores[] = "Error al importar: $titulo";
                        }
                    }

                    fclose($handle);
                }

                $mensaje = "Importados $librosImportados libros.";
                if (!empty($errores)) {
                    $mensaje .= " Errores: " . count($errores);
                }

                enviarRespuesta(true, [
                    'importados' => $librosImportados,
                    'errores' => $errores
                ], $mensaje, 200);
            } else {
                // Crear libro individual
                $datos = json_decode(file_get_contents('php://input'), true);

                // Validar campos requeridos
                $validacion = validarCamposRequeridos($datos, ['titulo', 'autor', 'genero', 'anio']);
                if (!$validacion['valido']) {
                    enviarRespuesta(false, null, $validacion['mensaje'], 400);
                }

                $titulo = sanitizar($datos['titulo']);
                $autor = sanitizar($datos['autor']);
                $genero = sanitizar($datos['genero']);
                $anio = intval($datos['anio']);
                $imagenUrl = isset($datos['imagen_url']) ? sanitizar($datos['imagen_url']) : null;
                $propietarioId = obtenerUsuarioId();

                // Validar año
                if (!validarAnio($anio)) {
                    enviarRespuesta(false, null, 'El año no es válido.', 400);
                }

                // Insertar libro
                $query = "INSERT INTO libros (titulo, autor, genero, anio, imagen_url, propietario_id) 
                          VALUES (:titulo, :autor, :genero, :anio, :imagen_url, :propietario_id)";

                $stmt = $db->prepare($query);
                $stmt->bindParam(':titulo', $titulo);
                $stmt->bindParam(':autor', $autor);
                $stmt->bindParam(':genero', $genero);
                $stmt->bindParam(':anio', $anio);
                $stmt->bindParam(':imagen_url', $imagenUrl);
                $stmt->bindParam(':propietario_id', $propietarioId);

                if ($stmt->execute()) {
                    $libroId = $db->lastInsertId();

                    // Obtener libro creado
                    $query = "SELECT l.*, u.nombre as propietario_nombre, u.apellidos as propietario_apellidos
                              FROM libros l
                              INNER JOIN usuarios u ON l.propietario_id = u.id
                              WHERE l.id = :id";

                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $libroId);
                    $stmt->execute();

                    $libro = $stmt->fetch();
                    $libro['disponible'] = true;

                    enviarRespuesta(true, $libro, 'Libro creado exitosamente.', 201);
                } else {
                    enviarRespuesta(false, null, 'Error al crear el libro.', 500);
                }
            }
            break;

        case 'DELETE':
            requerirAutenticacion();

            if (!isset($_GET['id'])) {
                enviarRespuesta(false, null, 'ID de libro requerido.', 400);
            }

            $id = intval($_GET['id']);
            $usuarioId = obtenerUsuarioId();

            // Verificar que el libro pertenece al usuario (o es admin)
            $query = "SELECT propietario_id FROM libros WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                enviarRespuesta(false, null, 'Libro no encontrado.', 404);
            }

            $libro = $stmt->fetch();

            if ($libro['propietario_id'] !== $usuarioId && !esAdmin()) {
                enviarRespuesta(false, null, 'No tienes permiso para eliminar este libro.', 403);
            }

            // Verificar si tiene préstamos activos
            $query = "SELECT COUNT(*) as total FROM prestamos 
                      WHERE libro_id = :id AND fecha_devolucion IS NULL";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $resultado = $stmt->fetch();

            if ($resultado['total'] > 0) {
                enviarRespuesta(false, null, 'No se puede eliminar el libro porque está prestado.', 409);
            }

            // Eliminar libro
            $query = "DELETE FROM libros WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                enviarRespuesta(true, null, 'Libro eliminado exitosamente.', 200);
            } else {
                enviarRespuesta(false, null, 'Error al eliminar el libro.', 500);
            }
            break;

        default:
            enviarRespuesta(false, null, 'Método no permitido.', 405);
    }
} catch (PDOException $e) {
    error_log("Error en API libros: " . $e->getMessage());
    enviarRespuesta(false, null, 'Error en el servidor.', 500);
}
