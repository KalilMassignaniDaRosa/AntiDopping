<?php

require_once __DIR__ . '/../models/Clube.php';

class ClubeController {
    private $db;
    private $clube;

    public function __construct($db) {
        $this->db = $db;
        $this->clube = new Clube($db);
    }

    public function listar() {
        try {
            $stmt = $this->clube->listar();
            $clubes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $clubes,
                'total' => count($clubes)
            ]);

        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao listar clubes: ' . $e->getMessage()
            ]);
        }
    }

    public function buscar($id) {
        try {
            $clube = $this->clube->buscarPorId($id);

            if($clube) {
                echo json_encode([
                    'success' => true,
                    'data' => $clube
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Clube não encontrado'
                ]);
            }

        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao buscar clube'
            ]);
        }
    }
}
?>
