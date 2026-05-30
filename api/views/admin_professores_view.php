<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Mari Admin - Professores</title>
    <!-- MESMO CSS DO ADMIN.PHP -->
    <link rel="stylesheet" href="../../estilos/professores.css">
</head>
<body>

<?php include '../header.php'; ?>

<div class="container">
    <!-- Modal de Cadastro/Edição -->
    <div id="modalCadastro" class="modal-overlay <?= $editData ? 'active' : '' ?>">
        <div class="modal-content">
            <button type="button" class="close-btn" onclick="fecharModal()">&times;</button>
            <h3 style="margin-top: 0; color: var(--azul);"><?= $editData ? 'Editar Disciplina/Professor' : 'Cadastrar Nova Disciplina' ?></h3>
            <form method="POST" action="admin_professores.php">
                <input type="hidden" name="acao" value="salvar">
                <?php if($editData): ?>
                    <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div>
                        <label style="font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px; display: block;">Nome da Matéria</label>
                        <input type="text" name="nome_materia" required value="<?= htmlspecialchars($editData['nome_materia'] ?? '') ?>" placeholder="Ex: Engenharia de Software II">
                    </div>
                    <div>
                        <label style="font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px; display: block;">Nome do Professor</label>
                        <input type="text" name="professor" required value="<?= htmlspecialchars($editData['professor'] ?? '') ?>" placeholder="Ex: Josenyr S. Rosa">
                    </div>
                    <div>
                        <label style="font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px; display: block;">Email do Professor</label>
                        <input type="email" name="email_prof" required value="<?= htmlspecialchars($editData['email_prof'] ?? '') ?>" placeholder="Ex: josenyr.rosa@fatec.sp.gov.br">
                    </div>
                    <div>
                        <label style="font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px; display: block;">URL da Ementa</label>
                        <input type="text" name="ementa_url" required value="<?= htmlspecialchars($editData['ementa_url'] ?? '') ?>" placeholder="Ex: https://fateczs.edu.br/ementas/es2.pdf">
                    </div>
                </div>
                
                <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                    <?php if($editData): ?>
                        <a href="admin_professores.php" class="btn-action" style="background: #94a3b8 !important;">Cancelar</a>
                    <?php else: ?>
                        <button type="button" class="btn-action" style="background: #94a3b8 !important;" onclick="fecharModal()">Cancelar</button>
                    <?php endif; ?>
                    <button type="submit"><?= $editData ? 'Salvar Alterações' : 'Cadastrar' ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista -->
    <div class="glass-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <form method="GET" style="display: flex; gap: 10px; align-items: center; margin-bottom: 0;">
                <input type="text" name="busca" placeholder="Filtrar por Matéria ou Professor..." value="<?= htmlspecialchars($busca) ?>" style="width:300px !important; margin-bottom: 0;">
                <button type="submit">Filtrar</button>
                <?php if($busca): ?>
                    <a href="admin_professores.php" class="btn-action" style="background: #94a3b8 !important;">Limpar Filtro</a>
                <?php endif; ?>
            </form>
            <button type="button" onclick="abrirModal()" style="background: #10b981 !important; padding: 12px 24px !important; border-radius: 8px !important; color: white !important; border: none !important; font-weight: 500; cursor: pointer !important; transition: all 0.3s ease !important;">+ Nova Disciplina</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Matéria</th>
                    <th>Professor</th>
                    <th>Email</th>
                    <th>Ementa URL</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($professores as $p): ?>
                <tr>
                    <td><strong><?= $p['id'] ?></strong></td>
                    <td><?= htmlspecialchars($p['nome_materia']) ?></td>
                    <td><?= htmlspecialchars($p['professor']) ?></td>
                    <td><a href="mailto:<?= htmlspecialchars($p['email_prof']) ?>"><?= htmlspecialchars($p['email_prof']) ?></a></td>
                    <td><a href="<?= htmlspecialchars($p['ementa_url']) ?>" target="_blank" style="color: var(--azul); text-decoration: none; font-weight: 500;">Ver Ementa</a></td>
                    <td style="white-space: nowrap;">
                        <a href="admin_professores.php?edit=<?= $p['id'] ?>" class="btn-edit" style="display: inline-flex; align-items: center; justify-content: center; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85rem;"><i class="fa-solid fa-pen" style="margin-right: 5px;"></i> Editar</a>
                        <button type="button" class="btn-del" onclick="abrirModalExclusao('<?= htmlspecialchars($p['id']) ?>')" style="padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.85rem; display: inline-flex; align-items: center; justify-content: center;"><i class="fa-solid fa-trash" style="margin-right: 5px;"></i> Excluir</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de Exclusão -->
<div class="modal-overlay" id="modalExclusao">
    <div class="modal-content" style="max-width: 400px; text-align: center; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
        <h3 style="color: var(--vermelho-fatec, #dc3545); margin-top: 0;">Confirmar Exclusão</h3>
        <p style="color: #64748b; margin-bottom: 20px;">Tem certeza que deseja excluir esta disciplina/professor? Esta ação não pode ser desfeita.</p>
        
        <div style="display: flex; justify-content: center; gap: 10px;">
            <button type="button" class="btn-action" style="background: #94a3b8 !important;" onclick="fecharModalExclusao()">Cancelar</button>
            <a href="#" id="btnConfirmarExclusao" class="btn-del" style="padding: 10px 20px; font-size: 1rem; text-decoration: none; border-radius: 8px;">Sim, Excluir</a>
        </div>
    </div>
</div>

<script>
function abrirModal() {
    document.getElementById('modalCadastro').classList.add('active');
}
function fecharModal() {
    document.getElementById('modalCadastro').classList.remove('active');
}

function abrirModalExclusao(idProfessor) {
    document.getElementById('btnConfirmarExclusao').href = 'admin_professores.php?del=' + encodeURIComponent(idProfessor);
    document.getElementById('modalExclusao').classList.add('active');
}
function fecharModalExclusao() {
    document.getElementById('modalExclusao').classList.remove('active');
}
</script>
</body>
</html>
