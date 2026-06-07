<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<div class="header">
    <div style="display: flex; align-items: center; gap: 30px;">
        <h1>Gestão - MarIA</h1>
        <nav style="display: flex; gap: 15px;">
            <a href="dashboard.php" style="<?= $currentPage == 'dashboard.php' ? 'font-weight: 600;' : 'opacity: 0.8; font-weight: 400;' ?>">Dashboard</a>
            <a href="admin_protocolo.php" style="<?= $currentPage == 'admin_protocolo.php' ? 'font-weight: 600;' : 'opacity: 0.8; font-weight: 400;' ?>">Protocolos</a>
            <a href="admin_professores.php" style="<?= $currentPage == 'admin_professores.php' ? 'font-weight: 600;' : 'opacity: 0.8; font-weight: 400;' ?>">Professores</a>
            <a href="admin_alunos.php" style="<?= $currentPage == 'admin_alunos.php' ? 'font-weight: 600;' : 'opacity: 0.8; font-weight: 400;' ?>">Alunos</a>
            <a href="admin_usuarios.php" style="<?= $currentPage == 'admin_usuarios.php' ? 'font-weight: 600;' : 'opacity: 0.8; font-weight: 400;' ?>">Administradores</a>
        </nav>
    </div>
    <a href="../logout.php">Sair</a>
</div>
