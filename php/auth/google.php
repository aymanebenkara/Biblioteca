<?php
/**
 * API de Login con Google
 * Endpoint: POST /php/auth/google.php
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

// Obtener token
$datos = json_decode(file_get_contents('php://input'), true);

if (!isset($datos['credential'])) {
    enviarRespuesta(false, null, 'Token de Google requerido.', 400);
}

$token = $datos['credential'];

try {
    // Verificar token con el endpoint público de Google
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $token;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        enviarRespuesta(false, null, 'Token de Google inválido.', 401);
    }
    
    $payload = json_decode($response, true);
    
    if (!isset($payload['email'])) {
        enviarRespuesta(false, null, 'No se pudo obtener el email de Google.', 400);
    }
    
    $email = $payload['email'];
    $nombre = $payload['given_name'] ?? 'Usuario';
    // Changed fallback from 'Google' to ''
    $apellidos = $payload['family_name'] ?? '';
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        enviarRespuesta(false, null, 'Error de conexión a la base de datos.', 500);
    }
    
    // Buscar si el usuario ya existe
    $query = "SELECT id, nombre, apellidos, email, es_admin FROM usuarios WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // El usuario existe, iniciar sesión
        $usuario = $stmt->fetch();
    } else {
        // El usuario no existe, crearlo
        // Generar una contraseña aleatoria ya que accederá por Google
        $randomPassword = bin2hex(random_bytes(10));
        $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);
        
        $queryInsert = "INSERT INTO usuarios (nombre, apellidos, email, password, es_admin) 
                        VALUES (:nombre, :apellidos, :email, :password, 0)";
                        
        $stmtInsert = $db->prepare($queryInsert);
        $stmtInsert->bindParam(':nombre', $nombre);
        $stmtInsert->bindParam(':apellidos', $apellidos);
        $stmtInsert->bindParam(':email', $email);
        $stmtInsert->bindParam(':password', $hashedPassword);
        
        if (!$stmtInsert->execute()) {
            enviarRespuesta(false, null, 'Error al registrar el usuario con Google.', 500);
        }
        
        $nuevoId = $db->lastInsertId();
        
        $usuario = [
            'id' => $nuevoId,
            'nombre' => $nombre,
            'apellidos' => $apellidos,
            'email' => $email,
            'es_admin' => 0
        ];
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
    
    $usuario['es_admin'] = (bool) $usuario['es_admin'];
    
    enviarRespuesta(true, $usuario, 'Inicio de sesión con Google exitoso.', 200);

} catch (Exception $e) {
    error_log("Error en login de Google: " . $e->getMessage());
    enviarRespuesta(false, null, 'Error en el servidor al procesar Google Login.', 500);
}
?>
