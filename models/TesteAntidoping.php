<?php


class TesteAntidoping
{
    private $conn;
    private $table = "testes_antidoping";

    public $id;
    public $atleta_id;
    public $data_coleta;
    public $hora_coleta;
    public $tipo_teste;
    public $laboratorio_id;
    public $resultado;
    public $substancia_detectada;
    public $nivel_substancia;
    public $data_resultado;
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
                  (atleta_id, laboratorio_id, data_coleta, hora_coleta, tipo_teste, resultado, substancia_detectada, nivel_substancia, observacoes, data_resultado, created_at, updated_at)
                  VALUES (:atleta_id, :laboratorio_id, :data_coleta, :hora_coleta, :tipo_teste, :resultado, :substancia_detectada, :nivel_substancia, :observacoes, :data_resultado, NOW(), NOW())";

        try {
            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(":atleta_id", $this->atleta_id);
            $stmt->bindParam(":laboratorio_id", $this->laboratorio_id);
            $stmt->bindParam(":data_coleta", $this->data_coleta);
            $stmt->bindParam(":hora_coleta", $this->hora_coleta);
            $stmt->bindParam(":tipo_teste", $this->tipo_teste);
            $stmt->bindParam(":resultado", $this->resultado);
            $stmt->bindParam(":substancia_detectada", $this->substancia_detectada);
            $stmt->bindParam(":nivel_substancia", $this->nivel_substancia);
            $stmt->bindParam(":observacoes", $this->observacoes);
            $stmt->bindParam(":data_resultado", $this->data_resultado);

            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erro ao criar teste: " . $e->getMessage());
            return false;
        }
    }

    public function listar()
    {
        $query = "SELECT
                t.*,
                a.nome as atleta_nome,
                a.cpf as atleta_cpf,
                c.nome as clube_nome,
                l.nome as laboratorio_nome
              FROM " . $this->table . " t
              LEFT JOIN atletas a ON t.atleta_id = a.id
              LEFT JOIN clubes c ON a.clube_id = c.id
              LEFT JOIN laboratorios l ON t.laboratorio_id = l.id
              ORDER BY t.data_coleta DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function buscarPorId($id)
    {
        $query = "SELECT
                    t.*,
                    a.nome as atleta_nome,
                    a.cpf as atleta_cpf,
                    a.data_nascimento as atleta_data_nascimento,
                    c.nome as clube_nome,
                    l.nome as laboratorio_nome
                  FROM " . $this->table . " t
                  LEFT JOIN atletas a ON t.atleta_id = a.id
                  LEFT JOIN clubes c ON a.clube_id = c.id
                  LEFT JOIN laboratorios l ON t.laboratorio_id = l.id
                  WHERE t.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function buscarPorAtleta($atleta_id)
    {
        $query = "SELECT
                    t.*,
                    l.nome as laboratorio_nome
                  FROM " . $this->table . " t
                  LEFT JOIN laboratorios l ON t.laboratorio_id = l.id
                  WHERE t.atleta_id = :atleta_id
                  ORDER BY t.data_coleta DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":atleta_id", $atleta_id);
        $stmt->execute();
        return $stmt;
    }

    public function atualizar()
    {
        $query = "UPDATE " . $this->table . "
                  SET atleta_id = :atleta_id,
                      laboratorio_id = :laboratorio_id,
                      data_coleta = :data_coleta,
                      hora_coleta = :hora_coleta,
                      tipo_teste = :tipo_teste,
                      resultado = :resultado,
                      substancia_detectada = :substancia_detectada,
                      nivel_substancia = :nivel_substancia,
                      observacoes = :observacoes,
                      data_resultado = :data_resultado,
                      updated_at = NOW()
                  WHERE id = :id";

        try {
            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(":atleta_id", $this->atleta_id);
            $stmt->bindParam(":laboratorio_id", $this->laboratorio_id);
            $stmt->bindParam(":data_coleta", $this->data_coleta);
            $stmt->bindParam(":hora_coleta", $this->hora_coleta);
            $stmt->bindParam(":tipo_teste", $this->tipo_teste);
            $stmt->bindParam(":resultado", $this->resultado);
            $stmt->bindParam(":substancia_detectada", $this->substancia_detectada);
            $stmt->bindParam(":nivel_substancia", $this->nivel_substancia);
            $stmt->bindParam(":observacoes", $this->observacoes);
            $stmt->bindParam(":data_resultado", $this->data_resultado);
            $stmt->bindParam(":id", $this->id);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao atualizar teste: " . $e->getMessage());
            return false;
        }
    }

    public function obterDadosDashboard()
    {
        $dados = [];

        try {
            $queryAtletas = "SELECT COUNT(*) as total FROM atletas WHERE status = 'ativo'";
            $stmtAtletas = $this->conn->prepare($queryAtletas);
            $stmtAtletas->execute();
            $dados['total_atletas_ativos'] = $stmtAtletas->fetch(PDO::FETCH_ASSOC)['total'];

            $queryTotalTestes = "SELECT COUNT(*) as total FROM " . $this->table;
            $stmtTotalTestes = $this->conn->prepare($queryTotalTestes);
            $stmtTotalTestes->execute();
            $dados['total_testes'] = $stmtTotalTestes->fetch(PDO::FETCH_ASSOC)['total'];

            $queryPendentes = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE resultado = 'pendente'";
            $stmtPendentes = $this->conn->prepare($queryPendentes);
            $stmtPendentes->execute();
            $dados['testes_pendentes'] = $stmtPendentes->fetch(PDO::FETCH_ASSOC)['total'];

            $queryPositivos = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE resultado = 'positivo'";
            $stmtPositivos = $this->conn->prepare($queryPositivos);
            $stmtPositivos->execute();
            $dados['testes_positivos'] = $stmtPositivos->fetch(PDO::FETCH_ASSOC)['total'];

            $queryStatus = "SELECT resultado, COUNT(*) as quantidade
                          FROM " . $this->table . "
                          GROUP BY resultado";
            $stmtStatus = $this->conn->prepare($queryStatus);
            $stmtStatus->execute();
            $dados['testes_por_status'] = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);

            $queryMensal = "SELECT
                            DATE_FORMAT(data_coleta, '%Y-%m') as mes,
                            COUNT(*) as quantidade
                          FROM " . $this->table . "
                          WHERE data_coleta >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                          GROUP BY mes
                          ORDER BY mes DESC";
            $stmtMensal = $this->conn->prepare($queryMensal);
            $stmtMensal->execute();
            $dados['testes_por_mes'] = $stmtMensal->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao obter dados do dashboard: " . $e->getMessage());
            $dados = [
                'total_atletas_ativos' => 0,
                'total_testes' => 0,
                'testes_pendentes' => 0,
                'testes_positivos' => 0,
                'testes_por_status' => [],
                'testes_por_mes' => []
            ];
        }

        return $dados;
    }

    public function gerarRelatorio($filtros)
    {
        $query = "SELECT
                    t.*,
                    a.nome as atleta_nome,
                    a.cpf as atleta_cpf,
                    c.nome as clube_nome,
                    l.nome as laboratorio_nome
                  FROM " . $this->table . " t
                  LEFT JOIN atletas a ON t.atleta_id = a.id
                  LEFT JOIN clubes c ON a.clube_id = c.id
                  LEFT JOIN laboratorios l ON t.laboratorio_id = l.id
                  WHERE 1=1";

        $params = [];

        if (!empty($filtros['data_inicio'])) {
            $query .= " AND t.data_coleta >= :data_inicio";
            $params[':data_inicio'] = $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {
            $query .= " AND t.data_coleta <= :data_fim";
            $params[':data_fim'] = $filtros['data_fim'];
        }

        if (!empty($filtros['status'])) {
            $query .= " AND t.resultado = :resultado";
            $params[':resultado'] = $filtros['status'];
        }

        if (!empty($filtros['clube_id'])) {
            $query .= " AND a.clube_id = :clube_id";
            $params[':clube_id'] = $filtros['clube_id'];
        }

        $query .= " ORDER BY t.data_coleta DESC";

        try {
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao gerar relatório: " . $e->getMessage());
            return [];
        }
    }
}
