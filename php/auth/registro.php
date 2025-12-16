<?php
/**
 * API de Registro de Usuarios
 * Endpoint: POST /php/auth/registro.php
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
$validacion = validarCamposRequeridos($datos, ['nombre', 'apellidos', 'email', 'password']);
if (!$validacion['valido']) {
    enviarRespuesta(false, null, $validacion['mensaje'], 400);
}

// Sanitizar datos
$nombre = sanitizar($datos['nombre']);
$apellidos = sanitizar($datos['apellidos']);
$email = sanitizar($datos['email']);
$password = $datos['password'];

// Validar email
if (!validarEmail($email)) {
    enviarRespuesta(false, null, 'El email no es válido.', 400);
}

// Validar longitud de contraseña
if (strlen($password) < 6) {
    enviarRespuesta(false, null, 'La contraseña debe tener al menos 6 caracteres.', 400);
}

try {
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        enviarRespuesta(false, null, 'Error de conexión a la base de datos.', 500);
    }

    // Verificar si el email ya existe
    $query = "SELECT id FROM usuarios WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        enviarRespuesta(false, null, 'El email ya está registrado.', 409);
    }

    // Hashear la contraseña
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Insertar nuevo usuario
    $query = "INSERT INTO usuarios (nombre, apellidos, email, password) 
              VALUES (:nombre, :apellidos, :email, :password)";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':apellidos', $apellidos);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $passwordHash);

    if ($stmt->execute()) {
        $usuarioId = $db->lastInsertId();

        // Obtener datos del usuario creado
        $query = "SELECT id, nombre, apellidos, email, es_admin, fecha_registro 
                  FROM usuarios WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $usuarioId);
        $stmt->execute();

        $usuario = $stmt->fetch();

        enviarRespuesta(true, $usuario, 'Usuario registrado exitosamente.', 201);
    } else {
        enviarRespuesta(false, null, 'Error al registrar el usuario.', 500);
    }

} catch (PDOException $e) {
    error_log("Error en registro: " . $e->getMessage());
    enviarRespuesta(false, null, 'Error en el servidor.', 500);
}
?>