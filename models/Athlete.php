<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/validator.php';

class Athlete {
    private $db;
    private $table = 'athletes';
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
        $validation = $this->validateAthleteData($data);
        if (!$validation['valid']) {
            throw new Exception("Dados inválidos: " . implode(', ', $validation['errors']));
        }
        
        // Verifica se CPF já existe
        if ($this->cpfExists($data['cpf'])) {
            throw new Exception("CPF já cadastrado no sistema");
        }
        
        $query = "INSERT INTO {$this->table} 
                 (name, cpf, birth_date, federation, club, sport, position, 
                  registration_number, email, phone, address, city, state, status) 
                 VALUES (:name, :cpf, :birth_date, :federation, :club, :sport, :position,
                         :registration_number, :email, :phone, :address, :city, :state, :status)";
        
        $stmt = $this->db->prepare($query);
        
        // Gera número de registro único
        $registrationNumber = $this->generateRegistrationNumber();
        
        $stmt->bindValue(':name', trim($data['name']));
        $stmt->bindValue(':cpf', preg_replace('/[^0-9]/', '', $data['cpf']));
        $stmt->bindValue(':birth_date', $data['birth_date']);
        $stmt->bindValue(':federation', $data['federation']);
        $stmt->bindValue(':club', $data['club']);
        $stmt->bindValue(':sport', $data['sport']);
        $stmt->bindValue(':position', $data['position'] ?? null);
        $stmt->bindValue(':registration_number', $registrationNumber);
        $stmt->bindValue(':email', $data['email'] ?? null);
        $stmt->bindValue(':phone', $data['phone'] ?? null);
        $stmt->bindValue(':address', $data['address'] ?? null);
        $stmt->bindValue(':city', $data['city'] ?? null);
        $stmt->bindValue(':state', $data['state'] ?? null);
        $stmt->bindValue(':status', 'active');
        
        try {
            $result = $stmt->execute();
            
            if ($result) {
                $athleteId = $this->db->lastInsertId();
                $this->logger->log("Athlete created: ID $athleteId - $registrationNumber", 'ATHLETE');
                
                return [
                    'success' => true,
                    'athlete_id' => $athleteId,
                    'registration_number' => $registrationNumber,
                    'message' => 'Atleta cadastrado com sucesso'
                ];
            }
            
            throw new Exception('Erro ao cadastrar atleta');
            
        } catch (PDOException $e) {
            $this->logger->logError("Error creating athlete: " . $e->getMessage());
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
        
        if (isset($filters['status'])) {
            $query .= " AND status = :status";
            $params[':status'] = $filters['status'];
        } else {
            $query .= " AND status = 'active'";
        }
        
        if (isset($filters['federation'])) {
            $query .= " AND federation = :federation";
            $params[':federation'] = $filters['federation'];
        }
        
        if (isset($filters['club'])) {
            $query .= " AND club LIKE :club";
            $params[':club'] = "%{$filters['club']}%";
        }
        
        if (isset($filters['sport'])) {
            $query .= " AND sport = :sport";
            $params[':sport'] = $filters['sport'];
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
        
        $athletes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Contagem total para paginação
        $total = $this->getTotalCount($filters);
        
        return [
            'data' => $athletes,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }
    
    public function update($id, $data) {
        // Verifica se atleta existe
        $current = $this->read($id);
        if (empty($current['data'])) {
            throw new Exception('Atleta não encontrado');
        }
        
        // Valida dados de atualização
        $validation = $this->validateUpdateData($data);
        if (!$validation['valid']) {
            throw new Exception("Dados inválidos: " . implode(', ', $validation['errors']));
        }
        
        $allowedFields = [
            'name', 'federation', 'club', 'sport', 'position', 
            'email', 'phone', 'address', 'city', 'state', 'status'
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
            $this->logger->log("Athlete updated: ID $id", 'ATHLETE');
            return ['success' => true, 'message' => 'Atleta atualizado com sucesso'];
        }
        
        throw new Exception('Erro ao atualizar atleta');
    }
    
    public function delete($id) {
        // Soft delete - marca como inativo
        $query = "UPDATE {$this->table} SET status = 'inactive', updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);
        
        $result = $stmt->execute();
        
        if ($result) {
            $this->logger->log("Athlete deactivated: ID $id", 'ATHLETE');
            return ['success' => true, 'message' => 'Atleta desativado com sucesso'];
        }
        
        throw new Exception('Erro ao desativar atleta');
    }
    
    public function search($term) {
        $query = "SELECT id, name, registration_number, club, federation 
                 FROM {$this->table} 
                 WHERE (name LIKE :term OR registration_number LIKE :term OR cpf LIKE :term)
                 AND status = 'active'
                 ORDER BY name
                 LIMIT 20";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':term', "%$term%");
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function validateAthleteData($data) {
        $errors = [];
        
        $required = ['name', 'cpf', 'birth_date', 'federation', 'club', 'sport'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "Campo {$field} é obrigatório";
            }
        }
        
        if (!empty($data['cpf']) && !$this->validator->validateCPF($data['cpf'])) {
            $errors[] = 'CPF inválido';
        }
        
        if (!empty($data['email']) && !$this->validator->validateEmail($data['email'])) {
            $errors[] = 'Email inválido';
        }
        
        if (!empty($data['birth_date']) && !$this->validator->validateDate($data['birth_date'])) {
            $errors[] = 'Data de nascimento inválida';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    private function validateUpdateData($data) {
        $errors = [];
        
        if (!empty($data['email']) && !$this->validator->validateEmail($data['email'])) {
            $errors[] = 'Email inválido';
        }
        
        if (!empty($data['birth_date']) && !$this->validator->validateDate($data['birth_date'])) {
            $errors[] = 'Data de nascimento inválida';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    private function cpfExists($cpf) {
        $cleanCpf = preg_replace('/[^0-9]/', '', $cpf);
        
        $query = "SELECT id FROM {$this->table} WHERE cpf = :cpf AND status = 'active'";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':cpf', $cleanCpf);
        $stmt->execute();
        
        return $stmt->fetch() !== false;
    }
    
    private function generateRegistrationNumber() {
        $prefix = 'CBF';
        $year = date('Y');
        $random = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
        
        $number = $prefix . $year . $random;
        
        // Verifica se já existe
        $query = "SELECT id FROM {$this->table} WHERE registration_number = :number";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':number', $number);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            return $this->generateRegistrationNumber(); // Recursivo até encontrar único
        }
        
        return $number;
    }
    
    private function getTotalCount($filters) {
        $query = "SELECT COUNT(*) as total FROM {$this->table} WHERE 1=1";
        $params = [];
        
        if (isset($filters['status'])) {
            $query .= " AND status = :status";
            $params[':status'] = $filters['status'];
        } else {
            $query .= " AND status = 'active'";
        }
        
        if (isset($filters['federation'])) {
            $query .= " AND federation = :federation";
            $params[':federation'] = $filters['federation'];
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchColumn();
    }
}
?>