<?php
class Database {
    private $host = "localhost";
    private $nome_banco = "cbf_antidoping";
    private $usuario = "root";
    private $senha = "root";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->nome_banco . ";charset=utf8mb4",
                $this->usuario,
                $this->senha,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

            // Teste a conexão
            $this->conn->query("SELECT 1");

        } catch(PDOException $e) {
            error_log("Erro de conexão: " . $e->getMessage());
            throw new Exception("Erro de conexão com o banco de dados: " . $e->getMessage());
        }

        return $this->conn;
    }
}
?>