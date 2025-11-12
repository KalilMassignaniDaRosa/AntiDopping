<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/validator.php';

class Laboratory {
    private $db;
    private $table = 'laboratories';
    private $validator;
    private $logger;
    
    public function __construct() {
        $database = DatabaseManager::getInstance();
        $this->db = $database->getConnection();
        $this->validator = new Validator();
        $this->logger = new Logger();
    }
    
    public function create($data) {
        // Validação dos dados
        $validation = $this->validateLaboratoryData($data);
        if (!$validation['valid']) {
            throw new Exception("Dados inválidos: " . implode(', ', $validation['errors']));
        }
        
        // Verifica se código já existe
        if ($this->codeExists($data['code'])) {
            throw new Exception("Código do laboratório já existe");
        }
        
        $query = "INSERT INTO {$this->table} 
                 (name, code, address, contact_email, contact_phone, 
                  accreditation_status, accreditation_expiry) 
                 VALUES (:name, :code, :address, :contact_email, :contact_phone, 
                         :accreditation_status, :accreditation_expiry)";
        
        $stmt = $this->db->prepare($query);
        
        $stmt->bindValue(':name', trim($data['name']));
        $stmt->bindValue(':code', strtoupper(trim($data['code'])));
        $stmt->bindValue(':address', $data['address'] ?? null);
        $stmt->bindValue(':contact_email', $data['contact_email'] ?? null);
        $stmt->bindValue(':contact_phone', $data['contact_phone'] ?? null);
        $stmt->bindValue(':accreditation_status', $data['accreditation_status'] ?? 'accredited');
        $stmt->bindValue(':accreditation_expiry', $data['accreditation_expiry'] ?? null);
        
        try {
            $result = $stmt->execute();
            
            if ($result) {
                $labId = $this->db->lastInsertId();
                $this->logger->log("Laboratory created: ID $labId - {$data['code']}", 'LABORATORY');
                
                return [
                    'success' => true,
                    'laboratory_id' => $labId,
                    'message' => 'Laboratório cadastrado com sucesso'
                ];
            }
            
            throw new Exception('Erro ao cadastrar laboratório');
            
        } catch (PDOException $e) {
            $this->logger->logError("Error creating laboratory: " . $e->getMessage());
            throw new Exception('Erro no banco de dados: ' . $e->getMessage());
        }
    }
    
    public function read($id = null, $filters = []) {
        $query = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];
        
        if ($id) {
            $query .= " AND id = :id";
            $params[':id'] = $id;
        }
        
        if (isset($filters['accreditation_status'])) {
            $query .= " AND accreditation_status = :status";
            $params[':status'] = $filters['accreditation_status'];
        }
        
        $query .= " ORDER BY name ASC";
        
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
        
        $laboratories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Contagem total para paginação
        $total = $this->getTotalCount($filters);
        
        return [
            'data' => $laboratories,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }
    
    public function update($id, $data) {
        // Verifica se laboratório existe
        $current = $this->read($id);
        if (empty($current['data'])) {
            throw new Exception('Laboratório não encontrado');
        }
        
        // Valida dados de atualização
        $validation = $this->validateUpdateData($data);
        if (!$validation['valid']) {
            throw new Exception("Dados inválidos: " . implode(', ', $validation['errors']));
        }
        
        $allowedFields = [
            'name', 'address', 'contact_email', 'contact_phone',
            'accreditation_status', 'accreditation_expiry'
        ];
        
        $updates = [];
        $params = [':id' => $id];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
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
            $this->logger->log("Laboratory updated: ID $id", 'LABORATORY');
            return ['success' => true, 'message' => 'Laboratório atualizado com sucesso'];
        }
        
        throw new Exception('Erro ao atualizar laboratório');
    }
    
    public function getAllActive() {
        $query = "SELECT id, name, code FROM {$this->table} 
                 WHERE accreditation_status = 'accredited' 
                 ORDER BY name";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function validateLaboratoryData($data) {
        $errors = [];
        
        $required = ['name', 'code'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "Campo {$field} é obrigatório";
            }
        }
        
        if (!empty($data['contact_email']) && !$this->validator->validateEmail($data['contact_email'])) {
            $errors[] = 'Email de contato inválido';
        }
        
        if (!empty($data['accreditation_expiry']) && !$this->validator->validateDate($data['accreditation_expiry'])) {
            $errors[] = 'Data de expiração da acreditação inválida';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    private function validateUpdateData($data) {
        $errors = [];
        
        if (!empty($data['contact_email']) && !$this->validator->validateEmail($data['contact_email'])) {
            $errors[] = 'Email de contato inválido';
        }
        
        if (!empty($data['accreditation_expiry']) && !$this->validator->validateDate($data['accreditation_expiry'])) {
            $errors[] = 'Data de expiração da acreditação inválida';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    private function codeExists($code) {
        $cleanCode = strtoupper(trim($code));
        
        $query = "SELECT id FROM {$this->table} WHERE code = :code";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':code', $cleanCode);
        $stmt->execute();
        
        return $stmt->fetch() !== false;
    }
    
    private function getTotalCount($filters) {
        $query = "SELECT COUNT(*) as total FROM {$this->table} WHERE 1=1";
        $params = [];
        
        if (isset($filters['accreditation_status'])) {
            $query .= " AND accreditation_status = :status";
            $params[':status'] = $filters['accreditation_status'];
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchColumn();
    }
}
?>