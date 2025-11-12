<?php
require_once __DIR__ . '/../../models/Athlete.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

header('Content-Type: application/json');

$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    $response->sendError('Método não permitido', 405);
    exit;
}

try {
    // Verifica autenticação
    $auth = new AuthMiddleware();
    $user = $auth->authenticate();
    $auth->checkPermission($user, 'delete');
    
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        $response->sendError('ID do atleta é obrigatório', 400);
        exit;
    }
    
    $athleteModel = new Athlete();
    $result = $athleteModel->delete($id);
    
    $response->sendSuccess($result);
    
} catch (Exception $e) {
    $response->sendError($e->getMessage(), 400);
}
?>