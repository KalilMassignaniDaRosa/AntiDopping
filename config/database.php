<?php
require_once __DIR__ . '/../utils/logger.php';

class DistributedDatabase {
    private $servers = [
        'primary' => [
            'host' => 'localhost',
            'username' => 'cbf_user',
            'password' => 'secure_password_123',
            'database' => 'cbf_antidoping',
            'port' => 3306
        ],
        'secondary_1' => [
            'host' => 'localhost',
            'username' => 'cbf_user',
            'password' => 'secure_password_123',
            'database' => 'cbf_antidoping',
            'port' => 3306
        ]
    ];
    
    private $currentServer;
    private $connection;
    private $logger;
    
    public function __construct() {
        $this->logger = new Logger();
        $this->currentServer = $this->getOptimalServer();
        $this->connect();
    }
    
    private function getOptimalServer() {
        // Estratégia de balanceamento simples - round robin
        $servers = array_keys($this->servers);
        $selected = $servers[array_rand($servers)];
        
        $this->logger->log("Database server selected: $selected", 'DATABASE');
        return $selected;
    }
    
    private function connect() {
        $server = $this->servers[$this->currentServer];
        
        try {
            $dsn = "mysql:host={$server['host']};port={$server['port']};dbname={$server['database']};charset=utf8mb4";
            
            $this->connection = new PDO(
                $dsn,
                $server['username'],
                $server['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_PERSISTENT => false,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
            
            $this->logger->log("Connected to database: {$server['host']}", 'DATABASE');
            
        } catch (PDOException $e) {
            $this->logger->logError("Database connection failed: " . $e->getMessage());
            $this->handleConnectionFailure();
        }
    }
    
    private function handleConnectionFailure() {
        // Tenta conectar a um servidor secundário
        $backupServers = array_diff(array_keys($this->servers), [$this->currentServer]);
        
        foreach ($backupServers as $server) {
            try {
                $this->currentServer = $server;
                $this->connect();
                $this->logger->log("Failover to database: $server", 'DATABASE');
                return;
            } catch (PDOException $e) {
                continue;
            }
        }
        
        // Todos os servidores falharam
        throw new PDOException("All database servers are unavailable: " . $e->getMessage());
    }
    
    public function getConnection() {
        // Verifica se a conexão está ativa
        try {
            $this->connection->query('SELECT 1');
        } catch (PDOException $e) {
            $this->logger->log("Database connection lost, reconnecting...", 'DATABASE');
            $this->connect();
        }
        
        return $this->connection;
    }
    
    public function getServerInfo() {
        return [
            'current_server' => $this->currentServer,
            'servers' => array_keys($this->servers)
        ];
    }
    
    public function testConnection() {
        try {
            $stmt = $this->connection->query('SELECT 1 as test');
            $result = $stmt->fetch();
            return $result && $result['test'] == 1;
        } catch (PDOException $e) {
            return false;
        }
    }
}

// Singleton para a conexão com o banco
class DatabaseManager {
    private static $instance;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new DistributedDatabase();
        }
        return self::$instance;
    }
    
    public static function getConnection() {
        return self::getInstance()->getConnection();
    }
    
    public static function test() {
        return self::getInstance()->testConnection();
    }
}

// Função global para facilitar
function getDBConnection() {
    return DatabaseManager::getConnection();
}

// Teste de conexão inicial (opcional)
try {
    $db = DatabaseManager::getInstance();
    if ($db->testConnection()) {
        error_log("CBF Anti-Doping System: Database connection established successfully");
    }
} catch (Exception $e) {
    error_log("CBF Anti-Doping System: Database connection failed - " . $e->getMessage());
}
?>