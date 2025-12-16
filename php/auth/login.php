<?php
/**
 * API de Login de Usuarios
 * Endpoint: POST /php/auth/login.php
 */

// Incluir archivos necesarios
require_once '../config/database.php';
require_once '../utils/funciones.php';

// Configurar headers CORS y JSON
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    enviarRespuesta(false, null, 'Método no permitido. Use POST.', 405);
}

// Obtener datos del cuerpo de la petición
$datos = json_decode(file_get_contents('php://input'), true);

// Validar campos requeridos
$validacion = validarCamposRequeridos($datos, ['email', 'password']);
if (!$validacion['valido']) {
    enviarRespuesta(false, null, $validacion['mensaje'], 400);
}

$email = sanitizar($datos['email']);
$password = $datos['password'];

try {
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        enviarRespuesta(false, null, 'Error de conexión a la base de datos.', 500);
    }

    // Buscar usuario por email
    $query = "SELECT id, nombre, apellidos, email, password, es_admin, fecha_registro 
              FROM usuarios WHERE email = :email";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        enviarRespuesta(false, null, 'Credenciales incorrectas.', 401);
    }

    $usuario = $stmt->fetch();

    // Verificar contraseña
    if (!password_verify($password, $usuario['password'])) {
        enviarRespuesta(false, null, 'Credenciales incorrectas.', 401);
    }

    // Iniciar sesión
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['nombre'] = $usuario['nombre'];
    $_SESSION['apellidos'] = $usuario['apellidos'];
    $_SESSION['email'] = $usuario['email'];
    $_SESSION['es_admin'] = (bool) $usuario['es_admin'];

    // Remover password del array antes de enviar
    unset($usuario['password']);

    // Convertir es_admin a booleano
    $usuario['es_admin'] = (bool) $usuario['es_admin'];

    enviarRespuesta(true, $usuario, 'Inicio de sesión exitoso.', 200);

} catch (PDOException $e) {
    error_log("Error en login: " . $e->getMessage());
    enviarRespuesta(false, null, 'Error en el servidor.', 500);
}
?>