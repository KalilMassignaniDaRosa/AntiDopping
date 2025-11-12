<?php
require_once __DIR__ . '/../../models/Athlete.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

header('Content-Type: application/json');

$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    $response->sendError('Método não permitido', 405);
    exit;
}

try {
    // Verifica autenticação
    $auth = new AuthMiddleware();
    $user = $auth->authenticate();
    $auth->checkPermission($user, 'write');
    
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        $response->sendError('ID do atleta é obrigatório', 400);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input)) {
        $response->sendError('Dados não fornecidos', 400);
        exit;
    }
    
    $athleteModel = new Athlete();
    $result = $athleteModel->update($id, $input);
    
    $response->sendSuccess($result);
    
} catch (Exception $e) {
    $response->sendError($e->getMessage(), 400);
}
?>