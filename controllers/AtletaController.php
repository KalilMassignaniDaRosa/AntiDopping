<?php
require_once __DIR__ . '/../models/Atleta.php';

class AtletaController
{
    private $db;
    private $atleta;

    public function __construct($db)
    {
        $this->db = $db;
        $this->atleta = new Atleta($db);
    }

    public function listar()
    {
        try {
            $stmt = $this->atleta->listar();
            $atletas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'sucesso' => true,
                'dados' => $atletas,
                'total' => count($atletas)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Erro ao listar atletas: ' . $e->getMessage()
            ]);
        }
    }

    public function listarPorStatus($status)
    {
        try {
            $stmt = $this->atleta->buscarPorStatus($status);
            $atletas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'sucesso' => true,
                'dados' => $atletas,
                'total' => count($atletas)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Erro ao listar atletas por status: ' . $e->getMessage()
            ]);
        }
    }

    public function criar()
    {
        try {
            // Obter dados da requisição
            $entrada = json_decode(file_get_contents('php://input'), true);
            if (!$entrada && !empty($_POST)) {
                $entrada = $_POST;
            }

            if (!$entrada) {
                http_response_code(400);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'Nenhum dado recebido'
                ]);
                return;
            }

            $camposObrigatorios = ['nome', 'cpf', 'data_nascimento', 'clube_id', 'posicao'];
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

            // Limpar e formatar CPF
            $cpf_limpo = preg_replace('/\D/', '', $entrada['cpf']);

            if (!$this->validarCPF($cpf_limpo, false)) {
                http_response_code(400);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'CPF inválido: ' . $entrada['cpf']
                ]);
                return;
            }

            // Verificar se CPF já existe
            $cpfExistente = $this->atleta->buscarPorCpf($cpf_limpo);
            if ($cpfExistente) {
                http_response_code(400);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'CPF já cadastrado'
                ]);
                return;
            }

            // Atribuir valores
            $this->atleta->nome = htmlspecialchars(strip_tags($entrada['nome']));
            $this->atleta->cpf = $cpf_limpo;
            $this->atleta->data_nascimento = $entrada['data_nascimento'];
            $this->atleta->clube_id = $entrada['clube_id'];
            $this->atleta->posicao = htmlspecialchars(strip_tags($entrada['posicao']));
            $this->atleta->status = $entrada['status'] ?? 'ativo';
            $this->atleta->observacoes = $entrada['observacoes'] ?? null;

            if ($this->atleta->criar()) {
                echo json_encode([
                    'sucesso' => true,
                    'mensagem' => 'Atleta cadastrado com sucesso',
                    'id' => $this->atleta->id
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'Erro ao cadastrar atleta'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Erro ao processar requisição: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function buscar($id)
    {
        try {
            $atleta = $this->atleta->buscarPorId($id);

            if ($atleta) {
                echo json_encode([
                    'sucesso' => true,
                    'dados' => $atleta
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'Atleta não encontrado'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Erro ao buscar atleta'
            ]);
        }
    }

    public function atualizar($id)
    {
        try {
            $entrada = json_decode(file_get_contents('php://input'), true);

            if (!$entrada) {
                http_response_code(400);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'Nenhum dado recebido para atualização'
                ]);
                return;
            }

            error_log("=== TENTANDO ATUALIZAR ATLETA ===");
            error_log("ID: " . $id);
            error_log("Dados recebidos: " . print_r($entrada, true));

            // Buscar atleta existente
            $atletaExistente = $this->atleta->buscarPorId($id);
            if (!$atletaExistente) {
                error_log("Atleta não encontrado com ID: " . $id);
                http_response_code(404);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'Atleta não encontrado'
                ]);
                return;
            }

            error_log("Dados existentes: " . print_r($atletaExistente, true));

            // Preparar dados para atualização
            $dadosAtualizacao = [
                'id' => $id,
                'nome' => isset($entrada['nome']) ? $entrada['nome'] : $atletaExistente['nome'],
                'data_nascimento' => isset($entrada['data_nascimento']) ? $entrada['data_nascimento'] : $atletaExistente['data_nascimento'],
                'clube_id' => isset($entrada['clube_id']) ? $entrada['clube_id'] : $atletaExistente['clube_id'],
                'posicao' => isset($entrada['posicao']) ? $entrada['posicao'] : $atletaExistente['posicao'],
                'status' => isset($entrada['status']) ? $entrada['status'] : $atletaExistente['status'],
                'observacoes' => isset($entrada['observacoes']) ? $entrada['observacoes'] : $atletaExistente['observacoes']
            ];

            // Tratamento do CPF
            if (isset($entrada['cpf']) && !empty($entrada['cpf'])) {
                $cpf_limpo = preg_replace('/[^0-9]/', '', $entrada['cpf']);

                // CPF existente no DB (limpo) — limpa também o que veio do banco
                $cpf_existente_no_db = preg_replace('/[^0-9]/', '', $atletaExistente['cpf']);

                // APENAS validar se o CPF foi alterado
                if ($cpf_limpo !== $cpf_existente_no_db) {
                    if (!$this->validarCPF($cpf_limpo)) {
                        error_log("CPF inválido: " . $entrada['cpf']);
                        http_response_code(400);
                        echo json_encode([
                            'sucesso' => false,
                            'mensagem' => 'CPF inválido. Por favor, informe um CPF válido.'
                        ]);
                        return;
                    }

                    // Verificar se CPF já existe em outro atleta
                    $cpfExistente = $this->atleta->buscarPorCpf($cpf_limpo);
                    if ($cpfExistente && $cpfExistente['id'] != $id) {
                        error_log("CPF já existe para outro atleta: " . $cpf_limpo);
                        http_response_code(400);
                        echo json_encode([
                            'sucesso' => false,
                            'mensagem' => 'CPF já cadastrado para outro atleta'
                        ]);
                        return;
                    }
                }

                // sempre atribuir o CPF limpo (apenas dígitos)
                $dadosAtualizacao['cpf'] = $cpf_limpo;
            } else {
                // Se não foi enviado CPF, mantém o existente (em formato limpo)
                $dadosAtualizacao['cpf'] = preg_replace('/[^0-9]/', '', $atletaExistente['cpf']);
            }

            error_log("Dados finais para atualização: " . print_r($dadosAtualizacao, true));

            // Atribuir valores ao model
            $this->atleta->id = $dadosAtualizacao['id'];
            $this->atleta->nome = $dadosAtualizacao['nome'];
            $this->atleta->cpf = $dadosAtualizacao['cpf'];
            $this->atleta->data_nascimento = $dadosAtualizacao['data_nascimento'];
            $this->atleta->clube_id = $dadosAtualizacao['clube_id'];
            $this->atleta->posicao = $dadosAtualizacao['posicao'];
            $this->atleta->status = $dadosAtualizacao['status'];
            $this->atleta->observacoes = $dadosAtualizacao['observacoes'];

            // Executar atualização
            $resultado = $this->atleta->atualizar();

            error_log("Resultado da atualização: " . ($resultado ? 'SUCESSO' : 'FALHA'));

            if ($resultado) {
                echo json_encode([
                    'sucesso' => true,
                    'mensagem' => 'Atleta atualizado com sucesso'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'Erro ao atualizar atleta no banco de dados'
                ]);
            }
        } catch (Exception $e) {
            error_log("ERRO NA ATUALIZAÇÃO: " . $e->getMessage());
            error_log("STACK TRACE: " . $e->getTraceAsString());

            http_response_code(500);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Erro ao processar requisição: ' . $e->getMessage()
            ]);
        }
    }

    public function deletar($id)
    {
        try {
            $atletaExistente = $this->atleta->buscarPorId($id);
            if (!$atletaExistente) {
                http_response_code(404);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'Atleta não encontrado'
                ]);
                return;
            }

            if ($this->atleta->deletar($id)) {
                echo json_encode([
                    'sucesso' => true,
                    'mensagem' => 'Atleta excluído com sucesso'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'Erro ao excluir atleta'
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

    private function validarCPF($cpf, $rigoroso = false)
    {
        // Remove tudo que não for dígito
        $cpf = preg_replace('/\D/', '', $cpf);

        // Deve ter exatamente 11 dígitos
        if (strlen($cpf) !== 11) {
            return false;
        }

        if ($rigoroso) {
            // Validação clássica (dígitos verificadores)
            for ($t = 9; $t < 11; $t++) {
                $d = 0;
                for ($c = 0; $c < $t; $c++) {
                    $d += intval($cpf[$c]) * (($t + 1) - $c);
                }
                $d = ((10 * $d) % 11) % 10;
                if (intval($cpf[$t]) != $d) {
                    return false;
                }
            }
        }

        return true;
    }
}
?>