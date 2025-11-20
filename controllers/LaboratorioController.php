<?php

require_once __DIR__ . '/../models/Laboratorio.php';

class LaboratorioController {
    private $db;
    private $laboratorio;

    public function __construct($db) {
        $this->db = $db;
        $this->laboratorio = new Laboratorio($db);
    }

    public function listar() {
        try {
            $stmt = $this->laboratorio->listar();
            $laboratorios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $laboratorios,
                'total' => count($laboratorios)
            ]);

        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao listar laboratórios: ' . $e->getMessage()
            ]);
        }
    }
}
?>
