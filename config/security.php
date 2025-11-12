<?php
class SecurityConfig {
    // Chave para JWT
    const JWT_SECRET = 'cbf_antidoping_secret_key_2024';
    
    // Configurações de CORS
    const ALLOWED_ORIGINS = [
        'https://cbf.com.br',
        'https://app.cbf.com.br',
        'http://localhost:3000'
    ];
    
    // Rate limiting
    const RATE_LIMIT = [
        'max_requests' => 1000,
        'time_window' => 3600 // 1 hora
    ];
    
    // Configurações de senha
    const PASSWORD_OPTIONS = [
        'cost' => 12
    ];
    
    // Headers de segurança
    public static function setSecurityHeaders() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
?>