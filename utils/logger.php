<?php
class Logger {
    private $logFile;
    
    public function __construct($logFile = 'system.log') {
        $this->logFile = __DIR__ . '/../logs/' . $logFile;
        
        // Criar diretório de logs se não existir
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }
    
    public function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public function logApiCall($endpoint, $method, $status, $responseTime) {
        $message = "API Call: {$method} {$endpoint} - Status: {$status} - Response Time: {$responseTime}ms";
        $this->log($message, 'API');
    }
    
    public function logError($message, $exception = null) {
        $errorMessage = $message;
        if ($exception) {
            $errorMessage .= " - Exception: " . $exception->getMessage();
        }
        $this->log($errorMessage, 'ERROR');
    }
}
?>