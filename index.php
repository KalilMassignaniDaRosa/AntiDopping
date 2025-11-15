<?php
// Ativar display de erros para desenvolvimento
ini_set('display_errors', 1);
ini_set('memory_limit', '1024M'); 
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurações básicas
date_default_timezone_set('America/Sao_Paulo');

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Lidar com requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Incluir arquivos essenciais com verificação
    $required_files = [
        'config/database.php',
        'utils/logger.php', 
        'utils/response.php',
        'middleware/cors.php'
    ];
    
    foreach ($required_files as $file) {
        if (!file_exists(__DIR__ . '/' . $file)) {
            throw new Exception("Arquivo essencial não encontrado: $file");
        }
        require_once __DIR__ . '/' . $file;
    }

    // Inicializar logger
    $logger = new Logger();

    // Obter informações da requisição de forma segura
    $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    // Remover query string da URI
    $request_path = parse_url($request_uri, PHP_URL_PATH) ?? '/';
    
    $logger->log("Request: $method $request_path");

    // Inicializar response
    $response = new Response();

    // Roteamento simplificado e robusto
    $routes = [
        '/api/athletes' => 'api/athletes/index.php',
        '/api/doping-tests' => 'api/doping-tests/index.php', 
        '/api/laboratories' => 'api/laboratories/index.php',
        '/api/reports' => 'api/reports/index.php',
        '/api/auth/login' => 'api/auth/login.php'
    ];

    $route_found = false;
    
    foreach ($routes as $route => $file) {
        if (strpos($request_path, $route) === 0) {
            if (file_exists(__DIR__ . '/' . $file)) {
                require_once __DIR__ . '/' . $file;
                $route_found = true;
                break;
            } else {
                $response->sendError("Endpoint não implementado: $route", 501);
            }
        }
    }

    // Se nenhuma rota API foi encontrada, servir frontend ou página inicial
    if (!$route_found) {
        // Servir arquivos estáticos
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|html|txt)$/', $request_path)) {
            $static_file = __DIR__ . '/frontend' . $request_path;
            if (file_exists($static_file)) {
                $mime_types = [
                    'css' => 'text/css',
                    'js' => 'application/javascript',
                    'png' => 'image/png',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'ico' => 'image/x-icon',
                    'html' => 'text/html',
                    'txt' => 'text/plain'
                ];
                
                $extension = strtolower(pathinfo($static_file, PATHINFO_EXTENSION));
                if (isset($mime_types[$extension])) {
                    header('Content-Type: ' . $mime_types[$extension]);
                }
                
                readfile($static_file);
                exit;
            } else {
                $response->sendError("Arquivo não encontrado: $request_path", 404);
            }
        }
        // Página inicial
        elseif ($request_path === '/' || $request_path === '/index.html' || $request_path === '/dashboard') {
            $frontend_index = __DIR__ . '/frontend/index.html';
            if (file_exists($frontend_index)) {
                header('Content-Type: text/html');
                readfile($frontend_index);
                exit;
            } else {
                $response->sendError('Frontend não encontrado', 404);
            }
        }
        // API Home
        else {
            $response->sendSuccess([
                'system' => 'CBF Anti-Doping System',
                'version' => '1.0.0',
                'timestamp' => date('c'),
                'status' => 'online',
                'endpoints' => array_keys($routes)
            ]);
        }
    }

} catch (Throwable $e) {
    // Capturar qualquer erro não tratado
    error_log("Erro não tratado em index.php: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine());
    
    // Responder com erro 500
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => [
            'message' => 'Erro interno do servidor',
            'code' => 500
        ],
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
}
?>