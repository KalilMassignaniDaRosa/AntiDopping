<?php
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth.php';

header('Content-Type: application/json');

$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response->sendError('Método não permitido', 405);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $response->sendError('Username e password são obrigatórios', 400);
        exit;
    }
    
    $auth = new Authentication();
    $result = $auth->login($username, $password);
    
    if ($result['success']) {
        $response->sendSuccess($result);
    } else {
        $response->sendError($result['error'], 401);
    }
    
} catch (Exception $e) {
    $response->sendError($e->getMessage(), 400);
}
?>