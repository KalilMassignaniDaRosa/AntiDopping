<?php
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

header('Content-Type: application/json');

class WADAIntegration {
    private $wadaEndpoint = 'https://api.wada-ama.org/v1/';
    private $apiKey;
    private $logger;
    
    public function __construct() {
        $this->apiKey = getenv('WADA_API_KEY');
        $this->logger = new Logger();
    }
    
    public function reportTest($testData) {
        $payload = [
            'sample_code' => $testData['sample_code'],
            'athlete_id' => $testData['athlete_id'],
            'test_date' => $testData['test_date'],
            'test_type' => $testData['test_type'],
            'laboratory_code' => $testData['laboratory_code'],
            'result' => $testData['result'],
            'result_date' => $testData['result_date'] ?? date('Y-m-d')
        ];
        
        return $this->makeRequest('tests/report', 'POST', $payload);
    }
    
    public function getProhibitedSubstances() {
        return $this->makeRequest('substances/prohibited', 'GET');
    }
    
    public function getAthleteWhereabouts($athleteId) {
        return $this->makeRequest("athletes/{$athleteId}/whereabouts", 'GET');
    }
    
    public function syncRegulations() {
        $response = $this->makeRequest('regulations/latest', 'GET');
        
        if ($response['success']) {
            $this->updateLocalRegulations($response['data']);
        }
        
        return $response;
    }
    
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->wadaEndpoint . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            $this->logger->logError("WADA API Error: " . $error);
            return ['success' => false, 'error' => 'Erro de conexão: ' . $error];
        }
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return ['success' => true, 'data' => $data];
        } else {
            $this->logger->logError("WADA API HTTP Error: $httpCode - $response");
            return ['success' => false, 'error' => "Erro HTTP $httpCode", 'response' => $response];
        }
    }
    
    private function updateLocalRegulations($regulations) {
        // Atualiza a tabela local de substâncias proibidas
        $database = DatabaseManager::getInstance();
        $db = $database->getConnection();
        
        try {
            // Limpa tabela atual
            $db->exec("DELETE FROM prohibited_substances WHERE source = 'WADA'");
            
            // Insere novos dados
            $stmt = $db->prepare("
                INSERT INTO prohibited_substances 
                (substance_name, category, prohibited_status, prohibited_in_competition, 
                 prohibited_out_competition, update_date, source) 
                VALUES (?, ?, ?, ?, ?, ?, 'WADA')
            ");
            
            foreach ($regulations['substances'] as $substance) {
                $stmt->execute([
                    $substance['name'],
                    $substance['category'],
                    $substance['status'],
                    $substance['prohibited_in_competition'] ? 1 : 0,
                    $substance['prohibited_out_competition'] ? 1 : 0,
                    date('Y-m-d')
                ]);
            }
            
            $this->logger->log("WADA regulations synchronized successfully", 'INTEGRATION');
            
        } catch (Exception $e) {
            $this->logger->logError("Error syncing WADA regulations: " . $e->getMessage());
        }
    }
}

// Endpoint da API
$response = new Response();
$method = $_SERVER['REQUEST_METHOD'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response->sendError('Método não permitido', 405);
    exit;
}

try {
    // Verifica autenticação
    $auth = new AuthMiddleware();
    $user = $auth->authenticate();
    $auth->checkPermission($user, 'write');
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? '';
    
    $wadaIntegration = new WADAIntegration();
    
    switch ($action) {
        case 'report_test':
            $result = $wadaIntegration->reportTest($input);
            break;
            
        case 'sync_substances':
            $result = $wadaIntegration->getProhibitedSubstances();
            break;
            
        case 'sync_regulations':
            $result = $wadaIntegration->syncRegulations();
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