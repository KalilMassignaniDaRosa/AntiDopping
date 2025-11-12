<?php
require_once __DIR__ . '/../config/database.php';

class Authentication {
    private $db;
    private $logger;
    
    public function __construct() {
        $database = DatabaseManager::getInstance();
        $this->db = $database->getConnection();
        $this->logger = new Logger();
    }
    
    public function login($username, $password) {
        $query = "SELECT id, username, email, password_hash, full_name, role, federation, is_active 
                 FROM system_users 
                 WHERE username = :username OR email = :username";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':username', $username);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $this->logger->log("Login failed: User not found - $username", 'AUTH');
            return ['success' => false, 'error' => 'Credenciais inválidas'];
        }
        
        if (!$user['is_active']) {
            $this->logger->log("Login failed: User inactive - $username", 'AUTH');
            return ['success' => false, 'error' => 'Usuário inativo'];
        }
        
        if (password_verify($password, $user['password_hash'])) {
            // Atualiza último login
            $this->updateLastLogin($user['id']);
            
            // Gera token
            $token = $this->generateToken($user);
            
            $this->logger->log("Login successful: {$user['username']}", 'AUTH');
            
            return [
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role'],
                    'federation' => $user['federation']
                ]
            ];
        } else {
            $this->logger->log("Login failed: Invalid password - $username", 'AUTH');
            return ['success' => false, 'error' => 'Credenciais inválidas'];
        }
    }
    
    public function verifyToken($token) {
        // Em produção, usar JWT
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
        
        return $validTokens[$token] ?? null;
    }
    
    private function generateToken($user) {
        // Em produção, gerar JWT
        // Aqui retorna tokens pré-definidos baseados no role
        
        $tokens = [
            'admin' => 'cbf_admin_token',
            'technician' => 'lab_technician_token',
            'user' => 'federation_user_token'
        ];
        
        return $tokens[$user['role']] ?? 'federation_user_token';
    }
    
    private function updateLastLogin($userId) {
        $query = "UPDATE system_users SET last_login = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $userId);
        $stmt->execute();
    }
}
?>