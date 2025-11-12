<?php
// config.php - Configuração do Banco de Dados
session_start();

// Configurações para InfinityFree
define('DB_HOST', 'sql200.infinityfree.com'); // Altere conforme seu painel
define('DB_USER', 'seu_usuario'); // Seu usuário do banco
define('DB_PASS', 'sua_senha'); // Sua senha do banco
define('DB_NAME', 'cbf_antidoping'); // Nome do banco
define('DB_CHARSET', 'utf8mb4');

// Configurações gerais
define('SITE_URL', 'http://seu-site.great-site.net');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 2097152); // 2MB em bytes

// Criar conexão
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch(PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// Funções auxiliares
function limpar_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function verificar_login() {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: login.php");
        exit();
    }
}

function verificar_admin() {
    if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'admin') {
        header("Location: index.php");
        exit();
    }
}

function registrar_log($pdo, $usuario_id, $acao, $tabela = null, $registro_id = null, $detalhes = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
    $sql = "INSERT INTO logs_auditoria (usuario_id, acao, tabela, registro_id, detalhes, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id, $acao, $tabela, $registro_id, $detalhes, $ip]);
}

function formatar_data($data) {
    return date('d/m/Y', strtotime($data));
}

function formatar_cpf($cpf) {
    return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "$1.$2.$3-$4", $cpf);
}
?>