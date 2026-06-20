<?php
/**
 * Controller: Delegação de Assinaturas
 * 
 * Orquestra as requisições GET (listar) e POST (criar/revogar) referentes 
 * às senhas temporárias de assinatura.
 *
 * @category Controller
 */
session_start();
require_once '../config.php';
require_once '../models/DelegacaoModel.php';
require_once '../use_cases/CriarDelegacaoUseCase.php';
require_once '../use_cases/ListarDelegacoesUseCase.php';

// Segurança: Bloqueia acesso não autenticado
if (!isset($_SESSION['admin_logado'])) {
    header("Location: ../login.php");
    exit;
}

$id_logado = $_SESSION['admin_id'];

// Flash Messages (Post/Redirect/Get pattern) para evitar reenvio no refresh
$senha_gerada = $_SESSION['flash_senha'] ?? null;
$mensagem = $_SESSION['flash_msg'] ?? '';
$tipo_msg = $_SESSION['flash_tipo'] ?? 'info';
unset($_SESSION['flash_senha'], $_SESSION['flash_msg'], $_SESSION['flash_tipo']);

// Injeção de Dependência
$model = new DelegacaoModel($pdo);
$criarUseCase = new CriarDelegacaoUseCase($model);
$listarUseCase = new ListarDelegacoesUseCase($model);

// 1. Processamento de Ações (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'criar') {
        $id_delegado = $_POST['id_usuario_delegado'] ?? '';
        
        // Identifica o tipo de validade escolhido
        $tipo_validade = $_POST['tipo_validade'] ?? 'horas';
        
        $horas_validade = null;
        $data_exata = null;
        
        if ($tipo_validade === 'horas') {
            $horas_validade = (int) ($_POST['horas_validade'] ?? 2);
        } else {
            $data_exata = $_POST['data_exata'] ?? null;
        }
        
        if (!empty($id_delegado)) {
            try {
                $nova_senha = $criarUseCase->execute($id_logado, $id_delegado, $horas_validade, $data_exata);
                $_SESSION['flash_senha'] = $nova_senha;
                $_SESSION['flash_msg'] = "Delegação gerada com sucesso! Guarde a senha abaixo, ela NÃO será exibida novamente.";
                $_SESSION['flash_tipo'] = "success";
            } catch (Exception $e) {
                $_SESSION['flash_msg'] = "Erro: " . $e->getMessage();
                $_SESSION['flash_tipo'] = "error";
            }
        } else {
            $_SESSION['flash_msg'] = "Erro: Preencha todos os campos corretamente.";
            $_SESSION['flash_tipo'] = "error";
        }
        
        // Redirect para evitar reenvio de POST (PRG Pattern)
        header("Location: delegacao_controller.php");
        exit;
    }
    
    if ($acao === 'revogar') {
        $id_delegacao = (int) ($_POST['id_delegacao'] ?? 0);
        
        if ($id_delegacao > 0) {
            require_once '../use_cases/RevogarDelegacaoUseCase.php';
            $revogarUseCase = new RevogarDelegacaoUseCase($model);
            
            try {
                $revogarUseCase->execute($id_delegacao, $id_logado);
                $_SESSION['flash_msg'] = "Delegação revogada com sucesso.";
                $_SESSION['flash_tipo'] = "success";
            } catch (Exception $e) {
                $_SESSION['flash_msg'] = "Erro: " . $e->getMessage();
                $_SESSION['flash_tipo'] = "error";
            }
        }
        
        // Redirect PRG Pattern
        header("Location: delegacao_controller.php");
        exit;
    }
    
    if ($acao === 'ver_senha') {
        $id_delegacao = (int) ($_POST['id_delegacao'] ?? 0);
        $login_fatec = trim($_POST['login_fatec'] ?? '');
        $senha_fatec = $_POST['senha_fatec'] ?? '';
        
        if ($id_delegacao > 0 && !empty($login_fatec) && !empty($senha_fatec)) {
            require_once '../use_cases/VisualizarSenhaUseCase.php';
            $visualizarUseCase = new VisualizarSenhaUseCase($model);
            
            try {
                $senha_revelada = $visualizarUseCase->execute($id_delegacao, $id_logado, $login_fatec, $senha_fatec);
                $_SESSION['flash_msg'] = "Senha revelada com sucesso!";
                $_SESSION['flash_senha'] = $senha_revelada; // Passamos no mesmo padrão de criação
                $_SESSION['flash_tipo'] = "success";
            } catch (Exception $e) {
                $_SESSION['flash_msg'] = "Falha: " . $e->getMessage();
                $_SESSION['flash_tipo'] = "error";
            }
        } else {
            $_SESSION['flash_msg'] = "Erro: Preencha suas credenciais corretamente.";
            $_SESSION['flash_tipo'] = "error";
        }
        
        // Redirect PRG Pattern
        header("Location: delegacao_controller.php");
        exit;
    }
}

// 2. Busca de Dados para a View (GET/POST)
// Puxa a lista de usuários para o dropdown do formulário
$stmtUsuarios = $pdo->prepare("SELECT id, nome FROM usuarios_secretaria WHERE id != ? ORDER BY nome ASC");
$stmtUsuarios->execute([$id_logado]);
$usuarios_disponiveis = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

// Puxa as delegações blindadas do usuário logado
$delegacoes = $listarUseCase->execute($id_logado);

// 3. Renderiza a View
require_once '../views/delegacoes_view.php';
