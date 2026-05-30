<?php
session_start();
include 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
} catch (PDOException $e) { die("Erro na conexão: " . $e->getMessage()); }

if (isset($_POST['login'])) {
    $usuarioDigitado = $_POST['user'];
    $senhaDigitada = $_POST['pass'];

    // CORREÇÃO: Puxando da tabela correta: usuarios_secretaria
    $stmt = $pdo->prepare("SELECT id, nome, senha FROM usuarios_secretaria WHERE usuario = ?");
    $stmt->execute([$usuarioDigitado]);
    $usuarioBD = $stmt->fetch();

    // Compara a senha digitada com o Hash salvo no banco
    if ($usuarioBD && password_verify($senhaDigitada, $usuarioBD['senha'])) {
        $_SESSION['admin_id'] = $usuarioBD['id'];
        $_SESSION['admin_nome'] = $usuarioBD['nome'];
        $_SESSION['admin_logado'] = true;
        
        header('Location: controllers/admin_protocolo.php');
        exit;
    } else {
        $erro = "Usuário ou senha incorretos, colega!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - Mari Admin</title>
    <style>
        body { background: #003366; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0;}
        .login-card { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 40px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.2); color: white; text-align: center; width: 300px;}
        input { display: block; width: 100%; margin: 10px 0; padding: 10px; border-radius: 10px; border: none; box-sizing: border-box; }
        button { background: #800020; color: white; border: none; padding: 10px 20px; border-radius: 10px; cursor: pointer; width: 100%; margin-top: 10px; font-weight: bold;}
        button:hover { background: #600018; }
    </style>
</head>
<body>
    <form class="login-card" method="POST">
        <h2>Painel da MarIA</h2>
        <?php if(isset($erro)) echo "<p style='color: #ffcccc;'>$erro</p>"; ?>
        <input type="text" name="user" placeholder="Usuário" required>
        <input type="password" name="pass" placeholder="Senha" required>
        <button type="submit" name="login">Entrar</button>
    </form>
</body>
</html>