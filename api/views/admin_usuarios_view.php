<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Mari Admin - Administradores</title>
    <link rel="stylesheet" href="../../estilos/usuarios.css">
</head>
<body>

<?php include '../header.php'; ?>

<div class="container">
    <?php if(isset($erroCadastro)): ?>
        <div style="background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 500;">
            <?= htmlspecialchars($erroCadastro) ?>
        </div>
    <?php endif; ?>

    <!-- Modal de Cadastro -->
    <div id="modalCadastro" class="modal-overlay <?= isset($_POST['acao']) && isset($erroCadastro) ? 'active' : '' ?>">
        <div class="modal-content">
            <button type="button" class="close-btn" onclick="fecharModal()">&times;</button>
            <h3 style="margin-top: 0; color: var(--azul-fatec);">Novo Administrador</h3>
            <form method="POST" action="admin_usuarios.php" enctype="multipart/form-data">
                <input type="hidden" name="acao" value="salvar">
                
                <div class="form-grid">
                    <div>
                        <label style="font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px; display: block;">Nome Completo</label>
                        <input type="text" name="nome" required placeholder="Ex: Ana Silva">
                    </div>
                    <div>
                        <label style="font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px; display: block;">Usuário (Login)</label>
                        <input type="text" name="usuario" required placeholder="Ex: ana.silva">
                    </div>
                    <div>
                        <label style="font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px; display: block;">Senha</label>
                        <input type="password" name="senha" required placeholder="Digite uma senha forte">
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label style="font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px; display: block;">Foto da Assinatura (Opcional)</label>
                        <input type="file" name="assinatura_foto" accept="image/png, image/jpeg" style="width: 100%; padding: 10px; border: 1px dashed #ccc; border-radius: 4px;">
                    </div>
                </div>
                
                <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn-action" style="background: #94a3b8 !important;" onclick="fecharModal()">Cancelar</button>
                    <button type="submit">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista -->
    <div class="glass-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <form method="GET" style="display: flex; gap: 10px; align-items: center; margin-bottom: 0;">
                <input type="text" name="busca" placeholder="Filtrar por Nome ou Login..." value="<?= htmlspecialchars($busca) ?>" style="width:300px !important; margin-bottom: 0;">
                <button type="submit">Filtrar</button>
                <?php if($busca): ?>
                    <a href="admin_usuarios.php" class="btn-action" style="background: #94a3b8 !important;">Limpar</a>
                <?php endif; ?>
            </form>
            <button type="button" onclick="abrirModal()" style="background: #10b981 !important; padding: 12px 24px !important; border-radius: 8px !important; color: white !important; border: none !important; font-weight: 500; cursor: pointer !important; transition: all 0.3s ease !important;">+ Novo Admin</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Login</th>
                    <th>Criado em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($usuarios as $u): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($u['nome']) ?></strong></td>
                    <td><?= htmlspecialchars($u['usuario']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($u['criado_em'])) ?></td>
                    <td style="white-space: nowrap;">
                        <?php if ($u['id'] !== $_SESSION['admin_id']): ?>
                            <button type="button" class="btn-del" onclick="abrirModalExclusao('<?= htmlspecialchars($u['id']) ?>')" style="padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.85rem;">Excluir</button>
                        <?php else: ?>
                            <span style="color: #94a3b8; font-size: 0.8rem; font-weight: 600;">(Você)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function abrirModal() {
    document.getElementById('modalCadastro').classList.add('active');
}
function fecharModal() {
    document.getElementById('modalCadastro').classList.remove('active');
}
</script>

<!-- Modal de Exclusão -->
<div class="modal-overlay" id="modalExclusao">
    <div class="modal-content" style="max-width: 400px; text-align: center;">
        <h3 style="color: var(--vermelho-fatec); margin-top: 0;">Confirmar Exclusão</h3>
        <p style="color: #64748b; margin-bottom: 20px;">Tem certeza que deseja excluir o acesso deste administrador? Esta ação não pode ser desfeita.</p>
        
        <div style="display: flex; justify-content: center; gap: 10px;">
            <button type="button" class="btn-action" style="background: #94a3b8 !important;" onclick="fecharModalExclusao()">Cancelar</button>
            <a href="#" id="btnConfirmarExclusao" class="btn-del" style="padding: 10px 20px; font-size: 1rem;">Sim, Excluir</a>
        </div>
    </div>
</div>

<script>
    function abrirModalExclusao(idUsuario) {
        document.getElementById('btnConfirmarExclusao').href = 'admin_usuarios.php?del=' + encodeURIComponent(idUsuario);
        document.getElementById('modalExclusao').classList.add('active');
    }

    function fecharModalExclusao() {
        document.getElementById('modalExclusao').classList.remove('active');
    }
</script>

</body>
</html>
