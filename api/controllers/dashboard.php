<?php
session_start();
// Proteção de acesso
if (!isset($_SESSION['admin_logado'])) { header('Location: ../login.php'); exit; }

// Ajuste o caminho do config.php para a arquitetura da pasta controllers
include '../config.php'; 

// --- 1. CONSULTAS SQL PARA OS KPIs ---

// Total de Alunos
$stmtAlunos = $pdo->query("SELECT COUNT(*) as total FROM alunos");
$totalAlunos = $stmtAlunos->fetch()['total'];

// Total de Protocolos (Pendentes vs Concluídos)
$stmtPendentes = $pdo->query("SELECT COUNT(*) as total FROM protocolos WHERE status = 'Pendente'");
$totalPendentes = $stmtPendentes->fetch()['total'];

$stmtConcluidos = $pdo->query("SELECT COUNT(*) as total FROM protocolos WHERE status = 'Concluído'");
$totalConcluidos = $stmtConcluidos->fetch()['total'];

$totalProtocolos = $totalPendentes + $totalConcluidos;

// Total de Disciplinas/Professores
$stmtDisciplinas = $pdo->query("SELECT COUNT(*) as total FROM professores_disciplinas");
$totalDisciplinas = $stmtDisciplinas->fetch()['total'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Gerencial - MarIA</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .dashboard-header h1 { color: #002050; margin: 0; }
        
        /* Menu Superior Padrão do Sistema */
        .admin-nav { background: #002050; padding: 15px 30px; color: white; display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .admin-nav a { color: white; text-decoration: none; margin-left: 20px; opacity: 0.8; }
        .admin-nav a:hover, .admin-nav a.active { opacity: 1; font-weight: bold; }
        
        .container { padding: 20px 40px; }

        /* Estilos dos Cartões de KPI */
        .kpi-container { display: flex; gap: 20px; margin-bottom: 40px; flex-wrap: wrap; }
        .kpi-card { 
            background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
            flex: 1; min-width: 200px; display: flex; align-items: center; border-left: 5px solid #0056b3;
        }
        .kpi-card.pendentes { border-left-color: #ffc107; }
        .kpi-card.concluidos { border-left-color: #28a745; }
        
        .kpi-icon { font-size: 40px; margin-right: 20px; }
        .kpi-info h3 { margin: 0; font-size: 14px; color: #666; text-transform: uppercase; }
        .kpi-info p { margin: 5px 0 0 0; font-size: 28px; font-weight: bold; color: #333; }
        
        /* Container do Gráfico */
        .chart-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); max-width: 600px; margin: 0 auto; }
    </style>
</head>
<body>

    <!-- Incluindo o Menu Padrão do Painel Admin -->
    <div style="background: #002050; color: white;">
        <?php include '../header.php'; ?>
    </div>

    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1>📊 Visão Executiva da Secretaria</h1>
                <p style="color: #666; margin-top: 5px;">Dados atualizados em tempo real do banco de dados</p>
            </div>
        </div>

        <div class="kpi-container">
            <div class="kpi-card">
                <div class="kpi-icon">🎓</div>
                <div class="kpi-info">
                    <h3>Alunos Matriculados</h3>
                    <p><?= $totalAlunos ?></p>
                </div>
            </div>

            <div class="kpi-card pendentes">
                <div class="kpi-icon">⏳</div>
                <div class="kpi-info">
                    <h3>Protocolos Pendentes</h3>
                    <p><?= $totalPendentes ?></p>
                </div>
            </div>

            <div class="kpi-card concluidos">
                <div class="kpi-icon">✅</div>
                <div class="kpi-info">
                    <h3>Protocolos Concluídos</h3>
                    <p><?= $totalConcluidos ?></p>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon">📚</div>
                <div class="kpi-info">
                    <h3>Disciplinas Cadastradas</h3>
                    <p><?= $totalDisciplinas ?></p>
                </div>
            </div>
        </div>

        <div class="chart-container">
            <h3 style="text-align: center; color: #333; margin-bottom: 20px;">Desempenho de Atendimento</h3>
            <canvas id="graficoProtocolos"></canvas>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('graficoProtocolos').getContext('2d');
        const graficoProtocolos = new Chart(ctx, {
            type: 'doughnut', // Gráfico de rosca super elegante
            data: {
                labels: ['Pendentes', 'Concluídos'],
                datasets: [{
                    data: [<?= $totalPendentes ?>, <?= $totalConcluidos ?>],
                    backgroundColor: ['#ffc107', '#28a745'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>

</body>
</html>
