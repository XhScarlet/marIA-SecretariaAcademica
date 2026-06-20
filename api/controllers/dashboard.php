<?php
/**
 * Controller Gerencial do Dashboard.
 *
 * Atua coordenando a extração de dados analíticos via Model e a exibição 
 * desses dados delegando para a camada de View. Isso resolve o débito técnico 
 * anterior onde o SQL e o HTML estavam acoplados no mesmo arquivo.
 *
 * @category   Controller
 */

session_start();
if (!isset($_SESSION['admin_logado'])) { 
    header('Location: ../login.php'); 
    exit; 
}

require_once '../config.php'; 
require_once '../models/DashboardModel.php';

// Inicializa o Model injetando a dependência (PDO)
$dashboardModel = new DashboardModel($pdo);

// Busca de dados
$totalAlunos = $dashboardModel->getTotalAlunos();
$protocolos = $dashboardModel->getTotaisProtocolos();

// Variáveis extraídas para compatibilidade com a view antiga sem quebrar variáveis
$totalPendentes = $protocolos['pendentes'];
$totalAndamento = $protocolos['andamento'];
$totalConcluidos = $protocolos['concluidos'];
$totalProtocolos = $protocolos['total'];

$totalDisciplinas = $dashboardModel->getTotalDisciplinas();

$graficoServicos = $dashboardModel->getDadosServicosParaGrafico();
$labelsServicos = $graficoServicos['labels'];
$dadosServicos = $graficoServicos['dados'];

// Renderiza a View
require_once '../views/dashboard_view.php';
