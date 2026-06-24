<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Documentos - FATEC ZS</title>
    <link rel="stylesheet" href="../../estilos/protocolos.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body>

<?php include '../header.php'; ?>

<div class="container">
    <div class="glass-card">
        <h3 style="margin-top:0; color: var(--azul-fatec); display: flex; align-items: center; gap: 10px;">
            <i class="ph ph-files"></i> Histórico de Documentos Oficiais
        </h3>
        <p style="color:#64748b; font-size: 0.9rem; margin-bottom: 20px;">
            Consulta aos documentos e certificados que foram gerados e assinados digitalmente pelo sistema.
        </p>

        <form method="GET" style="margin-bottom: 25px; display: flex; gap: 10px;">
            <input type="text" name="busca" placeholder="Buscar por Protocolo ou Nome do Aluno..." value="<?= htmlspecialchars($busca ?? '') ?>" style="padding: 12px 16px; border-radius: 8px; border: 1px solid #e2e8f0; flex: 1; outline: none; font-family: 'Inter', sans-serif;">
            <button type="submit" class="btn-action" style="padding: 12px 24px; border-radius: 8px;">
                <i class="ph ph-magnifying-glass"></i> Filtrar
            </button>
            <?php if (!empty($busca)): ?>
                <a href="historico_documentos.php" class="btn-action" style="background: #f1f5f9 !important; color: #64748b; text-decoration: none; padding: 12px 24px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;">
                    Limpar
                </a>
            <?php endif; ?>
        </form>

        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Data de Emissão</th>
                        <th>Protocolo</th>
                        <th>Aluno</th>
                        <th>Serviço</th>
                        <th>Emitido Por</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documentos)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #64748b; padding: 30px;">
                                Nenhum documento gerado encontrado no histórico local.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($documentos as $doc): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($doc['gerado_em'])) ?></td>
                                <td><strong><?= htmlspecialchars($doc['id_protocolo']) ?></strong></td>
                                <td><?= htmlspecialchars($doc['nome_aluno']) ?></td>
                                <td><?= htmlspecialchars($doc['tipo_servico']) ?></td>
                                <td><?= htmlspecialchars($doc['quem_gerou']) ?></td>
                                <td>
                                    <a href="visualizar_pdf.php?arquivo=<?= urlencode($doc['nome_arquivo']) ?>" 
                                       target="_blank" 
                                       class="btn-edit" style="text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                                        <i class="ph ph-file-pdf"></i> Rever Documento
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
