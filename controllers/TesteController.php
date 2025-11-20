<?php

require_once __DIR__ . '/../models/TesteAntidoping.php';

class TesteController
{
    private $db;
    private $teste;

    public function __construct($db)
    {
        $this->db = $db;
        $this->teste = new TesteAntidoping($db);
    }

    public function listar()
    {
        try {
            $stmt = $this->teste->listar();
            $testes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $testes,
                'total' => count($testes)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao listar testes: ' . $e->getMessage()
            ]);
        }
    }

    public function buscar($id)
    {
        try {
            $teste = $this->teste->buscarPorId($id);

            if ($teste) {
                echo json_encode([
                    'success' => true,
                    'data' => $teste
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Teste não encontrado'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao buscar teste'
            ]);
        }
    }

    public function criar()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }

            $camposObrigatorios = ['atleta_id', 'data_coleta', 'tipo_teste', 'laboratorio_id'];
            $camposFaltantes = [];

            foreach ($camposObrigatorios as $campo) {
                if (empty($input[$campo])) {
                    $camposFaltantes[] = $campo;
                }
            }

            if (!empty($camposFaltantes)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Dados obrigatórios não informados: ' . implode(', ', $camposFaltantes)
                ]);
                return;
            }

            // CORREÇÃO: Mapeamento correto dos campos
            $this->teste->atleta_id = $input['atleta_id'];
            $this->teste->data_coleta = $input['data_coleta'];
            $this->teste->hora_coleta = $input['hora_coleta'] ?? null;
            $this->teste->tipo_teste = $input['tipo_teste'];
            $this->teste->laboratorio_id = $input['laboratorio_id'];
            $this->teste->resultado = $input['resultado'] ?? 'pendente';
            $this->teste->substancia_detectada = $input['substancia_detectada'] ?? null;
            $this->teste->nivel_substancia = $input['nivel_substancia'] ?? null;
            $this->teste->data_resultado = $input['data_resultado'] ?? null;
            $this->teste->observacoes = $input['observacoes'] ?? null;

            if ($this->teste->criar()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Teste cadastrado com sucesso',
                    'id' => $this->teste->id
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao cadastrar teste'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao processar requisição: ' . $e->getMessage()
            ]);
        }
    }

    public function atualizar($id)
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }

            $testeExistente = $this->teste->buscarPorId($id);
            if (!$testeExistente) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Teste não encontrado'
                ]);
                return;
            }

            // Se resultado foi alterado e não é mais "pendente", definir data_resultado
            if (isset($input['resultado']) && $input['resultado'] !== 'pendente' && $testeExistente['resultado'] === 'pendente') {
                $input['data_resultado'] = date('Y-m-d');
            }

            // Atribuir valores
            $this->teste->id = $id;
            $this->teste->atleta_id = $input['atleta_id'] ?? $testeExistente['atleta_id'];
            $this->teste->data_coleta = $input['data_coleta'] ?? $testeExistente['data_coleta'];
            $this->teste->hora_coleta = $input['hora_coleta'] ?? $testeExistente['hora_coleta'];
            $this->teste->tipo_teste = $input['tipo_teste'] ?? $testeExistente['tipo_teste'];
            $this->teste->laboratorio_id = $input['laboratorio_id'] ?? $testeExistente['laboratorio_id'];
            $this->teste->resultado = $input['resultado'] ?? $testeExistente['resultado'];
            $this->teste->substancia_detectada = $input['substancia_detectada'] ?? $testeExistente['substancia_detectada'];
            $this->teste->nivel_substancia = $input['nivel_substancia'] ?? $testeExistente['nivel_substancia'];
            $this->teste->data_resultado = $input['data_resultado'] ?? $testeExistente['data_resultado'];
            $this->teste->observacoes = $input['observacoes'] ?? $testeExistente['observacoes'];

            error_log("Atualizando teste ID: $id com resultado: " . $this->teste->resultado);

            if ($this->teste->atualizar()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Teste atualizado com sucesso'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao atualizar teste'
                ]);
            }
        } catch (Exception $e) {
            error_log("Erro ao atualizar teste: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao processar requisição: ' . $e->getMessage()
            ]);
        }
    }

    public function listarPorAtleta($atleta_id)
    {
        try {
            $stmt = $this->teste->buscarPorAtleta($atleta_id);
            $testes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $testes,
                'total' => count($testes)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao listar testes do atleta: ' . $e->getMessage()
            ]);
        }
    }

    public function dashboard()
    {
        try {
            $dashboard = $this->teste->obterDadosDashboard();

            echo json_encode([
                'success' => true,
                'data' => $dashboard
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao carregar dashboard: ' . $e->getMessage()
            ]);
        }
    }

    public function gerarRelatorio()
    {
        try {
            $filtros = $_GET;
            $relatorio = $this->teste->gerarRelatorio($filtros);

            echo json_encode([
                'success' => true,
                'data' => $relatorio
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao gerar relatório: ' . $e->getMessage()
            ]);
        }
    }
}
