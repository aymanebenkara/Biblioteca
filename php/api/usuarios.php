<?php
/**
 * API de Gestión de Usuarios
 * Endpoints:
 * - GET /php/api/usuarios.php - Obtener todos los usuarios (solo admin)
 * - GET /php/api/usuarios.php?id=X - Obtener usuario específico
 * - DELETE /php/api/usuarios.php?id=X - Eliminar usuario (solo admin, sin préstamos)
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
header('Access-Control-Allow-Methods: GET, DELETE, OPTIONS');
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
            // Obtener usuarios
            if (isset($_GET['id'])) {
                // Obtener usuario específico
                requerirAutenticacion();

                $id = intval($_GET['id']);
                $usuarioActualId = obtenerUsuarioId();

                // Solo admin o el mismo usuario pueden ver los detalles
                if (!esAdmin() && $id !== $usuarioActualId) {
                    enviarRespuesta(false, null, 'No tienes permiso para ver este usuario.', 403);
                }

                $query = "SELECT id, nombre, apellidos, email, es_admin, fecha_registro 
                          FROM usuarios WHERE id = :id";

                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->execute();

                if ($stmt->rowCount() === 0) {
                    enviarRespuesta(false, null, 'Usuario no encontrado.', 404);
                }

                $usuario = $stmt->fetch();
                $usuario['es_admin'] = (bool) $usuario['es_admin'];

                enviarRespuesta(true, $usuario, 'Usuario obtenido exitosamente.', 200);

            } else {
                // Obtener todos los usuarios (solo admin)
                requerirAdmin();

                $query = "SELECT id, nombre, apellidos, email, es_admin, fecha_registro 
                          FROM usuarios ORDER BY fecha_registro DESC";

                $stmt = $db->prepare($query);
                $stmt->execute();

                $usuarios = $stmt->fetchAll();

                // Convertir es_admin a booleano
                foreach ($usuarios as &$usuario) {
                    $usuario['es_admin'] = (bool) $usuario['es_admin'];
                }

                enviarRespuesta(true, $usuarios, 'Usuarios obtenidos exitosamente.', 200);
            }
            break;

        case 'DELETE':
            // Eliminar usuario (solo admin)
            requerirAdmin();

            if (!isset($_GET['id'])) {
                enviarRespuesta(false, null, 'ID de usuario requerido.', 400);
            }

            $id = intval($_GET['id']);

            // No permitir eliminar al admin principal
            if ($id === 1) {
                enviarRespuesta(false, null, 'No se puede eliminar el administrador principal.', 403);
            }

            // Verificar si el usuario tiene préstamos activos
            $query = "SELECT COUNT(*) as total FROM prestamos 
                      WHERE prestatario_id = :id AND fecha_devolucion IS NULL";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $resultado = $stmt->fetch();

            if ($resultado['total'] > 0) {
                enviarRespuesta(false, null, 'No se puede eliminar el usuario porque tiene préstamos activos.', 409);
            }

            // Verificar si tiene libros prestados a otros
            $query = "SELECT COUNT(*) as total FROM prestamos p
                      INNER JOIN libros l ON p.libro_id = l.id
                      WHERE l.propietario_id = :id AND p.fecha_devolucion IS NULL";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $resultado = $stmt->fetch();

            if ($resultado['total'] > 0) {
                enviarRespuesta(false, null, 'No se puede eliminar el usuario porque tiene libros prestados a otros usuarios.', 409);
            }

            // Eliminar usuario
            $query = "DELETE FROM usuarios WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    enviarRespuesta(true, null, 'Usuario eliminado exitosamente.', 200);
                } else {
                    enviarRespuesta(false, null, 'Usuario no encontrado.', 404);
                }
            } else {
                enviarRespuesta(false, null, 'Error al eliminar el usuario.', 500);
            }
            break;

        default:
            enviarRespuesta(false, null, 'Método no permitido.', 405);
    }

} catch (PDOException $e) {
    error_log("Error en API usuarios: " . $e->getMessage());
    enviarRespuesta(false, null, 'Error en el servidor.', 500);
}
?>