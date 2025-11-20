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
                'success' => true,
                'data' => $atletas,
                'total' => count($atletas)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao listar atletas: ' . $e->getMessage()
            ]);
        }
    }

    public function listarPorStatus($status)
    {
        try {
            $stmt = $this->atleta->buscarPorStatus($status);
            $atletas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $atletas,
                'total' => count($atletas)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao listar atletas por status: ' . $e->getMessage()
            ]);
        }
    }

    public function criar()
    {
        try {
            // Obter dados da requisição
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input && !empty($_POST)) {
                $input = $_POST;
            }

            if (!$input) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Nenhum dado recebido'
                ]);
                return;
            }

            $camposObrigatorios = ['nome', 'cpf', 'data_nascimento', 'clube_id', 'posicao'];
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

            // Limpar e formatar CPF
            $cpf_limpo = preg_replace('/\D/', '', $input['cpf']);

            if (!$this->validarCPF($cpf_limpo, false)) { // false = modo flexível
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'CPF inválido: ' . $input['cpf']
                ]);
                return;
            }


            // Verificar se CPF já existe
            $cpfExistente = $this->atleta->buscarPorCpf($cpf_limpo);
            if ($cpfExistente) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'CPF já cadastrado'
                ]);
                return;
            }

            // Atribuir valores
            $this->atleta->nome = htmlspecialchars(strip_tags($input['nome']));
            $this->atleta->cpf = $cpf_limpo; // Usar CPF limpo
            $this->atleta->data_nascimento = $input['data_nascimento'];
            $this->atleta->clube_id = $input['clube_id'];
            $this->atleta->posicao = htmlspecialchars(strip_tags($input['posicao']));
            $this->atleta->status = $input['status'] ?? 'ativo';
            $this->atleta->observacoes = $input['observacoes'] ?? null;

            if ($this->atleta->criar()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Atleta cadastrado com sucesso',
                    'id' => $this->atleta->id
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao cadastrar atleta'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao processar requisição: ' . $e->getMessage(),
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
                    'success' => true,
                    'data' => $atleta
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Atleta não encontrado'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao buscar atleta'
            ]);
        }
    }

    public function atualizar($id)
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Nenhum dado recebido para atualização'
                ]);
                return;
            }

            error_log("=== TENTANDO ATUALIZAR ATLETA ===");
            error_log("ID: " . $id);
            error_log("Dados recebidos: " . print_r($input, true));

            // Buscar atleta existente
            $atletaExistente = $this->atleta->buscarPorId($id);
            if (!$atletaExistente) {
                error_log("Atleta não encontrado com ID: " . $id);
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Atleta não encontrado'
                ]);
                return;
            }

            error_log("Dados existentes: " . print_r($atletaExistente, true));

            // Preparar dados para atualização
            $dadosAtualizacao = [
                'id' => $id,
                'nome' => isset($input['nome']) ? $input['nome'] : $atletaExistente['nome'],
                'data_nascimento' => isset($input['data_nascimento']) ? $input['data_nascimento'] : $atletaExistente['data_nascimento'],
                'clube_id' => isset($input['clube_id']) ? $input['clube_id'] : $atletaExistente['clube_id'],
                'posicao' => isset($input['posicao']) ? $input['posicao'] : $atletaExistente['posicao'],
                'status' => isset($input['status']) ? $input['status'] : $atletaExistente['status'],
                'observacoes' => isset($input['observacoes']) ? $input['observacoes'] : $atletaExistente['observacoes']
            ];

            // Tratamento do CPF
            if (isset($input['cpf']) && !empty($input['cpf'])) {
                $cpf_limpo = preg_replace('/[^0-9]/', '', $input['cpf']);

                // CPF existente no DB (limpo) — limpa também o que veio do banco
                $cpf_existente_no_db = preg_replace('/[^0-9]/', '', $atletaExistente['cpf']);

                // APENAS validar se o CPF foi alterado
                if ($cpf_limpo !== $cpf_existente_no_db) {
                    if (!$this->validarCPF($cpf_limpo)) {
                        error_log("CPF inválido: " . $input['cpf']);
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'message' => 'CPF inválido. Por favor, informe um CPF válido.'
                        ]);
                        return;
                    }

                    // Verificar se CPF já existe em outro atleta
                    $cpfExistente = $this->atleta->buscarPorCpf($cpf_limpo);
                    if ($cpfExistente && $cpfExistente['id'] != $id) {
                        error_log("CPF já existe para outro atleta: " . $cpf_limpo);
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'message' => 'CPF já cadastrado para outro atleta'
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
                    'success' => true,
                    'message' => 'Atleta atualizado com sucesso'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao atualizar atleta no banco de dados'
                ]);
            }
        } catch (Exception $e) {
            error_log("ERRO NA ATUALIZAÇÃO: " . $e->getMessage());
            error_log("STACK TRACE: " . $e->getTraceAsString());

            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao processar requisição: ' . $e->getMessage()
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
                    'success' => false,
                    'message' => 'Atleta não encontrado'
                ]);
                return;
            }

            if ($this->atleta->deletar($id)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Atleta excluído com sucesso'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao excluir atleta'
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

    private function validarCPF($cpf, $strict = false)
    {
        // Remove tudo que não for dígito
        $cpf = preg_replace('/\D/', '', $cpf);

        // Deve ter exatamente 11 dígitos
        if (strlen($cpf) !== 11) {
            return false;
        }

        if ($strict) {
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
