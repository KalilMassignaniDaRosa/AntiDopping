<?php
// Configurações do sistema
define('SYSTEM_NAME', 'Sistema Antidoping CBF');
define('SYSTEM_VERSION', '1.0.0');
define('ENVIRONMENT', 'production'); // development, testing, production

// Configurações de URL
define('BASE_URL', 'https://antidoping.cbf.com.br');
define('API_BASE', '/api');

// Configurações de banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'cbf_antidoping');
define('DB_USER', 'cbf_user');
define('DB_PASS', 'secure_password');
define('DB_CHARSET', 'utf8mb4');

// Configurações de segurança
define('JWT_SECRET', 'your-secret-key-here');
define('ENCRYPTION_KEY', 'your-encryption-key-here');

// Configurações de upload
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['pdf', 'jpg', 'jpeg', 'png']);

// Configurações de e-mail
define('SMTP_HOST', 'smtp.cbf.com.br');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@cbf.com.br');
define('SMTP_PASS', 'email-password');

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Error reporting
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Include paths
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/models');
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/utils');
?>