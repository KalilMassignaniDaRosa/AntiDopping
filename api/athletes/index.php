<?php
require_once __DIR__ . '/../../models/Athlete.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

header('Content-Type: application/json');

$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Verifica autenticação
    $auth = new AuthMiddleware();
    $user = $auth->authenticate();
    
    $athleteModel = new Athlete();
    
    switch ($method) {
        case 'GET':
            $id = $_GET['id'] ?? null;
            $filters = [
                'status' => $_GET['status'] ?? null,
                'federation' => $_GET['federation'] ?? null,
                'club' => $_GET['club'] ?? null,
                'sport' => $_GET['sport'] ?? null,
                'page' => $_GET['page'] ?? 1,
                'limit' => $_GET['limit'] ?? 50
            ];
            
            if (isset($_GET['search'])) {
                $result = $athleteModel->search($_GET['search']);
                $response->sendSuccess($result);
            } else {
                $result = $athleteModel->read($id, $filters);
                $response->sendSuccess($result);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $result = $athleteModel->create($input);
            $response->sendSuccess($result, 201);
            break;
            
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                $response->sendError('ID do atleta é obrigatório', 400);
            }
            
            $result = $athleteModel->update($id, $input);
            $response->sendSuccess($result);
            break;
            
        case 'DELETE':
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                $response->sendError('ID do atleta é obrigatório', 400);
            }
            
            $result = $athleteModel->delete($id);
            $response->sendSuccess($result);
            break;
            
        default:
            $response->sendError('Método não permitido', 405);
    }
    
} catch (Exception $e) {
    $response->sendError($e->getMessage(), 400);
}
?>