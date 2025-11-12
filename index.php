<?php
// Incluir arquivos de configuração
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/utils/logger.php';
require_once __DIR__ . '/utils/response.php';
require_once __DIR__ . '/middleware/cors.php';

// Configuração de CORS
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Lidar com requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuração de timezone
date_default_timezone_set('America/Sao_Paulo');

// Inicializar logger
$logger = new Logger();

// Obter informações da requisição
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$query_string = $_SERVER['QUERY_STRING'] ?? '';

$logger->log("Request: $method $request_uri" . ($query_string ? "?$query_string" : ''));

// Remover base path se necessário
$base_path = '';
$script_dir = dirname($_SERVER['SCRIPT_NAME']);
if ($script_dir !== '/' && strpos($request_uri, $script_dir) === 0) {
    $request_uri = substr($request_uri, strlen($script_dir));
}

// Roteamento
$response = new Response();

try {
    // Rotas da API
    if (preg_match('#^/api/athletes(/.*)?$#', $request_uri)) {
        if (file_exists(__DIR__ . '/api/athletes/index.php')) {
            require_once __DIR__ . '/api/athletes/index.php';
        } else {
            $response->sendError('Endpoint de atletas não encontrado', 404);
        }
    } 
    elseif (preg_match('#^/api/doping-tests(/.*)?$#', $request_uri)) {
        if (file_exists(__DIR__ . '/api/doping-tests/index.php')) {
            require_once __DIR__ . '/api/doping-tests/index.php';
        } else {
            $response->sendError('Endpoint de testes antidoping não encontrado', 404);
        }
    } 
    elseif (preg_match('#^/api/laboratories(/.*)?$#', $request_uri)) {
        if (file_exists(__DIR__ . '/api/laboratories/index.php')) {
            require_once __DIR__ . '/api/laboratories/index.php';
        } else {
            $response->sendError('Endpoint de laboratórios não encontrado', 404);
        }
    } 
    elseif (preg_match('#^/api/reports(/.*)?$#', $request_uri)) {
        if (file_exists(__DIR__ . '/api/reports/index.php')) {
            require_once __DIR__ . '/api/reports/index.php';
        } else {
            $response->sendError('Endpoint de relatórios não encontrado', 404);
        }
    }
    elseif (preg_match('#^/api/auth(/.*)?$#', $request_uri)) {
        if (file_exists(__DIR__ . '/api/auth/login.php')) {
            require_once __DIR__ . '/api/auth/login.php';
        } else {
            $response->sendError('Endpoint de autenticação não encontrado', 404);
        }
    }
    // Servir arquivos estáticos do frontend
    elseif (preg_match('#\.(css|js|png|jpg|jpeg|gif|ico|html)$#', $request_uri)) {
        $file_path = __DIR__ . '/frontend' . $request_uri;
        if (file_exists($file_path)) {
            $mime_types = [
                'css' => 'text/css',
                'js' => 'application/javascript',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'ico' => 'image/x-icon',
                'html' => 'text/html'
            ];
            
            $extension = pathinfo($file_path, PATHINFO_EXTENSION);
            if (isset($mime_types[$extension])) {
                header('Content-Type: ' . $mime_types[$extension]);
            }
            
            readfile($file_path);
            exit;
        } else {
            $response->sendError('Arquivo não encontrado', 404);
        }
    }
    // Página inicial do frontend
    elseif ($request_uri === '/' || $request_uri === '/index.html' || $request_uri === '/dashboard') {
        $frontend_index = __DIR__ . '/frontend/index.html';
        if (file_exists($frontend_index)) {
            header('Content-Type: text/html');
            readfile($frontend_index);
            exit;
        } else {
            $response->sendError('Frontend não encontrado', 404);
        }
    }
    // API Home - Rota padrão
    else {
        echo json_encode([
            'success' => true,
            'system' => 'CBF Anti-Doping System',
            'version' => '1.0.0',
            'timestamp' => date('c'),
            'endpoints' => [
                '/api/athletes' => 'Gerenciamento de atletas',
                '/api/doping-tests' => 'Gerenciamento de testes antidoping',
                '/api/laboratories' => 'Gerenciamento de laboratórios',
                '/api/reports' => 'Relatórios e estatísticas',
                '/api/auth/login' => 'Autenticação de usuários'
            ],
            'usage' => [
                'GET /api/athletes' => 'Listar atletas',
                'POST /api/athletes' => 'Criar atleta',
                'GET /api/doping-tests' => 'Listar testes',
                'POST /api/doping-tests?action=register' => 'Registrar teste',
                'GET /api/reports?action=statistics' => 'Obter estatísticas'
            ]
        ]);
    }
    
} catch (Exception $e) {
    $logger->logError("Error in routing: " . $e->getMessage());
    $response->sendError('Erro interno do servidor: ' . $e->getMessage(), 500);
}
?>