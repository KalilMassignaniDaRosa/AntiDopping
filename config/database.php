<?php
require_once __DIR__ . '/../utils/logger.php';
ini_set('memory_limit', '256M'); // Reduzi para 256MB - mais seguro

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
            'host' => 'localhost', // ATENÇÃO: mesmo servidor? Isso não é distribuição real
            'username' => 'cbf_user',
            'password' => 'secure_password_123', 
            'database' => 'cbf_antidoping',
            'port' => 3306
        ]
    ];
    
    private $currentServer;
    private $connection;
    private $logger;
    private $connectionAttempts = 0;
    private $maxConnectionAttempts = 3; // EVITA LOOP INFINITO
    
    public function __construct() {
        $this->logger = new Logger();
        $this->currentServer = $this->getOptimalServer();
        $this->connect();
    }
    
    private function getOptimalServer() {
        // Estratégia mais eficiente em memória
        static $lastServer = 0;
        $servers = array_keys($this->servers);
        $selected = $servers[$lastServer % count($servers)];
        $lastServer++;
        
        $this->logger->log("Database server selected: $selected", 'DATABASE');
        return $selected;
    }
    
    private function connect() {
        // Proteção contra loop infinito
        if ($this->connectionAttempts >= $this->maxConnectionAttempts) {
            throw new PDOException("Maximum connection attempts reached");
        }
        
        $this->connectionAttempts++;
        $server = $this->servers[$this->currentServer];
        
        try {
            $dsn = "mysql:host={$server['host']};port={$server['port']};dbname={$server['database']};charset=utf8mb4";
            
            $this->connection = new PDO(
                $dsn,
                $server['username'],
                $server['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_PERSISTENT => false, // ✅ Mantenha false para economizar memória
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                    PDO::ATTR_TIMEOUT => 5 // ✅ Timeout de 5 segundos
                ]
            );
            
            $this->logger->log("Connected to database: {$server['host']}", 'DATABASE');
            $this->connectionAttempts = 0; // Reset attempts on success
            
        } catch (PDOException $e) {
            $this->logger->logError("Database connection failed: " . $e->getMessage());
            $this->handleConnectionFailure();
        }
    }
    
    private function handleConnectionFailure() {
        // Limpa a conexão anterior para liberar memória
        $this->connection = null;
        
        $backupServers = array_diff(array_keys($this->servers), [$this->currentServer]);
        
        foreach ($backupServers as $server) {
            try {
                $this->currentServer = $server;
                $this->connect();
                $this->logger->log("Failover to database: $server", 'DATABASE');
                return;
            } catch (PDOException $e) {
                // Limpa memória antes de tentar próximo
                $this->connection = null;
                continue;
            }
        }
        
        // Todos os servidores falharam
        throw new PDOException("All database servers are unavailable after {$this->connectionAttempts} attempts");
    }
    
    public function getConnection() {
        // Verifica se a conexão está ativa de forma mais eficiente
        if ($this->connection === null) {
            $this->connect();
            return $this->connection;
        }
        
        try {
            // Query mais leve para testar conexão
            $this->connection->query('SELECT 1')->closeCursor();
        } catch (PDOException $e) {
            $this->logger->log("Database connection lost, reconnecting...", 'DATABASE');
            $this->connection = null; // Libera memória
            $this->connect();
        }
        
        return $this->connection;
    }
    
    // ✅ MÉTODO PARA LIBERAR MEMÓRIA
    public function closeConnection() {
        if ($this->connection !== null) {
            $this->connection = null;
            $this->logger->log("Database connection closed", 'DATABASE');
        }
    }
    
    public function getServerInfo() {
        return [
            'current_server' => $this->currentServer,
            'servers' => array_keys($this->servers)
        ];
    }
    
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->query('SELECT 1 as test');
            $result = $stmt->fetch();
            $stmt->closeCursor(); // ✅ Importante: libera recursos
            return $result && $result['test'] == 1;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // ✅ DESTRUCTOR para limpeza automática
    public function __destruct() {
        $this->closeConnection();
    }
}

// Singleton OTIMIZADO
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
    
    // ✅ NOVO: Método para limpar instância quando necessário
    public static function clearInstance() {
        if (self::$instance !== null) {
            self::$instance->closeConnection();
            self::$instance = null;
        }
    }
}

// Função global para facilitar
function getDBConnection() {
    return DatabaseManager::getConnection();
}

// ✅ Teste de conexão MAIS LEVE
try {
    if (DatabaseManager::test()) {
        error_log("CBF Anti-Doping System: Database connection established successfully");
        // Libera memória imediatamente após o teste
        DatabaseManager::clearInstance();
    }
} catch (Exception $e) {
    error_log("CBF Anti-Doping System: Database connection failed - " . $e->getMessage());
}
?>