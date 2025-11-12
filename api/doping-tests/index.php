<?php
require_once __DIR__ . '/../../models/DopingTest.php';
require_once __DIR__ . '/../../models/Athlete.php';
require_once __DIR__ . '/../../models/Laboratory.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../utils/validator.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/logger.php';

header('Content-Type: application/json');

$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Verifica autenticação
    $auth = new AuthMiddleware();
    $user = $auth->authenticate();
    
    $dopingTestModel = new DopingTest();
    $validator = new Validator();
    
    switch ($method) {
        case 'GET':
            handleGetRequest($dopingTestModel, $user, $response, $validator, $auth);
            break;
            
        case 'POST':
            handlePostRequest($dopingTestModel, $user, $response, $validator, $auth);
            break;
            
        case 'PUT':
            handlePutRequest($dopingTestModel, $user, $response, $validator, $auth);
            break;
            
        case 'DELETE':
            handleDeleteRequest($dopingTestModel, $user, $response, $auth);
            break;
            
        default:
            $response->sendError('Método não permitido', 405);
    }
    
} catch (Exception $e) {
    $response->sendError($e->getMessage(), 400);
}

// ========== FUNÇÕES PARA REQUISIÇÕES GET ==========

function handleGetRequest($dopingTestModel, $user, $response, $validator, $auth) {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'statistics':
            $auth->checkPermission($user, 'reports');
            handleGetStatistics($dopingTestModel, $response, $validator);
            break;
            
        case 'by_athlete':
            $auth->checkPermission($user, 'read');
            handleGetTestsByAthlete($dopingTestModel, $response, $validator);
            break;
            
        case 'by_laboratory':
            $auth->checkPermission($user, 'read');
            handleGetTestsByLaboratory($dopingTestModel, $response, $validator);
            break;
            
        case 'pending':
            $auth->checkPermission($user, 'read');
            handleGetPendingTests($dopingTestModel, $response);
            break;
            
        case 'details':
            $auth->checkPermission($user, 'read');
            handleGetTestDetails($dopingTestModel, $response, $validator);
            break;
            
        case 'search':
            $auth->checkPermission($user, 'read');
            handleSearchTests($dopingTestModel, $response);
            break;
            
        default:
            $auth->checkPermission($user, 'read');
            handleGetAllTests($dopingTestModel, $response, $validator);
    }
}

function handleGetAllTests($dopingTestModel, $response, $validator) {
    $filters = [
        'athlete_id' => $_GET['athlete_id'] ?? null,
        'laboratory_id' => $_GET['laboratory_id'] ?? null,
        'status' => $_GET['status'] ?? null,
        'test_type' => $_GET['test_type'] ?? null,
        'result' => $_GET['result'] ?? null,
        'start_date' => $_GET['start_date'] ?? null,
        'end_date' => $_GET['end_date'] ?? null,
        'federation' => $_GET['federation'] ?? null,
        'club' => $_GET['club'] ?? null,
        'page' => $_GET['page'] ?? 1,
        'limit' => $_GET['limit'] ?? 50
    ];
    
    // Valida datas se fornecidas
    if ($filters['start_date'] && !$validator->validateDate($filters['start_date'])) {
        $response->sendError('Data inicial inválida', 400);
        return;
    }
    
    if ($filters['end_date'] && !$validator->validateDate($filters['end_date'])) {
        $response->sendError('Data final inválida', 400);
        return;
    }
    
    try {
        $result = $dopingTestModel->getTests($filters);
        $response->sendSuccess($result);
    } catch (Exception $e) {
        $response->sendError($e->getMessage(), 400);
    }
}

function handleGetStatistics($dopingTestModel, $response, $validator) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    $groupBy = $_GET['group_by'] ?? 'test_type';
    $laboratoryId = $_GET['laboratory_id'] ?? null;
    $federation = $_GET['federation'] ?? null;
    
    // Valida datas
    if (!$validator->validateDate($startDate) || !$validator->validateDate($endDate)) {
        $response->sendError('Datas inválidas', 400);
        return;
    }
    
    if ($startDate > $endDate) {
        $response->sendError('Data inicial não pode ser maior que data final', 400);
        return;
    }
    
    // Valida group_by
    $allowedGroups = ['test_type', 'laboratory', 'federation', 'month', 'week', 'status'];
    if (!in_array($groupBy, $allowedGroups)) {
        $response->sendError('Grupo inválido para estatísticas. Use: ' . implode(', ', $allowedGroups), 400);
        return;
    }
    
    try {
        $result = $dopingTestModel->getStatistics($startDate, $endDate, $groupBy, $laboratoryId, $federation);
        $response->sendSuccess($result);
    } catch (Exception $e) {
        $response->sendError($e->getMessage(), 400);
    }
}

function handleGetTestsByAthlete($dopingTestModel, $response, $validator) {
    $athleteId = $_GET['athlete_id'] ?? null;
    
    if (!$athleteId) {
        $response->sendError('ID do atleta é obrigatório', 400);
        return;
    }
    
    if (!$validator->validateInteger($athleteId)) {
        $response->sendError('ID do atleta deve ser um número inteiro', 400);
        return;
    }
    
    $filters = [
        'athlete_id' => (int)$athleteId,
        'page' => $_GET['page'] ?? 1,
        'limit' => $_GET['limit'] ?? 100
    ];
    
    try {
        $result = $dopingTestModel->getTests($filters);
        $response->sendSuccess($result);
    } catch (Exception $e) {
        $response->sendError($e->getMessage(), 400);
    }
}

function handleGetTestsByLaboratory($dopingTestModel, $response, $validator) {
    $laboratoryId = $_GET['laboratory_id'] ?? null;
    
    if (!$laboratoryId) {
        $response->sendError('ID do laboratório é obrigatório', 400);
        return;
    }
    
    if (!$validator->validateInteger($laboratoryId)) {
        $response->sendError('ID do laboratório deve ser um número inteiro', 400);
        return;
    }
    
    $filters = [
        'laboratory_id' => (int)$laboratoryId,
        'start_date' => $_GET['start_date'] ?? null,
        'end_date' => $_GET['end_date'] ?? null,
        'page' => $_GET['page'] ?? 1,
        'limit' => $_GET['limit'] ?? 100
    ];
    
    try {
        $result = $dopingTestModel->getTests($filters);
        $response->sendSuccess($result);
    } catch (Exception $e) {
        $response->sendError($e->getMessage(), 400);
    }
}

function handleGetPendingTests($dopingTestModel, $response) {
    $filters = [
        'status' => 'pending',
        'laboratory_id' => $_GET['laboratory_id'] ?? null,
        'start_date' => $_GET['start_date'] ?? null,
        'end_date' => $_GET['end_date'] ?? null,
        'page' => $_GET['page'] ?? 1,
        'limit' => $_GET['limit'] ?? 100
    ];
    
    try {
        $result = $dopingTestModel->getTests($filters);
        $response->sendSuccess($result);
    } catch (Exception $e) {
        $response->sendError($e->getMessage(), 400);
    }
}

function handleGetTestDetails($dopingTestModel, $response, $validator) {
    $testId = $_GET['test_id'] ?? null;
    
    if (!$testId) {
        $response->sendError('ID do teste é obrigatório', 400);
        return;
    }
    
    if (!$validator->validateInteger($testId)) {
        $response->sendError('ID do teste deve ser um número inteiro', 400);
        return;
    }
    
    try {
        $testDetails = $dopingTestModel->getTestDetails($testId);
        
        if (!$testDetails) {
            $response->sendError('Teste não encontrado', 404);
            return;
        }
        
        $response->sendSuccess($testDetails);
    } catch (Exception $e) {
        $response->sendError($e->getMessage(), 400);
    }
}

function handleSearchTests($dopingTestModel, $response) {
    $searchTerm = $_GET['q'] ?? '';
    
    if (strlen($searchTerm) < 2) {
        $response->sendError('Termo de busca deve ter pelo menos 2 caracteres', 400);
        return;
    }
    
    try {
        // Busca por código de amostra, nome do atleta ou número de registro
        $database = DatabaseManager::getInstance();
        $db = $database->getConnection();
        
        $query = "SELECT 
                 dt.*,
                 a.name as athlete_name,
                 a.registration_number,
                 a.club,
                 a.federation,
                 l.name as laboratory_name
                 FROM doping_tests dt
                 LEFT JOIN athletes a ON dt.athlete_id = a.id
                 LEFT JOIN laboratories l ON dt.laboratory_id = l.id
                 WHERE dt.sample_code LIKE :search 
                    OR a.name LIKE :search 
                    OR a.registration_number LIKE :search
                 ORDER BY dt.test_date DESC
                 LIMIT 50";
        
        $stmt = $db->prepare($query);
        $stmt->bindValue(':search', "%$searchTerm%");
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response->sendSuccess([
            'search_term' => $searchTerm,
            'results' => $results,
            'total' => count($results)
        ]);
        
    } catch (Exception $e) {
        $response->sendError($e->getMessage(), 400);
    }
}

// ========== FUNÇÕES PARA REQUISIÇÕES POST ==========

function handlePostRequest($dopingTestModel, $user, $response, $validator, $auth) {
    $action = $_GET['action'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input)) {
        $response->sendError('Dados não fornecidos', 400);
        return;
    }
    
    switch ($action) {
        case 'register':
            $auth->checkPermission($user, 'write');
            handleRegisterTest($dopingTestModel, $input, $user, $response, $validator);
            break;
            
        case 'update_result':
            $auth->checkPermission($user, 'write');
            handleUpdateResult($dopingTestModel, $input, $user, $response, $validator);
            break;
            
        case 'bulk_register':
            $auth->checkPermission($user, 'write');
            handleBulkRegister($dopingTestModel, $input, $user, $response, $validator);
            break;
            
        default:
            $response->sendError('Ação não especificada. Use: register, update_result ou bulk_register', 400);
    }
}

function handleRegisterTest($dopingTestModel, $input, $user, $response, $validator) {
    // Validação básica dos dados
    $validation = $validator->validateDopingTestData($input);
    if (!$validation['valid']) {
        $response->sendError('Dados inválidos: ' . implode(', ', $validation['errors']), 400);
        return;
    }
    
    // Adiciona informações do usuário
    $input['created_by'] = $user['username'];
    
    try {
        $result = $dopingTestModel->registerTest($input);
        $response->sendSuccess($result, 201);
    } catch (Exception $e) {
        $response->sendError($e->getMessage(), 400);
    }
}

function handleUpdateResult($dopingTestModel, $input, $user, $response, $validator) {
    $testId = $_GET['test_id'] ?? $input['test_id'] ?? null;
    
    if (!$testId) {
        $response->sendError('ID do teste é obrigatório', 400);
        return;
    }
    
    if (!$validator->validateInteger($testId)) {
        $response->sendError('ID do teste deve ser um número inteiro', 400);
        return;
    }
    
    // Validação dos dados do resultado
    $validation = $validator->validateTestResultData($input);
    if (!$validation['valid']) {
        $response->sendError('Dados inválidos: ' . implode(', ', $validation['errors']), 400);
        return;
    }
    
    try {
        $result = $dopingTestModel->updateResult($testId, $input);
        $response->sendSuccess($result);
    } catch (Exception $e) {
        $response->sendError($e->getMessage(), 400);
    }
}

function handleBulkRegister($dopingTestModel, $input, $user, $response, $validator) {
    $tests = $input['tests'] ?? [];
    
    if (empty($tests) || !is_array($tests)) {
        $response->sendError('Lista de testes não fornecida ou inválida', 400);
        return;
    }
    
    if (count($tests) > 100) {
        $response->sendError('Número máximo de testes por lote é 100', 400);
        return;
    }
    
    $results = [
        'successful' => 0,
        'failed' => 0,
        'details' => []
    ];
    
    foreach ($tests as $index => $testData) {
        try {
            // Validação básica
            $validation = $validator->validateDopingTestData($testData);
            
            if (!$validation['valid']) {
                $results['failed']++;
                $results['details'][] = [
                    'index' => $index,
                    'success' => false,
                    'errors' => $validation['errors']
                ];
                continue;
            }
            
            // Adiciona informações do usuário
            $testData['created_by'] = $user['username'];
            
            $result = $dopingTestModel->registerTest($testData);
            $results['successful']++;
            $results['details'][] = [
                'index' => $index,
                'success' => true,
                'test_id' => $result['test_id'] ?? null,
                'sample_code' => $result['sample_code'] ?? null,
                'message' => $result['message'] ?? 'Teste registrado com sucesso'
            ];
            
        } catch (Exception $e) {
            $results['failed']++;
            $results['details'][] = [
                'index' => $index,
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    $response->sendSuccess($results);
}

// ========== FUNÇÕES PARA REQUISIÇÕES PUT ==========

function handlePutRequest($dopingTestModel, $user, $response, $validator, $auth) {
    $testId = $_GET['id'] ?? null;
    
    if (!$testId) {
        $response->sendError('ID do teste é obrigatório', 400);
        return;
    }
    
    if (!$validator->validateInteger($testId)) {
        $response->sendError('ID do teste deve ser um número inteiro', 400);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input)) {
        $response->sendError('Dados não fornecidos', 400);
        return;
    }
    
    $auth->checkPermission($user, 'write');
    handleUpdateTest($dopingTestModel, $testId, $input, $user, $response, $validator);
}

function handleUpdateTest($dopingTestModel, $testId, $input, $user, $response, $validator) {
    // Valida campos que podem ser atualizados
    $allowedFields = [
        'test_type', 'test_date', 'laboratory_id', 'collection_officer',
        'collection_location', 'test_reason', 'notes', 'status'
    ];
    
    $updateData = [];
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updateData[$field] = $input[$field];
        }
    }
    
    // Validações específicas
    if (isset($updateData['test_type']) && !$validator->validateTestType($updateData['test_type'])) {
        $response->sendError('Tipo de teste inválido', 400);
        return;
    }
    
    if (isset($updateData['test_date']) && !$validator->validateDate($updateData['test_date'])) {
        $response->sendError('Data do teste inválida', 400);
        return;
    }
    
    if (isset($updateData['status']) && !$validator->validateTestStatus($updateData['status'])) {
        $response->sendError('Status inválido', 400);
        return;
    }
    
    if (isset($updateData['test_reason']) && !$validator->validateTestReason($updateData['test_reason'])) {
        $response->sendError('Motivo do teste inválido', 400);
        return;
    }
    
    if (isset($updateData['laboratory_id']) && !$validator->validateInteger($updateData['laboratory_id'])) {
        $response->sendError('ID do laboratório deve ser numérico', 400);
        return;
    }
    
    if (empty($updateData)) {
        $response->sendError('Nenhum campo válido para atualização', 400);
        return;
    }
    
    try {
        $result = $dopingTestModel->updateTest($testId, $updateData);
        $response->sendSuccess($result);
    } catch (Exception $e) {
        $response->sendError($e->getMessage(), 400);
    }
}

// ========== FUNÇÕES PARA REQUISIÇÕES DELETE ==========

function handleDeleteRequest($dopingTestModel, $user, $response, $auth) {
    $testId = $_GET['id'] ?? null;
    
    if (!$testId) {
        $response->sendError('ID do teste é obrigatório', 400);
        return;
    }
    
    $auth->checkPermission($user, 'delete');
    handleDeleteTest($dopingTestModel, $testId, $user, $response);
}

function handleDeleteTest($dopingTestModel, $testId, $user, $response) {
    try {
        $result = $dopingTestModel->deleteTest($testId);
        $response->sendSuccess($result);
    } catch (Exception $e) {
        $response->sendError($e->getMessage(), 400);
    }
}

?>