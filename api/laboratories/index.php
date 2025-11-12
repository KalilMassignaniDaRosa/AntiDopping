<?php
require_once __DIR__ . '/../../models/Laboratory.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

header('Content-Type: application/json');

$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Verifica autenticação
    $auth = new AuthMiddleware();
    $user = $auth->authenticate();
    
    $laboratoryModel = new Laboratory();
    
    switch ($method) {
        case 'GET':
            $id = $_GET['id'] ?? null;
            $filters = [
                'accreditation_status' => $_GET['status'] ?? null,
                'page' => $_GET['page'] ?? 1,
                'limit' => $_GET['limit'] ?? 50
            ];
            
            $result = $laboratoryModel->read($id, $filters);
            $response->sendSuccess($result);
            break;
            
        case 'POST':
            $auth->checkPermission($user, 'write');
            $input = json_decode(file_get_contents('php://input'), true);
            $result = $laboratoryModel->create($input);
            $response->sendSuccess($result, 201);
            break;
            
        case 'PUT':
            $auth->checkPermission($user, 'write');
            $id = $_GET['id'] ?? null;
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$id) {
                $response->sendError('ID do laboratório é obrigatório', 400);
            }
            
            $result = $laboratoryModel->update($id, $input);
            $response->sendSuccess($result);
            break;
            
        default:
            $response->sendError('Método não permitido', 405);
    }
    
} catch (Exception $e) {
    $response->sendError($e->getMessage(), 400);
}
?>