<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Mari Admin - Alunos</title>
    <link rel="stylesheet" href="../../estilos/alunos.css">
</head>
<body>

<?php include '../header.php'; ?>

<div class="container">
    <!-- Modal de Cadastro/Edição -->
    <div id="modalCadastro" class="modal-overlay <?= $editData ? 'active' : '' ?>">
        <div class="modal-content">
            <button type="button" class="close-btn" onclick="fecharModal()">&times;</button>
            <h3 style="margin-top: 0; color: var(--azul-fatec);"><?= $editData ? 'Editar Aluno' : 'Cadastrar Novo Aluno' ?></h3>
            <form method="POST" action="admin_alunos.php">
                <input type="hidden" name="acao" value="salvar">
                <?php if($editData): ?>
                    <input type="hidden" name="ra_original" value="<?= htmlspecialchars($editData['ra']) ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div>
                        <label style="font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px; display: block;">RA do Aluno</label>
                        <input type="text" name="ra" required value="<?= htmlspecialchars($editData['ra'] ?? '') ?>" placeholder="Ex: 1111111111111">
                    </div>
                    <div>
                        <label style="font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px; display: block;">Nome do Aluno</label>
                        <input type="text" name="nome" required value="<?= htmlspecialchars($editData['nome'] ?? '') ?>" placeholder="Ex: Carlos Oliveira">
                    </div>
                    <div>
                        <label style="font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px; display: block;">Curso</label>
                        <input type="text" name="curso" required value="<?= htmlspecialchars($editData['curso'] ?? '') ?>" placeholder="Ex: DSM">
                    </div>
                    <div>
                        <label style="font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px; display: block;">Semestre</label>
                        <input type="number" name="semestre" required value="<?= htmlspecialchars($editData['semestre'] ?? '') ?>" placeholder="Ex: 2">
                    </div>
                    <div>
                        <label style="font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px; display: block;">Turno</label>
                        <select name="turno" required>
                            <option value="Manhã" <?= ($editData['turno'] ?? '') == 'Manhã' ? 'selected' : '' ?>>Manhã</option>
                            <option value="Tarde" <?= ($editData['turno'] ?? '') == 'Tarde' ? 'selected' : '' ?>>Tarde</option>
                            <option value="Noite" <?= ($editData['turno'] ?? '') == 'Noite' ? 'selected' : '' ?>>Noite</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px; display: block;">Status</label>
                        <select name="status" required>
                            <option value="Ativo" <?= ($editData['status'] ?? '') == 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                            <option value="Inativo" <?= ($editData['status'] ?? '') == 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                </div>
                
                <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                    <?php if($editData): ?>
                        <a href="admin_alunos.php" class="btn-action" style="background: #94a3b8 !important;">Cancelar</a>
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
                <input type="text" name="busca" placeholder="Filtrar por Nome ou RA..." value="<?= htmlspecialchars($busca) ?>" style="width:300px !important; margin-bottom: 0;">
                <button type="submit">Filtrar</button>
                <?php if($busca): ?>
                    <a href="admin_alunos.php" class="btn-action" style="background: #94a3b8 !important;">Limpar Filtro</a>
                <?php endif; ?>
            </form>
            <button type="button" onclick="abrirModal()" style="background: #10b981 !important; padding: 12px 24px !important; border-radius: 8px !important; color: white !important; border: none !important; font-weight: 500; cursor: pointer !important; transition: all 0.3s ease !important;">+ Novo Aluno</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>RA</th>
                    <th>Nome</th>
                    <th>Curso</th>
                    <th>Semestre</th>
                    <th>Turno</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($alunos as $a): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($a['ra']) ?></strong></td>
                    <td><?= htmlspecialchars($a['nome']) ?></td>
                    <td><?= htmlspecialchars($a['curso']) ?></td>
                    <td><?= htmlspecialchars($a['semestre']) ?>º</td>
                    <td><?= htmlspecialchars($a['turno']) ?></td>
                    <td><span class="status-badge <?= htmlspecialchars($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span></td>
                    <td style="white-space: nowrap;">
                        <a href="admin_alunos.php?edit=<?= $a['ra'] ?>" class="btn-edit" style="display: inline-flex; align-items: center; justify-content: center; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85rem;"><i class="fa-solid fa-pen" style="margin-right: 5px;"></i> Editar</a>
                        <button type="button" class="btn-del" onclick="abrirModalExclusao('<?= htmlspecialchars($a['ra']) ?>')" style="padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.85rem; display: inline-flex; align-items: center; justify-content: center;"><i class="fa-solid fa-trash" style="margin-right: 5px;"></i> Excluir</button>
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
        <p style="color: #64748b; margin-bottom: 20px;">Tem certeza que deseja excluir este aluno? Esta ação não pode ser desfeita.</p>
        
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

function abrirModalExclusao(raAluno) {
    document.getElementById('btnConfirmarExclusao').href = 'admin_alunos.php?del=' + encodeURIComponent(raAluno);
    document.getElementById('modalExclusao').classList.add('active');
}
function fecharModalExclusao() {
    document.getElementById('modalExclusao').classList.remove('active');
}
</script>
</body>
</html>
