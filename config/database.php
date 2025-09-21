<?php
// データベース設定
require_once 'environment.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    
    public function __construct() {
        $config = getDatabaseConfig();
        $this->host = $config['host'];
        $this->db_name = $config['db_name'];
        $this->username = $config['username'];
        $this->password = $config['password'];
    }
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

// データベース接続の取得
function getDB() {
    static $db = null;
    if ($db === null) {
        try {
            // 新しいDatabaseManagerを使用
            require_once __DIR__ . '/../src/Utils/DatabaseManager.php';
            $dbManager = DatabaseManager::getInstance();
            $db = $dbManager->getConnection();
            
            // 接続が失敗した場合は従来の方法でフォールバック
            if ($db === null) {
                $database = new Database();
                $db = $database->getConnection();
            }
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            // フォールバック
            $database = new Database();
            $db = $database->getConnection();
        }
    }
    return $db;
}
?>

