<?php
require_once __DIR__ . '/../../models/Laboratory.php';
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
    
    $laboratoryModel = new Laboratory();
    $result = $laboratoryModel->getAllActive();
    
    $response->sendSuccess($result);
    
} catch (Exception $e) {
    $response->sendError($e->getMessage(), 400);
}
?>