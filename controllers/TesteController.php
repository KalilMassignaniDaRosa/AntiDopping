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
                'sucesso' => true,
                'dados' => $testes,
                'total' => count($testes)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Erro ao listar testes: ' . $e->getMessage()
            ]);
        }
    }

    public function buscar($id)
    {
        try {
            $teste = $this->teste->buscarPorId($id);

            if ($teste) {
                echo json_encode([
                    'sucesso' => true,
                    'dados' => $teste
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'Teste não encontrado'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Erro ao buscar teste'
            ]);
        }
    }

    public function criar()
    {
        try {
            $entrada = json_decode(file_get_contents('php://input'), true);
            if (!$entrada) {
                $entrada = $_POST;
            }

            $camposObrigatorios = ['atleta_id', 'data_coleta', 'tipo_teste', 'laboratorio_id'];
            $camposFaltantes = [];

            foreach ($camposObrigatorios as $campo) {
                if (empty($entrada[$campo])) {
                    $camposFaltantes[] = $campo;
                }
            }

            if (!empty($camposFaltantes)) {
                http_response_code(400);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'Dados obrigatórios não informados: ' . implode(', ', $camposFaltantes)
                ]);
                return;
            }

            // Mapeamento correto dos campos
            $this->teste->atleta_id = $entrada['atleta_id'];
            $this->teste->data_coleta = $entrada['data_coleta'];
            $this->teste->hora_coleta = $entrada['hora_coleta'] ?? null;
            $this->teste->tipo_teste = $entrada['tipo_teste'];
            $this->teste->laboratorio_id = $entrada['laboratorio_id'];
            $this->teste->resultado = $entrada['resultado'] ?? 'pendente';
            $this->teste->substancia_detectada = $entrada['substancia_detectada'] ?? null;
            $this->teste->nivel_substancia = $entrada['nivel_substancia'] ?? null;
            $this->teste->data_resultado = $entrada['data_resultado'] ?? null;
            $this->teste->observacoes = $entrada['observacoes'] ?? null;

            if ($this->teste->criar()) {
                echo json_encode([
                    'sucesso' => true,
                    'mensagem' => 'Teste cadastrado com sucesso',
                    'id' => $this->teste->id
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'Erro ao cadastrar teste'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Erro ao processar requisição: ' . $e->getMessage()
            ]);
        }
    }

    public function atualizar($id)
    {
        try {
            $entrada = json_decode(file_get_contents('php://input'), true);
            if (!$entrada) {
                $entrada = $_POST;
            }

            $testeExistente = $this->teste->buscarPorId($id);
            if (!$testeExistente) {
                http_response_code(404);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'Teste não encontrado'
                ]);
                return;
            }

            // Se resultado foi alterado e não é mais "pendente", definir data_resultado
            if (isset($entrada['resultado']) && $entrada['resultado'] !== 'pendente' && $testeExistente['resultado'] === 'pendente') {
                $entrada['data_resultado'] = date('Y-m-d');
            }

            // Atribuir valores
            $this->teste->id = $id;
            $this->teste->atleta_id = $entrada['atleta_id'] ?? $testeExistente['atleta_id'];
            $this->teste->data_coleta = $entrada['data_coleta'] ?? $testeExistente['data_coleta'];
            $this->teste->hora_coleta = $entrada['hora_coleta'] ?? $testeExistente['hora_coleta'];
            $this->teste->tipo_teste = $entrada['tipo_teste'] ?? $testeExistente['tipo_teste'];
            $this->teste->laboratorio_id = $entrada['laboratorio_id'] ?? $testeExistente['laboratorio_id'];
            $this->teste->resultado = $entrada['resultado'] ?? $testeExistente['resultado'];
            $this->teste->substancia_detectada = $entrada['substancia_detectada'] ?? $testeExistente['substancia_detectada'];
            $this->teste->nivel_substancia = $entrada['nivel_substancia'] ?? $testeExistente['nivel_substancia'];
            $this->teste->data_resultado = $entrada['data_resultado'] ?? $testeExistente['data_resultado'];
            $this->teste->observacoes = $entrada['observacoes'] ?? $testeExistente['observacoes'];

            error_log("Atualizando teste ID: $id com resultado: " . $this->teste->resultado);

            if ($this->teste->atualizar()) {
                echo json_encode([
                    'sucesso' => true,
                    'mensagem' => 'Teste atualizado com sucesso'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'Erro ao atualizar teste'
                ]);
            }
        } catch (Exception $e) {
            error_log("Erro ao atualizar teste: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Erro ao processar requisição: ' . $e->getMessage()
            ]);
        }
    }

    public function listarPorAtleta($atleta_id)
    {
        try {
            $stmt = $this->teste->buscarPorAtleta($atleta_id);
            $testes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'sucesso' => true,
                'dados' => $testes,
                'total' => count($testes)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Erro ao listar testes do atleta: ' . $e->getMessage()
            ]);
        }
    }

    public function dashboard()
    {
        try {
            $dashboard = $this->teste->obterDadosDashboard();

            echo json_encode([
                'sucesso' => true,
                'dados' => $dashboard
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Erro ao carregar dashboard: ' . $e->getMessage()
            ]);
        }
    }

    public function gerarRelatorio()
    {
        try {
            $filtros = $_GET;
            $relatorio = $this->teste->gerarRelatorio($filtros);

            echo json_encode([
                'sucesso' => true,
                'dados' => $relatorio
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Erro ao gerar relatório: ' . $e->getMessage()
            ]);
        }
    }
}
?>