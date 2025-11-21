<?php
class Clube {
    private $conn;
    private $tabela = "clubes";

    public $id;
    public $nome;
    public $federacao_id;
    public $cidade;
    public $estado;
    public $data_criacao;
    public $data_atualizacao;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listar() {
        $query = "SELECT c.*, f.nome as federacao_nome 
                 FROM " . $this->tabela . " c
                 LEFT JOIN federacoes f ON c.federacao_id = f.id
                 ORDER BY c.nome";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function buscarPorId($id) {
        $query = "SELECT c.*, f.nome as federacao_nome 
                 FROM " . $this->tabela . " c
                 LEFT JOIN federacoes f ON c.federacao_id = f.id
                 WHERE c.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return false;
    }
}
?>