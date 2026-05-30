<?php
session_start();
if (!isset($_SESSION['admin_logado'])) { header('Location: ../login.php'); exit; }

include '../config.php';
require_once '../models/Usuario.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { die("Erro na conexão: " . $e->getMessage()); }

$usuarioModel = new Usuario($pdo);

// --- AÇÕES CRUD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] == 'salvar') {
        $nome = $_POST['nome'];
        $usuario = $_POST['usuario'];
        $senha = $_POST['senha'];
        $nomeImagem = null;

        if (isset($_FILES['assinatura_foto']) && $_FILES['assinatura_foto']['error'] === 0) {
            $extensao = pathinfo($_FILES['assinatura_foto']['name'], PATHINFO_EXTENSION);
            $nomeImagem = uniqid('ass_', true) . '.' . $extensao;
            move_uploaded_file($_FILES['assinatura_foto']['tmp_name'], '../../avatar/' . $nomeImagem);
        }

        try {
            $usuarioModel->criar($nome, $usuario, $senha, $nomeImagem);
            header("Location: admin_usuarios.php");
            exit;
        } catch (PDOException $e) {
            $erroCadastro = "Erro: Já existe um usuário com este login.";
        }
    }
}

// Excluir
if (isset($_GET['del'])) {
    $usuarioModel->excluir($_GET['del'], $_SESSION['admin_id']);
    header("Location: admin_usuarios.php");
    exit;
}

// Lógica de Filtro
$busca = $_GET['busca'] ?? '';
$usuarios = $usuarioModel->listar($busca);

// Carregar a View
require '../views/admin_usuarios_view.php';
