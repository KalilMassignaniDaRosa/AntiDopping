<?php
class TesteAntidoping {
    private $conn;
    private $tabela = "testes_antidoping";

    public $id;
    public $atleta_id;
    public $laboratorio_id;
    public $data_coleta;
    public $hora_coleta;
    public $tipo_teste;
    public $resultado;
    public $substancia_detectada;
    public $nivel_substancia;
    public $observacoes;
    public $usuario_registro;
    public $data_resultado;
    public $data_criacao;
    public $data_atualizacao;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function listar() {
        $query = "SELECT t.*, 
                 a.nome as atleta_nome, a.cpf as atleta_cpf,
                 c.nome as clube_nome,
                 l.nome as laboratorio_nome
                 FROM " . $this->tabela . " t
                 LEFT JOIN atletas a ON t.atleta_id = a.id
                 LEFT JOIN clubes c ON a.clube_id = c.id
                 LEFT JOIN laboratorios l ON t.laboratorio_id = l.id
                 ORDER BY t.data_coleta DESC, t.id DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function buscarPorId($id) {
        $query = "SELECT t.*, 
                 a.nome as atleta_nome, a.cpf as atleta_cpf, a.data_nascimento as atleta_data_nascimento,
                 c.nome as clube_nome,
                 l.nome as laboratorio_nome, l.cidade as laboratorio_cidade
                 FROM " . $this->tabela . " t
                 LEFT JOIN atletas a ON t.atleta_id = a.id
                 LEFT JOIN clubes c ON a.clube_id = c.id
                 LEFT JOIN laboratorios l ON t.laboratorio_id = l.id
                 WHERE t.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return false;
    }

    public function buscarPorAtleta($atleta_id) {
        $query = "SELECT t.*, l.nome as laboratorio_nome
                 FROM " . $this->tabela . " t
                 LEFT JOIN laboratorios l ON t.laboratorio_id = l.id
                 WHERE t.atleta_id = ?
                 ORDER BY t.data_coleta DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $atleta_id);
        $stmt->execute();

        return $stmt;
    }

    public function criar() {
        $query = "INSERT INTO " . $this->tabela . "
                SET atleta_id=:atleta_id, laboratorio_id=:laboratorio_id,
                data_coleta=:data_coleta, hora_coleta=:hora_coleta,
                tipo_teste=:tipo_teste, resultado=:resultado,
                substancia_detectada=:substancia_detectada,
                nivel_substancia=:nivel_substancia, observacoes=:observacoes,
                usuario_registro=:usuario_registro, data_resultado=:data_resultado";

        $stmt = $this->conn->prepare($query);

        // Limpar dados
        $this->substancia_detectada = $this->substancia_detectada ? htmlspecialchars(strip_tags($this->substancia_detectada)) : null;
        $this->nivel_substancia = $this->nivel_substancia ? htmlspecialchars(strip_tags($this->nivel_substancia)) : null;
        $this->observacoes = $this->observacoes ? htmlspecialchars(strip_tags($this->observacoes)) : null;
        $this->usuario_registro = "sistema";

        // Vincular valores
        $stmt->bindParam(":atleta_id", $this->atleta_id);
        $stmt->bindParam(":laboratorio_id", $this->laboratorio_id);
        $stmt->bindParam(":data_coleta", $this->data_coleta);
        $stmt->bindParam(":hora_coleta", $this->hora_coleta);
        $stmt->bindParam(":tipo_teste", $this->tipo_teste);
        $stmt->bindParam(":resultado", $this->resultado);
        $stmt->bindParam(":substancia_detectada", $this->substancia_detectada);
        $stmt->bindParam(":nivel_substancia", $this->nivel_substancia);
        $stmt->bindParam(":observacoes", $this->observacoes);
        $stmt->bindParam(":usuario_registro", $this->usuario_registro);
        $stmt->bindParam(":data_resultado", $this->data_resultado);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    public function atualizar() {
        $query = "UPDATE " . $this->tabela . "
                SET atleta_id=:atleta_id, laboratorio_id=:laboratorio_id,
                data_coleta=:data_coleta, hora_coleta=:hora_coleta,
                tipo_teste=:tipo_teste, resultado=:resultado,
                substancia_detectada=:substancia_detectada,
                nivel_substancia=:nivel_substancia, observacoes=:observacoes,
                data_resultado=:data_resultado
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Limpar dados
        $this->substancia_detectada = $this->substancia_detectada ? htmlspecialchars(strip_tags($this->substancia_detectada)) : null;
        $this->nivel_substancia = $this->nivel_substancia ? htmlspecialchars(strip_tags($this->nivel_substancia)) : null;
        $this->observacoes = $this->observacoes ? htmlspecialchars(strip_tags($this->observacoes)) : null;

        // Vincular valores
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

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function obterDadosDashboard() {
        $dados = [];

        // Total de atletas ativos
        $query = "SELECT COUNT(*) as total FROM atletas WHERE status = 'ativo'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $dados['total_atletas_ativos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Total de testes
        $query = "SELECT COUNT(*) as total FROM " . $this->tabela;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $dados['total_testes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Testes pendentes
        $query = "SELECT COUNT(*) as total FROM " . $this->tabela . " WHERE resultado = 'pendente'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $dados['testes_pendentes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Testes positivos
        $query = "SELECT COUNT(*) as total FROM " . $this->tabela . " WHERE resultado = 'positivo'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $dados['testes_positivos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        return $dados;
    }

    public function gerarRelatorio($filtros = []) {
        $query = "SELECT t.*, 
                 a.nome as atleta_nome, a.cpf as atleta_cpf,
                 c.nome as clube_nome,
                 l.nome as laboratorio_nome
                 FROM " . $this->tabela . " t
                 LEFT JOIN atletas a ON t.atleta_id = a.id
                 LEFT JOIN clubes c ON a.clube_id = c.id
                 LEFT JOIN laboratorios l ON t.laboratorio_id = l.id
                 WHERE 1=1";

        $parametros = [];

        if (!empty($filtros['data_inicio'])) {
            $query .= " AND t.data_coleta >= ?";
            $parametros[] = $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {
            $query .= " AND t.data_coleta <= ?";
            $parametros[] = $filtros['data_fim'];
        }

        if (!empty($filtros['resultado'])) {
            $query .= " AND t.resultado = ?";
            $parametros[] = $filtros['resultado'];
        }

        $query .= " ORDER BY t.data_coleta DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($parametros);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>