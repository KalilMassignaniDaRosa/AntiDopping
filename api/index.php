<?php
// Cabeçalhos CORS
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
        'sucesso' => false,
        'mensagem' => 'Erro de conexão com o banco de dados: ' . $e->getMessage()
    ]);
    exit;
}

// Obter método e URI
$metodo = $_SERVER['REQUEST_METHOD'];
$uri_requisicao = $_SERVER['REQUEST_URI'];

// Parse da URI
$url_parseada = parse_url($uri_requisicao);
$caminho = $url_parseada['path'];

// Extrair a parte da API (remover o prefixo até /api/)
$caminho_base = '/AntiDopping/api/';
$caminho_api = str_replace($caminho_base, '', $caminho);

// Se não encontrou, tenta outro padrão comum
if ($caminho_api === $caminho) {
    // Tenta encontrar /api/ em qualquer posição
    $posicao_api = strpos($caminho, '/api/');
    if ($posicao_api !== false) {
        $caminho_api = substr($caminho, $posicao_api + 5); // +5 para pular '/api/'
    }
}

// Dividir em partes
$partes_uri = explode('/', trim($caminho_api, '/'));

// Log para debug
error_log("Requisição API: {$metodo} {$uri_requisicao} -> " . implode('/', $partes_uri));

try {
    // ROTA RAIZ DA API
    if (empty($partes_uri[0]) || $partes_uri[0] === '') {
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'API CBF Antidoping - Sistema funcionando',
            'data_hora' => date('Y-m-d H:i:s'),
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
    if($partes_uri[0] === 'atletas') {
        $controller = new AtletaController($db);

        switch($metodo) {
            case 'GET':
                if(isset($partes_uri[1]) && is_numeric($partes_uri[1])) {
                    $controller->buscar($partes_uri[1]);
                } elseif(isset($partes_uri[1]) && $partes_uri[1] === 'status' && isset($partes_uri[2])) {
                    $controller->listarPorStatus($partes_uri[2]);
                } else {
                    $controller->listar();
                }
                break;
            case 'POST':
                $controller->criar();
                break;
            case 'PUT':
                if(isset($partes_uri[1]) && is_numeric($partes_uri[1])) {
                    $controller->atualizar($partes_uri[1]);
                } else {
                    http_response_code(400);
                    echo json_encode(['sucesso' => false, 'mensagem' => 'ID do atleta não informado']);
                }
                break;
            case 'DELETE':
                if(isset($partes_uri[1]) && is_numeric($partes_uri[1])) {
                    $controller->deletar($partes_uri[1]);
                } else {
                    http_response_code(400);
                    echo json_encode(['sucesso' => false, 'mensagem' => 'ID do atleta não informado']);
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido']);
        }
        exit;
    }

    // ROTAS DE TESTES
    if($partes_uri[0] === 'testes') {
        $controller = new TesteController($db);
        switch($metodo) {
            case 'GET':
                if(isset($partes_uri[1]) && $partes_uri[1] === 'dashboard') {
                    $controller->dashboard();
                } elseif(isset($partes_uri[1]) && $partes_uri[1] === 'atleta' && isset($partes_uri[2])) {
                    $controller->listarPorAtleta($partes_uri[2]);
                } elseif(isset($partes_uri[1]) && $partes_uri[1] === 'relatorio') {
                    $controller->gerarRelatorio();
                } elseif(isset($partes_uri[1]) && is_numeric($partes_uri[1])) {
                    $controller->buscar($partes_uri[1]);
                } else {
                    $controller->listar();
                }
                break;
            case 'POST':
                $controller->criar();
                break;
            case 'PUT':
                if(isset($partes_uri[1]) && is_numeric($partes_uri[1])) {
                    $controller->atualizar($partes_uri[1]);
                } else {
                    http_response_code(400);
                    echo json_encode(['sucesso' => false, 'mensagem' => 'ID do teste não informado']);
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido']);
        }
        exit;
    }

    // ROTAS DE CLUBES
    if($partes_uri[0] === 'clubes') {
        $controller = new ClubeController($db);
        if($metodo === 'GET') {
            if(isset($partes_uri[1]) && is_numeric($partes_uri[1])) {
                $controller->buscar($partes_uri[1]);
            } else {
                $controller->listar();
            }
        } else {
            http_response_code(405);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido']);
        }
        exit;
    }

    // ROTAS DE LABORATÓRIOS
    if($partes_uri[0] === 'laboratorios') {
        $controller = new LaboratorioController($db);
        if($metodo === 'GET') {
            $controller->listar();
        } else {
            http_response_code(405);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido']);
        }
        exit;
    }

    // Rota não encontrada
    http_response_code(404);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Rota não encontrada',
        'solicitado' => $partes_uri[0],
        'rotas_disponiveis' => ['atletas', 'testes', 'clubes', 'laboratorios']
    ]);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro interno do servidor',
        'erro' => $e->getMessage()
    ]);
}
?>