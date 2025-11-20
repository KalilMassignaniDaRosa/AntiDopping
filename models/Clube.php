<?php

class Clube {
    private $conn;
    private $table = "clubes";

    public $id;
    public $nome;
    public $cidade;
    public $estado;
    public $federacao_id;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listar() {
        $query = "SELECT
                    c.*,
                    f.nome as federacao_nome,
                    f.estado as federacao_estado
                  FROM " . $this->table . " c
                  LEFT JOIN federacoes f ON c.federacao_id = f.id
                  ORDER BY c.nome ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function buscarPorId($id) {
        $query = "SELECT
                    c.*,
                    f.nome as federacao_nome,
                    f.estado as federacao_estado
                  FROM " . $this->table . " c
                  LEFT JOIN federacoes f ON c.federacao_id = f.id
                  WHERE c.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
