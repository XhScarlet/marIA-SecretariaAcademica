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

        <form method="GET" style="margin-bottom: 25px; display: flex; gap: 10px; align-items: stretch; height: 48px;">
            <input type="text" name="busca" placeholder="Buscar por Protocolo ou Nome do Aluno..." value="<?= htmlspecialchars($busca ?? '') ?>" style="padding: 0 16px; border-radius: 8px; border: 1px solid #e2e8f0; flex: 1; outline: none; font-family: 'Inter', sans-serif; font-size: 0.95rem; box-sizing: border-box; height: 100%; background: #ffffff;">
            
            <button type="submit" class="btn-action" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0 28px; border-radius: 8px; border: none; cursor: pointer; font-weight: 500; font-size: 0.95rem; height: 100%; box-sizing: border-box;">
               Filtrar
            </button>
            
            <?php if (!empty($busca)): ?>
                <a href="historico_documentos.php" class="btn-action" style="background: #f1f5f9 !important; color: #64748b; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; padding: 0 28px; border-radius: 8px; font-weight: 500; font-size: 0.95rem; height: 100%; box-sizing: border-box; transition: background 0.2s;">
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
                                    <div style="display: flex; gap: 10px; align-items: center; white-space: nowrap;">
                                        <a href="visualizar_pdf.php?arquivo=<?= urlencode($doc['nome_arquivo']) ?>" 
                                           target="_blank" 
                                           class="btn-action" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 16px; border-radius: 6px; font-weight: 500; font-size: 0.9rem; height: 38px; box-sizing: border-box;">
                                            <i class="ph ph-file-pdf" style="font-size: 1.1rem;"></i> Rever Documento
                                        </a>
                                        <?php if (!empty($doc['detalhes'])): ?>
                                            <button type="button" 
                                                    class="btn-action btn-radar" style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 16px; border-radius: 6px; font-weight: 500; font-size: 0.9rem; height: 38px; box-sizing: border-box; border: none; cursor: pointer;"
                                                    data-json='<?= htmlspecialchars($doc['detalhes'], ENT_QUOTES, 'UTF-8') ?>'
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalRadar">
                                                <i class="ph ph-shield-check" style="font-size: 1.1rem;"></i> Metadados
                                            </button>
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

<!-- Modal Radar -->
<div class="modal fade" id="modalRadar" tabindex="-1" aria-labelledby="modalRadarLabel" aria-hidden="true" style="display: none;">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 650px; margin: 1.75rem auto;">
    <div class="modal-content" style="background-color: #ffffff; color: #334155; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);"> 
      <div class="modal-header" style="border-bottom: 1px solid #e2e8f0; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <h5 class="modal-title" id="modalRadarLabel" style="margin: 0; display: flex; align-items: center; gap: 10px; color: #0f172a; font-weight: 600;">
            <div style="background: #eff6ff; color: #3b82f6; padding: 8px; border-radius: 8px; display: flex;"><i class="ph ph-shield-check" style="font-size: 1.4rem;"></i></div> 
            Metadados de Auditoria (Radar)
        </h5>
        <button type="button" class="btn-close-custom" onclick="fecharModal()" style="background: #f1f5f9; border: none; color: #64748b; font-size: 1.2rem; cursor: pointer; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">&times;</button>
      </div>
      <div class="modal-body" style="padding: 1.5rem;">
        <p style="color: #64748b; font-size: 0.95rem; margin-top: 0; margin-bottom: 16px;">Rastreabilidade imutável e ponta a ponta deste documento no ecossistema da MarIA:</p>
        <pre style="background: #f8fafc; padding: 16px; border-radius: 8px; overflow-x: auto; border: 1px solid #e2e8f0; box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.02);"><code id="json_output" style="color: #0f172a; font-family: 'Cascadia Code', 'Fira Code', 'Courier New', Courier, monospace; font-size: 0.9rem; line-height: 1.5;"></code></pre>
      </div>
    </div>
  </div>
</div>
<!-- Overlay do modal -->
<div id="modalOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1040;"></div>

<script>
// Gerenciamento de Modal Customizado (já que não há garantia de carregamento do bootstrap.js)
const modal = document.getElementById('modalRadar');
const overlay = document.getElementById('modalOverlay');

function fecharModal() {
    modal.style.display = 'none';
    modal.style.opacity = '0';
    overlay.style.display = 'none';
    modal.classList.remove('show');
}

document.querySelectorAll('.btn-radar').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault(); // Previne ação extra do bootstrap
        const jsonString = this.getAttribute('data-json');
        try {
            // Formata o JSON bonitinho com espaçamento
            const obj = JSON.parse(jsonString);
            document.getElementById('json_output').textContent = JSON.stringify(obj, null, 2);
        } catch (e) {
            document.getElementById('json_output').textContent = "Log antigo ou formato inválido.";
        }
        
        // Exibe o modal corrigindo a opacidade e forçando centralização absoluta
        modal.classList.add('show');
        modal.style.opacity = '1';
        
        // Sobrepõe qualquer limitação de CSS garantindo o overlay do modal
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100vw';
        modal.style.height = '100vh';
        modal.style.display = 'flex';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        modal.style.zIndex = '1050';
        
        overlay.style.display = 'block';
    });
});
</script>

</body>
</html>
