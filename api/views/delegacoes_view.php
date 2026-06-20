<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gerenciador de Assinaturas - MarIA</title>
    <link rel="stylesheet" href="../../estilos/protocolos.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        .header-title {
            color: #0f172a;
            font-size: 2rem;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-success { background: #dcfce7; border: 1px solid #86efac; color: #166534; }
        .alert-error { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }
        .alert-info { background: #e0f2fe; border: 1px solid #7dd3fc; color: #075985; }

        .senha-destaque {
            font-family: monospace;
            font-size: 1.5rem;
            background: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            border: 2px dashed var(--azul-fatec);
            display: inline-block;
            margin-top: 10px;
            color: var(--azul-fatec);
            font-weight: bold;
        }

        .badge-ativo { background: #dcfce7; color: #15803d; }
        .badge-expirado { background: #fee2e2; color: #991b1b; }
        .badge-revogado { background: #f1f5f9; color: #475569; }

    </style>
</head>
<body>

    <?php include '../header.php'; ?>

    <div class="container">

        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= htmlspecialchars($tipo_msg) ?>">
                <?= $mensagem ?>
                <?php if ($senha_gerada): ?>
                    <br>
                    <div class="senha-destaque"><?= $senha_gerada ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Tabela de Auditoria e Histórico -->
        <div class="glass-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h3 style="margin-top:0; margin-bottom: 5px;"><i class="ph ph-list-magnifying-glass"></i> Minhas Delegações</h3>
                    <p style="color:#64748b; font-size: 0.9rem; margin: 0;">
                        Histórico de acessos criados por você e permissões que foram concedidas a você.
                    </p>
                </div>
                <button type="button" class="btn-action" style="background: #10b981 !important; white-space: nowrap;" onclick="abrirModalNovaDelegacao()">+ Criar Nova Delegação de Assinatura</button>
            </div>

            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Data Criação</th>
                            <th>Autor (De)</th>
                            <th>Delegado (Para)</th>
                            <th>Expira Em</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($delegacoes)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #64748b; padding: 30px;">
                                    Nenhum registro de delegação encontrado no seu escopo.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($delegacoes as $del): ?>
                                <tr>
                                    <td><?= $del['criado_em_formatada'] ?></td>
                                    <td style="font-weight: 600; color: #0f172a;">
                                        <?= htmlspecialchars($del['quem_delegou']) ?>
                                        <?= $del['quem_delegou'] == $_SESSION['admin_nome'] ? '<span style="color:#94a3b8; font-weight:normal;">(Você)</span>' : '' ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($del['quem_recebeu']) ?>
                                        <?= $del['quem_recebeu'] == $_SESSION['admin_nome'] ? '<span style="color:#94a3b8; font-weight:normal;">(Você)</span>' : '' ?>
                                    </td>
                                    <td><?= $del['data_expiracao_formatada'] ?></td>
                                    <td>
                                        <?php 
                                            $classeBadge = '';
                                            if ($del['status_calculado'] === 'Ativo') $classeBadge = 'badge-ativo';
                                            elseif ($del['status_calculado'] === 'Expirado') $classeBadge = 'badge-expirado';
                                            else $classeBadge = 'badge-revogado';
                                        ?>
                                        <span class="status-badge <?= $classeBadge ?>">
                                            <?= $del['status_calculado'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 8px;">
                                            <?php if ($del['status_calculado'] === 'Ativo'): ?>
                                                
                                                <?php if ($del['quem_delegou'] == $_SESSION['admin_nome']): ?>
                                                    <!-- Botão Revogar para o Autor -->
                                                    <form method="POST" action="delegacao_controller.php" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja revogar esta senha? O usuário delegado não poderá mais usá-la.');">
                                                        <input type="hidden" name="acao" value="revogar">
                                                        <input type="hidden" name="id_delegacao" value="<?= $del['id'] ?>">
                                                        <button type="submit" class="btn-del" style="padding: 6px 12px !important; font-size: 0.8rem !important; border-radius: 6px !important;">
                                                            <i class="ph ph-x-circle"></i> Revogar
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if ($del['quem_recebeu'] == $_SESSION['admin_nome']): ?>
                                                    <!-- Botão Ver Senha para o Delegado -->
                                                    <button type="button" class="btn-edit" style="padding: 6px 12px !important; font-size: 0.8rem !important; border-radius: 6px !important;" onclick="abrirModalSenha(<?= $del['id'] ?>)">
                                                        <i class="ph ph-eye"></i> Ver Senha
                                                    </button>
                                                <?php endif; ?>
                                                
                                            <?php else: ?>
                                                <span style="color: #cbd5e1;">-</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Modal de Nova Delegação -->
    <div id="modalNovaDelegacao" class="modal-overlay">
        <div class="modal-content" style="max-width: 600px;">
            <button type="button" class="close-btn" onclick="fecharModalNovaDelegacao()">&times;</button>
            <h3 style="color: var(--azul-fatec); margin-top: 0; margin-bottom: 5px;">
                <i class="ph ph-key"></i> Nova Delegação Temporária
            </h3>
            <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 25px;">
                Permita que outro membro assine documentos em seu nome por um período estrito.
            </p>

            <form method="POST" action="delegacao_controller.php">
                <input type="hidden" name="acao" value="criar">
                
                <div class="form-grid">
                    <div style="grid-column: 1 / -1;">
                        <label style="font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px; display: block;">Conceder permissão para:</label>
                        <select name="id_usuario_delegado" required style="width: 100%;">
                            <option value="">-- Selecione o Servidor/Docente --</option>
                            <?php foreach ($usuarios_disponiveis as $u): ?>
                                <option value="<?= htmlspecialchars($u['id']) ?>">
                                    <?= htmlspecialchars($u['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label style="font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px; display: block;">Tipo de Validade:</label>
                        <select name="tipo_validade" id="tipoValidade" onchange="toggleValidade()" style="width: 100%;">
                            <option value="horas">Em Horas</option>
                            <option value="data">Data Exata</option>
                        </select>
                    </div>

                    <div id="containerHoras">
                        <label style="font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px; display: block;">Horas:</label>
                        <input type="number" name="horas_validade" min="1" max="72" value="2" style="width: 100%; padding: 12px 16px; border-radius: 8px; border: 1px solid var(--border-color); box-sizing: border-box;">
                    </div>

                    <div id="containerData" style="display: none;">
                        <label style="font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 5px; display: block;">Data Exata:</label>
                        <input type="datetime-local" name="data_exata" style="width: 100%; padding: 12px 16px; border-radius: 8px; border: 1px solid var(--border-color); box-sizing: border-box;">
                    </div>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px;">
                    <button type="button" class="btn-action" style="background: #94a3b8 !important;" onclick="fecharModalNovaDelegacao()">Cancelar</button>
                    <button type="submit" class="btn-action"><i class="ph ph-shield-check"></i> Gerar Senha Segura</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Desbloqueio de Senha -->
    <div id="modalSenha" class="modal-overlay">
        <div class="modal-content" style="max-width: 360px; text-align: center; padding: 40px; border-radius: 16px; background: white;">
            <h3 style="color: #1e293b; margin-top: 0; font-size: 22px; display: flex; flex-direction: column; align-items: center; gap: 10px;">
                <i class="ph ph-shield-check" style="font-size: 32px; color: var(--azul-fatec);"></i>
                Desbloqueio de Senha
            </h3>
            <p style="font-size: 14px; color: #64748b; line-height: 1.5; margin-bottom: 25px;">
                Insira suas credenciais corporativas para visualizar a senha delegada.
            </p>

            <form method="POST" action="delegacao_controller.php">
                <input type="hidden" name="acao" value="ver_senha">
                <input type="hidden" name="id_delegacao" id="modal_id_delegacao" value="">

                <input type="text" name="login_fatec" placeholder="Seu Login da Fatec" required style="width: 100%; padding: 14px; margin: 10px 0; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; outline: none; font-family: 'Inter', sans-serif;">
                
                <input type="password" name="senha_fatec" placeholder="Sua Senha" required style="width: 100%; padding: 14px; margin: 10px 0 20px 0; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; outline: none; font-family: 'Inter', sans-serif;">

                <button type="submit" style="background: var(--azul-fatec); color: white; padding: 14px; border: none; border-radius: 8px; cursor: pointer; width: 100%; font-size: 15px; font-weight: 600; margin-bottom: 10px; transition: background 0.3s; font-family: 'Inter', sans-serif;">Desbloquear</button>
                <button type="button" onclick="fecharModalSenha()" style="background: #f1f5f9; color: #64748b; padding: 14px; border: none; border-radius: 8px; cursor: pointer; width: 100%; font-size: 15px; font-weight: 600; transition: background 0.3s; font-family: 'Inter', sans-serif;">Cancelar</button>
            </form>
        </div>
    </div>

    <script>
        function abrirModalSenha(id_delegacao) {
            document.getElementById('modal_id_delegacao').value = id_delegacao;
            document.getElementById('modalSenha').classList.add('active');
        }

        function fecharModalSenha() {
            document.getElementById('modalSenha').classList.remove('active');
        }

        function abrirModalNovaDelegacao() {
            document.getElementById('modalNovaDelegacao').classList.add('active');
        }

        function fecharModalNovaDelegacao() {
            document.getElementById('modalNovaDelegacao').classList.remove('active');
        }

        function toggleValidade() {
            const tipo = document.getElementById('tipoValidade').value;
            if (tipo === 'horas') {
                document.getElementById('containerHoras').style.display = 'block';
                document.getElementById('containerData').style.display = 'none';
            } else {
                document.getElementById('containerHoras').style.display = 'none';
                document.getElementById('containerData').style.display = 'block';
            }
        }
        
        // Fecha modal ao clicar fora dele
        window.onclick = function(event) {
            if (event.target == document.getElementById('modalSenha')) {
                fecharModalSenha();
            }
            if (event.target == document.getElementById('modalNovaDelegacao')) {
                fecharModalNovaDelegacao();
            }
        }
    </script>
</body>
</html>
