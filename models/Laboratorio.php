<?php

class Laboratorio {
    private $conn;
    private $table = "laboratorios";

    public $id;
    public $nome;
    public $cidade;
    public $estado;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listar() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY nome ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>
