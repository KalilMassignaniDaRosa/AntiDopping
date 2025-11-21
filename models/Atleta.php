<?php
class Atleta {
    private $conn;
    private $tabela = "atletas";

    public $id;
    public $nome;
    public $cpf;
    public $data_nascimento;
    public $clube_id;
    public $posicao;
    public $status;
    public $observacoes;
    public $data_criacao;
    public $data_atualizacao;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listar() {
        $query = "SELECT a.*, c.nome as clube_nome, 
                 TIMESTAMPDIFF(YEAR, a.data_nascimento, CURDATE()) as idade
                 FROM " . $this->tabela . " a
                 LEFT JOIN clubes c ON a.clube_id = c.id
                 ORDER BY a.nome";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function buscarPorId($id) {
        $query = "SELECT a.*, c.nome as clube_nome,
                 TIMESTAMPDIFF(YEAR, a.data_nascimento, CURDATE()) as idade
                 FROM " . $this->tabela . " a
                 LEFT JOIN clubes c ON a.clube_id = c.id
                 WHERE a.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return false;
    }

    public function buscarPorCpf($cpf) {
        $query = "SELECT * FROM " . $this->tabela . " WHERE cpf = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $cpf);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return false;
    }

    public function buscarPorStatus($status) {
        $query = "SELECT a.*, c.nome as clube_nome,
                 TIMESTAMPDIFF(YEAR, a.data_nascimento, CURDATE()) as idade
                 FROM " . $this->tabela . " a
                 LEFT JOIN clubes c ON a.clube_id = c.id
                 WHERE a.status = ?
                 ORDER BY a.nome";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $status);
        $stmt->execute();

        return $stmt;
    }

    public function criar() {
        $query = "INSERT INTO " . $this->tabela . "
                SET nome=:nome, cpf=:cpf, data_nascimento=:data_nascimento,
                clube_id=:clube_id, posicao=:posicao, status=:status, observacoes=:observacoes";

        $stmt = $this->conn->prepare($query);

        // Limpar dados
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->cpf = htmlspecialchars(strip_tags($this->cpf));
        $this->posicao = htmlspecialchars(strip_tags($this->posicao));
        $this->observacoes = $this->observacoes ? htmlspecialchars(strip_tags($this->observacoes)) : null;

        // Vincular valores
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":cpf", $this->cpf);
        $stmt->bindParam(":data_nascimento", $this->data_nascimento);
        $stmt->bindParam(":clube_id", $this->clube_id);
        $stmt->bindParam(":posicao", $this->posicao);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":observacoes", $this->observacoes);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    public function atualizar() {
        $query = "UPDATE " . $this->tabela . "
                SET nome=:nome, cpf=:cpf, data_nascimento=:data_nascimento,
                clube_id=:clube_id, posicao=:posicao, status=:status, observacoes=:observacoes
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Limpar dados
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->cpf = htmlspecialchars(strip_tags($this->cpf));
        $this->posicao = htmlspecialchars(strip_tags($this->posicao));
        $this->observacoes = $this->observacoes ? htmlspecialchars(strip_tags($this->observacoes)) : null;

        // Vincular valores
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":cpf", $this->cpf);
        $stmt->bindParam(":data_nascimento", $this->data_nascimento);
        $stmt->bindParam(":clube_id", $this->clube_id);
        $stmt->bindParam(":posicao", $this->posicao);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":observacoes", $this->observacoes);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function deletar($id) {
        $query = "DELETE FROM " . $this->tabela . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }
}
?>