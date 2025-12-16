<?php
/**
 * API de Gestión de Préstamos
 * Endpoints:
 * - GET /php/api/prestamos.php - Todos los préstamos (admin)
 * - GET /php/api/prestamos.php?usuario=X - Préstamos de un usuario
 * - GET /php/api/prestamos.php?activos=1 - Préstamos activos
 * - POST /php/api/prestamos.php - Crear préstamo
 * - PUT /php/api/prestamos.php?id=X&devolver=1 - Devolver libro
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
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
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
            // Obtener préstamos con diferentes filtros

            if (isset($_GET['usuario'])) {
                // Préstamos de un usuario específico
                requerirAutenticacion();

                $usuarioId = intval($_GET['usuario']);
                $usuarioActualId = obtenerUsuarioId();

                // Solo admin o el mismo usuario pueden ver sus préstamos
                if (!esAdmin() && $usuarioId !== $usuarioActualId) {
                    enviarRespuesta(false, null, 'No tienes permiso para ver estos préstamos.', 403);
                }

                $query = "SELECT p.*, 
                          l.titulo as libro_titulo, l.autor as libro_autor, l.genero as libro_genero,
                          l.propietario_id as libro_propietario_id,
                          prop.nombre as propietario_nombre, prop.apellidos as propietario_apellidos,
                          prest.nombre as prestatario_nombre, prest.apellidos as prestatario_apellidos,
                          DATEDIFF(COALESCE(p.fecha_devolucion, NOW()), p.fecha_prestamo) as dias_prestamo
                          FROM prestamos p
                          INNER JOIN libros l ON p.libro_id = l.id
                          INNER JOIN usuarios prop ON l.propietario_id = prop.id
                          INNER JOIN usuarios prest ON p.prestatario_id = prest.id
                          WHERE p.prestatario_id = :usuario_id OR l.propietario_id = :usuario_id2
                          ORDER BY p.fecha_prestamo DESC";

                $stmt = $db->prepare($query);
                $stmt->bindParam(':usuario_id', $usuarioId);
                $stmt->bindParam(':usuario_id2', $usuarioId);
                $stmt->execute();

                $prestamos = $stmt->fetchAll();

                foreach ($prestamos as &$prestamo) {
                    $prestamo['activo'] = $prestamo['fecha_devolucion'] === null;
                }

                enviarRespuesta(true, $prestamos, 'Préstamos del usuario obtenidos.', 200);

            } elseif (isset($_GET['activos'])) {
                // Solo préstamos activos
                requerirAutenticacion();

                $query = "SELECT p.*, 
                          l.titulo as libro_titulo, l.autor as libro_autor, l.genero as libro_genero,
                          prop.nombre as propietario_nombre, prop.apellidos as propietario_apellidos,
                          prest.nombre as prestatario_nombre, prest.apellidos as prestatario_apellidos,
                          DATEDIFF(NOW(), p.fecha_prestamo) as dias_prestamo
                          FROM prestamos p
                          INNER JOIN libros l ON p.libro_id = l.id
                          INNER JOIN usuarios prop ON l.propietario_id = prop.id
                          INNER JOIN usuarios prest ON p.prestatario_id = prest.id
                          WHERE p.fecha_devolucion IS NULL
                          ORDER BY p.fecha_prestamo DESC";

                $stmt = $db->prepare($query);
                $stmt->execute();

                $prestamos = $stmt->fetchAll();

                foreach ($prestamos as &$prestamo) {
                    $prestamo['activo'] = true;
                }

                enviarRespuesta(true, $prestamos, 'Préstamos activos obtenidos.', 200);

            } else {
                // Todos los préstamos (solo admin)
                requerirAdmin();

                $query = "SELECT p.*, 
                          l.titulo as libro_titulo, l.autor as libro_autor, l.genero as libro_genero,
                          prop.nombre as propietario_nombre, prop.apellidos as propietario_apellidos,
                          prest.nombre as prestatario_nombre, prest.apellidos as prestatario_apellidos,
                          DATEDIFF(COALESCE(p.fecha_devolucion, NOW()), p.fecha_prestamo) as dias_prestamo
                          FROM prestamos p
                          INNER JOIN libros l ON p.libro_id = l.id
                          INNER JOIN usuarios prop ON l.propietario_id = prop.id
                          INNER JOIN usuarios prest ON p.prestatario_id = prest.id
                          ORDER BY p.fecha_prestamo DESC";

                $stmt = $db->prepare($query);
                $stmt->execute();

                $prestamos = $stmt->fetchAll();

                foreach ($prestamos as &$prestamo) {
                    $prestamo['activo'] = $prestamo['fecha_devolucion'] === null;
                }

                enviarRespuesta(true, $prestamos, 'Todos los préstamos obtenidos.', 200);
            }
            break;

        case 'POST':
            // Crear nuevo préstamo
            requerirAutenticacion();

            $datos = json_decode(file_get_contents('php://input'), true);

            // Validar campos requeridos
            $validacion = validarCamposRequeridos($datos, ['libro_id']);
            if (!$validacion['valido']) {
                enviarRespuesta(false, null, $validacion['mensaje'], 400);
            }

            $libroId = intval($datos['libro_id']);
            $prestatarioId = obtenerUsuarioId();

            // Verificar que el libro existe
            $query = "SELECT propietario_id FROM libros WHERE id = :libro_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':libro_id', $libroId);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                enviarRespuesta(false, null, 'El libro no existe.', 404);
            }

            $libro = $stmt->fetch();

            // Verificar que el usuario no esté pidiendo prestado su propio libro
            if ($libro['propietario_id'] === $prestatarioId) {
                enviarRespuesta(false, null, 'No puedes pedir prestado tu propio libro.', 400);
            }

            // Verificar que el libro no esté ya prestado
            $query = "SELECT id FROM prestamos 
                      WHERE libro_id = :libro_id AND fecha_devolucion IS NULL";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':libro_id', $libroId);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                enviarRespuesta(false, null, 'El libro ya está prestado.', 409);
            }

            // Crear préstamo
            $query = "INSERT INTO prestamos (libro_id, prestatario_id) 
                      VALUES (:libro_id, :prestatario_id)";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':libro_id', $libroId);
            $stmt->bindParam(':prestatario_id', $prestatarioId);

            if ($stmt->execute()) {
                $prestamoId = $db->lastInsertId();

                // Obtener préstamo creado con información completa
                $query = "SELECT p.*, 
                          l.titulo as libro_titulo, l.autor as libro_autor, l.genero as libro_genero,
                          prop.nombre as propietario_nombre, prop.apellidos as propietario_apellidos,
                          prest.nombre as prestatario_nombre, prest.apellidos as prestatario_apellidos,
                          0 as dias_prestamo
                          FROM prestamos p
                          INNER JOIN libros l ON p.libro_id = l.id
                          INNER JOIN usuarios prop ON l.propietario_id = prop.id
                          INNER JOIN usuarios prest ON p.prestatario_id = prest.id
                          WHERE p.id = :id";

                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $prestamoId);
                $stmt->execute();

                $prestamo = $stmt->fetch();
                $prestamo['activo'] = true;

                enviarRespuesta(true, $prestamo, 'Préstamo creado exitosamente.', 201);
            } else {
                enviarRespuesta(false, null, 'Error al crear el préstamo.', 500);
            }
            break;

        case 'PUT':
            // Devolver libro
            requerirAutenticacion();

            if (!isset($_GET['id']) || !isset($_GET['devolver'])) {
                enviarRespuesta(false, null, 'ID de préstamo requerido.', 400);
            }

            $prestamoId = intval($_GET['id']);
            $usuarioId = obtenerUsuarioId();

            // Verificar que el préstamo existe y pertenece al usuario
            $query = "SELECT p.*, l.propietario_id 
                      FROM prestamos p
                      INNER JOIN libros l ON p.libro_id = l.id
                      WHERE p.id = :id";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $prestamoId);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                enviarRespuesta(false, null, 'Préstamo no encontrado.', 404);
            }

            $prestamo = $stmt->fetch();

            // Solo el prestatario o el propietario pueden devolver el libro
            if (
                $prestamo['prestatario_id'] !== $usuarioId &&
                $prestamo['propietario_id'] !== $usuarioId &&
                !esAdmin()
            ) {
                enviarRespuesta(false, null, 'No tienes permiso para devolver este libro.', 403);
            }

            // Verificar que no esté ya devuelto
            if ($prestamo['fecha_devolucion'] !== null) {
                enviarRespuesta(false, null, 'El libro ya ha sido devuelto.', 400);
            }

            // Marcar como devuelto
            $query = "UPDATE prestamos SET fecha_devolucion = NOW() WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $prestamoId);

            if ($stmt->execute()) {
                // Obtener préstamo actualizado
                $query = "SELECT p.*, 
                          l.titulo as libro_titulo, l.autor as libro_autor, l.genero as libro_genero,
                          prop.nombre as propietario_nombre, prop.apellidos as propietario_apellidos,
                          prest.nombre as prestatario_nombre, prest.apellidos as prestatario_apellidos,
                          DATEDIFF(p.fecha_devolucion, p.fecha_prestamo) as dias_prestamo
                          FROM prestamos p
                          INNER JOIN libros l ON p.libro_id = l.id
                          INNER JOIN usuarios prop ON l.propietario_id = prop.id
                          INNER JOIN usuarios prest ON p.prestatario_id = prest.id
                          WHERE p.id = :id";

                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $prestamoId);
                $stmt->execute();

                $prestamo = $stmt->fetch();
                $prestamo['activo'] = false;

                enviarRespuesta(true, $prestamo, 'Libro devuelto exitosamente.', 200);
            } else {
                enviarRespuesta(false, null, 'Error al devolver el libro.', 500);
            }
            break;

        default:
            enviarRespuesta(false, null, 'Método no permitido.', 405);
    }

} catch (PDOException $e) {
    error_log("Error en API préstamos: " . $e->getMessage());
    enviarRespuesta(false, null, 'Error en el servidor.', 500);
}
?>