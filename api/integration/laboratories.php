<?php
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

header('Content-Type: application/json');

class LaboratoryIntegration {
    private $logger;
    
    public function __construct() {
        $this->logger = new Logger();
    }
    
    public function sendTestToLaboratory($testId, $laboratoryId) {
        // Obtém dados do teste
        $testData = $this->getTestData($testId);
        $laboratoryData = $this->getLaboratoryData($laboratoryId);
        
        if (!$testData || !$laboratoryData) {
            return ['success' => false, 'error' => 'Dados não encontrados'];
        }
        
        // Simula envio para API do laboratório
        $payload = [
            'sample_code' => $testData['sample_code'],
            'athlete_info' => [
                'name' => $testData['athlete_name'],
                'registration_number' => $testData['registration_number']
            ],
            'test_type' => $testData['test_type'],
            'collection_date' => $testData['test_date'],
            'collection_officer' => $testData['collection_officer']
        ];
        
        // Aqui iria a integração real com o laboratório
        $this->logger->log("Test sent to laboratory: {$laboratoryData['name']} - Sample: {$testData['sample_code']}", 'INTEGRATION');
        
        return [
            'success' => true,
            'message' => 'Teste enviado para o laboratório',
            'tracking_code' => 'LAB_' . $testData['sample_code'],
            'estimated_completion' => date('Y-m-d', strtotime('+5 days'))
        ];
    }
    
    public function receiveResultFromLaboratory($trackingCode, $resultData) {
        // Processa resultado recebido do laboratório
        $this->logger->log("Result received from laboratory: $trackingCode", 'INTEGRATION');
        
        // Atualiza o teste no sistema
        $testId = $this->getTestIdByTrackingCode($trackingCode);
        
        if ($testId) {
            $dopingTestModel = new DopingTest();
            $result = $dopingTestModel->updateResult($testId, $resultData);
            
            return [
                'success' => true,
                'message' => 'Resultado processado com sucesso',
                'test_id' => $testId
            ];
        }
        
        return ['success' => false, 'error' => 'Teste não encontrado'];
    }
    
    private function getTestData($testId) {
        $database = DatabaseManager::getInstance();
        $db = $database->getConnection();
        
        $query = "SELECT dt.*, a.name as athlete_name, a.registration_number 
                 FROM doping_tests dt 
                 JOIN athletes a ON dt.athlete_id = a.id 
                 WHERE dt.id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$testId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getLaboratoryData($laboratoryId) {
        $database = DatabaseManager::getInstance();
        $db = $database->getConnection();
        
        $query = "SELECT * FROM laboratories WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$laboratoryId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getTestIdByTrackingCode($trackingCode) {
        $database = DatabaseManager::getInstance();
        $db = $database->getConnection();
        
        $query = "SELECT id FROM doping_tests WHERE sample_code = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([str_replace('LAB_', '', $trackingCode)]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : null;
    }
}

// Endpoint da API
$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Verifica autenticação
    $auth = new AuthMiddleware();
    $user = $auth->authenticate();
    $auth->checkPermission($user, 'write');
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? '';
    
    $labIntegration = new LaboratoryIntegration();
    
    switch ($action) {
        case 'send_test':
            $testId = $input['test_id'] ?? null;
            $laboratoryId = $input['laboratory_id'] ?? null;
            
            if (!$testId || !$laboratoryId) {
                $response->sendError('test_id e laboratory_id são obrigatórios', 400);
            }
            
            $result = $labIntegration->sendTestToLaboratory($testId, $laboratoryId);
            break;
            
        case 'receive_result':
            $trackingCode = $input['tracking_code'] ?? null;
            $resultData = $input['result_data'] ?? null;
            
            if (!$trackingCode || !$resultData) {
                $response->sendError('tracking_code e result_data são obrigatórios', 400);
            }
            
            $result = $labIntegration->receiveResultFromLaboratory($trackingCode, $resultData);
            break;
            
        default:
            $response->sendError('Ação não especificada', 400);
            exit;
    }
    
    $response->sendSuccess($result);
    
} catch (Exception $e) {
    $response->sendError($e->getMessage(), 400);
}
?>