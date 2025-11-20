<?php
// Headers CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Responder imediatamente para requisições OPTIONS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Configurações de erro
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclui os arquivos
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../controllers/AtletaController.php';
require_once __DIR__ . '/../controllers/TesteController.php';
require_once __DIR__ . '/../controllers/ClubeController.php';
require_once __DIR__ . '/../controllers/LaboratorioController.php';

// Conexão com o banco
try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Não foi possível conectar ao banco de dados");
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro de conexão com o banco de dados: ' . $e->getMessage()
    ]);
    exit;
}

// Obter método e URI
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// Parse da URI
$parsed_url = parse_url($request_uri);
$path = $parsed_url['path'];

// Extrair a parte da API (remover o prefixo até /api/)
$base_path = '/AntiDopping/api/';
$api_path = str_replace($base_path, '', $path);

// Se não encontrou, tenta outro padrão comum
if ($api_path === $path) {
    // Tenta encontrar /api/ em qualquer posição
    $api_pos = strpos($path, '/api/');
    if ($api_pos !== false) {
        $api_path = substr($path, $api_pos + 5); // +5 para pular '/api/'
    }
}

// Dividir em partes
$uri_parts = explode('/', trim($api_path, '/'));

// Log para debug
error_log("API Request: {$method} {$request_uri} -> " . implode('/', $uri_parts));

try {
    // ROTA RAIZ DA API
    if (empty($uri_parts[0]) || $uri_parts[0] === '') {
        echo json_encode([
            'success' => true,
            'message' => 'API CBF Antidoping - Sistema funcionando',
            'timestamp' => date('Y-m-d H:i:s'),
            'endpoints' => [
                'GET /atletas' => 'Listar todos os atletas',
                'GET /atletas/{id}' => 'Buscar atleta por ID',
                'GET /atletas/status/{status}' => 'Listar atletas por status',
                'POST /atletas' => 'Criar novo atleta',
                'PUT /atletas/{id}' => 'Atualizar atleta',
                'DELETE /atletas/{id}' => 'Excluir atleta',
                'GET /testes' => 'Listar todos os testes',
                'GET /testes/{id}' => 'Buscar teste por ID',
                'GET /testes/atleta/{id}' => 'Listar testes por atleta',
                'GET /testes/dashboard' => 'Dados do dashboard',
                'GET /testes/relatorio' => 'Gerar relatório',
                'POST /testes' => 'Criar novo teste',
                'PUT /testes/{id}' => 'Atualizar teste',
                'GET /clubes' => 'Listar todos os clubes',
                'GET /clubes/{id}' => 'Buscar clube por ID',
                'GET /laboratorios' => 'Listar todos os laboratórios'
            ]
        ]);
        exit;
    }

    // ROTAS DE ATLETAS
    if($uri_parts[0] === 'atletas') {
        $controller = new AtletaController($db);

        switch($method) {
            case 'GET':
                if(isset($uri_parts[1]) && is_numeric($uri_parts[1])) {
                    $controller->buscar($uri_parts[1]);
                } elseif(isset($uri_parts[1]) && $uri_parts[1] === 'status' && isset($uri_parts[2])) {
                    $controller->listarPorStatus($uri_parts[2]);
                } else {
                    $controller->listar();
                }
                break;
            case 'POST':
                $controller->criar();
                break;
            case 'PUT':
                if(isset($uri_parts[1]) && is_numeric($uri_parts[1])) {
                    $controller->atualizar($uri_parts[1]);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'ID do atleta não informado']);
                }
                break;
            case 'DELETE':
                if(isset($uri_parts[1]) && is_numeric($uri_parts[1])) {
                    $controller->deletar($uri_parts[1]);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'ID do atleta não informado']);
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        }
        exit;
    }

    // ROTAS DE TESTES
    if($uri_parts[0] === 'testes') {
        $controller = new TesteController($db);
        switch($method) {
            case 'GET':
                if(isset($uri_parts[1]) && $uri_parts[1] === 'dashboard') {
                    $controller->dashboard();
                } elseif(isset($uri_parts[1]) && $uri_parts[1] === 'atleta' && isset($uri_parts[2])) {
                    $controller->listarPorAtleta($uri_parts[2]);
                } elseif(isset($uri_parts[1]) && $uri_parts[1] === 'relatorio') {
                    $controller->gerarRelatorio();
                } elseif(isset($uri_parts[1]) && is_numeric($uri_parts[1])) {
                    $controller->buscar($uri_parts[1]);
                } else {
                    $controller->listar();
                }
                break;
            case 'POST':
                $controller->criar();
                break;
            case 'PUT':
                if(isset($uri_parts[1]) && is_numeric($uri_parts[1])) {
                    $controller->atualizar($uri_parts[1]);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'ID do teste não informado']);
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        }
        exit;
    }

    // ROTAS DE CLUBES
    if($uri_parts[0] === 'clubes') {
        $controller = new ClubeController($db);
        if($method === 'GET') {
            if(isset($uri_parts[1]) && is_numeric($uri_parts[1])) {
                $controller->buscar($uri_parts[1]);
            } else {
                $controller->listar();
            }
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        }
        exit;
    }

    // ROTAS DE LABORATÓRIOS
    if($uri_parts[0] === 'laboratorios') {
        $controller = new LaboratorioController($db);
        if($method === 'GET') {
            $controller->listar();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        }
        exit;
    }

    // Rota não encontrada
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Rota não encontrada',
        'requested' => $uri_parts[0],
        'available_routes' => ['atletas', 'testes', 'clubes', 'laboratorios']
    ]);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor',
        'error' => $e->getMessage()
    ]);
}
?>
