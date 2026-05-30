<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Mari Admin - FATEC ZS</title>
    <link rel="stylesheet" href="../../estilos/protocolos.css">
</head>
<body>

<?php include '../header.php'; ?>

<div class="container">
    <div class="glass-card">
        <form method="GET">
            <input type="text" name="busca" placeholder="Filtrar por RA ou Protocolo..." value="<?= $busca ?>" style="padding:10px; width:300px; border-radius:10px; border:1px solid #ccc;">
            <button type="submit" style="padding:10px 20px; border-radius:10px; background:var(--azul); color:white; border:none; cursor:pointer;">Filtrar</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Protocolo</th>
                    <th>Aluno (RA)</th>
                    <th>Serviço</th>
                    <th>Detalhes</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($protocolos as $p): ?>
                <tr>
                    <td><strong><?= $p['id_protocolo'] ?></strong></td>
                    <td><?= $p['nome'] ?> (<?= $p['ra_aluno'] ?>)</td>
                    <td><?= $p['tipo_servico'] ?></td>
                    <td style="font-size:0.85em; color:#555; font-style:italic; max-width:220px;">
                        <?= !empty($p['detalhes']) && $p['detalhes'] !== 'Nenhum detalhe informado'
                            ? htmlspecialchars($p['detalhes'])
                            : '<span style="color:#aaa;">—</span>' ?>
                    </td>
                    <td>
                        <form action="admin_protocolo.php" method="POST" style="margin: 0; display: inline-block;">
                            <input type="hidden" name="id" value="<?= $p['id_protocolo'] ?>">
                            <select name="novo_status" onchange="this.form.submit()" class="status-badge <?= str_replace(' ', '', $p['status']) ?>" style="cursor: pointer; border: none; outline: none; appearance: none; -webkit-appearance: none; padding-right: 25px; background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23000000%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 10px top 50%; background-size: 10px auto;">
                                <option value="Pendente" <?= $p['status'] === 'Pendente' ? 'selected' : '' ?>>PENDENTE</option>
                                <option value="Em andamento" <?= $p['status'] === 'Em andamento' ? 'selected' : '' ?>>EM ANDAMENTO</option>
                                <option value="Concluído" <?= $p['status'] === 'Concluído' ? 'selected' : '' ?>>CONCLUÍDO</option>
                            </select>
                        </form>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($p['data_abertura'])) ?></td>
                    <td style="white-space: nowrap;">
                        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                            <?php if (!empty($p['arquivo_comprovante'])): ?>
                                <a href="../uploads/<?= htmlspecialchars($p['arquivo_comprovante']) ?>" target="_blank" style="background: #007bff; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; font-size: 0.85rem; font-weight: 500; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,123,255,0.2);">
                                    <i class="fa-solid fa-file-pdf" style="margin-right: 5px;"></i> Ver Doc
                                </a>
                            <?php else: ?>
                                <span style="color: #999; font-size: 0.85rem; padding: 6px 12px; border: 1px dashed #ccc; border-radius: 6px; display: inline-flex; align-items: center;">Sem anexo</span>
                            <?php endif; ?>

                            <?php if (stripos($p['tipo_servico'], 'Declaração') !== false || stripos($p['tipo_servico'], 'Atestado') !== false || stripos($p['tipo_servico'], 'Matrícula') !== false || true): ?>
                                <a href="preview_documento.php?id=<?= urlencode($p['id_protocolo']) ?>" target="_blank" style="background: #10b981; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; font-size: 0.85rem; font-weight: 500; transition: all 0.2s; box-shadow: 0 2px 4px rgba(16,185,129,0.2);">
                                    <i class="fa-solid fa-pen-nib" style="margin-right: 5px;"></i> Gerar Atestado
                                </a>
                            <?php endif; ?>
                            
                            <button type="button" class="btn-del" onclick="abrirModalExclusao('<?= htmlspecialchars($p['id_protocolo']) ?>')" style="padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.85rem; display: inline-flex; align-items: center;">
                                <i class="fa-solid fa-trash" style="margin-right: 5px;"></i> Excluir
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de Exclusão -->
<div class="modal-overlay" id="modalExclusao">
    <div class="modal-content" style="max-width: 400px; text-align: center;">
        <h3 style="color: var(--vermelho-fatec); margin-top: 0;">Confirmar Exclusão</h3>
        <p style="color: #64748b; margin-bottom: 20px;">Tem certeza que deseja excluir este protocolo? Esta ação não pode ser desfeita.</p>
        
        <div style="display: flex; justify-content: center; gap: 10px;">
            <button type="button" class="btn-action" style="background: #94a3b8 !important;" onclick="fecharModalExclusao()">Cancelar</button>
            <a href="#" id="btnConfirmarExclusao" class="btn-del" style="padding: 10px 20px; font-size: 1rem;">Sim, Excluir</a>
        </div>
    </div>
</div>

<script>
    function abrirModalExclusao(idProtocolo) {
        document.getElementById('btnConfirmarExclusao').href = 'admin_protocolo.php?del=' + encodeURIComponent(idProtocolo);
        document.getElementById('modalExclusao').classList.add('active');
    }

    function fecharModalExclusao() {
        document.getElementById('modalExclusao').classList.remove('active');
    }
</script>

</body>
</html>
