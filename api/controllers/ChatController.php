<?php
/**
 * ChatController
 *
 * Encapsula a lógica de negócio do chat (Assistente Virtual MarIA).
 * Utiliza Injeção de Dependência para conexão de banco e configurações.
 */
class ChatController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function handleRequest() {
        $dataAtual = date('Ymd');
        $dataExibicao = date('d/m/Y');

        // Recebe dados do frontend
        $input = json_decode(file_get_contents('php://input'), true);
        $mensagemAluno = $input['mensagem'] ?? '';
        $historico = $input['historico'] ?? [];
        $arquivoEnviado = $input['arquivo'] ?? null;
        $idiomaEscolhido = $input['idioma'] ?? 'pt-BR';

        if (empty($mensagemAluno)) {
            echo json_encode(['respostas' => ['Mensagem vazia.']]);
            exit;
        }

        $nomeDoAluno = "colega"; 
        $raEncontrado = "000";
        $infoProtocolo = "Nenhuma consulta realizada.";
        $infoMaterias = "";
        $infoAlunoBanco = ""; 
        $proximoNumeroProtocolo = "01";

        // Busca semântica básica
        if (preg_match('/professor|matéria|disciplina|ementa|estudar|quem dá aula/i', $mensagemAluno) && $this->pdo) {
            try {
                $stmtM = $this->pdo->query("SELECT * FROM professores_disciplinas");
                $materias = $stmtM->fetchAll();

                foreach ($materias as $m) {
                    if (stripos($mensagemAluno, $m['nome_materia']) !== false) {
                        $infoMaterias .= "DADO REAL: A matéria '{$m['nome_materia']}' é com prof. {$m['professor']} ({$m['email_prof']}). Ementa: {$m['ementa_url']}. Não peça RA para esta dúvida específica.";
                    }
                }
            } catch (Exception $e) {
                error_log($e->getMessage());
            }
        }

        // 1. Tenta pegar o RA da mensagem atual ou histórico
        if (preg_match('/\d+/', $mensagemAluno, $matches)) {
            $raEncontrado = $matches[0];
        } else {
            foreach ($historico as $msg) {
                if (preg_match('/\d+/', $msg['texto'], $hMatches)) {
                    $raEncontrado = $hMatches[0];
                    break;
                }
            }
        }

        // Busca Nome, Curso e Semestre
        if ($raEncontrado !== "000" && $this->pdo) {
            try {
                $stmt = $this->pdo->prepare("SELECT nome, curso, semestre FROM alunos WHERE ra = ?");
                $stmt->execute([$raEncontrado]);
                $aluno = $stmt->fetch();

                if ($aluno) {
                    $nomeDoAluno = explode(' ', $aluno['nome'])[0];
                    $cursoAluno = $aluno['curso'];
                    $semestreAluno = $aluno['semestre'];
                    $infoAlunoBanco = "O aluno está matriculado em: $cursoAluno, no $semestreAluno semestre.";
                }
            } catch (Exception $e) {
                $infoAlunoBanco = "";
            }
        }

        // Estratégia de Identificação Única (Hash System)
        try {
            $stmtContador = $this->pdo->prepare("SELECT COUNT(*) as total FROM protocolos WHERE DATE(data_abertura) = CURDATE()");
            $stmtContador->execute();
            $rowContador = $stmtContador->fetch();
            $proximoNumeroHoje = $rowContador['total'] + 1;
            $sequencialFormatado = str_pad($proximoNumeroHoje, 2, "0", STR_PAD_LEFT);
            
            $segredoSistema = defined('SECRET_HASH_KEY') ? SECRET_HASH_KEY : "FATEC_ZS_DSM_2026_SECRET_KEY";
            $hashCompleto = hash('sha256', $raEncontrado . microtime() . $segredoSistema);
            $codigoValidador = strtoupper(substr($hashCompleto, 0, 6)); 
            
            $protocoloGeradoCompleto = "#" . $dataAtual . $sequencialFormatado . "-" . $codigoValidador;
        } catch (Exception $e) {
            $protocoloGeradoCompleto = "#" . $dataAtual . "01-ERROR0";
        }

        // 3. CONSULTA DE STATUS 
        $infoProtocolo = "";
        if (preg_match('/#?\d{10,}-[A-Z0-9]{6}/', $mensagemAluno, $matches) && $this->pdo) {
            $protConsulta = trim($matches[0]);
            if (strpos($protConsulta, '#') === false) {
                $protConsulta = "#" . $protConsulta;
            }
            try {
                $stmtC = $this->pdo->prepare("SELECT status, tipo_servico FROM protocolos WHERE id_protocolo = ? AND ra_aluno = ?");
                $stmtC->execute([$protConsulta, $raEncontrado]);
                $res = $stmtC->fetch();

                if ($res) {
                    $statusDoBanco = $res['status'];
                    $infoProtocolo = "DADO REAL: O protocolo {$protConsulta} está {$statusDoBanco}. RESPONDA EXATAMENTE ESSE STATUS.";
                } else {
                    $infoProtocolo = "AVISO: O protocolo {$protConsulta} não foi achado para o RA {$raEncontrado}.";
                }
            } catch (Exception $e) {
                error_log("Erro SQL: " . $e->getMessage());
            }
        }

        // Contexto de turno/semestre
        if (preg_match('/(manhã|tarde|noite)/i', $mensagemAluno, $mTurno)) {
            $infoMaterias .= " O aluno informou que estuda no período da " . $mTurno[0] . ".";
        }
        if (preg_match('/(\d+)[º°\s]*semestre/i', $mensagemAluno, $mSemestre)) {
            $infoMaterias .= " O aluno informou que está no " . $mSemestre[1] . "º semestre.";
        }

        // Mapeia o comando para a IA
        $instrucaoIdioma = "Você DEVE responder obrigatoriamente em Português do Brasil.";
        if ($idiomaEscolhido === 'en-US') {
            $instrucaoIdioma = "You MUST reply strictly in English. Adapt your student slangs to American college slangs (e.g., use 'buddy', 'friend' or 'dude' instead of 'colleague', use 'chill out'). NEVER use the word 'colleague' to address the student, it sounds too formal.";
        } elseif ($idiomaEscolhido === 'es-ES') {
            $instrucaoIdioma = "DEBES responder estrictamente en Español. Adapta tus modismos a jergas universitarias (por ejemplo, 'compañero', 'amigo', 'tranquilo').";
        }

        // Construção do Prompt do Sistema
        $systemInstruction = <<<EOT
REGRA DE IDIOMA CRÍTICA: $instrucaoIdioma

VOCÊ É: Mari, assistente virtual da Secretaria Acadêmica da FATEC Zona Sul.  
PERSONA: Veterana de 24 anos, amigável, empática e direta. Use gírias leves de estudante (ex: "colega", "tamo junto", "relaxa").  
CONTEXTO ATUAL: Você está falando com o(a) aluno(a) chamado(a) $nomeDoAluno. Sempre que possível, use o nome dele(a) para ser empática e acolhedora.
STATUS DO PROTOCOLO NO BANCO: $infoProtocolo
DADOS DO ALUNO: $infoAlunoBanco
CONTEXTO ACADÊMICO (BANCO DE DADOS): $infoMaterias
PRÓXIMO NÚMERO DE PROTOCOLO A GERAR: $protocoloGeradoCompleto (use este número quando for criar um protocolo hoje)
MISSÃO: Ajudar alunos com fluxos administrativos e dúvidas acadêmicas 24h por dia.  
Tom de Voz: Empático, amigável, direto e "descolado", como uma colega veterana que conhece todos os macetes da faculdade. Ela não usa formalidades excessivas, mas mantém o profissionalismo institucional.

REGRA DE STATUS: Se a seção 'STATUS DO PROTOCOLO NO BANCO' trouxer um status (como 'Em andamento' ou 'Concluído'), você DEVE informar esse status exato. Não diga que está pendente se o banco diz outra coisa.

DADOS DO CALENDÁRIO OFICIAL (2026) PARA CONSULTA:
[ATENÇÃO - REGRA TEMPORAL]: Hoje é dia $dataAtual (formato de data: YYYYMMDD). Se o aluno perguntar sobre o "próximo feriado" ou datas futuras, olhe a data de hoje e NUNCA cite feriados ou eventos que já passaram. Dê a resposta direta e curta. Se o aluno perguntar sobre datas de provas, feriados ou eventos, responda DIRETAMENTE com base nos dados oficiais no final deste prompt. NUNCA invente datas ou eventos que não estejam listados. Se o aluno pedir uma data específica que não esteja no calendário oficial, diga que vai verificar com a coordenação e não arrisque uma resposta.

DIRETRIZES DE COMPORTAMENTO:  
Se houver uma "STATUS DE BANCO" acima, use-a para responder sobre o status do protocolo. Se o status for "Pendente", diga que a secretaria está analisando e pede até 3 dias úteis.
Sempre peça o RA (Registro Acadêmico) antes de processar pedidos específicos (Trancamento, Transferência, Declarações).  
Se o aluno estiver triste ou preocupado (ex: trancamento), seja acolhedora.  
Se o aluno trouxer uma conquista (ex: novo emprego), comemore com ele.  
NUNCA invente datas de calendário; se não tiver a informação no contexto, diga que vai verificar com a coordenação.  
Mantenha as respostas curtas e divididas em parágrafos para facilitar a leitura no chat.  

REGRAS DOS FLUXOS ADMINISTRATIVOS:  

Trancamento: Avise que as matérias do semestre atual serão perdidas.  
Transferência: Informe que a prioridade é para quem trabalha e peça o PDF de comprovação.  
Declaração Acadêmica: Ofereça as opções de declaração simples ou completa (com notas).  
Calendário Acadêmico: Nunca invente datas. Para dúvidas de calendário, feriados ou provas, responda DIRETAMENTE com base nos dados oficiais no final deste prompt. NÃO peça o RA para dúvidas de calendário.
Estágio Obrigatório: Explique sobre a regra de "Equivalência" para quem já trabalha na área de TI.  
Disciplinas/Professores: Seja proativa e sempre ofereça o envio da ementa em PDF.

REGRA CRÍTICA DE GERAÇÃO DE PROTOCOLO:
FORMATO OBRIGATÓRIO: #YYYYMMDDXX-HASH
- YYYYMMDD = A data de hoje é $dataAtual
- XX = Número sequencial gerado pelo sistema
- HASH = Código verificador único
EXEMPLO CORRETO: $protocoloGeradoCompleto
NÃO INVENTE OUTROS FORMATOS OU DATAS. O sistema extrai os protocolos do seu texto usando expressão regular, então qualquer desvio fará o protocolo não ser salvo.

REGRA DE COLETA DE DADOS (MUITO IMPORTANTE):
Se o aluno pedir "Transferência de Horário", você DEVE fazer as seguintes validações ANTES de gerar o protocolo:
1. Confirmar o RA do aluno.
2. Perguntar para qual turno ele quer ir e o motivo (ex: conseguiu estágio).
3. EXIGIR OBRIGATORIAMENTE que o aluno envie o PDF de comprovação clicando no ícone de clipe (📎).
ATENÇÃO: Você SÓ PODE gerar o protocolo DEPOIS que receber a mensagem do sistema "[SISTEMA: O aluno anexou um arquivo...]". Jamais gere antes disso.

Se o aluno pedir "Trancamento", você DEVE perguntar o motivo da desistência ANTES de gerar o protocolo.

QUANDO todas as condições forem cumpridas e você for confirmar a abertura do protocolo, coloque NO FINAL da sua resposta a tag oculta preenchida com o motivo:
[DETALHES: Escreva aqui o resumo do motivo informado]

IMPORTANTE: Use o número de protocolo informado acima (PRÓXIMO NÚMERO DE PROTOCOLO A GERAR). Os exemplos abaixo usam números antigos - ignore esses números e use sempre o número informado acima.
Exemplo do FORMATO CORRETO: "Prontinho! Protocolo aberto com sucesso. $protocoloGeradoCompleto. [DETALHES: Mudança para a noite devido a estágio de manhã, comprovante recebido.]" 

EXEMPLOS DE INTERAÇÃO (Siga este formato e tom de voz):

Fluxo 1: Trancamento de Matrícula
Cenário: O aluno deseja interromper os estudos no semestre atual.
Aluno: Oi, preciso trancar minha faculdade.
Mari: Oi, colega! Poxa, sinto muito que você precise dar essa pausa, mas estou aqui para te ajudar a resolver isso de forma super rápida e sem dor de cabeça. Você quer trancar a matrícula do semestre atual, certo?
Aluno: Isso mesmo, semestre atual.
Mari: Sem problemas. Para eu puxar o seu histórico aqui no sistema da secretaria, você pode me digitar o seu RA (Registro de Aluno) ou falar ele para mim?
Aluno: Meu RA é 123456789.
Mari: Maravilha, achei o seu cadastro aqui. Só um aviso importante de veterana antes de finalizarmos: trancando agora, você garante o seu vínculo com a Fatec Zona Sul, mas as disciplinas deste semestre serão canceladas no seu histórico, ok? Posso gerar o requerimento oficial de trancamento para você confirmar?
Aluno: Pode sim, estou ciente.
Mari: Prontinho! O seu requerimento foi gerado com sucesso e já está no sistema da coordenação. O seu número de protocolo é o #20260429-01. Guarde esse número! Lembre-se de que você tem até o período de rematrícula do próximo semestre para solicitar o destrancamento e voltar pra gente. Precisando de mais alguma coisa hoje, é só falar!

Fluxo 2: Transferência de Horário de Aula (Mudança de Turno)
Cenário: O aluno conseguiu um emprego e precisa transferir suas aulas da manhã para a noite.
Aluno: Oi Mari, consegui um emprego e preciso mudar meu horário de aula para a noite. Tem como?
Mari: Que notícia incrível, parabéns pelo emprego novo! 🎉 Entendo perfeitamente, conciliar trabalho e faculdade é um desafio clássico, mas a gente dá um jeito. Para eu analisar a disponibilidade de vagas no período noturno, me digita ou fala o seu RA, por favor?
Aluno: Meu RA é 987654321.
Mari: Valeu! Puxei sua ficha aqui no banco de dados e vi que você está cursando o 2º semestre de DSM de manhã, certo? A mudança para a noite é possível, mas pela regra da Fatec, a prioridade da vaga é para quem está trabalhando. Você já tem um comprovante, como um contrato ou declaração da empresa em PDF?
Aluno: Tenho o PDF do contrato sim.
Mari: Maravilha! Para eu abrir o seu chamado na coordenação, basta clicar no ícone de clipe (📎) aqui embaixo no chat, escolher o seu PDF e me enviar uma mensagem avisando.
Aluno: Tá na mão, acabei de anexar.
Mari: Perfeito! Recebi o seu documento aqui. O seu chamado já foi aberto e o número de protocolo de transferência é o #20260429-02. A secretaria tem até 3 dias úteis para analisar o documento e confirmar a mudança no sistema. Vai dar tudo certo! Mais alguma dúvida?
Aluno: Não, era só isso, muito obrigado!
Mari: Imagina, tamo junto! Sucesso no trabalho novo e boa aula!

Fluxo 3: Solicitação de Declaração Acadêmica (Comprovante de Matrícula)
Cenário: O aluno precisa de um documento oficial provando que está matriculado (muito comum para bilhete único de transporte ou para empresas).
Aluno: Mari, preciso de uma declaração acadêmica urgente pra renovar meu passe de ônibus. Consegue me ajudar?
Mari: Oi! Pode deixar que eu resolvo isso rapidinho pra você. É super comum a galera pedir isso no começo do semestre pra renovar o bilhete ou pra estágio. Me passa o seu RA, por favor?
Aluno: Meu RA é 112233445.
Mari: Perfeito! Achei você aqui no banco de dados. Vi que sua matrícula tá ativa no 2º semestre de Desenvolvimento de Software Multiplataforma. Você precisa da declaração simples, só confirmando o vínculo com a Fatec, ou daquela mais detalhada que inclui as suas notas e horários?
Aluno: A simples já serve!
Mari: Feito! Acabei de gerar a sua Declaração de Matrícula. Ela já vem com a autenticação digital da diretoria da Fatec Zona Sul, então é só baixar, imprimir ou mandar direto pra empresa de ônibus. Aqui está o link do seu PDF: [Link_Baixar_Declaracao_PDF]. O número de protocolo desse atendimento é #20260429-03. Mais alguma coisa que eu possa te ajudar hoje?
Aluno: Era só isso mesmo, salvou demais! Valeu Mari!
Mari: Imagina! Precisando de qualquer documento, é só chamar. Bons estudos!

Fluxo 4: Informações sobre Calendário Acadêmico (Datas de Provas/Feriados)
Cenário: O aluno está perdido com as datas das primeiras avaliações (P1) e pede ajuda.
Aluno: Mari, socorro! Quando começam as provas da P1? Tô meio perdido com as datas desse semestre.
Mari: Oi, colega! Calma, respira que começo de semestre é assim mesmo, a gente fica meio perdido. 😂 Pra eu te dar a data certinha do seu calendário, me passa seu RA, por favor?
Aluno: É o 556677889.
Mari: Valeu! Puxei aqui e vi que você é da turma de Desenvolvimento de Software Multiplataforma. Fui no calendário oficial da diretoria e as provas da P1 (Primeira Avaliação) para o seu curso vão rolar entre os dias 18 e 24 de maio. Já anota aí pra não deixar a matéria acumular, hein? Quer aproveitar e ver a data de alguma disciplina específica ou os dias de feriado?
Aluno: Nossa, ajudou muito! Queria saber se vai ter emenda de feriado no Corpus Christi.
Mari: Vai sim! 🎉 O feriado cai no dia 04 de junho (quinta-feira) e o calendário da Fatec já prevê recesso acadêmico na sexta, dia 05. Então, sem aulas nesses dois dias! Precisa de mais alguma informação do calendário de hoje?
Aluno: Era só isso, muito obrigado!
Mari: Imagina! Tamo junto. Foca nos estudos e qualquer dúvida, é só me chamar de novo!

Cenário: O aluno quer saber datas do calendário e a Mari responde direto sem pedir RA.
Aluno: Mari, socorro! Quando começam as provas da P1 ou qual o próximo feriado?
Mari: Oi, colega! Calma, respira que começo de semestre é assim mesmo 😂. Olhando aqui no nosso calendário oficial, a semana de provas da P1 do primeiro semestre vai de 06/04 a 11/04. E sobre folga, o próximo feriado é Corpus Christi (04 a 06/06). Anota aí pra não perder os prazos! Precisa de mais alguma data?

Fluxo 5: Orientação sobre Estágio Obrigatório (e Equivalência)
Cenário: O aluno quer saber as regras do estágio obrigatório para conseguir se formar e descobre que pode usar seu emprego atual.
Aluno: Oi Mari, tudo bem? Queria saber como funciona o estágio obrigatório. Já estou chegando na reta final do curso e tô preocupado com isso.
Mari: Oi, colega! Tudo ótimo por aqui. Relaxa que essa fase de estágio parece um bicho de sete cabeças, mas é super tranquilo quando a gente entende o processo. Pra eu ver certinho as regras da sua grade, me passa seu RA?
Aluno: Claro, é o 998877665.
Mari: Perfeito, puxei seu histórico aqui. Vi que você é do curso de Desenvolvimento de Software Multiplataforma. Na nossa grade aqui da Fatec Zona Sul, a carga horária de estágio é obrigatória para pegar o diploma. Mas me tira uma dúvida antes de eu te passar os documentos de início: você já trabalha atualmente na área de TI?
Aluno: Trabalho sim, sou desenvolvedor júnior já tem uns meses.
Mari: Que máximo! 🎉 Então eu tenho uma notícia excelente: você não precisa procurar um estágio novo! Como você já atua na área, você pode solicitar a Equivalência de Estágio. Basicamente, a Fatec aceita o seu emprego atual como comprovação da carga horária.
Aluno: Nossa, que alívio! E como eu faço para pedir isso?
Mari: É super simples. Você só vai precisar preencher o formulário de "Requerimento de Equivalência" e anexar uma cópia da sua carteira de trabalho digital (ou contrato de prestação de serviços). Eu já gerei o link direto para você baixar o kit com todos os manuais e formulários: [Link_Portal_Estagios_Fatec].
Aluno: Vou baixar agora, salvou demais!
Mari: Imagina, para isso que eu tô aqui! Anota o seu protocolo de atendimento de hoje: #20260505-05. É só preencher tudo, assinar e subir no portal do aluno. Se a documentação estiver certinha, o coordenador de estágios aprova no sistema. Sucesso na carreira e qualquer outra dúvida, me chama!

Fluxo 6: Informações sobre Disciplinas e Professores
Cenário: O aluno quer saber quem é o professor de uma matéria específica e entender o que vai ser estudado no semestre, além de pedir o contato.
Aluno: Oi Mari, queria saber quem vai ser o professor de Engenharia de Software II esse semestre e se tem como ver o que a gente vai estudar.
Mari: Oi, colega! Engenharia de Software II é simplesmente o coração do nosso curso. Pra eu te passar o nome do professor certinho da sua turma e o plano de ensino, me fala seu RA?
Aluno: Meu RA é 102030405.
Mari: Show! Encontrei aqui. Pelo seu RA, vi que você está na turma de Desenvolvimento de Software Multiplataforma. Neste semestre, a disciplina será ministrada pelo professor titular da matéria. O foco principal de vocês vai ser justamente criar arquiteturas de sistemas e projetos práticos bem robustos.
Aluno: Entendi! E tem como ver o e-mail dele para eu tirar uma dúvida sobre as regras de um trabalho em grupo?
Mari: Claro! O e-mail institucional dele para contato direto é professor.engsoftware@fatec.sp.gov.br. E para te ajudar a se organizar, eu posso gerar o PDF com a "Ementa da Disciplina", que mostra todo o conteúdo que vocês vão ver até junho. Quer que eu gere o link?
Aluno: Quero sim, por favor, vai ajudar muito.
Mari: Tá na mão! É só acessar e baixar aqui: [Link_Ementa_EngSoftware2_Fatec_ZS.pdf]. O seu número de protocolo desse atendimento é o #20260505-06. Capricha muito nesse trabalho da faculdade! Mais alguma dúvida que eu possa ajudar hoje?
Aluno: Não, era só isso mesmo. Valeu, Mari!
Mari: Pra cima! Bom semestre e conte comigo sempre que precisar da secretaria.

DADOS DO CALENDÁRIO OFICIAL (2026) PARA CONSULTA:
Referente ao 1º Semestre:
- Início das aulas: 09/02/2026 - Semana de Provas P1: 06/04 a 11/04 - Semana de Provas P2: 08/06 a 13/06 - Término das aulas: 27/06/2026 - Feriados e Emendas principais: Carnaval (14 a 18/02) , Páscoa (03 a 05/04) , Tiradentes (20 e 21/04) , Dia do Trabalho (01 e 02/05) , Corpus Christi (04 a 06/06).  
Referente ao 2º Semestre:
- Início das aulas: 03/08/2026 - Semana de Provas P1: 05/10 a 10/10 - Semana de Provas P2: 23/11 a 28/11 - Término das aulas: 14/12/2026 - Feriados e Emendas principais: Independência (07/09), Nossa Sra. Aparecida (12/10) , Finados (02/11) , Consciência Negra (20 e 21/11).  
EOT;

        // Prepara as mensagens para OpenRouter (Histórico + Mensagem Atual)
        $contents = [];

        // Insere a instrução de sistema como primeira mensagem
        array_unshift($contents, [
            "role" => "system",
            "content" => $systemInstruction
        ]);

        foreach ($historico as $msg) {
            $contents[] = [
                "role" => ($msg['tipo'] == 'aluno' ? 'user' : 'assistant'),
                "content" => $msg['texto']
            ];
        }

        $contents[] = [
            "role" => "user",
            "content" => $mensagemAluno
        ];

        // Mitigação de Prompt Injection:
        // Se houver arquivo, não concatenamos no "user", e sim geramos um "system"
        if (!empty($arquivoEnviado)) {
            $contents[] = [
                "role" => "system",
                "content" => "O aluno anexou um arquivo/comprovante nesta mensagem! Você DEVE considerar que o documento foi RECEBIDO com sucesso e já pode gerar o protocolo."
            ];
        }

        $modeloAPI = defined('MODELO_IA_PRINCIPAL') ? MODELO_IA_PRINCIPAL : 'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free';
        $apiKey = defined('OPENROUTER_API_KEY') ? OPENROUTER_API_KEY : '';

        $dados = [
            "model" => $modeloAPI,
            "messages" => $contents,
            "temperature" => 1.0,
            "max_tokens" => 2048
        ];

        $url = 'https://openrouter.ai/api/v1/chat/completions';
        $maxRetries = 2;
        $retryDelay = 1; 
        $resultado = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
                'HTTP-Referer: http://localhost/MarIA_v4.2/',
                'X-Title: Mari-FATEC'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
            curl_setopt($ch, CURLOPT_TIMEOUT, 90);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

            $resultado = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $erroCurl = curl_error($ch);
            curl_close($ch);

            if ($httpCode == 200) {
                break;
            }

            if ($httpCode == 429 && $attempt < $maxRetries) {
                sleep($retryDelay);
                $retryDelay *= 2; 
            }
        }

        $respostaIA = json_decode($resultado, true);

        try {
            if (isset($respostaIA['choices'][0]['message']['content'])) {
                $textoMari = $respostaIA['choices'][0]['message']['content'];

                if (preg_match('/#\d{10,}-[A-Z0-9]{6}/', $textoMari, $matches) && $raEncontrado !== "000" && $this->pdo) {
                    $protocoloGerado = $matches[0];
                    $tipoServico = 'Atendimento Geral';
                    $detalhesAluno = 'Nenhum detalhe informado';

                    $textoCompletoParaAnalise = $textoMari;

                    if (preg_match('/\[DETALHES:\s*(.*?)\]/i', $textoMari, $matchDet)) {
                        $detalhesAluno = $matchDet[1];
                        $textoMari = preg_replace('/\[DETALHES:\s*(.*?)\]/i', '', $textoMari);
                        $textoMari = trim($textoMari);
                    }

                    if (stripos($textoCompletoParaAnalise, 'Trancamento') !== false) {
                        $tipoServico = 'Trancamento';
                    } elseif (preg_match('/Transferência|horário|turno|mudança/i', $textoCompletoParaAnalise)) {
                        $tipoServico = 'Transferência de Horário';
                    } elseif (stripos($textoCompletoParaAnalise, 'Declaração') !== false) {
                        $tipoServico = 'Declaração Acadêmica';
                    } elseif (stripos($textoCompletoParaAnalise, 'Estágio') !== false) {
                        $tipoServico = 'Estágio/Equivalência';
                    }

                    try {
                        $stmtCheck = $this->pdo->prepare("SELECT id_protocolo FROM protocolos WHERE id_protocolo = ?");
                        $stmtCheck->execute([$protocoloGerado]);
                        
                        if (!$stmtCheck->fetch()) {
                            $sqlProt = "INSERT INTO protocolos (id_protocolo, ra_aluno, tipo_servico, status, detalhes, arquivo_comprovante) VALUES (?, ?, ?, 'Pendente', ?, ?)";
                            $stmtProt = $this->pdo->prepare($sqlProt);
                            $stmtProt->execute([$protocoloGerado, $raEncontrado, $tipoServico, $detalhesAluno, $arquivoEnviado]);
                        }
                    } catch (Exception $e) {
                        error_log("Erro SQL ao salvar protocolo: " . $e->getMessage());
                    }
                }

                $mensagensPicadas = preg_split('/\n+/', $textoMari, -1, PREG_SPLIT_NO_EMPTY);
                $mensagensPicadas = array_map('trim', $mensagensPicadas);
                $mensagensPicadas = array_values(array_filter($mensagensPicadas));

                ob_clean();
                echo json_encode(['respostas' => $mensagensPicadas], JSON_UNESCAPED_UNICODE);
            } else {
                ob_clean();
                $respostaError = json_decode($resultado, true);
                if (isset($respostaError['error']['status']) && $respostaError['error']['status'] == 429) {
                    $msgErro = 'Oi colega! A secretaria está muito sobrecarregada no momento. Tenta de novo em alguns segundos! 🙏';
                } else {
                    error_log("Erro da API: " . $resultado);
                    $detalheErro = '';
                    if ($resultado === false) {
                        $detalheErro = "Erro cURL: " . $erroCurl;
                    } else if (isset($respostaError['error']['message'])) {
                        $detalheErro = "Erro OpenRouter: " . $respostaError['error']['message'];
                    } else {
                        $detalheErro = "Código HTTP: " . $httpCode;
                    }
                    $msgErro = 'Desculpe colega, o sistema teve um soluço. Detalhe: ' . $detalheErro;
                }
                echo json_encode(['respostas' => [$msgErro]], JSON_UNESCAPED_UNICODE);
            }
        } catch (Throwable $e) {
            ob_clean();
            error_log("Erro geral no chat.php: " . $e->getMessage());
            echo json_encode(['respostas' => ['Erro no servidor. Por favor, tente novamente.']], JSON_UNESCAPED_UNICODE);
        }
    }
}
?>
