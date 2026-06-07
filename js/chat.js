/**
 * @fileoverview Core JavaScript da Interface da Mari (Secretaria Digital).
 * @description Este script gerencia o ciclo de vida do chat, integrando APIs de Web Speech (STT/TTS),
 * manipulação do DOM e comunicação assíncrona (AJAX/Fetch) com o backend.
 */
const chatBox = document.getElementById('chatBox');
const userInput = document.getElementById('userInput');
const sendBtn = document.getElementById('sendBtn');
const attachBtn = document.getElementById('attachBtn');
const fileInput = document.getElementById('fileInput');

let chatContexto = []; // Array global para memória
let arquivoPendente = null; // Guarda o nome do arquivo subido
let idiomaAtual = 'pt-BR'; // Idioma padrão global

// --- DARK MODE LOGIC ---
const body = document.body;
const savedTheme = localStorage.getItem('theme');
const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

// Aplica o tema logo no início
if (savedTheme === 'dark' || (!savedTheme && systemPrefersDark)) {
    body.classList.add('dark-mode');
}

document.addEventListener('DOMContentLoaded', () => {
    // Configura o botão de tema
    const toggleThemeBtn = document.getElementById('toggleTheme');
    if (body.classList.contains('dark-mode')) {
        toggleThemeBtn.innerHTML = '<i class="ph ph-sun"></i>';
    } else {
        toggleThemeBtn.innerHTML = '<i class="ph ph-moon"></i>';
    }
    toggleThemeBtn.addEventListener('click', () => {
        body.classList.toggle('dark-mode');
        if (body.classList.contains('dark-mode')) {
            toggleThemeBtn.innerHTML = '<i class="ph ph-sun"></i>';
            localStorage.setItem('theme', 'dark');
        } else {
            toggleThemeBtn.innerHTML = '<i class="ph ph-moon"></i>';
            localStorage.setItem('theme', 'light');
        }
    });

    document.querySelectorAll('.lang-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            // Remove ativo de todos e coloca no clicado
            document.querySelectorAll('.lang-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            idiomaAtual = btn.getAttribute('data-lang');
            
            // Altera o placeholder do input para o idioma certo
            const placeholders = {
                'pt-BR': 'Digite sua mensagem aqui...',
                'en-US': 'Type your message here...',
                'es-ES': 'Escribe tu mensaje aquí...'
            };
            userInput.placeholder = placeholders[idiomaAtual];

            // Atualiza a mensagem de boas vindas se ela ainda estiver na tela original
            const msgBoasVindas = document.getElementById('msgBoasVindas');
            if (msgBoasVindas && chatContexto.length === 0) {
                if (idiomaAtual === 'en-US') {
                    msgBoasVindas.innerHTML = "Hey buddy! I'm Mari, the secretary assistant. Type your Student ID (RA) and let me know how I can help today!";
                } else if (idiomaAtual === 'es-ES') {
                    msgBoasVindas.innerHTML = "¡Hola, compañero! Soy Mari, la asistente de secretaría. ¡Escribe tu RA y dime cómo puedo ayudarte hoy!";
                } else {
                    msgBoasVindas.innerHTML = "Oi, colega! Sou a Mari, assistente da secretaria. Digite seu RA e me diga como posso ajudar hoje!";
                }
            }
        });
    });
});

// Quando clicar no clipe, abre a janela de escolha de arquivo
attachBtn.addEventListener('click', () => fileInput.click());

// Quando o aluno escolher o arquivo, faz o upload automático via AJAX
fileInput.addEventListener('change', async () => {
    if (fileInput.files.length === 0) return;

    const formData = new FormData();
    formData.append('comprovante', fileInput.files[0]);

    userInput.placeholder = "Enviando documento...";

    try {
        const response = await fetch('api/upload.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.sucesso) {
            arquivoPendente = data.nome_arquivo; // Salva o nome para enviar junto com o protocolo
            addMessage(`📎 Documento anexado com sucesso: ${fileInput.files[0].name}`, 'aluno');
            
            // AUTO-ENVIAR A MENSAGEM PARA A MARI NÃO FICAR ESPERANDO
            userInput.value = "Pronto, acabei de anexar o arquivo!";
            sendMessage();
        } else {
            alert(data.erro);
        }
    } catch (error) {
        console.error("Erro no upload:", error);
        alert("Erro ao enviar arquivo.");
    }
    fileInput.value = ''; // Limpa o input file
});


/**
 * Adiciona uma mensagem (balão de texto) de forma dinâmica ao chat visual.
 * A função aplica uma renderização básica de Markdown (conversão de ** para tags <strong>).
 * 
 * Agora utiliza DOMPurify para mitigação de vulnerabilidade XSS (Cross-Site Scripting).
 *
 * @param {string} text - O conteúdo da mensagem em texto/markdown.
 * @param {string} sender - O identificador do remetente ('aluno' | 'mari').
 */
function addMessage(text, sender) {
    const textoFormatado = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

    const msgDiv = document.createElement('div');
    msgDiv.classList.add('message');
    msgDiv.classList.add(sender === 'aluno' ? 'msg-aluno' : 'msg-mari');

    // Sanitiza o conteúdo HTML antes de injetar no DOM para prevenir execução de scripts maliciosos.
    msgDiv.innerHTML = DOMPurify.sanitize(textoFormatado);

    chatBox.appendChild(msgDiv);
    chatBox.scrollTop = chatBox.scrollHeight; // Rola a tela para baixo
}

//FUNÇÃO DE VOZ PARA ACESSIBILIDADE DE PESSOAS COM DEFICIÊNCIA VISUAL
let vozAtiva = true; // Controle global do som
const toggleVoiceBtn = document.getElementById('toggleVoice');

// Alterna o estado da voz ao clicar no ícone
toggleVoiceBtn.addEventListener('click', () => {
    vozAtiva = !vozAtiva;
    toggleVoiceBtn.innerHTML = vozAtiva ? '<i class="ph ph-speaker-high"></i>' : '<i class="ph ph-speaker-slash"></i>';
    toggleVoiceBtn.classList.toggle('muted', !vozAtiva);
    console.log('Som:', vozAtiva ? 'ATIVO' : 'MUTADO'); // Debug

    // Cancela qualquer fala em andamento se o usuário mutar
    if (!vozAtiva) window.speechSynthesis.cancel();
});

//FUNÃO DE ATALHO DE VOZ: 3 CLIQUES RÁPIDOS EM QUALQUER LUGAR DA TELA
//ACESSIBILIDADE PARA PESSOAS COM DEFICIÊNCIA VISUAL QUE TÊM DIFICULDADE DE ACHAR O BOTÃO PEQUENO
let contadorCliques = 0;
let timerCliques;

// Monitora cliques em qualquer lugar do documento
document.addEventListener('click', (e) => {
    // Evita disparar se o aluno estiver clicando no botão de enviar ou no input
    if (e.target === userInput || e.target === sendBtn) return;

    contadorCliques++;

    // LIMPA A SELEÇÃO ACIDENTAL
    // Isso remove aquele "azul" do texto imediatamente
    window.getSelection().removeAllRanges();

    // Reinicia o contador se o intervalo entre cliques for muito longo
    clearTimeout(timerCliques);
    timerCliques = setTimeout(() => {
        contadorCliques = 0;
    }, 500); // 500ms de janela para os 3 cliques

    if (contadorCliques === 3) {
        contadorCliques = 0; // Reseta
        toggleVoiceBtn.click(); // Aciona a função de som que já criamos

        // Feedback sonoro para o aluno saber que o comando funcionou
        const feedback = new SpeechSynthesisUtterance(vozAtiva ? "Voz ligada" : "Voz desligada");
        feedback.lang = 'pt-BR';
        window.speechSynthesis.speak(feedback);
    }
});

// ── VOZES NEURAIS DO EDGE (Azure TTS embutido, 100% grátis) ────────────
// O navegador carrega as vozes de forma assíncrona; precisamos esperar
let vozesDisponiveis = [];
window.speechSynthesis.onvoiceschanged = () => {
    vozesDisponiveis = window.speechSynthesis.getVoices();
    console.log("🗣️ Vozes carregadas:", vozesDisponiveis.length);
};

// Garante que as vozes sejam carregadas mesmo em navegadores que não disparam o evento
if (window.speechSynthesis.getVoices().length > 0) {
    vozesDisponiveis = window.speechSynthesis.getVoices();
}

function falarResposta(texto) {
    // Remove os asteriscos do negrito e todos os emojis para a voz não descrevê-los
    const textoLimpo = texto
        .replace(/\*\*/g, '')
        .replace(/[\u{1F000}-\u{1FFFF}]/gu, '')   // Emojis
        .replace(/[\u{2600}-\u{27BF}]/gu, '')      // Símbolos
        .replace(/[\u{FE00}-\u{FEFF}]/gu, '')      // Seletores de variação
        .replace(/\s{2,}/g, ' ')                   // Remove espaços duplos
        .trim();

    // Só executa se a voz estiver ativa
    if (!vozAtiva) return;

    const sintetizador = window.speechSynthesis;
    const expressao = new SpeechSynthesisUtterance(textoLimpo);

    // 1. Define o idioma base
    expressao.lang = idiomaAtual;

    // 2. BUSCA AS VOZES INSTALADAS NO NAVEGADOR
    const vozes = sintetizador.getVoices();
    let vozSelecionada = null;
    
    // Lista negra global de vozes masculinas conhecidas (PT, EN, ES)
    const vozMasculina = (v) => /Daniel|Ricardo|Felipe|António|Rui|David|Mark|Guy|Christopher|Eric|Pablo|Diego|Jorge|Alonso/i.test(v.name);
    // Lista branca global de vozes femininas conhecidas e de alta qualidade
    const vozFemininaPremium = (v) => /Francisca|Thalita|Zira|Jenny|Aria|Michelle|Hazel|Helena|Laura|Sabina|Elvira|Monica/i.test(v.name);

    if (idiomaAtual === 'pt-BR') {
        // ORDEM DE PREFERÊNCIA DE VOZES FEMININAS BR (FORÇA A FRANCISCA)
        vozSelecionada =
            vozes.find(v => /Francisca/i.test(v.name)) || // 1ª PRIORIDADE ABSOLUTA: Força a Francisca
            vozes.find(v => v.lang.replace('_', '-').toLowerCase() === 'pt-br' && vozFemininaPremium(v)) ||
            vozes.find(v => v.name.includes('Google português do Brasil')) ||
            vozes.find(v => v.lang.replace('_', '-').toLowerCase() === 'pt-br' && !vozMasculina(v)) ||
            vozes.find(v => v.lang.toLowerCase().startsWith('pt'));
    } else {
        // 3. FILTRA A VOZ CORRETA PARA O IDIOMA ATUAL (en-US, es-ES)
        const prefixoIdioma = idiomaAtual.split('-')[0].toLowerCase();
        
        // Tenta achar uma voz feminina premium primeiro, depois tenta qualquer voz que NÃO seja masculina
        vozSelecionada = 
            vozes.find(voz => voz.lang.toLowerCase().startsWith(prefixoIdioma) && vozFemininaPremium(voz)) ||
            vozes.find(voz => voz.lang.toLowerCase().startsWith(prefixoIdioma) && !vozMasculina(voz)) ||
            vozes.find(voz => voz.lang.toLowerCase().startsWith(prefixoIdioma));
    }

    // Se achou uma voz nativa daquele idioma, força o navegador a usar!
    if (vozSelecionada) {
        expressao.voice = vozSelecionada;
        console.log("Voz alterada com sucesso para (Mulher garantida):", vozSelecionada.name);
    } else {
        console.warn("Voz nativa não encontrada para " + idiomaAtual + ". Usando padrão do sistema.");
    }

    // Configurações de tom e velocidade (sotaques nativos ficam melhores em velocidade 1.0)
    expressao.rate = idiomaAtual === 'pt-BR' ? 1.1 : 1.0; 
    expressao.pitch = 1.1; // Ajuste para soar um pouco mais jovem/feminino caso a voz seja neutra

    // CONTROLE DE VÍDEO DA MARI (Lip Sync Visual)
    const mariStatic = document.getElementById('mariStatic');
    const mariVideo = document.getElementById('mariVideo');

    expressao.onstart = function() {
        if (mariStatic && mariVideo) {
            mariStatic.style.opacity = '0';
            mariVideo.style.opacity = '1';
            mariVideo.play().catch(e => console.log('Autoplay bloqueado:', e));
        }
    };

    expressao.onend = function() {
        if (mariStatic && mariVideo) {
            mariVideo.pause();
            mariVideo.style.opacity = '0';
            mariStatic.style.opacity = '1';
        }
    };

    expressao.onerror = function() {
        if (mariStatic && mariVideo) {
            mariVideo.pause();
            mariVideo.style.opacity = '0';
            mariStatic.style.opacity = '1';
        }
    };

    sintetizador.speak(expressao);
}

//MICROFONE PARA RECONHECIMENTO DE VOZ - SEGURE PARA FALAR, SOLTE PARA PARAR
// Funciona em QUALQUER lugar da tela (acessibilidade para pessoas com deficiência visual)

const btnMic = document.getElementById('micBtn');

// Verifica se o navegador suporta o reconhecimento de voz nativo
const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

if (SpeechRecognition) {
    const recognition = new SpeechRecognition();
    recognition.lang = 'pt-BR';
    recognition.continuous = true;
    recognition.interimResults = true;

    let gravando = false;
    let mousePressionado = false; // Nova flag para controle total
    let timerPressao = null;
    let micAtivo = false; // Flag para saber se o mic realmente abriu
    let pararPendente = false; // Flag para abortar se soltar o botão antes do mic abrir
    const TEMPO_PRESS = 150;

    function emitirBip(frequencia, duracao) {
        try {
            const context = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = context.createOscillator();
            const gain = context.createGain();
            oscillator.connect(gain);
            gain.connect(context.destination);
            oscillator.frequency.value = frequencia;
            gain.gain.value = 0.05;
            oscillator.start();
            setTimeout(() => oscillator.stop(), duracao);
        } catch(e) {}
    }

    function aoPresionar(e) {
        if (e.target === sendBtn || e.target === userInput) return;
        if (mousePressionado) return;

        mousePressionado = true;
        timerPressao = setTimeout(() => {
            gravando = true;
            pararPendente = false;
            emitirBip(660, 50);
            try {
                recognition.start();
            } catch(err) {}
        }, TEMPO_PRESS);
    }

    function aoSoltar() {
        if (timerPressao) {
            clearTimeout(timerPressao);
            timerPressao = null;
        }
        
        if (mousePressionado && gravando) {
            if (micAtivo) {
                emitirBip(440, 50);
                try {
                    // O stop() no modo continuous espera o fim da frase. O abort() corta o mic na hora!
                    recognition.abort(); 
                } catch(err) {}
            } else {
                // Soltou o botão, mas o microfone ainda nem tinha ligado (Race Condition)
                pararPendente = true;
            }
            
            // UI Update imediato para não dar a impressão de estar gravando
            btnMic.style.transform = 'scale(1)';
            btnMic.style.backgroundColor = '';
            if(userInput.value.trim() !== '') {
                userInput.placeholder = 'Enviando...';
            } else {
                userInput.placeholder = 'Digite sua mensagem aqui...';
            }
        }
        
        mousePressionado = false;
    }

    document.addEventListener('mousedown', aoPresionar);
    document.addEventListener('mouseup',   aoSoltar);
    window.addEventListener('mouseup', aoSoltar);
    
    // Tratamento massivo de fallbacks para Touch e Telas com Stylus/Caneta
    document.addEventListener('touchstart', aoPresionar, { passive: true });
    document.addEventListener('touchend',   aoSoltar,    { passive: true });
    document.addEventListener('touchcancel', aoSoltar,   { passive: true });
    
    // API Moderna de Ponteiros (cobre mouse, touch e canetas em telas do Windows/Surface)
    document.addEventListener('pointerup', aoSoltar);
    document.addEventListener('pointercancel', aoSoltar);
    document.addEventListener('mouseleave', aoSoltar);

    let transcricaoFinal = '';

    recognition.onstart = () => {
        micAtivo = true;
        
        // Se o usuário soltou o botão ANTES do evento onstart disparar
        if (pararPendente || !mousePressionado) {
            try {
                recognition.abort();
            } catch(e) {}
            return;
        }

        btnMic.style.transform = 'scale(1.2)';
        btnMic.style.backgroundColor = '#ff4444';
        userInput.placeholder = '🎙️ Pode falar, estou ouvindo...';
        transcricaoFinal = ''; // Reseta sempre que iniciar uma nova gravação
    };

    recognition.onresult = (event) => {
        let transcricaoIntermediaria = '';
        
        // Varre as palavras que a IA capturou
        for (let i = event.resultIndex; i < event.results.length; i++) {
            if (event.results[i].isFinal) {
                // Palavra confirmada
                transcricaoFinal += event.results[i][0].transcript;
            } else {
                // Palavra que ela ainda está "pensando" se entendeu direito
                transcricaoIntermediaria += event.results[i][0].transcript;
            }
        }
        
        // Joga o texto imediatamente no campo de digitação pro aluno LER o que a Mari está ouvindo
        userInput.value = transcricaoFinal + transcricaoIntermediaria;
    };

    recognition.onend = () => {
        micAtivo = false;
        pararPendente = false;
        
        // Se parou por erro ou silêncio, mas o botão continua apertado...
        if (mousePressionado) {
            setTimeout(() => {
                try { 
                    if (mousePressionado) recognition.start(); 
                } catch(e) {}
            }, 100); // Pequeno delay para respirar o canal
            return;
        }

        // Só finaliza se o mouse foi realmente solto
        gravando = false;
        btnMic.style.transform = 'scale(1)';
        btnMic.style.backgroundColor = '';
        userInput.placeholder = 'Digite sua mensagem aqui...';

        if (userInput.value.trim()) {
            sendMessage();
        }
        
        // Limpa para a próxima frase
        transcricaoFinal = '';
    };

    recognition.onerror = (event) => {
        console.warn('Erro no microfone:', event.error);
        // Se for erro de rede, não mata a gravação, apenas tenta de novo no onend
        if (event.error === 'network') {
            userInput.placeholder = '⚠️ Instabilidade na rede... tentando reconectar...';
        }
    };

    recognition.onerror = (event) => {
        gravando = false;
        btnMic.style.transform = 'scale(1)';
        btnMic.style.backgroundColor = '';
        btnMic.style.boxShadow = '';
        userInput.placeholder = 'Digite sua mensagem aqui...';
        if (event.error !== 'aborted') {
            console.error('Erro no reconhecimento de voz:', event.error);
        }
    };

} else {
    // Navegador não suporta reconhecimento de voz
    btnMic.style.display = 'none';
    console.log('Reconhecimento de voz não suportado neste navegador.');
}

/**
 * Captura a intenção do usuário e orquestra o envio do payload ao backend.
 * Esta função agrupa o histórico conversacional, lida com arquivos pendentes
 * e gerencia o estado da UI (Loading, Respostas, e Enfileiramento de Áudio).
 * 
 * @async
 * @function sendMessage
 * @returns {Promise<void>}
 */
async function sendMessage() {
    const text = userInput.value.trim();
    if (!text) return;

    // Renderiza otimisticamente a mensagem na UI (Optimistic UI update).
    addMessage(text, 'aluno');
    userInput.value = '';

    // Adiciona a fala do aluno no histórico ANTES de enviar
    chatContexto.push({ tipo: 'aluno', texto: text });

    // Mostra um aviso de "Digitando..."
    const loadingId = 'loading-' + Date.now();
    const loadingDiv = document.createElement('div');
    loadingDiv.id = loadingId;
    loadingDiv.classList.add('message', 'msg-mari');
    loadingDiv.innerHTML = '<div class="typing-indicator"><span></span><span></span><span></span></div>';
    chatBox.appendChild(loadingDiv);
    chatBox.scrollTop = chatBox.scrollHeight;

    try {
        // 2. Envia para o chat.php COM O HISTÓRICO
        const response = await fetch('api/chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                mensagem: text,
                historico: chatContexto, // Envia o que já foi falado
                arquivo: arquivoPendente, // Injeta o arquivo na requisição
                idioma: idiomaAtual // Envia o idioma escolhido!
            })
        });

        const data = await response.json();
        arquivoPendente = null; // Limpa o arquivo pendente após o envio

        // Remove o "Digitando..." assim que o PHP responde
        if (document.getElementById(loadingId)) {
            document.getElementById(loadingId).remove();
        }

        // Verifica se o PHP mandou o array de respostas picadas
        if (data.respostas && Array.isArray(data.respostas)) {
            let mensagensParaExibir = data.respostas;

            // Cancela qualquer fala anterior UMA ÚNICA VEZ antes de enfileirar as novas
            // (não pode ficar cancelando dentro do loop, senão corta a fala no meio)
            window.speechSynthesis.cancel();

            // Exibe todas as mensagens na tela e enfileira a fala de cada uma
            // O navegador fala uma após a outra automaticamente, sem delay artificial
            for (let i = 0; i < mensagensParaExibir.length; i++) {
                let frase = mensagensParaExibir[i];

                // Remove qualquer tag [DETALHES: ...] que a IA mandou fora da hora
                frase = frase.replace(/\[DETALHES:.*?\]/gi, '').trim();
                if (!frase) continue; // Se sobrou só vazio, pula a mensagem

                // Se a frase for muito curta (ex: "1." ou "Sim"),
                // junta com a próxima para evitar pausas estranhas
                if (frase.length < 5 && i < mensagensParaExibir.length - 1) {
                    frase = frase + " " + mensagensParaExibir[i + 1];
                    frase = frase.replace(/\[DETALHES:.*?\]/gi, '').trim();
                    i++; // Pula a próxima já que juntamos
                }

                // Se for a partir da segunda mensagem, cria um delay artificial de 5 segundos simulando "digitando..."
                if (i > 0) {
                    const typingId = 'typing-delay-' + Date.now();
                    const typingDiv = document.createElement('div');
                    typingDiv.id = typingId;
                    typingDiv.classList.add('message', 'msg-mari');
                    typingDiv.innerHTML = '<div class="typing-indicator"><span></span><span></span><span></span></div>';
                    chatBox.appendChild(typingDiv);
                    chatBox.scrollTop = chatBox.scrollHeight;

                    // Espera 5 segundos simulando digitação
                    await new Promise(resolve => setTimeout(resolve, 5000));

                    // Tira as bolinhas da tela
                    const divToRemove = document.getElementById(typingId);
                    if (divToRemove) divToRemove.remove();
                }

                // 1. Mostra na tela
                addMessage(frase, 'mari');

                // 2. Enfileira a frase no TTS (toca uma após a outra, sem interrupção)
                falarResposta(frase);

                // 3. Salva no histórico para manter a memória da Mari
                chatContexto.push({ tipo: 'mari', texto: frase });
            }
        }

    } catch (error) {
        if (document.getElementById(loadingId)) {
            document.getElementById(loadingId).remove();
        }
        addMessage('Poxa, colega! Perdi a conexão aqui. Tenta de novo?', 'mari');
        console.error("Erro no fetch:", error);
    }
}

// Eventos de clique e Enter
sendBtn.addEventListener('click', sendMessage);
userInput.addEventListener('keypress', function (e) {
    if (e.key === 'Enter') sendMessage();
});
