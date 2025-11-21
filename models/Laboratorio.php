<?php
class Laboratorio {
    private $conn;
    private $tabela = "laboratorios";

    public $id;
    public $nome;
    public $cidade;
    public $estado;
    public $pais;
    public $credenciamento;
    public $credenciado_wada;
    public $email;
    public $telefone;
    public $data_criacao;
    public $data_atualizacao;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listar() {
        $query = "SELECT * FROM " . $this->tabela . " ORDER BY nome";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function buscarPorId($id) {
        $query = "SELECT * FROM " . $this->tabela . " WHERE id = ?";
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