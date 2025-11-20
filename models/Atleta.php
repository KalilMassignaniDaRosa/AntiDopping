<?php

class Atleta
{
    private $conn;
    private $table = "atletas";

    public $id;
    public $nome;
    public $cpf;
    public $data_nascimento;
    public $clube_id;
    public $posicao;
    public $status;
    public $observacoes;
    public $created_at;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function criar()
    {
        $query = "INSERT INTO " . $this->table . "
                  (nome, cpf, data_nascimento, clube_id, posicao, status, observacoes, created_at, updated_at)
                  VALUES (:nome, :cpf, :data_nascimento, :clube_id, :posicao, :status, :observacoes, NOW(), NOW())";

        try {
            $stmt = $this->conn->prepare($query);

            $this->nome = htmlspecialchars(strip_tags($this->nome));
            $this->cpf = htmlspecialchars(strip_tags($this->cpf));
            $this->posicao = htmlspecialchars(strip_tags($this->posicao));

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
        } catch (PDOException $e) {
            error_log("Erro ao criar atleta: " . $e->getMessage());
            return false;
        }
    }

    public function listar()
    {
        $query = "SELECT
                    a.id,
                    a.nome,
                    a.cpf,
                    a.data_nascimento,
                    TIMESTAMPDIFF(YEAR, a.data_nascimento, CURDATE()) as idade,
                    a.posicao,
                    a.status,
                    a.observacoes,
                    c.nome as clube_nome,
                    c.cidade as clube_cidade,
                    f.nome as federacao_nome,
                    f.estado as federacao_estado,
                    a.created_at,
                    a.updated_at
                  FROM " . $this->table . " a
                  LEFT JOIN clubes c ON a.clube_id = c.id
                  LEFT JOIN federacoes f ON c.federacao_id = f.id
                  ORDER BY a.nome ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function buscarPorId($id)
    {
        $query = "SELECT
                    a.*,
                    TIMESTAMPDIFF(YEAR, a.data_nascimento, CURDATE()) as idade,
                    c.nome as clube_nome,
                    c.cidade as clube_cidade,
                    f.nome as federacao_nome
                  FROM " . $this->table . " a
                  LEFT JOIN clubes c ON a.clube_id = c.id
                  LEFT JOIN federacoes f ON c.federacao_id = f.id
                  WHERE a.id = :id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function buscarPorCpf($cpf)
    {
        $cpf_limpo = preg_replace('/\D/', '', $cpf);

        $query = "SELECT * FROM " . $this->table . "
              WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = :cpf
              LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":cpf", $cpf_limpo);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function atualizar()
    {
        $query = "UPDATE " . $this->table . "
              SET nome = :nome,
                  cpf = :cpf,
                  data_nascimento = :data_nascimento,
                  clube_id = :clube_id,
                  posicao = :posicao,
                  status = :status,
                  observacoes = :observacoes,
                  updated_at = NOW()
              WHERE id = :id";

        try {
            $stmt = $this->conn->prepare($query);

            // Log detalhado
            error_log("Executando query de atualização: " . $query);
            error_log("Parâmetros:");
            error_log(" - nome: " . $this->nome);
            error_log(" - cpf: " . $this->cpf);
            error_log(" - data_nascimento: " . $this->data_nascimento);
            error_log(" - clube_id: " . $this->clube_id);
            error_log(" - posicao: " . $this->posicao);
            error_log(" - status: " . $this->status);
            error_log(" - observacoes: " . $this->observacoes);
            error_log(" - id: " . $this->id);

            $stmt->bindParam(":nome", $this->nome);
            $stmt->bindParam(":cpf", $this->cpf);
            $stmt->bindParam(":data_nascimento", $this->data_nascimento);
            $stmt->bindParam(":clube_id", $this->clube_id);
            $stmt->bindParam(":posicao", $this->posicao);
            $stmt->bindParam(":status", $this->status);
            $stmt->bindParam(":observacoes", $this->observacoes);
            $stmt->bindParam(":id", $this->id);

            $resultado = $stmt->execute();

            // Verificar se alguma linha foi afetada
            $linhasAfetadas = $stmt->rowCount();
            error_log("Linhas afetadas na atualização: " . $linhasAfetadas);

            if ($linhasAfetadas === 0) {
                error_log("AVISO: Nenhuma linha foi afetada na atualização - possivelmente os dados são iguais");
            }

            return $resultado;
        } catch (PDOException $e) {
            error_log("ERRO PDO na atualização: " . $e->getMessage());
            error_log("Código do erro: " . $e->getCode());
            error_log("Info do erro: " . print_r($stmt->errorInfo(), true));
            return false;
        }
    }

    public function deletar($id)
    {
        $query = "UPDATE " . $this->table . " SET status = 'inativo', updated_at = NOW() WHERE id = :id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao deletar atleta: " . $e->getMessage());
            return false;
        }
    }

    public function buscarPorStatus($status)
    {
        $query = "SELECT
                    a.id,
                    a.nome,
                    a.cpf,
                    a.data_nascimento,
                    TIMESTAMPDIFF(YEAR, a.data_nascimento, CURDATE()) as idade,
                    a.posicao,
                    a.status,
                    a.observacoes,
                    c.nome as clube_nome,
                    c.cidade as clube_cidade,
                    f.nome as federacao_nome,
                    f.estado as federacao_estado,
                    a.created_at,
                    a.updated_at
                  FROM " . $this->table . " a
                  LEFT JOIN clubes c ON a.clube_id = c.id
                  LEFT JOIN federacoes f ON c.federacao_id = f.id
                  WHERE a.status = :status
                  ORDER BY a.nome ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->execute();
        return $stmt;
    }

    public function contarTotal()
    {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function buscarPorClube($clube_id)
    {
        $query = "SELECT
                    a.*,
                    TIMESTAMPDIFF(YEAR, a.data_nascimento, CURDATE()) as idade,
                    c.nome as clube_nome
                  FROM " . $this->table . " a
                  LEFT JOIN clubes c ON a.clube_id = c.id
                  WHERE a.clube_id = :clube_id
                  ORDER BY a.nome ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":clube_id", $clube_id);
        $stmt->execute();
        return $stmt;
    }

    public function listarPorPosicao($posicao)
    {
        $query = "SELECT
                    a.*,
                    TIMESTAMPDIFF(YEAR, a.data_nascimento, CURDATE()) as idade,
                    c.nome as clube_nome
                  FROM " . $this->table . " a
                  LEFT JOIN clubes c ON a.clube_id = c.id
                  WHERE a.posicao = :posicao
                  ORDER BY a.nome ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":posicao", $posicao);
        $stmt->execute();
        return $stmt;
    }
}
