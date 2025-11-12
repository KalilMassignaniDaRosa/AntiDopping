<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/validator.php';
require_once __DIR__ . '/../utils/logger.php';

class DopingTest {
    private $db;
    private $table = 'doping_tests';
    private $validator;
    private $logger;
    
    public function __construct() {
        $database = DatabaseManager::getInstance();
        $this->db = $database->getConnection();
        $this->validator = new Validator();
        $this->logger = new Logger();
    }
    
    /**
     * Registra um novo teste antidoping
     */
    public function registerTest($data) {
        // Validação dos dados
        $validation = $this->validateTestData($data);
        if (!$validation['valid']) {
            throw new Exception("Dados inválidos: " . implode(', ', $validation['errors']));
        }
        
        // Verifica se atleta existe e está ativo
        if (!$this->athleteExists($data['athlete_id'])) {
            throw new Exception('Atleta não encontrado ou inativo');
        }
        
        // Verifica se laboratório existe
        if (!$this->laboratoryExists($data['laboratory_id'])) {
            throw new Exception('Laboratório não encontrado');
        }
        
        $query = "INSERT INTO {$this->table} 
                 (athlete_id, test_type, test_date, laboratory_id, collection_officer,
                  sample_code, collection_location, test_reason, status, created_by) 
                 VALUES (:athlete_id, :test_type, :test_date, :laboratory_id, :collection_officer,
                         :sample_code, :collection_location, :test_reason, :status, :created_by)";
        
        $stmt = $this->db->prepare($query);
        
        // Gera código de amostra único
        $sampleCode = $this->generateSampleCode();
        
        $stmt->bindValue(':athlete_id', $data['athlete_id']);
        $stmt->bindValue(':test_type', $data['test_type']);
        $stmt->bindValue(':test_date', $data['test_date']);
        $stmt->bindValue(':laboratory_id', $data['laboratory_id']);
        $stmt->bindValue(':collection_officer', $data['collection_officer']);
        $stmt->bindValue(':sample_code', $sampleCode);
        $stmt->bindValue(':collection_location', $data['collection_location'] ?? null);
        $stmt->bindValue(':test_reason', $data['test_reason'] ?? 'routine');
        $stmt->bindValue(':status', 'pending');
        $stmt->bindValue(':created_by', $data['created_by'] ?? 'system');
        
        try {
            $result = $stmt->execute();
            
            if ($result) {
                $testId = $this->db->lastInsertId();
                $this->logger->log("Doping test registered: ID $testId - $sampleCode", 'DOPING_TEST');
                
                return [
                    'success' => true,
                    'test_id' => $testId,
                    'sample_code' => $sampleCode,
                    'message' => 'Teste antidoping registrado com sucesso'
                ];
            }
            
            throw new Exception('Erro ao registrar teste antidoping');
            
        } catch (PDOException $e) {
            $this->logger->logError("Error registering doping test: " . $e->getMessage());
            throw new Exception('Erro no banco de dados: ' . $e->getMessage());
        }
    }
    
    /**
     * Atualiza o resultado de um teste
     */
    public function updateResult($testId, $resultData) {
        // Verifica se teste existe
        $test = $this->getTest($testId);
        if (!$test) {
            throw new Exception('Teste antidoping não encontrado');
        }
        
        // Valida dados do resultado
        $validation = $this->validateResultData($resultData);
        if (!$validation['valid']) {
            throw new Exception("Dados inválidos: " . implode(', ', $validation['errors']));
        }
        
        $query = "UPDATE {$this->table} SET 
                 result = :result,
                 result_date = :result_date,
                 analyzed_substances = :analyzed_substances,
                 technical_manager = :technical_manager,
                 notes = :notes,
                 status = 'completed',
                 updated_at = NOW()
                 WHERE id = :test_id";
        
        $stmt = $this->db->prepare($query);
        
        $stmt->bindValue(':result', $resultData['result']);
        $stmt->bindValue(':result_date', $resultData['result_date'] ?? date('Y-m-d'));
        $stmt->bindValue(':analyzed_substances', $resultData['analyzed_substances'] ?? null);
        $stmt->bindValue(':technical_manager', $resultData['technical_manager']);
        $stmt->bindValue(':notes', $resultData['notes'] ?? null);
        $stmt->bindValue(':test_id', $testId);
        
        $result = $stmt->execute();
        
        if ($result) {
            $this->logger->log("Doping test result updated: ID $testId - Result: {$resultData['result']}", 'DOPING_TEST');
            
            // Se resultado for positivo, dispara alerta
            if ($resultData['result'] === 'positive') {
                $this->handlePositiveResult($testId);
            }
            
            return [
                'success' => true,
                'message' => 'Resultado do teste atualizado com sucesso'
            ];
        }
        
        throw new Exception('Erro ao atualizar resultado do teste');
    }
    
    /**
     * Obtém lista de testes com filtros
     */
    public function getTests($filters = []) {
        $query = "SELECT 
                 dt.*,
                 a.name as athlete_name,
                 a.registration_number,
                 a.club,
                 a.federation,
                 a.sport,
                 l.name as laboratory_name,
                 l.code as laboratory_code
                 FROM {$this->table} dt
                 LEFT JOIN athletes a ON dt.athlete_id = a.id
                 LEFT JOIN laboratories l ON dt.laboratory_id = l.id
                 WHERE 1=1";
        
        $params = [];
        
        if (isset($filters['athlete_id'])) {
            $query .= " AND dt.athlete_id = :athlete_id";
            $params[':athlete_id'] = $filters['athlete_id'];
        }
        
        if (isset($filters['laboratory_id'])) {
            $query .= " AND dt.laboratory_id = :laboratory_id";
            $params[':laboratory_id'] = $filters['laboratory_id'];
        }
        
        if (isset($filters['status'])) {
            $query .= " AND dt.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (isset($filters['test_type'])) {
            $query .= " AND dt.test_type = :test_type";
            $params[':test_type'] = $filters['test_type'];
        }
        
        if (isset($filters['result'])) {
            $query .= " AND dt.result = :result";
            $params[':result'] = $filters['result'];
        }
        
        if (isset($filters['start_date'])) {
            $query .= " AND dt.test_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        
        if (isset($filters['end_date'])) {
            $query .= " AND dt.test_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        
        if (isset($filters['federation'])) {
            $query .= " AND a.federation = :federation";
            $params[':federation'] = $filters['federation'];
        }
        
        if (isset($filters['club'])) {
            $query .= " AND a.club LIKE :club";
            $params[':club'] = "%{$filters['club']}%";
        }
        
        $query .= " ORDER BY dt.test_date DESC, dt.created_at DESC";
        
        // Paginação
        $page = $filters['page'] ?? 1;
        $limit = $filters['limit'] ?? 50;
        $offset = ($page - 1) * $limit;
        
        $query .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        $stmt = $this->db->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        
        $stmt->execute();
        
        $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Contagem total para paginação
        $total = $this->getTestsCount($filters);
        
        return [
            'data' => $tests,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }
    
    /**
     * Obtém estatísticas detalhadas dos testes
     */
    public function getStatistics($startDate, $endDate, $groupBy = 'test_type', $laboratoryId = null, $federation = null) {
        $allowedGroupBy = ['test_type', 'laboratory', 'federation', 'month', 'week', 'status'];
        
        if (!in_array($groupBy, $allowedGroupBy)) {
            throw new Exception('Grupo inválido para estatísticas. Use: ' . implode(', ', $allowedGroupBy));
        }
        
        // Construção dinâmica da query
        $baseQuery = "FROM {$this->table} dt";
        $whereConditions = ["dt.test_date BETWEEN :start_date AND :end_date"];
        $params = [
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ];
        
        // Adiciona joins necessários
        if ($groupBy === 'laboratory' || $laboratoryId) {
            $baseQuery .= " LEFT JOIN laboratories l ON dt.laboratory_id = l.id";
        }
        
        if ($groupBy === 'federation' || $federation) {
            $baseQuery .= " LEFT JOIN athletes a ON dt.athlete_id = a.id";
        }
        
        // Filtros adicionais
        if ($laboratoryId) {
            $whereConditions[] = "dt.laboratory_id = :laboratory_id";
            $params[':laboratory_id'] = $laboratoryId;
        }
        
        if ($federation) {
            if ($groupBy !== 'federation') {
                $baseQuery .= " LEFT JOIN athletes a ON dt.athlete_id = a.id";
            }
            $whereConditions[] = "a.federation = :federation";
            $params[':federation'] = $federation;
        }
        
        $whereClause = !empty($whereConditions) ? "WHERE " . implode(' AND ', $whereConditions) : "";
        
        // Query base para estatísticas
        $selectBase = "SELECT ";
        $groupByClause = "";
        
        switch ($groupBy) {
            case 'test_type':
                $selectBase .= "
                    dt.test_type as group_key,
                    dt.test_type as group_label,
                    COUNT(*) as total_tests,
                    SUM(CASE WHEN dt.result = 'positive' THEN 1 ELSE 0 END) as positive_tests,
                    SUM(CASE WHEN dt.result = 'negative' THEN 1 ELSE 0 END) as negative_tests,
                    SUM(CASE WHEN dt.result = 'inconclusive' THEN 1 ELSE 0 END) as inconclusive_tests,
                    SUM(CASE WHEN dt.result IS NULL AND dt.status = 'completed' THEN 1 ELSE 0 END) as pending_results,
                    SUM(CASE WHEN dt.status = 'pending' THEN 1 ELSE 0 END) as pending_tests,
                    ROUND(SUM(CASE WHEN dt.result = 'positive' THEN 1 ELSE 0 END) * 100.0 / NULLIF(SUM(CASE WHEN dt.result IS NOT NULL THEN 1 ELSE 0 END), 0), 2) as positive_percentage
                ";
                $groupByClause = "GROUP BY dt.test_type ORDER BY total_tests DESC";
                break;
                
            case 'laboratory':
                $selectBase .= "
                    l.id as group_key,
                    l.name as group_label,
                    l.code as laboratory_code,
                    COUNT(*) as total_tests,
                    SUM(CASE WHEN dt.result = 'positive' THEN 1 ELSE 0 END) as positive_tests,
                    SUM(CASE WHEN dt.result = 'negative' THEN 1 ELSE 0 END) as negative_tests,
                    SUM(CASE WHEN dt.result = 'inconclusive' THEN 1 ELSE 0 END) as inconclusive_tests,
                    AVG(CASE WHEN dt.result_date IS NOT NULL THEN DATEDIFF(dt.result_date, dt.test_date) ELSE NULL END) as avg_processing_days,
                    ROUND(SUM(CASE WHEN dt.result = 'positive' THEN 1 ELSE 0 END) * 100.0 / NULLIF(SUM(CASE WHEN dt.result IS NOT NULL THEN 1 ELSE 0 END), 0), 2) as positive_percentage
                ";
                $groupByClause = "GROUP BY dt.laboratory_id ORDER BY total_tests DESC";
                break;
                
            case 'federation':
                $selectBase .= "
                    a.federation as group_key,
                    a.federation as group_label,
                    COUNT(*) as total_tests,
                    SUM(CASE WHEN dt.result = 'positive' THEN 1 ELSE 0 END) as positive_tests,
                    SUM(CASE WHEN dt.result = 'negative' THEN 1 ELSE 0 END) as negative_tests,
                    SUM(CASE WHEN dt.result = 'inconclusive' THEN 1 ELSE 0 END) as inconclusive_tests,
                    ROUND(SUM(CASE WHEN dt.result = 'positive' THEN 1 ELSE 0 END) * 100.0 / NULLIF(SUM(CASE WHEN dt.result IS NOT NULL THEN 1 ELSE 0 END), 0), 2) as positive_percentage
                ";
                $groupByClause = "GROUP BY a.federation ORDER BY total_tests DESC";
                break;
                
            case 'month':
                $selectBase .= "
                    DATE_FORMAT(dt.test_date, '%Y-%m') as group_key,
                    DATE_FORMAT(dt.test_date, '%Y-%m') as group_label,
                    COUNT(*) as total_tests,
                    SUM(CASE WHEN dt.result = 'positive' THEN 1 ELSE 0 END) as positive_tests,
                    SUM(CASE WHEN dt.result = 'negative' THEN 1 ELSE 0 END) as negative_tests,
                    SUM(CASE WHEN dt.result = 'inconclusive' THEN 1 ELSE 0 END) as inconclusive_tests,
                    SUM(CASE WHEN dt.result IS NULL AND dt.status = 'completed' THEN 1 ELSE 0 END) as pending_results
                ";
                $groupByClause = "GROUP BY DATE_FORMAT(dt.test_date, '%Y-%m') ORDER BY group_key DESC";
                break;
                
            case 'week':
                $selectBase .= "
                    CONCAT(YEAR(dt.test_date), '-W', LPAD(WEEK(dt.test_date), 2, '0')) as group_key,
                    CONCAT('Semana ', WEEK(dt.test_date), ' de ', YEAR(dt.test_date)) as group_label,
                    COUNT(*) as total_tests,
                    SUM(CASE WHEN dt.result = 'positive' THEN 1 ELSE 0 END) as positive_tests,
                    SUM(CASE WHEN dt.result = 'negative' THEN 1 ELSE 0 END) as negative_tests,
                    SUM(CASE WHEN dt.result = 'inconclusive' THEN 1 ELSE 0 END) as inconclusive_tests
                ";
                $groupByClause = "GROUP BY YEAR(dt.test_date), WEEK(dt.test_date) ORDER BY group_key DESC";
                break;
                
            case 'status':
                $selectBase .= "
                    dt.status as group_key,
                    dt.status as group_label,
                    COUNT(*) as total_tests,
                    SUM(CASE WHEN dt.result = 'positive' THEN 1 ELSE 0 END) as positive_tests,
                    SUM(CASE WHEN dt.result = 'negative' THEN 1 ELSE 0 END) as negative_tests,
                    SUM(CASE WHEN dt.result = 'inconclusive' THEN 1 ELSE 0 END) as inconclusive_tests,
                    CASE 
                        WHEN dt.status = 'completed' THEN COUNT(*)
                        ELSE 0 
                    END as completed_tests
                ";
                $groupByClause = "GROUP BY dt.status ORDER BY total_tests DESC";
                break;
        }
        
        $query = $selectBase . " " . $baseQuery . " " . $whereClause . " " . $groupByClause;
        
        try {
            $stmt = $this->db->prepare($query);
            
            // Bind dos parâmetros
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Estatísticas gerais
            $generalStats = $this->getGeneralStatistics($startDate, $endDate, $laboratoryId, $federation);
            
            return [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'filters' => [
                    'group_by' => $groupBy,
                    'laboratory_id' => $laboratoryId,
                    'federation' => $federation
                ],
                'general_stats' => $generalStats,
                'grouped_stats' => $results
            ];
            
        } catch (PDOException $e) {
            $this->logger->logError("Error getting statistics: " . $e->getMessage());
            throw new Exception('Erro ao obter estatísticas: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtém estatísticas gerais
     */
    private function getGeneralStatistics($startDate, $endDate, $laboratoryId = null, $federation = null) {
        $whereConditions = ["test_date BETWEEN :start_date AND :end_date"];
        $params = [
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ];
        
        $baseQuery = "FROM {$this->table} dt";
        
        if ($laboratoryId) {
            $whereConditions[] = "laboratory_id = :laboratory_id";
            $params[':laboratory_id'] = $laboratoryId;
        }
        
        if ($federation) {
            $baseQuery .= " LEFT JOIN athletes a ON dt.athlete_id = a.id";
            $whereConditions[] = "a.federation = :federation";
            $params[':federation'] = $federation;
        }
        
        $whereClause = "WHERE " . implode(' AND ', $whereConditions);
        
        $query = "SELECT 
                 COUNT(*) as total_tests,
                 SUM(CASE WHEN result = 'positive' THEN 1 ELSE 0 END) as positive_tests,
                 SUM(CASE WHEN result = 'negative' THEN 1 ELSE 0 END) as negative_tests,
                 SUM(CASE WHEN result = 'inconclusive' THEN 1 ELSE 0 END) as inconclusive_tests,
                 SUM(CASE WHEN result IS NULL AND status = 'completed' THEN 1 ELSE 0 END) as pending_results,
                 SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tests,
                 SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tests,
                 SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tests,
                 ROUND(SUM(CASE WHEN result = 'positive' THEN 1 ELSE 0 END) * 100.0 / NULLIF(SUM(CASE WHEN result IS NOT NULL THEN 1 ELSE 0 END), 0), 2) as overall_positive_rate,
                 AVG(CASE WHEN result_date IS NOT NULL THEN DATEDIFF(result_date, test_date) ELSE NULL END) as overall_avg_processing_days
                 " . $baseQuery . " " . $whereClause;
        
        $stmt = $this->db->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calcula estatísticas adicionais
        $stats['completion_rate'] = $stats['total_tests'] > 0 ? 
            round(($stats['completed_tests'] / $stats['total_tests']) * 100, 2) : 0;
        
        $stats['positive_rate'] = $stats['total_tests'] > 0 ? 
            round(($stats['positive_tests'] / $stats['total_tests']) * 100, 2) : 0;
        
        return $stats;
    }
    
    /**
     * Gera relatórios detalhados
     */
    public function generateReport($startDate, $endDate, $reportType = 'general') {
        switch ($reportType) {
            case 'detailed':
                return $this->generateDetailedReport($startDate, $endDate);
            case 'laboratory':
                return $this->generateLaboratoryReport($startDate, $endDate);
            case 'federation':
                return $this->generateFederationReport($startDate, $endDate);
            default:
                return $this->generateGeneralReport($startDate, $endDate);
        }
    }
    
    /**
     * Obtém detalhes completos de um teste
     */
    public function getTestDetails($testId) {
        $query = "SELECT 
                 dt.*,
                 a.name as athlete_name,
                 a.registration_number,
                 a.birth_date,
                 a.federation,
                 a.club,
                 a.sport,
                 a.position,
                 a.email as athlete_email,
                 a.phone as athlete_phone,
                 l.name as laboratory_name,
                 l.code as laboratory_code,
                 l.contact_email as laboratory_email,
                 l.contact_phone as laboratory_phone,
                 l.address as laboratory_address
                 FROM {$this->table} dt
                 LEFT JOIN athletes a ON dt.athlete_id = a.id
                 LEFT JOIN laboratories l ON dt.laboratory_id = l.id
                 WHERE dt.id = :test_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':test_id', $testId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Atualiza informações básicas do teste
     */
    public function updateTest($testId, $updateData) {
        $allowedFields = [
            'test_type', 'test_date', 'laboratory_id', 'collection_officer',
            'collection_location', 'test_reason', 'notes', 'status'
        ];
        
        $updates = [];
        $params = [':id' => $testId];
        
        foreach ($allowedFields as $field) {
            if (isset($updateData[$field])) {
                $updates[] = "{$field} = :{$field}";
                $params[":{$field}"] = $updateData[$field];
            }
        }
        
        if (empty($updates)) {
            throw new Exception('Nenhum campo válido para atualização');
        }
        
        $updates[] = "updated_at = NOW()";
        
        $query = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        $result = $stmt->execute($params);
        
        if ($result) {
            $this->logger->log("Test updated: ID $testId", 'DOPING_TEST');
            return ['success' => true, 'message' => 'Teste atualizado com sucesso'];
        }
        
        throw new Exception('Erro ao atualizar teste');
    }
    
    /**
     * Exclui um teste (apenas se estiver pendente)
     */
    public function deleteTest($testId) {
        // Verifica se o teste existe e está pendente
        $test = $this->getTest($testId);
        if (!$test) {
            throw new Exception('Teste não encontrado');
        }
        
        if ($test['status'] !== 'pending') {
            throw new Exception('Só é possível excluir testes com status pendente');
        }
        
        $query = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $testId);
        
        $result = $stmt->execute();
        
        if ($result) {
            $this->logger->log("Test deleted: ID $testId", 'DOPING_TEST');
            return ['success' => true, 'message' => 'Teste excluído com sucesso'];
        }
        
        throw new Exception('Erro ao excluir teste');
    }
    
    // Métodos auxiliares privados
    
    private function validateTestData($data) {
        $errors = [];
        
        $required = ['athlete_id', 'test_type', 'test_date', 'laboratory_id', 'collection_officer'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "Campo {$field} é obrigatório";
            }
        }
        
        if (!empty($data['test_date']) && !$this->validator->validateDate($data['test_date'])) {
            $errors[] = 'Data do teste inválida';
        }
        
        $allowedTestTypes = ['blood', 'urine', 'both'];
        if (!empty($data['test_type']) && !in_array($data['test_type'], $allowedTestTypes)) {
            $errors[] = 'Tipo de teste inválido';
        }
        
        $allowedReasons = ['routine', 'competition', 'suspicion', 'random'];
        if (!empty($data['test_reason']) && !in_array($data['test_reason'], $allowedReasons)) {
            $errors[] = 'Motivo do teste inválido';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    private function validateResultData($data) {
        $errors = [];
        
        $required = ['result', 'technical_manager'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "Campo {$field} é obrigatório";
            }
        }
        
        $allowedResults = ['positive', 'negative', 'inconclusive'];
        if (!empty($data['result']) && !in_array($data['result'], $allowedResults)) {
            $errors[] = 'Resultado inválido';
        }
        
        if (!empty($data['result_date']) && !$this->validator->validateDate($data['result_date'])) {
            $errors[] = 'Data do resultado inválida';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    private function athleteExists($athleteId) {
        $query = "SELECT id FROM athletes WHERE id = :id AND status = 'active'";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $athleteId);
        $stmt->execute();
        
        return $stmt->fetch() !== false;
    }
    
    private function laboratoryExists($laboratoryId) {
        $query = "SELECT id FROM laboratories WHERE id = :id AND accreditation_status = 'accredited'";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $laboratoryId);
        $stmt->execute();
        
        return $stmt->fetch() !== false;
    }
    
    private function getTest($testId) {
        $query = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $testId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function generateSampleCode() {
        $prefix = 'CBF';
        $date = date('Ymd');
        $random = strtoupper(bin2hex(random_bytes(4)));
        
        $code = $prefix . $date . '_' . $random;
        
        // Verifica se já existe
        $query = "SELECT id FROM {$this->table} WHERE sample_code = :code";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':code', $code);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            return $this->generateSampleCode(); // Recursivo até encontrar único
        }
        
        return $code;
    }
    
    private function handlePositiveResult($testId) {
        // Registra alerta para resultado positivo
        $query = "INSERT INTO positive_result_alerts (test_id, alert_level, status, created_at) 
                 VALUES (:test_id, 'high', 'pending', NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':test_id', $testId);
        $stmt->execute();
        
        $this->logger->log("Positive result alert created for test: $testId", 'ALERT');
    }
    
    private function getTestsCount($filters) {
        $query = "SELECT COUNT(*) as total 
                 FROM {$this->table} dt
                 LEFT JOIN athletes a ON dt.athlete_id = a.id
                 WHERE 1=1";
        
        $params = [];
        
        if (isset($filters['athlete_id'])) {
            $query .= " AND dt.athlete_id = :athlete_id";
            $params[':athlete_id'] = $filters['athlete_id'];
        }
        
        if (isset($filters['laboratory_id'])) {
            $query .= " AND dt.laboratory_id = :laboratory_id";
            $params[':laboratory_id'] = $filters['laboratory_id'];
        }
        
        if (isset($filters['status'])) {
            $query .= " AND dt.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (isset($filters['start_date'])) {
            $query .= " AND dt.test_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        
        if (isset($filters['end_date'])) {
            $query .= " AND dt.test_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchColumn();
    }
    
    // Métodos para gerar relatórios específicos
    
    private function generateGeneralReport($startDate, $endDate) {
        $query = "SELECT 
                 DATE(dt.test_date) as test_day,
                 COUNT(*) as tests_count,
                 SUM(CASE WHEN dt.result = 'positive' THEN 1 ELSE 0 END) as positive_count,
                 SUM(CASE WHEN dt.result = 'negative' THEN 1 ELSE 0 END) as negative_count,
                 dt.test_type
                 FROM {$this->table} dt
                 WHERE dt.test_date BETWEEN :start_date AND :end_date
                 GROUP BY DATE(dt.test_date), dt.test_type
                 ORDER BY test_day DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':start_date', $startDate);
        $stmt->bindValue(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function generateDetailedReport($startDate, $endDate) {
        $query = "SELECT 
                 dt.*,
                 a.name as athlete_name,
                 a.registration_number,
                 a.club,
                 a.federation,
                 l.name as laboratory_name
                 FROM {$this->table} dt
                 LEFT JOIN athletes a ON dt.athlete_id = a.id
                 LEFT JOIN laboratories l ON dt.laboratory_id = l.id
                 WHERE dt.test_date BETWEEN :start_date AND :end_date
                 ORDER BY dt.test_date DESC, a.federation, a.club";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':start_date', $startDate);
        $stmt->bindValue(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function generateLaboratoryReport($startDate, $endDate) {
        $query = "SELECT 
                 l.name as laboratory_name,
                 l.code as laboratory_code,
                 COUNT(*) as total_tests,
                 SUM(CASE WHEN dt.result = 'positive' THEN 1 ELSE 0 END) as positive_tests,
                 SUM(CASE WHEN dt.result = 'negative' THEN 1 ELSE 0 END) as negative_tests,
                 AVG(DATEDIFF(dt.result_date, dt.test_date)) as avg_processing_days
                 FROM {$this->table} dt
                 LEFT JOIN laboratories l ON dt.laboratory_id = l.id
                 WHERE dt.test_date BETWEEN :start_date AND :end_date
                 GROUP BY dt.laboratory_id
                 ORDER BY total_tests DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':start_date', $startDate);
        $stmt->bindValue(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function generateFederationReport($startDate, $endDate) {
        $query = "SELECT 
                 a.federation,
                 COUNT(*) as total_tests,
                 SUM(CASE WHEN dt.result = 'positive' THEN 1 ELSE 0 END) as positive_tests,
                 SUM(CASE WHEN dt.result = 'negative' THEN 1 ELSE 0 END) as negative_tests,
                 dt.test_type
                 FROM {$this->table} dt
                 LEFT JOIN athletes a ON dt.athlete_id = a.id
                 WHERE dt.test_date BETWEEN :start_date AND :end_date
                 GROUP BY a.federation, dt.test_type
                 ORDER BY a.federation, total_tests DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':start_date', $startDate);
        $stmt->bindValue(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>