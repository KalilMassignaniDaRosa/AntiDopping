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
    
    $testId = $_GET['test_id'] ?? null;
    
    if (!$testId) {
        $response->sendError('ID do teste é obrigatório', 400);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input)) {
        $response->sendError('Dados do resultado não fornecidos', 400);
        exit;
    }
    
    $dopingTestModel = new DopingTest();
    $result = $dopingTestModel->updateResult($testId, $input);
    
    $response->sendSuccess($result);
    
} catch (Exception $e) {
    $response->sendError($e->getMessage(), 400);
}
?>