<nav class="navbar">
    <div class="nav-brand">
        <h2>🏆 CBF Antidoping</h2>
    </div>
    <ul class="nav-menu">
        <li><a href="index.php">Dashboard</a></li>
        <li><a href="atletas.php">Atletas</a></li>
        <li><a href="testes.php">Testes</a></li>
        <li><a href="competicoes.php">Competições</a></li>
        <li><a href="relatorios.php">Relatórios</a></li>
        <?php if ($_SESSION['usuario_tipo'] === 'admin'): ?>
            <li><a href="usuarios.php">Usuários</a></li>
        <?php endif; ?>
        <li><a href="logout.php">Sair</a></li>
    </ul>
    <div class="nav-user">
        <span><?= htmlspecialchars($_SESSION['usuario_nome']) ?></span>
        <small><?= ucfirst($_SESSION['usuario_tipo']) ?></small>
    </div>
</nav>