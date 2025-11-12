<?php
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../utils/logger.php';

class AuthMiddleware {
    private $logger;
    
    public function __construct() {
        $this->logger = new Logger();
    }
    
    public function authenticate() {
        $headers = getallheaders();
        $token = null;
        
        // Busca token no header Authorization
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }
        
        if (!$token) {
            $token = $_GET['token'] ?? null;
        }
        
        if (!$token) {
            $this->logger->log('Authentication failed: No token provided', 'AUTH');
            throw new Exception('Token de autenticação não fornecido', 401);
        }
        
        try {
            $user = $this->validateToken($token);
            $this->logger->log("User authenticated: {$user['username']}", 'AUTH');
            return $user;
        } catch (Exception $e) {
            $this->logger->logError("Authentication failed: " . $e->getMessage());
            throw new Exception('Token inválido ou expirado', 401);
        }
    }
    
    private function validateToken($token) {
        // Em produção, usar JWT ou OAuth
        // Aqui é uma implementação simplificada
        
        $validTokens = [
            'cbf_admin_token' => [
                'user_id' => 1,
                'username' => 'cbf_admin',
                'role' => 'admin',
                'permissions' => ['read', 'write', 'delete', 'reports']
            ],
            'lab_technician_token' => [
                'user_id' => 2,
                'username' => 'lab_tech',
                'role' => 'technician',
                'permissions' => ['read', 'write']
            ],
            'federation_user_token' => [
                'user_id' => 3,
                'username' => 'federation_user',
                'role' => 'user',
                'permissions' => ['read']
            ]
        ];
        
        if (isset($validTokens[$token])) {
            return $validTokens[$token];
        }
        
        throw new Exception('Token inválido');
    }
    
    public function checkPermission($user, $requiredPermission) {
        if (!in_array($requiredPermission, $user['permissions'])) {
            throw new Exception('Permissão insuficiente', 403);
        }
        return true;
    }
}

// Função global para facilitar o uso
function checkAuth($requiredPermission = null) {
    $auth = new AuthMiddleware();
    $user = $auth->authenticate();
    
    if ($requiredPermission) {
        $auth->checkPermission($user, $requiredPermission);
    }
    
    return $user;
}
?>