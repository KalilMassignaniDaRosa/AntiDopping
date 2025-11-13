<?php
// Ativar display de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log de erros
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 Debug - Sistema CBF Antidoping</h1>";

try {
    // Testar configurações básicas
    echo "<h2>📋 Informações do Servidor</h2>";
    echo "<pre>";
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
    echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
    echo "Current Dir: " . __DIR__ . "\n";
    echo "</pre>";

    // Testar extensões necessárias
    echo "<h2>🔌 Extensões PHP</h2>";
    $required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
    echo "<ul>";
    foreach ($required_extensions as $ext) {
        $loaded = extension_loaded($ext);
        $status = $loaded ? "✅" : "❌";
        echo "<li>$status $ext</li>";
    }
    echo "</ul>";

    // Testar permissões de arquivos
    echo "<h2>📁 Permissões de Arquivos</h2>";
    $important_dirs = ['logs', 'cache', 'config'];
    echo "<ul>";
    foreach ($important_dirs as $dir) {
        $path = __DIR__ . '/' . $dir;
        if (file_exists($path)) {
            $writable = is_writable($path);
            $status = $writable ? "✅" : "❌";
            echo "<li>$status $dir - " . ($writable ? "Gravável" : "Não gravável") . "</li>";
        } else {
            echo "<li>❌ $dir - Não existe</li>";
        }
    }
    echo "</ul>";

    // Testar includes básicos
    echo "<h2>📦 Includes Básicos</h2>";
    $required_files = [
        'config/database.php',
        'utils/logger.php',
        'utils/response.php',
        'middleware/cors.php'
    ];
    
    echo "<ul>";
    foreach ($required_files as $file) {
        if (file_exists(__DIR__ . '/' . $file)) {
            echo "<li>✅ $file - Existe</li>";
            
            // Tentar incluir
            try {
                require_once __DIR__ . '/' . $file;
                echo "<li style='margin-left: 20px;'>✅ $file - Incluído com sucesso</li>";
            } catch (Exception $e) {
                echo "<li style='margin-left: 20px; color: red;'>❌ $file - Erro: " . $e->getMessage() . "</li>";
            }
        } else {
            echo "<li>❌ $file - Não encontrado</li>";
        }
    }
    echo "</ul>";

    // Testar conexão com banco de dados
    echo "<h2>🗄️ Teste de Banco de Dados</h2>";
    try {
        require_once __DIR__ . '/config/database.php';
        $db = DatabaseManager::getConnection();
        echo "<p>✅ Conexão com banco de dados estabelecida</p>";
        
        // Testar consulta simples
        $stmt = $db->query("SELECT 1 as test");
        $result = $stmt->fetch();
        if ($result && $result['test'] == 1) {
            echo "<p>✅ Consulta SQL funcionando</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro na conexão com banco: " . $e->getMessage() . "</p>";
    }

    echo "<h2>🎯 Teste Completo - Index Principal</h2>";
    // Simular uma requisição ao index.php
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    // Capturar output do index.php
    ob_start();
    include __DIR__ . '/index.php';
    $output = ob_get_clean();
    
    echo "<p>✅ Index.php executou sem erros fatais</p>";
    echo "<details><summary>Ver Output</summary><pre>" . htmlspecialchars($output) . "</pre></details>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>💥 Erro Fatal</h2>";
    echo "<pre>" . $e->getMessage() . "\n\n" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><strong>Próximos passos:</strong></p>";
echo "<ol>";
echo "<li>Execute este arquivo debug.php no navegador</li>";
echo "<li>Verifique quais testes falharam (❌)</li>";
echo "<li>Corrija os problemas identificados</li>";
echo "<li>Teste o sistema novamente</li>";
echo "</ol>";
?>