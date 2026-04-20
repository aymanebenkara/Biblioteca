<?php
/**
 * Configuración de conexión a la base de datos
 * Este archivo maneja la conexión PDO a MySQL
 */

class Database {
    // Configuración de la base de datos
    private $host = 'localhost';
    private $db_name = 'biblioteca';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    
    // Variable para la conexión
    private $conn = null;
    
    /**
     * Obtener la conexión a la base de datos
     * @return PDO|null Conexión PDO o null si falla
     */
    public function getConnection() {
        // Si ya existe una conexión, retornarla
        if ($this->conn !== null) {
            return $this->conn;
        }
        
        try {
            // Crear DSN (Data Source Name)
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            
            // Opciones de PDO
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Lanzar excepciones en errores
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch asociativo por defecto
                PDO::ATTR_EMULATE_PREPARES => false, // Usar prepared statements reales
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}" // Establecer charset
            ];
            
            // Crear conexión PDO
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $e) {
            // Registrar error (en producción, usar un sistema de logs)
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            
            // En desarrollo, podemos usar error_log o simplemente devolver null
            // para evitar romper las respuestas JSON
            // if ($_SERVER['SERVER_NAME'] === 'localhost') {
            //     error_log("Error de conexión: " . $e->getMessage());
            // }
            
            return null;
        }
        
        return $this->conn;
    }
    
    /**
     * Cerrar la conexión a la base de datos
     */
    public function closeConnection() {
        $this->conn = null;
    }
}
?>
