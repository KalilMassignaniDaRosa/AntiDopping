<?php
require_once __DIR__ . '/../../models/DopingTest.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

header('Content-Type: application/json');

$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response->sendError('Método não permitido', 405);
    exit;
}

try {
    // Verifica autenticação
    $auth = new AuthMiddleware();
    $user = $auth->authenticate();
    $auth->checkPermission($user, 'write');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input)) {
        $response->sendError('Dados não fornecidos', 400);
        exit;
    }
    
    // Adiciona informações do usuário que está criando
    $input['created_by'] = $user['username'];
    
    $dopingTestModel = new DopingTest();
    $result = $dopingTestModel->registerTest($input);
    
    $response->sendSuccess($result, 201);
    
} catch (Exception $e) {
    $response->sendError($e->getMessage(), 400);
}
?>