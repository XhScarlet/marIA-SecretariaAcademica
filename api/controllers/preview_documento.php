<?php
session_start();
include '../config.php';

$id_protocolo = $_GET['id'] ?? '';
// Recebe o tipo de documento, ou usa 'I' como padrão
$tipo_documento = $_GET['tipo'] ?? 'I'; 

$stmt = $pdo->prepare("SELECT p.*, a.nome, a.curso, a.semestre, a.turno FROM protocolos p JOIN alunos a ON p.ra_aluno = a.ra WHERE p.id_protocolo = ?");
$stmt->execute([$id_protocolo]);
$dados = $stmt->fetch();

if (!$dados) die("Protocolo não encontrado!");

// MOTOR DE TEMPLATES (O "Cérebro" dos Documentos)
$titulo = "";
$texto_editavel = "";

switch ($tipo_documento) {
    case 'I':
        $titulo = "ATESTADO/CERTIDÃO DIVERSA";
        $texto_editavel = "Atestamos para os devidos fins (Prazo de emissão: 07 dias) que o(a) aluno(a) {$dados['nome']}, RA {$dados['ra_aluno']}, encontra-se regularmente matriculado(a) no {$dados['semestre']}º Semestre do curso de {$dados['curso']}, turno da {$dados['turno']}.";
        break;
    case 'II':
        $titulo = "ASSINATURA DE ACORDO DE COOPERAÇÃO (AC)";
        $texto_editavel = "A Secretaria Acadêmica valida o Acordo de Cooperação (AC) referente ao(à) aluno(a) {$dados['nome']}, RA {$dados['ra_aluno']}, do curso de {$dados['curso']}. (Prazo regulamentar de 15 dias cumprido).";
        break;
    case 'III':
        $titulo = "ASSINATURA TCE (TERMO DE COMPROMISSO DE ESTÁGIO)";
        $texto_editavel = "A Secretaria Acadêmica confirma a validação e assinatura do Termo de Compromisso de Estágio (TCE) do(a) aluno(a) {$dados['nome']}, RA {$dados['ra_aluno']}, do curso de {$dados['curso']}. (Prazo regulamentar de 15 dias cumprido).";
        break;
    case 'IV':
        $titulo = "SOLICITAÇÃO DE EQUIVALÊNCIA DE ESTÁGIO";
        $texto_editavel = "Declaramos o recebimento e deferimento da análise de Equivalência de Estágio do(a) aluno(a) {$dados['nome']}, RA {$dados['ra_aluno']}, regularmente matriculado(a) no curso de {$dados['curso']}. (Prazo de análise: 15 dias).";
        break;
    case 'V':
        $titulo = "APROVEITAMENTO DE ESTUDOS / EXAME DE PROFICIÊNCIA";
        $texto_editavel = "Certificamos a aprovação da solicitação de Aproveitamento de Estudos/Exame de Proficiência requerida pelo(a) aluno(a) {$dados['nome']}, RA {$dados['ra_aluno']}, do curso de {$dados['curso']}. (Prazo de emissão: 15 dias).";
        break;
    case 'VI':
        $titulo = "ATESTADO DE CONCLUSÃO";
        $texto_editavel = "Declaramos que o(a) aluno(a) {$dados['nome']}, RA {$dados['ra_aluno']}, concluiu com êxito todos os requisitos acadêmicos do curso de {$dados['curso']}. Este atestado (prazo 07 dias) possui validade legal até a emissão oficial do Diploma.";
        break;
    case 'VII':
        $titulo = "ATESTADO DE VAGA PARA TRANSFERÊNCIA";
        $texto_editavel = "Atestamos a existência de vaga e o deferimento do pedido de transferência para o(a) aluno(a) {$dados['nome']}, RA {$dados['ra_aluno']}, no curso de {$dados['curso']}. (Prazo de emissão: 07 dias).";
        break;
    case 'VIII':
        $titulo = "CARTEIRA DE IDENTIFICAÇÃO ESCOLAR";
        $texto_editavel = "Requerimento para emissão da Carteira de Identificação Escolar do(a) aluno(a) {$dados['nome']}, RA {$dados['ra_aluno']}, do curso de {$dados['curso']}. (Prazo estimado para confecção: 03 meses).";
        break;
    case 'IX':
        $titulo = "CERTIFICADO DE CONCLUSÃO";
        $texto_editavel = "Certificamos que o(a) aluno(a) {$dados['nome']}, portador(a) do RA {$dados['ra_aluno']}, concluiu o curso de {$dados['curso']} nesta instituição. (Prazo de emissão do certificado formal: 03 meses).";
        break;
    case 'X':
        $titulo = "CADASTRO PROGRAMA MSDNAA (MICROSOFT)";
        $texto_editavel = "Atestamos o vínculo do(a) aluno(a) {$dados['nome']}, RA {$dados['ra_aluno']}, do curso de {$dados['curso']}, para liberação de acesso ao Programa MSDNAA da Microsoft. (Prazo de processamento: 01 mês).";
        break;
    case 'XI':
        $titulo = "DIPLOMA (2ª VIA)";
        $texto_editavel = "Requerimento de emissão de 2ª via de Diploma deferido para o(a) aluno(a) {$dados['nome']}, RA {$dados['ra_aluno']}, egresso(a) do curso de {$dados['curso']}. (Prazo estimado de expedição e registro: 12 meses).";
        break;
    case 'XII':
        $titulo = "EXTRATO ACADÊMICO (SEMESTRAL)";
        $texto_editavel = "Extrato acadêmico gratuito emitido referente ao semestre letivo do(a) aluno(a) {$dados['nome']}, RA {$dados['ra_aluno']}, do curso de {$dados['curso']}. Documento para simples conferência.";
        break;
    case 'XIII':
        $titulo = "EXTRATO (REVISÃO DE NOTAS/FALTAS)";
        $texto_editavel = "Extrato oficial com a revisão de notas e faltas do(a) aluno(a) {$dados['nome']}, RA {$dados['ra_aluno']}, do curso de {$dados['curso']}. (Prazo de emissão: 07 dias).";
        break;
    case 'XIV':
        $titulo = "EMENTAS DISCIPLARES";
        $texto_editavel = "Encaminhamento das ementas das disciplinas cursadas pelo(a) aluno(a) {$dados['nome']}, RA {$dados['ra_aluno']}, no curso de {$dados['curso']}. (Prazo de separação e emissão: 15 dias).";
        break;
    case 'XV':
        $titulo = "GUIA DE TRANSFERÊNCIA";
        $texto_editavel = "Guia oficial de transferência expedida a pedido do(a) aluno(a) {$dados['nome']}, RA {$dados['ra_aluno']}, matriculado(a) no curso de {$dados['curso']}. (Prazo de emissão: 07 dias).";
        break;
    case 'XVI':
        $titulo = "HISTÓRICO ESCOLAR";
        $texto_editavel = "Apresentamos o Histórico Escolar atualizado do(a) aluno(a) {$dados['nome']}, RA {$dados['ra_aluno']}, regularmente matriculado(a) no curso de {$dados['curso']}. (Prazo de emissão: 07 dias).";
        break;
    default:
        $titulo = "ATESTADO DE MATRÍCULA";
        $texto_editavel = "Atestamos para os devidos fins que o(a) aluno(a) {$dados['nome']}, RA {$dados['ra_aluno']}, encontra-se matriculado(a).";
        break;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gerador de Documentos</title>
    <link rel="stylesheet" href="../../estilos/usuarios.css">
    <style>
        body { background-color: #f1f5f9; margin: 0; padding: 0; }
        .preview-container { display: flex; flex-direction: column; align-items: center; padding: 40px 20px; }
        .controles { background: #fff; padding: 25px; border-radius: 12px; margin-bottom: 30px; width: 21cm; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); box-sizing: border-box; }
        .folha-a4 { background: white; width: 21cm; min-height: 29.7cm; padding: 2.5cm; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); box-sizing: border-box; border-radius: 4px; }
        .btn-gerar { background: #10b981; color: white; padding: 16px; border: none; border-radius: 8px; font-size: 18px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 30px; box-shadow: 0 4px 6px rgba(16,185,129,0.2); transition: all 0.3s; }
        .btn-gerar:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 6px 8px rgba(16,185,129,0.3); }
        textarea { width: 100%; height: 180px; font-family: 'Segoe UI', Arial, sans-serif; font-size: 16px; padding: 15px; border: 2px dashed #cbd5e1; border-radius: 8px; line-height: 1.6; box-sizing: border-box; background: #f8fafc; outline: none; transition: border-color 0.3s; color: #334155; }
        textarea:focus { border-color: #3b82f6; background: #fff; }
        .hint { font-size: 13px; color: #64748b; font-weight: 500; display: block; margin-bottom: 8px; }

        /* Estilos do Modal de Assinatura */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); justify-content: center; align-items: center; z-index: 1000; }
        .modal-box { background: white; padding: 40px; border-radius: 16px; width: 360px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
        .modal-input { width: 100%; padding: 14px; margin: 10px 0; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 16px; outline: none; transition: border-color 0.3s; }
        .modal-input:focus { border-color: #3b82f6; }
        .btn-confirmar { background: #3b82f6; color: white; padding: 14px; border: none; border-radius: 8px; cursor: pointer; width: 100%; font-size: 16px; font-weight: 600; margin-top: 15px; transition: background 0.3s; }
        .btn-confirmar:hover { background: #2563eb; }
        .btn-cancelar { background: #f1f5f9; color: #64748b; padding: 14px; border: none; border-radius: 8px; cursor: pointer; width: 100%; font-size: 16px; font-weight: 600; margin-top: 10px; transition: background 0.3s; }
        .btn-cancelar:hover { background: #e2e8f0; }
    </style>
    <script>
        // Função para recarregar a página mudando o documento
        function mudarDocumento() {
            let tipo = document.getElementById('seletor_tipo').value;
            window.location.href = `preview_documento.php?id=<?= urlencode($id_protocolo) ?>&tipo=` + tipo;
        }
    </script>
</head>
<body>

<?php include '../header.php'; ?>

<div class="preview-container">
    <div style="width: 21cm; margin-bottom: 15px; display: flex; justify-content: flex-start;">
        <a href="admin_protocolo.php" style="background: #94a3b8; color: white; padding: 10px 15px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; transition: background 0.3s;">
            <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i> Voltar para Protocolos
        </a>
    </div>
    <div class="controles">
        <label style="font-weight: 600; font-size: 18px; color: #1e293b; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-file-lines" style="font-size: 24px; color: var(--azul-fatec, #0056b3);"></i> Selecione o Modelo de Documento
        </label>
        <div style="margin-top: 15px;">
    <select id="seletor_tipo" onchange="mudarDocumento()" style="padding: 10px; width: 100%; font-size: 16px; border: 1px solid #ccc; border-radius: 5px;">
        <option value="I" <?= $tipo_documento == 'I' ? 'selected' : '' ?>>I. Atestados e Certidões diversas (07 dias)</option>
        <option value="II" <?= $tipo_documento == 'II' ? 'selected' : '' ?>>II. Assinatura AC (Acordo de Cooperação) (15 dias)</option>
        <option value="III" <?= $tipo_documento == 'III' ? 'selected' : '' ?>>III. Assinatura TCE (Estágio) (15 dias)</option>
        <option value="IV" <?= $tipo_documento == 'IV' ? 'selected' : '' ?>>IV. Equivalência de Estágio (15 dias)</option>
        <option value="V" <?= $tipo_documento == 'V' ? 'selected' : '' ?>>V. Aproveitamento de Estudos/Proficiência (15 dias)</option>
        <option value="VI" <?= $tipo_documento == 'VI' ? 'selected' : '' ?>>VI. Atestado de Conclusão (07 dias)</option>
        <option value="VII" <?= $tipo_documento == 'VII' ? 'selected' : '' ?>>VII. Atestado de Vaga - Transferência (07 dias)</option>
        <option value="VIII" <?= $tipo_documento == 'VIII' ? 'selected' : '' ?>>VIII. Carteira de Identificação Escolar (03 meses)</option>
        <option value="IX" <?= $tipo_documento == 'IX' ? 'selected' : '' ?>>IX. Certificado de Conclusão (03 meses)</option>
        <option value="X" <?= $tipo_documento == 'X' ? 'selected' : '' ?>>X. Cadastro Programa MSDNAA (01 mês)</option>
        <option value="XI" <?= $tipo_documento == 'XI' ? 'selected' : '' ?>>XI. Diploma (2ª via) (12 meses)</option>
        <option value="XII" <?= $tipo_documento == 'XII' ? 'selected' : '' ?>>XII. Extrato gratuito (Semestral)</option>
        <option value="XIII" <?= $tipo_documento == 'XIII' ? 'selected' : '' ?>>XIII. Extrato - Revisão notas/faltas (07 dias)</option>
        <option value="XIV" <?= $tipo_documento == 'XIV' ? 'selected' : '' ?>>XIV. Ementas (15 dias)</option>
        <option value="XV" <?= $tipo_documento == 'XV' ? 'selected' : '' ?>>XV. Guia de Transferência (07 dias)</option>
        <option value="XVI" <?= $tipo_documento == 'XVI' ? 'selected' : '' ?>>XVI. Histórico Escolar (07 dias)</option>
    </select>
        </div>
    </div>

<div class="folha-a4">
    <div style="text-align: center; margin-bottom: 40px; border-bottom: 2px solid #b30000; padding-bottom: 10px;">
        <h2 style="color: #b30000; font-family: 'Times New Roman', serif;">FATEC ZONA SUL</h2>
        <h3 style="text-decoration: underline; font-family: 'Times New Roman', serif;"><?= $titulo ?></h3>
    </div>

    <form id="formDocumento" action="gerar_pdf.php" method="POST" target="_blank">
        <input type="hidden" name="id_protocolo" value="<?= htmlspecialchars($id_protocolo) ?>">
        <input type="hidden" name="titulo_pdf" value="<?= $titulo ?>">
        <input type="hidden" name="ra" value="<?= htmlspecialchars($dados['ra_aluno']) ?>">
        
        <input type="hidden" name="login_assinatura" id="formLogin">
        <input type="hidden" name="senha_assinatura" id="formSenha">
        
        <span class="hint"><i class="fa-solid fa-pen" style="margin-right: 5px;"></i> Edite o texto do documento abaixo se houver alguma observação específica:</span>
        <textarea name="corpo_texto"><?= $texto_editavel ?></textarea>
        
        <p style="text-align: right; margin-top: 50px; font-family: 'Times New Roman', serif; font-size: 16px;">
            São Paulo, <?= date('d/m/Y') ?>.
        </p>

        <button type="button" class="btn-gerar" onclick="abrirModal()"><i class="fa-solid fa-lock" style="margin-right: 5px;"></i> Assinar Eletronicamente e Gerar PDF Oficial</button>
    </form>
</div>
</div> <!-- Fechando preview-container -->

<div class="modal-overlay" id="modalAssinatura">
    <div class="modal-box">
        <h3 style="color: #1e293b; margin-top: 0; font-size: 22px;"><i class="fa-solid fa-file-signature" style="margin-right: 8px;"></i> Assinatura Digital</h3>
        <p style="font-size: 14px; color: #64748b; line-height: 1.5; margin-bottom: 25px;">Insira suas credenciais corporativas para aplicar seu carimbo criptográfico neste documento.</p>
        
        <input type="text" id="modalUser" class="modal-input" placeholder="Seu Login da Fatec">
        <input type="password" id="modalPass" class="modal-input" placeholder="Sua Senha">
        
        <button class="btn-confirmar" onclick="confirmarAssinatura()">Validar e Assinar</button>
        <button class="btn-cancelar" onclick="fecharModal()">Cancelar</button>
    </div>
</div>

<script>
    function abrirModal() {
        document.getElementById('modalAssinatura').style.display = 'flex';
    }

    function fecharModal() {
        document.getElementById('modalAssinatura').style.display = 'none';
        document.getElementById('modalPass').value = ''; 
    }

    function confirmarAssinatura() {
        const user = document.getElementById('modalUser').value;
        const pass = document.getElementById('modalPass').value;

        if(!user || !pass) {
            alert("Atenção: Credenciais incompletas.");
            return;
        }

        document.getElementById('formLogin').value = user;
        document.getElementById('formSenha').value = pass;

        document.getElementById('formDocumento').submit();

        fecharModal();
    }
</script>

</body>
</html>
