<?php
session_start();
if (!isset($_SESSION['admin_logado'])) { header('Location: ../login.php'); exit; }
include '../config.php'; 

// --- 1. CONSULTAS SQL PARA OS KPIs ---
$stmtAlunos = $pdo->query("SELECT COUNT(*) as total FROM alunos");
$totalAlunos = $stmtAlunos->fetch()['total'];

$stmtPendentes = $pdo->query("SELECT COUNT(*) as total FROM protocolos WHERE status = 'Pendente'");
$totalPendentes = $stmtPendentes->fetch()['total'];

$stmtAndamento = $pdo->query("SELECT COUNT(*) as total FROM protocolos WHERE status = 'Em andamento'");
$totalAndamento = $stmtAndamento->fetch()['total'];

$stmtConcluidos = $pdo->query("SELECT COUNT(*) as total FROM protocolos WHERE status = 'Concluído'");
$totalConcluidos = $stmtConcluidos->fetch()['total'];

$totalProtocolos = $totalPendentes + $totalAndamento + $totalConcluidos;

$stmtDisciplinas = $pdo->query("SELECT COUNT(*) as total FROM professores_disciplinas");
$totalDisciplinas = $stmtDisciplinas->fetch()['total'];

// --- CONSULTA PARA O GRÁFICO DE PIZZA (TIPOS DE SERVIÇO) ---
$stmtServicos = $pdo->query("SELECT tipo_servico, COUNT(*) as quantidade FROM protocolos GROUP BY tipo_servico");
$servicosData = $stmtServicos->fetchAll(PDO::FETCH_ASSOC);

$labelsServicos = [];
$dadosServicos = [];

// Prepara os dados para o JavaScript entender
foreach ($servicosData as $row) {
    // Se o serviço estiver vazio no banco, damos um nome padrão
    $labelsServicos[] = $row['tipo_servico'] ? $row['tipo_servico'] : 'Outros'; 
    $dadosServicos[] = $row['quantidade'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Gerencial - MarIA</title>
    <!-- Inclui o estilo base do painel admin com o Glassmorphism e Cores Oficiais -->
    <link rel="stylesheet" href="../../estilos/protocolos.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        /* Melhorias visuais específicas para o Dashboard (UI Premium) */
        .dashboard-container {
            padding: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Wrapper para a seção superior (KPIs + Radar) */
        .top-section-wrapper {
            display: flex;
            gap: 25px;
            margin-bottom: 50px;
            align-items: stretch;
            flex-wrap: wrap;
        }

        .kpis-left-column {
            flex: 2; /* Ocupa cerca de 66% da tela */
            display: flex;
            flex-wrap: wrap;
            align-content: flex-start;
            gap: 20px;
            min-width: 320px;
        }

        .radar-right-column {
            flex: 1; /* Ocupa cerca de 33% da tela */
            display: flex;
            min-width: 300px;
        }

        /* Efeito Glassmorphism nos Cartões */
        .kpi-glass-card {
            flex: 1 1 220px;
            max-width: 280px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 20px;
            padding: 25px;
            display: flex;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .kpi-glass-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
        }

        /* Efeito de brilho que passa pelo cartão ao passar o mouse */
        .kpi-glass-card::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 50%; height: 100%;
            background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.4) 50%, rgba(255,255,255,0) 100%);
            transform: skewX(-25deg);
            transition: all 0.7s ease;
        }
        .kpi-glass-card:hover::before {
            left: 200%;
        }

        .kpi-icon-wrapper {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 32px;
            margin-right: 20px;
            box-shadow: inset 0 0 10px rgba(255,255,255,0.5);
            flex-shrink: 0;
        }

        /* Gradientes Premium para os Ícones */
        .icon-alunos { background: linear-gradient(135deg, #003366, #00509e); color: white; }
        .icon-pendentes { background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white; }
        .icon-andamento { background: linear-gradient(135deg, #0284c7, #38bdf8); color: white; }
        .icon-concluidos { background: linear-gradient(135deg, #10b981, #34d399); color: white; }
        .icon-disciplinas { background: linear-gradient(135deg, #800020, #a81c3c); color: white; }

        .kpi-info h3 {
            margin: 0;
            font-size: 0.85rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }

        .kpi-info p {
            margin: 5px 0 0 0;
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1;
        }

        /* Container para alinhar os gráficos lado a lado em Glassmorphism */
        .charts-wrapper { 
            display: flex; 
            gap: 30px; 
            flex-wrap: wrap; 
            justify-content: center; 
            margin-top: 20px;
        }

        .chart-glass-container {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 25px;
            padding: 30px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
            flex: 1;
            min-width: 320px;
            max-width: 550px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .dashboard-title-area {
            margin-bottom: 40px;
            text-align: center;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
            animation: fadeInDown 0.8s ease;
        }
        
        .dashboard-title-area h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0 0 10px 0;
            letter-spacing: -0.5px;
        }
        
        .dashboard-title-area p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <!-- Header Padrão do Painel Admin -->
    <?php include '../header.php'; ?>

    <div class="dashboard-container">
        
        <div class="dashboard-title-area">
            <h2>Visão Executiva da Secretaria</h2>
            <p><i class="ph ph-arrows-clockwise"></i> Dados sincronizados em tempo real com o banco de dados</p>
        </div>

        <div class="top-section-wrapper">
            <!-- COLUNA ESQUERDA: KPIs -->
            <div class="kpis-left-column">
                <div class="kpi-glass-card">
                    <div class="kpi-icon-wrapper icon-alunos">
                        <i class="ph ph-graduation-cap"></i>
                    </div>
                    <div class="kpi-info">
                        <h3>Alunos</h3>
                        <p><?= $totalAlunos ?></p>
                    </div>
                </div>

                <div class="kpi-glass-card">
                    <div class="kpi-icon-wrapper icon-pendentes">
                        <i class="ph ph-hourglass-high"></i>
                    </div>
                    <div class="kpi-info">
                        <h3>Pendentes</h3>
                        <p><?= $totalPendentes ?></p>
                    </div>
                </div>

                <div class="kpi-glass-card">
                    <div class="kpi-icon-wrapper icon-andamento">
                        <i class="ph ph-spinner-gap"></i>
                    </div>
                    <div class="kpi-info">
                        <h3>Em Andamento</h3>
                        <p><?= $totalAndamento ?></p>
                    </div>
                </div>

                <div class="kpi-glass-card">
                    <div class="kpi-icon-wrapper icon-concluidos">
                        <i class="ph ph-check-circle"></i>
                    </div>
                    <div class="kpi-info">
                        <h3>Concluídos</h3>
                        <p><?= $totalConcluidos ?></p>
                    </div>
                </div>

                <div class="kpi-glass-card">
                    <div class="kpi-icon-wrapper icon-disciplinas">
                        <i class="ph ph-books"></i>
                    </div>
                    <div class="kpi-info">
                        <h3>Disciplinas</h3>
                        <p><?= $totalDisciplinas ?></p>
                    </div>
                </div>
            </div>

            <!-- COLUNA DIREITA: Radar de Aprendizado -->
            <div class="radar-right-column">
                <div class="kpi-glass-card" style="flex: 1; flex-direction: column; align-items: stretch; justify-content: flex-start; padding-bottom: 15px; width: 100%; max-width: none; height: 100%;">
                    <div style="display: flex; align-items: center; width: 100%; margin-bottom: 15px;">
                        <div class="kpi-icon-wrapper" style="background: linear-gradient(135deg, #6f42c1, #a78bfa); color: white;">
                            <i class="ph ph-brain"></i>
                        </div>
                        <div class="kpi-info">
                            <h3>Radar de Aprendizado</h3>
                        </div>
                    </div>
                    
                    <input type="text" id="buscaDuvida" placeholder="Buscar dúvida (ex: internet)..." style="width: 100%; padding: 10px 15px; border: 1px solid rgba(255,255,255,0.4); border-radius: 8px; margin-bottom: 15px; box-sizing: border-box; background: rgba(255,255,255,0.7); outline: none; font-family: 'Inter', sans-serif; font-size: 14px; color: #333;">

                    <div id="resultadoDuvidas" style="width: 100%; flex: 1; overflow-y: auto; padding-right: 5px; box-sizing: border-box; display: flex; flex-direction: column; gap: 8px;">
                        <!-- A lista de dúvidas será gerada aqui pelo JS -->
                    </div>
                </div>
            </div>
        </div>

        <div class="charts-wrapper">
            <div class="chart-glass-container">
                <h3 style="text-align: center; color: var(--azul-fatec); margin-bottom: 25px; font-size: 1.4rem; font-weight: 700;">Desempenho de Atendimento</h3>
                <div style="position: relative; height:320px; width:100%; display: flex; justify-content: center;">
                    <canvas id="graficoProtocolos"></canvas>
                </div>
            </div>

            <div class="chart-glass-container">
                <h3 style="text-align: center; color: var(--azul-fatec); margin-bottom: 25px; font-size: 1.4rem; font-weight: 700;">Demandas por Tipo de Serviço</h3>
                <div style="position: relative; height:320px; width:100%; display: flex; justify-content: center;">
                    <canvas id="graficoServicos"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Configuração Global de Tipografia e Cores Premium para o Chart.js
        Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
        Chart.defaults.color = '#64748b';
        
        const ctx = document.getElementById('graficoProtocolos').getContext('2d');
        const graficoProtocolos = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Pendentes', 'Em Andamento', 'Concluídos'],
                datasets: [{
                    data: [<?= $totalPendentes ?>, <?= $totalAndamento ?>, <?= $totalConcluidos ?>],
                    backgroundColor: [
                        'rgba(245, 158, 11, 0.85)', // Amber
                        'rgba(56, 189, 248, 0.85)', // Azul Claro
                        'rgba(16, 185, 129, 0.85)'  // Verde
                    ],
                    borderColor: [
                        '#ffffff',
                        '#ffffff',
                        '#ffffff'
                    ],
                    borderWidth: 4, // Borda branca grossa para destacar as fatias
                    hoverOffset: 12, // Dá um pulo elegante ao passar o mouse
                    hoverBackgroundColor: [
                        '#f59e0b',
                        '#0284c7',
                        '#10b981'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%', // Faz a rosca ficar bem fina e moderna
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: {
                            padding: 25,
                            font: { size: 14, weight: '600' },
                            usePointStyle: true, // Bolinhas na legenda ao invés de quadrados
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 51, 102, 0.95)', // Azul da Fatec
                        titleFont: { size: 13, family: 'Inter', weight: 'normal' },
                        bodyFont: { size: 18, weight: 'bold', family: 'Inter' },
                        padding: 15,
                        cornerRadius: 12,
                        displayColors: true,
                        boxPadding: 8,
                        borderColor: 'rgba(255,255,255,0.2)',
                        borderWidth: 1
                    }
                },
                animation: {
                    animateScale: true, // Cresce a partir do centro
                    animateRotate: true, // Gira ao aparecer
                    duration: 2000, // Animação longa e fluida
                    easing: 'easeOutQuart' // Desaceleração suave
                }
            }
        });

        // --- GRÁFICO 2: TIPOS DE SERVIÇOS (PIZZA) ---
        const ctxServicos = document.getElementById('graficoServicos').getContext('2d');
        const graficoServicos = new Chart(ctxServicos, {
            type: 'pie', // Gráfico de Pizza
            data: {
                labels: <?php echo json_encode($labelsServicos); ?>,
                datasets: [{
                    data: <?php echo json_encode($dadosServicos); ?>,
                    backgroundColor: [
                        'rgba(0, 51, 102, 0.85)',   // Azul FATEC
                        'rgba(128, 0, 32, 0.85)',   // Marsala / Red FATEC
                        'rgba(23, 162, 184, 0.85)', // Ciano
                        'rgba(253, 126, 20, 0.85)', // Laranja
                        'rgba(111, 66, 193, 0.85)', // Roxo
                        'rgba(32, 201, 151, 0.85)'  // Verde Água
                    ],
                    borderColor: '#ffffff',
                    borderWidth: 4,
                    hoverOffset: 12
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: { size: 13, weight: '600' },
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 51, 102, 0.95)',
                        titleFont: { size: 13, family: 'Inter', weight: 'normal' },
                        bodyFont: { size: 16, weight: 'bold', family: 'Inter' },
                        padding: 12,
                        cornerRadius: 10,
                        displayColors: true,
                        boxPadding: 6,
                        borderColor: 'rgba(255,255,255,0.2)',
                        borderWidth: 1
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true,
                    duration: 2000,
                    easing: 'easeOutQuart'
                }
            }
        });

        // --- SISTEMA DE RADAR DE APRENDIZADO (JSON) ---
        const jsonDuvidas = <?php 
            $arq = '../duvidas_nao_respondidas.json'; 
            echo file_exists($arq) ? file_get_contents($arq) : '[]'; 
        ?>;

        const inputBusca = document.getElementById('buscaDuvida');
        const divResultado = document.getElementById('resultadoDuvidas');

        // Função mágica para remover acentos de qualquer string
        function removerAcentos(texto) {
            return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        function renderizarDuvidas(filtro = '') {
            // Normalizamos o texto digitado (sem acento, tudo minúsculo)
            const filtroLimpo = removerAcentos(filtro.toLowerCase());

            const filtradas = jsonDuvidas.filter(d => {
                // Normalizamos a pergunta do banco também
                const perguntaLimpa = removerAcentos(d.pergunta.toLowerCase());
                return perguntaLimpa.includes(filtroLimpo);
            });

            if (filtradas.length === 0) {
                divResultado.innerHTML = '<p style="color: #64748b; font-size: 13px; text-align: center; margin-top: 20px;">Nenhuma dúvida registrada.</p>';
                return;
            }

            let html = `<div style="font-weight: 600; color: #0f172a; font-size: 12px; margin-bottom: 8px;">
                            ${filtro ? `Palavra '${filtro}'` : 'Dúvidas mapeadas'}: 
                            <span style="background: #e11d48; color: white; padding: 2px 6px; border-radius: 10px; font-size: 11px;">${filtradas.length}</span>
                        </div>`;

            [...filtradas].reverse().forEach(d => {
                html += `<div style="background: rgba(255,255,255,0.5); padding: 8px; border-radius: 8px; font-size: 13px; border: 1px solid rgba(255,255,255,0.4);">
                            <span style="color: #64748b; font-size: 11px; display: block; margin-bottom: 2px;">🕒 ${d.data}</span>
                            <strong style="color: #0f172a;">Aluno:</strong> "${d.pergunta}"
                         </div>`;
            });

            divResultado.innerHTML = html;
        }

        renderizarDuvidas();

        inputBusca.addEventListener('input', (e) => {
            renderizarDuvidas(e.target.value);
        });
    </script>

</body>
</html>
