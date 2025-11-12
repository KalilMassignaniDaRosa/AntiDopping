<?php
require_once __DIR__ . '/../../models/Athlete.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

header('Content-Type: application/json');

$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $response->sendError('Método não permitido', 405);
    exit;
}

try {
    // Verifica autenticação
    $auth = new AuthMiddleware();
    $user = $auth->authenticate();
    $auth->checkPermission($user, 'read');
    
    $athleteModel = new Athlete();
    
    $id = $_GET['id'] ?? null;
    $filters = [
        'status' => $_GET['status'] ?? null,
        'federation' => $_GET['federation'] ?? null,
        'club' => $_GET['club'] ?? null,
        'sport' => $_GET['sport'] ?? null,
        'page' => $_GET['page'] ?? 1,
        'limit' => $_GET['limit'] ?? 50
    ];
    
    $result = $athleteModel->read($id, $filters);
    $response->sendSuccess($result);
    
} catch (Exception $e) {
    $response->sendError($e->getMessage(), 400);
}
?>