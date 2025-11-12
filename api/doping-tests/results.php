<?php
require_once __DIR__ . '/../../models/DopingTest.php';
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
    
    $dopingTestModel = new DopingTest();
    
    $filters = [
        'athlete_id' => $_GET['athlete_id'] ?? null,
        'laboratory_id' => $_GET['laboratory_id'] ?? null,
        'status' => $_GET['status'] ?? null,
        'test_type' => $_GET['test_type'] ?? null,
        'start_date' => $_GET['start_date'] ?? null,
        'end_date' => $_GET['end_date'] ?? null,
        'page' => $_GET['page'] ?? 1,
        'limit' => $_GET['limit'] ?? 50
    ];
    
    $result = $dopingTestModel->getTests($filters);
    $response->sendSuccess($result);
    
} catch (Exception $e) {
    $response->sendError($e->getMessage(), 400);
}
?>