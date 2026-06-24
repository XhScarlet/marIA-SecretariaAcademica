<?php
/**
 * View Pública: Validador de Documentos Acadêmicos
 *
 * Responsável por renderizar a interface de consulta de autenticidade documental.
 *
 * FIXME: Acoplamento de Caminhos (Path Coupling)
 * Esta view pressupõe que será incluída a partir da raiz do projeto (validar.php), 
 * o que engessa sua reutilização. Os caminhos de assets ('estilos/index.css' e 
 * 'avatar/...') dependem do diretório do Front Controller de execução.
 * Sugestão Arquitetural: Definir constantes de ambiente (ex: BASE_URL) para 
 * resolução absoluta de rotas, isolando a View da infraestrutura de roteamento.
 *
 * @package    MarIA_Virtual_Assistant
 * @subpackage Views
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Validador de Documentos - FATEC ZS</title>
    <link rel="stylesheet" href="estilos/index.css">
    <style>
        body {
            background-color: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        .validador-container {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .validador-container h2 {
            color: #b00000;
        }
        .input-group {
            margin: 20px 0;
        }
        .input-group input {
            width: 80%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
            text-transform: uppercase;
        }
        .btn-validar {
            padding: 10px 20px;
            background-color: #b00000;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-validar:hover {
            background-color: #800000;
        }
        .resultado {
            margin-top: 30px;
            padding: 20px;
            border-radius: 5px;
            text-align: left;
        }
        .resultado.sucesso {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .resultado.erro {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <!-- Utilizando a mesma classe e estrutura do header da index -->
    <div class="fatec-logo-top" style="background-color: #0d2b56; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-sizing: border-box;">
        <img src="avatar/logo_fatec1.png" alt="Logo FATEC Zona Sul" style="height: 40px;">
        <div>
            <a href="index.html" style="color: white; text-decoration: none; font-weight: bold; border: 1px solid white; padding: 8px 15px; border-radius: 20px; transition: 0.3s;">Voltar ao Chat</a>
        </div>
    </div>

    <div class="validador-container">
        <h2>Validador de Documentos Acadêmicos</h2>
        <p>Digite o código verificador do protocolo para atestar a autenticidade do documento emitido pela secretaria da FATEC Zona Sul.</p>
        
        <form method="POST">
            <div class="input-group">
                <input type="text" name="protocolo" placeholder="Ex: #2026053001-A8F3D2" value="<?= htmlspecialchars($protocoloDigitado ?? '') ?>" required>
            </div>
            <button type="submit" class="btn-validar">Validar Documento</button>
        </form>

        <?php if ($buscou): ?>
            <?php if ($resultado): ?>
                <div class="resultado sucesso">
                    <h3 style="margin-top: 0;">✅ Documento Autêntico!</h3>
                    <p>Este documento pertence a: <strong><?= htmlspecialchars($resultado['nome']) ?></strong></p>
                    <p>Curso: <?= htmlspecialchars($resultado['curso']) ?></p>
                    <p>Tipo de Serviço: <?= htmlspecialchars($resultado['tipo_servico']) ?></p>
                    <p>Status no Sistema: <strong><?= htmlspecialchars($resultado['status']) ?></strong></p>
                    <p>Data de Emissão: <?= date('d/m/Y H:i', strtotime($resultado['data_abertura'])) ?></p>
                </div>
            <?php else: ?>
                <div class="resultado erro">
                    <h3 style="margin-top: 0;">❌ Documento Inválido ou Não Encontrado!</h3>
                    <p>Aviso: O código digitado não consta nos registros oficiais da FATEC Zona Sul ou foi alterado.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
