<?php
require_once 'config.php';

if (isset($_SESSION['usuario_id'])) {
    registrar_log($pdo, $_SESSION['usuario_id'], 'Logout realizado');
}

session_destroy();
header("Location: login.php");
exit();
?>