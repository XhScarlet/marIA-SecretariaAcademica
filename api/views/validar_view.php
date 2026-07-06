<?php
/**
 * View: Validar Autenticidade do Documento
 * 
 * @author Engenheiro de Software Sênior
 * @description Interface de validação ajustada estritamente para o padrão visual corporativo
 * da FATEC Zona Sul (Azul Institucional #003366 e Vermelho Marsala #800020).
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validação de Documento Oficial - FATEC ZS</title>
    
    <style>
        :root {
            /* Cores Institucionais FATEC Zona Sul */
            --fatec-blue: #003366;
            --fatec-blue-light: #004080;
            --fatec-red: #800020; /* Marsala */
            
            --surface-card: rgba(255, 255, 255, 0.98);
            --text-main: #333333;
            --text-muted: #666666;
            
            --success-color: #198754;
            --success-bg: rgba(25, 135, 84, 0.1);
            
            --danger-color: #800020;
            --danger-bg: rgba(128, 0, 32, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            /* Mantendo o exato fundo do index.css */
            background-image: 
                linear-gradient(rgba(0, 51, 102, 0.8), rgba(0, 80, 128, 0.8)),
                url('../../avatar/fateczs.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Container Principal FATEC */
        .fatec-card {
            background: var(--surface-card);
            border-radius: 30px;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.2);
            overflow: hidden;
            animation: slideUpFade 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        /* Header Exato do chat-header da FATEC */
        .header {
            background: linear-gradient(90deg, var(--fatec-blue) 0%, var(--fatec-blue-light) 100%);
            color: white;
            padding: 20px;
            text-align: center;
            border-bottom: 4px solid var(--fatec-red);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 20px;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 13px;
            opacity: 0.85;
        }

        .content-body {
            padding: 40px;
        }

        /* Status Banner */
        .status-banner {
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            margin-bottom: 30px;
            border: 1px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .status-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 15px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            animation: popIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) 0.3s forwards;
            transform: scale(0);
        }

        .status-banner h2 {
            font-size: 20px;
            margin-bottom: 8px;
            font-weight: 600;
            opacity: 0;
            animation: fadeIn 0.4s ease 0.5s forwards;
        }

        .status-banner p {
            font-size: 14px;
            opacity: 0;
            animation: fadeIn 0.4s ease 0.6s forwards;
        }

        /* Temas de Validação */
        .status-success {
            background-color: var(--success-bg);
            border-color: rgba(25, 135, 84, 0.3);
            color: var(--success-color);
        }
        
        .status-success .status-icon {
            background: var(--success-color);
            color: white;
            box-shadow: 0 0 15px rgba(25, 135, 84, 0.4);
        }

        .status-danger {
            background-color: var(--danger-bg);
            border-color: rgba(128, 0, 32, 0.3);
            color: var(--danger-color);
        }

        .status-danger .status-icon {
            background: var(--danger-color);
            color: white;
            box-shadow: 0 0 15px rgba(128, 0, 32, 0.4);
        }

        /* Detalhes do Documento */
        .details-grid {
            display: grid;
            gap: 16px;
            opacity: 0;
            animation: fadeIn 0.5s ease 0.7s forwards;
        }

        .detail-item {
            background: #f7f9fc;
            border: 1px solid #eaeaea;
            border-radius: 12px;
            padding: 16px;
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .detail-item:hover {
            transform: translateY(-2px);
            background: #f0f4f8;
        }

        .detail-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 4px;
            font-weight: 600;
        }

        .detail-value {
            font-size: 15px;
            font-weight: 500;
            color: var(--text-main);
            word-break: break-all;
        }

        .hash-code {
            font-family: monospace;
            color: var(--fatec-blue);
            background: rgba(0, 51, 102, 0.1);
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 13px;
        }

        /* Botão / Ação */
        .action-btn {
            display: block;
            width: 100%;
            padding: 16px;
            margin-top: 30px;
            background: var(--fatec-red);
            border: none;
            border-radius: 25px;
            color: white;
            text-align: center;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            cursor: pointer;
            opacity: 0;
            animation: fadeIn 0.5s ease 0.8s forwards;
            box-shadow: 0 3px 8px rgba(128, 0, 32, 0.3);
        }

        .action-btn:hover {
          
            transform: scale(1.03);
            box-shadow: 0 5px 15px rgba(128, 0, 32, 0.4);
        }

        svg {
            width: 32px;
            height: 32px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2.5;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* Animações */
        @keyframes slideUpFade {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes popIn {
            0% { transform: scale(0); opacity: 0; }
            60% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Mobile Adjustments */
        @media (max-width: 480px) {
            .content-body { padding: 24px; }
            .status-banner { padding: 20px 15px; }
            .status-icon { width: 56px; height: 56px; }
            svg { width: 28px; height: 28px; }
        }
    </style>
</head>
<body>

    <div class="fatec-card">
        <div class="header">
            <h1>Secretaria Digital - FATEC</h1>
            <p>Validação de Autenticidade de Documentos</p>
        </div>
        
        <div class="content-body">
            <?php if ($protocolo_encontrado): ?>
                
                <!-- Documento Autêntico -->
                <div class="status-banner status-success">
                    <div class="status-icon">
                        <svg viewBox="0 0 24 24">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                    <h2>Documento Autêntico</h2>
                    <p>Este documento foi validado eletronicamente e possui amparo legal em nossa base de dados.</p>
                </div>

                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Nome do Titular</div>
                        <div class="detail-value"><?= htmlspecialchars($dados_documento['nome_aluno']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Registro Acadêmico (RA)</div>
                        <div class="detail-value"><?= htmlspecialchars($dados_documento['ra']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Tipo do Documento</div>
                        <div class="detail-value"><?= htmlspecialchars($dados_documento['tipo_servico']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Gerado Em</div>
                        <div class="detail-value">
                            <?php 
                                $data = new DateTime($dados_documento['criado_em'] ?? 'now'); 
                                echo $data->format('d/m/Y \à\s H:i'); 
                            ?>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Chave de Autenticidade</div>
                        <div class="detail-value hash-code"><?= htmlspecialchars($chave_autenticidade) ?></div>
                    </div>
                </div>

            <?php else: ?>

                <!-- Documento Inválido / Falso -->
                <div class="status-banner status-danger">
                    <div class="status-icon">
                        <svg viewBox="0 0 24 24">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </div>
                    <h2>Documento Não Encontrado</h2>
                    <p>O protocolo informado não existe em nossa base de dados. Este documento pode ser falsificado.</p>
                </div>
                
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Status da Busca</div>
                        <div class="detail-value">Falha na correlação de chaves. O hash ou ID pesquisado é inválido.</div>
                    </div>
                </div>

            <?php endif; ?>

            <a href="index.html" class="action-btn">Voltar para a Secretaria (Mari)</a>
        </div>
    </div>

</body>
</html>
