<?php
require_once __DIR__ . '/../utils/logger.php';

class DistributedDatabase {
    // ... (código anterior permanece igual)
}

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
}

// Função global para facilitar
function getDBConnection() {
    return DatabaseManager::getConnection();
}
?>