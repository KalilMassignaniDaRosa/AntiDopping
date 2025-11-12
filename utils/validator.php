<?php
class Validator {
    
    public function validateRequired($value) {
        return !empty(trim($value));
    }
    
    public function validateCPF($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        if (strlen($cpf) != 11) {
            return false;
        }
        
        // Verifica se todos os dígitos são iguais
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        // Validação do CPF
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        
        return true;
    }
    
    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    public function validatePhone($phone) {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        return strlen($cleanPhone) >= 10 && strlen($cleanPhone) <= 11;
    }
    
    public function validatePostalCode($cep) {
        $cleanCep = preg_replace('/[^0-9]/', '', $cep);
        return strlen($cleanCep) === 8;
    }
    
    public function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitizeInput'], $input);
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public function validateTestType($type) {
        $allowedTypes = ['blood', 'urine', 'both'];
        return in_array($type, $allowedTypes);
    }
    
    public function validateTestResult($result) {
        $allowedResults = ['positive', 'negative', 'inconclusive'];
        return in_array($result, $allowedResults);
    }
    
    public function validateTestStatus($status) {
        $allowedStatus = ['pending', 'in_progress', 'completed', 'cancelled'];
        return in_array($status, $allowedStatus);
    }
    
    public function validateTestReason($reason) {
        $allowedReasons = ['routine', 'competition', 'suspicion', 'random'];
        return in_array($reason, $allowedReasons);
    }
    
    public function validateLaboratoryStatus($status) {
        $allowedStatus = ['accredited', 'suspended', 'revoked'];
        return in_array($status, $allowedStatus);
    }
    
    public function validateAthleteStatus($status) {
        $allowedStatus = ['active', 'inactive'];
        return in_array($status, $allowedStatus);
    }
    
    public function validateUserRole($role) {
        $allowedRoles = ['admin', 'technician', 'user'];
        return in_array($role, $allowedRoles);
    }
    
    public function validateNumeric($value) {
        return is_numeric($value);
    }
    
    public function validateInteger($value) {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    
    public function validateFloat($value) {
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }
    
    public function validateBoolean($value) {
        return in_array($value, [true, false, 0, 1, '0', '1'], true);
    }
    
    public function validateURL($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    public function validateJSON($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    public function validateDateRange($startDate, $endDate) {
        if (!$this->validateDate($startDate) || !$this->validateDate($endDate)) {
            return false;
        }
        
        return strtotime($startDate) <= strtotime($endDate);
    }
    
    public function validateAge($birthDate, $minAge = 14) {
        $birthDate = new DateTime($birthDate);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
        
        return $age >= $minAge;
    }
    
    public function validateFileType($filename, $allowedTypes) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $allowedTypes);
    }
    
    public function validateFileSize($fileSize, $maxSize) {
        return $fileSize <= $maxSize;
    }
    
    public function validatePasswordStrength($password) {
        // Pelo menos 8 caracteres, 1 letra maiúscula, 1 minúscula, 1 número
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password);
    }
    
    public function validateName($name) {
        // Permite letras, espaços e alguns caracteres especiais comuns em nomes
        return preg_match('/^[a-zA-ZÀ-ÿ\s\-\']+$/', $name);
    }
    
    public function validateAddress($address) {
        // Validação básica de endereço
        return strlen(trim($address)) >= 5;
    }
    
    // Método para validar dados de atleta
    public function validateAthleteData($data) {
        $errors = [];
        
        $required = ['name', 'cpf', 'birth_date', 'federation', 'club', 'sport'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "Campo {$field} é obrigatório";
            }
        }
        
        if (!empty($data['cpf']) && !$this->validateCPF($data['cpf'])) {
            $errors[] = 'CPF inválido';
        }
        
        if (!empty($data['email']) && !$this->validateEmail($data['email'])) {
            $errors[] = 'Email inválido';
        }
        
        if (!empty($data['birth_date']) && !$this->validateDate($data['birth_date'])) {
            $errors[] = 'Data de nascimento inválida';
        }
        
        if (!empty($data['birth_date']) && !$this->validateAge($data['birth_date'])) {
            $errors[] = 'Atleta deve ter pelo menos 14 anos';
        }
        
        if (!empty($data['phone']) && !$this->validatePhone($data['phone'])) {
            $errors[] = 'Telefone inválido';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    // Método para validar dados de teste antidoping
    public function validateDopingTestData($data) {
        $errors = [];
        
        $required = ['athlete_id', 'test_type', 'test_date', 'laboratory_id', 'collection_officer'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "Campo {$field} é obrigatório";
            }
        }
        
        if (!empty($data['test_type']) && !$this->validateTestType($data['test_type'])) {
            $errors[] = 'Tipo de teste inválido';
        }
        
        if (!empty($data['test_date']) && !$this->validateDate($data['test_date'])) {
            $errors[] = 'Data do teste inválida';
        }
        
        if (!empty($data['test_reason']) && !$this->validateTestReason($data['test_reason'])) {
            $errors[] = 'Motivo do teste inválido';
        }
        
        if (!empty($data['athlete_id']) && !$this->validateInteger($data['athlete_id'])) {
            $errors[] = 'ID do atleta deve ser numérico';
        }
        
        if (!empty($data['laboratory_id']) && !$this->validateInteger($data['laboratory_id'])) {
            $errors[] = 'ID do laboratório deve ser numérico';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    // Método para validar dados de resultado de teste
    public function validateTestResultData($data) {
        $errors = [];
        
        $required = ['result', 'technical_manager'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "Campo {$field} é obrigatório";
            }
        }
        
        if (!empty($data['result']) && !$this->validateTestResult($data['result'])) {
            $errors[] = 'Resultado inválido';
        }
        
        if (!empty($data['result_date']) && !$this->validateDate($data['result_date'])) {
            $errors[] = 'Data do resultado inválida';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

// Função global para facilitar o uso
function validateInput($data, $rules) {
    $validator = new Validator();
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        $value = $data[$field] ?? null;
        
        if (strpos($rule, 'required') !== false && empty($value)) {
            $errors[$field] = "O campo {$field} é obrigatório";
            continue;
        }
        
        if (!empty($value)) {
            switch ($rule) {
                case 'email':
                    if (!$validator->validateEmail($value)) {
                        $errors[$field] = "Email inválido";
                    }
                    break;
                    
                case 'cpf':
                    if (!$validator->validateCPF($value)) {
                        $errors[$field] = "CPF inválido";
                    }
                    break;
                    
                case 'date':
                    if (!$validator->validateDate($value)) {
                        $errors[$field] = "Data inválida";
                    }
                    break;
                    
                case 'phone':
                    if (!$validator->validatePhone($value)) {
                        $errors[$field] = "Telefone inválido";
                    }
                    break;
                    
                case 'numeric':
                    if (!$validator->validateNumeric($value)) {
                        $errors[$field] = "Valor deve ser numérico";
                    }
                    break;
                    
                case 'integer':
                    if (!$validator->validateInteger($value)) {
                        $errors[$field] = "Valor deve ser inteiro";
                    }
                    break;
            }
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}
?>