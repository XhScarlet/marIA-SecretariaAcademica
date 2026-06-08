<?php
/**
 * Controller responsável pelo processamento e roteamento do fluxo conversacional da Assistente Virtual.
 * 
 * Centraliza a inteligência de negócios do chat, integrando identificação de intenções (NLP baseada em heurísticas),
 * consulta de contexto no banco de dados e orquestração de chamadas à API de LLM (OpenRouter).
 * 
 * // FIXME: [Arquitetura] A classe atua como um "God Object", violando o Princípio da Responsabilidade Única (SRP).
 * Sugere-se refatorar extraindo a comunicação com a API LLM (Service Layer), o acesso a dados (Repository Pattern) 
 * e o roteamento/validação (Router/Middleware) para classes independentes.
 */
class ChatController {
    /**
     * @var PDO|null Instância da conexão com o banco de dados.
     */
    private $pdo;
    
    /**
     * Injeta a dependência de acesso a dados.
     * 
     * @param PDO|null $pdo Conexão ativa, permitindo fallback robusto caso o banco de dados esteja indisponível.
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Processa a entrada do chat, constrói o contexto base (RAG simplificado) e aciona a LLM.
     * 
     * O fluxo avalia a intenção do usuário combinando a mensagem atual com o histórico,
     * busca dados acadêmicos preliminares e constrói um prompt dinâmico e enxuto para otimizar tokens.
     * 
     * @return void A resposta é enviada via buffer de saída (echo) estruturada em JSON.
     */
    public function handleRequest() {
        $dataAtual = date('Ymd');
        $dataExibicao = date('d/m/Y');

        $input = json_decode(file_get_contents('php://input'), true);
        $mensagemAluno = $input['mensagem'] ?? '';
        $historico = $input['historico'] ?? [];
        $arquivoEnviado = $input['arquivo'] ?? null;
        $idiomaEscolhido = $input['idioma'] ?? 'pt-BR';

        if (empty($mensagemAluno)) {
            echo json_encode(['respostas' => ['Mensagem vazia.']]);
            exit;
        }

        /**
         * Resolução de Contexto (Multi-turn):
         * Agrega o histórico de mensagens do usuário à mensagem atual. Necessário pois entradas curtas
         * (ex: "sim", "12345") carecem de semântica isoladamente, o que causava quebra de fluxo na extração de intenções.
         */
        $textoCompletoParaIntencao = $mensagemAluno;
        if (is_array($historico)) {
            foreach ($historico as $msg) {
                if (isset($msg['tipo']) && $msg['tipo'] === 'aluno') {
                    $textoCompletoParaIntencao .= " " . $msg['texto'];
                }
            }
        }

        $nomeDoAluno = "colega"; 
        $raEncontrado = "000";
        $infoProtocolo = "Nenhuma consulta realizada.";
        $infoMaterias = "";
        $infoAlunoBanco = ""; 
        $proximoNumeroProtocolo = "01";

        /**
         * Heurística inicial para pré-carregamento de contexto de disciplinas.
         * Evita inchar o prompt da LLM com a grade inteira do curso.
         */
        if (preg_match('/professor|mat[ée]ria|disciplina|ementa|estudar|quem d[áa] aula|log[íi]stica/iu', $textoCompletoParaIntencao) && $this->pdo) {
            try {
                $stmtM = $this->pdo->query("SELECT * FROM professores_disciplinas");
                $materias = $stmtM->fetchAll();

                // Função anônima para remover acentos com segurança
                $removerAcentos = function($str) {
                    $acentos = array(
                        'À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A','Å'=>'A','Ç'=>'C','È'=>'E','É'=>'E','Ê'=>'E','Ë'=>'E',
                        'Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I','Ñ'=>'N','Ò'=>'O','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O','Ù'=>'U',
                        'Ú'=>'U','Û'=>'U','Ü'=>'U','Ý'=>'Y','à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a','ç'=>'c',
                        'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','ñ'=>'n','ò'=>'o','ó'=>'o',
                        'ô'=>'o','õ'=>'o','ö'=>'o','ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u','ý'=>'y','ÿ'=>'y'
                    );
                    return strtr($str, $acentos);
                };

                $textoNorm = strtolower($removerAcentos($textoCompletoParaIntencao));

                foreach ($materias as $m) {
                    $materiaNorm = strtolower($removerAcentos($m['nome_materia']));
                    if (strpos($textoNorm, $materiaNorm) !== false) {
                        $infoMaterias .= "DADO REAL: A matéria '{$m['nome_materia']}' é com prof. {$m['professor']} ({$m['email_prof']}). Ementa: {$m['ementa_url']}. Não peça RA para esta dúvida específica.";
                    }
                }
            } catch (Exception $e) {
                error_log($e->getMessage());
            }
        }

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

        /**
         * Hidratação do perfil do aluno. O enriquecimento do contexto humaniza as respostas da LLM 
         * e fortalece a precisão de fluxos acadêmicos que dependem de semestre/curso.
         */
        if ($raEncontrado !== "000" && $this->pdo) {
            try {
                $stmt = $this->pdo->prepare("SELECT nome, curso, semestre, turno FROM alunos WHERE ra = ?");
                $stmt->execute([$raEncontrado]);
                $aluno = $stmt->fetch();

                if ($aluno) {
                    $nomeDoAluno = explode(' ', $aluno['nome'])[0];
                    $cursoAluno = $aluno['curso'];
                    $semestreAluno = $aluno['semestre'];
                    $turnoAluno = $aluno['turno'];
                    $infoAlunoBanco = "O aluno está matriculado em: $cursoAluno, no $semestreAluno semestre, turno da $turnoAluno.";
                }
            } catch (Exception $e) {
                $infoAlunoBanco = "";
            }
        }

        /**
         * Geração descentralizada e determinística de IDs de protocolo.
         * O uso de hash garante exclusividade mesmo sob concorrência e o prefixo sequencial
         * facilita a busca diária humana pela coordenação.
         */
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

        /**
         * State Guard (Regra de Negócio):
         * Impede terminantemente que a LLM invente um número de protocolo se o 
         * fluxo não cumpriu seu pré-requisito principal (identificação do aluno via RA).
         */
        $instrucaoProtocolo = ($raEncontrado !== "000")
            ? "PRÓXIMO NÚMERO DE PROTOCOLO A GERAR: $protocoloGeradoCompleto (use este número OBRIGATORIAMENTE ao concluir o fluxo)"
            : "PRÓXIMO NÚMERO DE PROTOCOLO A GERAR: [BLOQUEADO - VOCÊ NÃO PODE GERAR NENHUM PROTOCOLO ANTES DE EXIGIR O RA DO ALUNO]";

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

        if (preg_match('/(manhã|tarde|noite)/iu', $textoCompletoParaIntencao, $mTurno)) {
            $infoMaterias .= " O aluno informou que estuda no período da " . $mTurno[0] . ".";
        }
        if (preg_match('/(\d+)[º°\s]*semestre/iu', $textoCompletoParaIntencao, $mSemestre)) {
            $infoMaterias .= " O aluno informou que está no " . $mSemestre[1] . "º semestre.";
        }

        $infoHorarios = "";
        if (preg_match('/hor[áa]rio|grade|aulas/iu', $textoCompletoParaIntencao)) {
            $infoHorarios = <<<EOT
DADOS DE HORÁRIOS DE AULAS (GRADES 2026.1) PARA CONSULTA:
Se o aluno pedir o horário das aulas, identifique o curso e forneça OBRIGATORIAMENTE o link do PDF correspondente. Se o aluno pedir o horário mas não tiver falado o curso ainda, pergunte de qual curso ele é.
- DSM (Desenvolvimento de Software Multiplataforma): https://fateczonasul.edu.br/wp-content/uploads/2026/02/HORARIO-DSM-1osem-2026.pdf
- ADS (Análise e Desenvolvimento de Sistemas): https://fateczonasul.edu.br/wp-content/uploads/2026/03/ADS_Grade_2026_2026-1_editais-2.pdf
- Gestão Empresarial: https://fateczonasul.edu.br/wp-content/uploads/2026/03/HORARIO_GESTAO_EMPRESARIAL_2026.1_versaofinal.pdf
- Logística: https://fateczonasul.edu.br/wp-content/uploads/2026/01/HORARIO-LOGISTICA_2026.01_Versao-30.12.2025.pdf
EOT;
        }

        $instrucaoIdioma = "Você DEVE responder obrigatoriamente em Português do Brasil.";
        if ($idiomaEscolhido === 'en-US') {
            $instrucaoIdioma = "You MUST reply strictly in English. Adapt your student slangs to American college slangs (e.g., use 'buddy', 'friend' or 'dude' instead of 'colleague', use 'chill out'). NEVER use the word 'colleague' to address the student, it sounds too formal.";
        } elseif ($idiomaEscolhido === 'es-ES') {
            $instrucaoIdioma = "DEBES responder estrictamente en Español. Adapta tus modismos a jergas universitarias (por ejemplo, 'compañero', 'amigo', 'tranquilo').";
        }

        /**
         * Otimização de Contexto (Injeção Dinâmica)
         * Evita o envio de todo o conhecimento base para a LLM em cada requisição.
         * Segmentar o prompt por intenção reduz significativamente o consumo de tokens,
         * otimiza a latência (Time To First Token) e corta custos com a API.
         * 
         * FIXME: O uso de expressões regulares (preg_match) para identificação de intenções 
         * é frágil e sujeito a falhas em linguagem natural. A melhoria arquitetural seria 
         * utilizar um banco de dados vetorial (Embeddings) para busca semântica (RAG) 
         * ou um modelo classificador de intenções leve pré-processamento.
         * 
         * @var string $exemplosDinamicos Acumula apenas os cenários relevantes para a intenção atual.
         */
        $exemplosDinamicos = "";

        if (preg_match('/trancar|trancamento|desistir/iu', $textoCompletoParaIntencao)) {
            $exemplosDinamicos .= <<<EOT

EXEMPLOS DE INTERAÇÃO - Fluxo 1: Trancamento de Matrícula
Cenário: O aluno deseja interromper os estudos no semestre atual.
Aluno: Oi, preciso trancar minha faculdade.
Mari: Oi, colega! Poxa, sinto muito que você precise dar essa pausa, mas estou aqui para te ajudar a resolver isso de forma super rápida e sem dor de cabeça. Você quer trancar a matrícula do semestre atual, certo?
Aluno: Isso mesmo, semestre atual.
Mari: Sem problemas. Para eu puxar o seu histórico aqui no sistema da secretaria, você pode me digitar o seu RA (Registro de Aluno) ou falar ele para mim?
Aluno: Meu RA é 123456789.
Mari: Maravilha, achei o seu cadastro aqui. Só um aviso importante de veterana antes de finalizarmos: trancando agora, você garante o seu vínculo com a Fatec Zona Sul, mas as disciplinas deste semestre serão canceladas no seu histórico, ok? Posso gerar o requerimento oficial de trancamento para você confirmar?
Aluno: Pode sim, estou ciente.
Mari: Prontinho! O seu requerimento foi gerado com sucesso e já está no sistema da coordenação. O seu número de protocolo é o #20260429-01. Guarde esse número! Lembre-se de que você tem até o período de rematrícula do próximo semestre para solicitar o destrancamento e voltar pra gente. Precisando de mais alguma coisa hoje, é só falar!
EOT;
        }

        if (preg_match('/transfer[êe]ncia|hor[áa]rio|turno|manh[ãa]|noite/iu', $textoCompletoParaIntencao)) {
            $exemplosDinamicos .= <<<EOT

EXEMPLOS DE INTERAÇÃO - Fluxo 2: Transferência de Horário de Aula (Mudança de Turno)
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
EOT;
        }

        if (preg_match('/declara[çc][ãa]o|comprovante|bilhete|passe.*[ôo]nibus/iu', $textoCompletoParaIntencao)) {
            $exemplosDinamicos .= <<<EOT

EXEMPLOS DE INTERAÇÃO - Fluxo 3: Solicitação de Declaração Acadêmica (Comprovante de Matrícula)
Cenário: O aluno precisa de um documento oficial provando que está matriculado.
Aluno: Mari, preciso de uma declaração acadêmica urgente pra renovar meu passe de ônibus. Consegue me ajudar?
Mari: Oi! Pode deixar que eu resolvo isso rapidinho pra você. É super comum a galera pedir isso no começo do semestre pra renovar o bilhete ou pra estágio. Me passa o seu RA, por favor?
Aluno: Meu RA é 112233445.
Mari: Perfeito! Achei você aqui no banco de dados. Vi que sua matrícula tá ativa no 2º semestre de Desenvolvimento de Software Multiplataforma. Você precisa da declaração simples, só confirmando o vínculo com a Fatec, ou daquela mais detalhada que inclui as suas notas e horários?
Aluno: A simples já serve!
Mari: Feito! Acabei de gerar a sua Declaração de Matrícula. Ela já vem com a autenticação digital da diretoria da Fatec Zona Sul, então é só baixar, imprimir ou mandar direto pra empresa de ônibus. Aqui está o link do seu PDF: [Link_Baixar_Declaracao_PDF]. O número de protocolo desse atendimento é #20260429-03. Mais alguma coisa que eu possa te ajudar hoje?
Aluno: Era só isso mesmo, salvou demais! Valeu Mari!
Mari: Imagina! Precisando de qualquer documento, é só chamar. Bons estudos!
EOT;
        }

        if (preg_match('/calend[áa]rio|prova|feriado|f[ée]rias|p1|p2|recesso|emenda/iu', $textoCompletoParaIntencao)) {
            $exemplosDinamicos .= <<<EOT

EXEMPLOS DE INTERAÇÃO - Fluxo 4: Informações sobre Calendário Acadêmico
Cenário: O aluno quer saber datas do calendário e a Mari responde direto sem pedir RA.
Aluno: Mari, socorro! Quando começam as provas da P1 ou qual o próximo feriado?
Mari: Oi, colega! Calma, respira que começo de semestre é assim mesmo 😂. Olhando aqui no nosso calendário oficial, a semana de provas da P1 do primeiro semestre vai de 06/04 a 11/04. E sobre folga, o próximo feriado é Corpus Christi (04 a 06/06). Anota aí pra não perder os prazos! Precisa de mais alguma data?

DADOS DO CALENDÁRIO OFICIAL (2026) PARA CONSULTA:
[ATENÇÃO - REGRA TEMPORAL]: Hoje é dia $dataAtual (formato de data: YYYYMMDD). NUNCA cite feriados ou eventos que já passaram.
Referente ao 1º Semestre:
- Início das aulas: 09/02/2026 - Semana de Provas P1: 06/04 a 11/04 - Semana de Provas P2: 08/06 a 13/06 - Término das aulas: 27/06/2026 - Feriados e Emendas principais: Carnaval (14 a 18/02) , Páscoa (03 a 05/04) , Tiradentes (20 e 21/04) , Dia do Trabalho (01 e 02/05) , Corpus Christi (04 a 06/06).  
Referente ao 2º Semestre:
- Início das aulas: 03/08/2026 - Semana de Provas P1: 05/10 a 10/10 - Semana de Provas P2: 23/11 a 28/11 - Término das aulas: 14/12/2026 - Feriados e Emendas principais: Independência (07/09), Nossa Sra. Aparecida (12/10) , Finados (02/11) , Consciência Negra (20 e 21/11).
- Férias Escolares: As férias de meio de ano (julho) começam no dia 28/06/2026 e vão até 02/08/2026. As férias de fim de ano começam após 14/12/2026.
EOT;
        }

        if (preg_match('/est[áa]gio|equival[êe]ncia/iu', $textoCompletoParaIntencao)) {
            $exemplosDinamicos .= <<<EOT

EXEMPLOS DE INTERAÇÃO - Fluxo 5: Orientação sobre Estágio Obrigatório (e Equivalência)
Cenário: O aluno quer saber as regras do estágio obrigatório e descobre que pode usar seu emprego atual.
Aluno: Oi Mari, tudo bem? Queria saber como funciona o estágio obrigatório.
Mari: Oi, colega! Tudo ótimo por aqui. Relaxa que essa fase de estágio parece um bicho de sete cabeças, mas é super tranquilo quando a gente entende o processo. Pra eu ver certinho as regras da sua grade, me passa seu RA?
Aluno: Claro, é o 998877665.
Mari: Perfeito, puxei seu histórico aqui... Mas me tira uma dúvida antes: você já trabalha atualmente na área de TI?
Aluno: Trabalho sim, sou desenvolvedor júnior já tem uns meses.
Mari: Que máximo! 🎉 Então eu tenho uma notícia excelente: você não precisa procurar um estágio novo! Como você já atua na área, você pode solicitar a Equivalência de Estágio. Basicamente, a Fatec aceita o seu emprego atual como comprovação da carga horária.
Aluno: Nossa, que alívio! E como eu faço para pedir isso?
Mari: É super simples. Você só vai precisar preencher o formulário de "Requerimento de Equivalência" e anexar uma cópia da sua carteira de trabalho digital... [Link_Portal_Estagios_Fatec]. O protocolo do atendimento é: #20260505-05.
EOT;
        }

        if (preg_match('/professor|mat[ée]ria|disciplina|ementa|estudar|quem d[áa] aula/iu', $textoCompletoParaIntencao)) {
            $exemplosDinamicos .= <<<EOT

EXEMPLOS DE INTERAÇÃO - Fluxo 6: Informações sobre Disciplinas e Professores
Cenário: O aluno quer saber quem é o professor de uma matéria específica e pedir contato/ementa.
Aluno: Oi Mari, queria saber quem vai ser o professor de Engenharia de Software II esse semestre...
Mari: Oi, colega!... Pra eu te passar o nome do professor certinho da sua turma e o plano de ensino, me fala seu RA?
Aluno: Meu RA é 102030405.
Mari: Show! Encontrei aqui. Pelo seu RA, vi que você está na turma... O foco principal de vocês vai ser justamente criar arquiteturas...
Aluno: Entendi! E tem como ver o e-mail dele?
Mari: Claro! O e-mail institucional dele é professor.engsoftware@fatec.sp.gov.br. Eu posso gerar o PDF com a "Ementa da Disciplina"... Quer que eu gere o link?
Aluno: Quero sim, por favor.
Mari: Tá na mão! É só acessar e baixar aqui: [Link_Ementa_EngSoftware2_Fatec_ZS.pdf]. O protocolo é o #20260505-06.
EOT;
        }

        if (preg_match('/matr[íi]cula|rematr[íi]cula|siga/iu', $textoCompletoParaIntencao)) {
            $exemplosDinamicos .= <<<EOT

DADOS DE MATRÍCULA E REMATRÍCULA PARA CONSULTA:
- Link Matrícula: https://siga.cps.sp.gov.br/matricula/matricularemota.aspx
- Link Rematrícula: https://siga.cps.sp.gov.br/aluno/login.aspx
- Passos obrigatórios para Rematrícula:
1) Acessar o Menu e clicar em Matrícula online.
2) Abrirá a tela de matrícula, onde constará instruções.
3) Selecionar as disciplinas clicando nas caixas ao lado.
4) Aparecerão somente disciplinas não cursadas ou reprovadas.
5) Conferir matrícula (TE Laranja = reprovada por nota).
6) Clicar em "Matricular Selecionadas" e CONFIRMAR.
7) As disciplinas selecionadas ficarão na cor Amarela.
8) Finalizada a etapa da rematrícula.
- Vídeo Regulamento: https://www.youtube.com/watch?v=qzSwNFgf3uQ
EOT;
        }

        if (preg_match('/endere[çc]o|onde|aonde|localiza[çc][ãa]o|hor[áa]rio.*(secretaria|atendimento|fatec)|telefone|contato/iu', $textoCompletoParaIntencao)) {
            $exemplosDinamicos .= <<<EOT

DADOS INSTITUCIONAIS DA FATEC ZONA SUL:
- Endereço: Rua Frederico Grotte, 322 - Jardim São Luís, São Paulo - SP, 05818-270
- Horário de Atendimento da Secretaria:
  Segunda a Quinta-feira: Das 9h às 11h / 12h às 14h / 18h às 20h
  Sexta-feira: Das 9h às 11h / 12h às 14h / 15h às 17h
EOT;
        }

        /**
         * Compilação final do System Prompt.
         * Aplica a estratégia de "Role-playing" robusta, concatenando estado de negócios (status, RA), 
         * travas de segurança (State Guards) e os cenários dinâmicos filtrados (RAG simplificado).
         */
        $systemInstruction = <<<EOT
REGRA DE IDIOMA CRÍTICA: $instrucaoIdioma

VOCÊ É: Mari, assistente virtual da Secretaria Acadêmica da FATEC Zona Sul.  
PERSONA: Veterana de 24 anos, amigável, empática e direta. Use gírias leves de estudante (ex: "colega", "tamo junto", "relaxa").  
CONTEXTO ATUAL: Você está falando com o(a) aluno(a) chamado(a) $nomeDoAluno. Sempre que possível, use o nome dele(a) para ser empática e acolhedora.
STATUS DO PROTOCOLO NO BANCO: $infoProtocolo
DADOS DO ALUNO: $infoAlunoBanco
CONTEXTO ACADÊMICO (BANCO DE DADOS): $infoMaterias
$instrucaoProtocolo
MISSÃO: Ajudar alunos com fluxos administrativos e dúvidas acadêmicas 24h por dia.  
Tom de Voz: Empático, amigável, direto e "descolado", como uma colega veterana que conhece todos os macetes da faculdade. Ela não usa formalidades excessivas, mas mantém o profissionalismo institucional.

REGRA DE STATUS: Se a seção 'STATUS DO PROTOCOLO NO BANCO' trouxer um status (como 'Em andamento' ou 'Concluído'), você DEVE informar esse status exato. Não diga que está pendente se o banco diz outra coisa.

DIRETRIZES DE COMPORTAMENTO:  
Se houver uma "STATUS DE BANCO" acima, use-a para responder sobre o status do protocolo. Se o status for "Pendente", diga que a secretaria está analisando e pede até 3 dias úteis.
Sempre peça o RA (Registro Acadêmico) antes de processar pedidos específicos (Trancamento, Transferência, Declarações).  
Se o aluno estiver triste ou preocupado (ex: trancamento), seja acolhedora.  
Se o aluno trouxer uma conquista (ex: novo emprego), comemore com ele.  
NUNCA invente datas de calendário; se não tiver a informação no contexto, diga que vai verificar com a coordenação.  
Mantenha as respostas curtas e divididas em parágrafos para facilitar a leitura no chat.  
REGRA ANTI-ALUCINAÇÃO (MUITO IMPORTANTE): Você é apenas a Mari. NUNCA simule a fala do aluno e NUNCA responda por ele. Se você fizer uma pergunta de confirmação (ex: "Está tudo certo para prosseguirmos?"), PARE DE ESCREVER IMEDIATAMENTE e aguarde a resposta real do aluno. Jamais gere o número de protocolo na mesma mensagem em que pede a confirmação.
REGRA DE APRENDIZADO: Se o aluno perguntar algo que não está na sua base de dados, responda educadamente que não sabe e, OBRIGATORIAMENTE, coloque no final da sua resposta a tag oculta [NÃO_SEI].

REGRAS DOS FLUXOS ADMINISTRATIVOS:  
Trancamento: Avise que as matérias do semestre atual serão perdidas.  
Transferência: Informe que a prioridade é para quem trabalha e peça o PDF de comprovação.  
Declaração Acadêmica: Ofereça as opções de declaração simples ou completa (com notas).  
Calendário Acadêmico: Nunca invente datas. Para dúvidas de calendário, feriados ou provas, responda DIRETAMENTE com base nos dados oficiais listados. NÃO peça o RA para dúvidas de calendário.
Estágio Obrigatório: Explique sobre a regra de "Equivalência" para quem já trabalha na área de TI.  
Disciplinas/Professores: Você SÓ PODE fornecer ementas se elas aparecerem no bloco 'CONTEXTO ACADÊMICO (BANCO DE DADOS)' sob a tag 'DADO REAL'. NUNCA invente links de PDFs ou URLs que não estejam lá. Se o aluno pedir uma ementa que não foi listada, diga educadamente que não tem acesso a esse documento no momento. NÃO exija o RA para informações públicas.
EASTER EGG DA BANCA (MUITO IMPORTANTE): Se o aluno mencionar palavras como "banca", "apresentação", "Josenyr", ou "Engenharia de Software II", você DEVE misturar a sua resposta padrão com este Easter Egg: "O prof. Josenyr S. Rosa é super experiente na área e costuma deixar a Engenharia de Software II bem prática, com foco em arquiteturas e projetos reais. Ele dá apoio nas dúvidas e tem boa energia nas aulas. E quer saber de um segredo da secretaria? Ele é incrível! Sem as aulas dele, meu código-fonte nem existiria. Merecemos um 10 nessa apresentação de TCC, né? 😉 Qualquer coisa, chama ele no e-mail: josenyr.rosa@fatec.sp.gov.br"

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

IMPORTANTE: Use o número de protocolo informado acima (PRÓXIMO NÚMERO DE PROTOCOLO A GERAR). 

REGRA DE CONTINGÊNCIA (MUITO IMPORTANTE E CRÍTICA): Se o aluno perguntar sobre VESTIBULAR, ISENÇÃO DE TAXA, INSCRIÇÕES, ou fizer perguntas administrativas que não estão nos seus fluxos suportados (Trancamento, Transferência, Declaração, Calendário, Estágio, Disciplinas): **PROIBIDO PEDIR O RA DO ALUNO**. Lembre-se que candidatos ao vestibular NÃO POSSUEM RA! Diga educadamente que esse caso foge da sua alçada e oriente o usuário a verificar a informação no site oficial (https://fateczonasul.edu.br/) ou a comparecer presencialmente na secretaria nos horários informados em seus dados institucionais. E OBRIGATORIAMENTE coloque no final da sua resposta a tag oculta [NÃO_SEI].

$exemplosDinamicos
$infoHorarios
EOT;

        $contents = [];

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

        /**
         * Mitigação de Prompt Injection em uploads:
         * O conteúdo do arquivo não é injetado como 'user' para evitar que payloads maliciosos
         * presentes no anexo sobrescrevam o System Prompt. Optamos por um 'system message' sintético.
         */
        if (!empty($arquivoEnviado)) {
            /**
             * Validação Tardia de Estado:
             * Mesmo que o frontend permita o upload, se a LLM não tiver o RA,
             * forçamos o sistema a instruí-la a segurar o processo e cobrar o RA.
             */
            $msgArquivo = ($raEncontrado !== "000")
                ? "[SISTEMA: O aluno anexou um documento nesta mensagem! Considere-o recebido com sucesso e prossiga com a geração do protocolo.]"
                : "[SISTEMA: O aluno anexou um documento, porém o RA DELE AINDA NÃO FOI INFORMADO. Agradeça, mas EXIJA o RA antes de gerar qualquer protocolo!]";
                
            $contents[] = [
                "role" => "system",
                "content" => $msgArquivo
            ];
        }

        $modeloAPI = defined('MODELO_IA_PRINCIPAL') ? MODELO_IA_PRINCIPAL : 'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free';
        $apiKey = defined('OPENROUTER_API_KEY') ? OPENROUTER_API_KEY : '';

        $dados = [
            "model" => $modeloAPI,
            "messages" => $contents,
            "temperature" => 1.0,
            /**
             * Alocação elástica de tokens. Modelos com capacidade de "Reasoning" (ex: Nemotron)
             * consomem uma quantidade substancial de tokens processando o "pensamento" invisível
             * antes de gerar o conteúdo real. 2048 causava interrupção prematura do payload JSON.
             */
            "max_tokens" => 8192
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
            /**
             * Validação de resiliência: Algumas falhas de rede com modelos pesados interrompem a 
             * conexão TCP antes do fechamento do payload, invalidando todo o JSON de resposta.
             */
            if ($respostaIA === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Falha fatal na integridade da resposta da LLM: Payload JSON truncado (possível timeout ou limite de max_tokens estourado no backbone).");
            }

            if (isset($respostaIA['choices'][0]['message']['content'])) {
                $textoMari = $respostaIA['choices'][0]['message']['content'];

                // --- SISTEMA DE LOG DE DÚVIDAS (JSON) ---
                if (preg_match('/\[NÃO_SEI\]/i', $textoMari)) {
                    // 1. Apaga a tag do texto para o aluno não ver
                    $textoMari = preg_replace('/\[NÃO_SEI\]/i', '', $textoMari); 
                    $textoMari = trim($textoMari);

                    // 2. Define o caminho do arquivo JSON (na pasta api)
                    $arquivoJson = __DIR__ . '/../duvidas_nao_respondidas.json';
                    
                    // 3. Lê as dúvidas antigas (se o arquivo existir)
                    $duvidasAtual = file_exists($arquivoJson) ? json_decode(file_get_contents($arquivoJson), true) : [];

                    // 4. Adiciona a nova dúvida com a data e o que o aluno digitou
                    $duvidasAtual[] = [
                        'data' => date('d/m/Y H:i'),
                        'pergunta' => $mensagemAluno
                    ];

                    // 5. Salva tudo de volta no arquivo JSON
                    file_put_contents($arquivoJson, json_encode($duvidasAtual, JSON_PRETTY_PRINT));
                }
                // ----------------------------------------

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
                        $detalheErro = "Código HTTP: " . $httpCode . " | RAW: " . substr(strval($resultado), 0, 500);
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
