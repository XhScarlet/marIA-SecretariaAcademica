// maria-wpp/app.js
const axios = require('axios');
const readline = require('readline');

// Configura a leitura de dados digitados no terminal
const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout
});

// URL local do seu endpoint da MarIA no XAMPP
const API_URL = 'http://localhost/MarIA_v5.3/api/chat.php';

console.log('====================================================');
console.log('🤖 SIMULADOR WHATSAPP - MarIA (Ambiente Local)');
console.log('Digite sua mensagem para simular um aluno. Digite "sair" para fechar.');
console.log('====================================================\n');

function iniciarChat() {
    rl.question('👤 Aluno diz: ', async (mensagem) => {
        
        if (mensagem.toLowerCase() === 'sair') {
            console.log('\n👋 Simulador encerrado. Bom descanso!');
            rl.close();
            return;
        }

        if (!mensagem.trim()) {
            iniciarChat();
            return;
        }

        console.log('⏳ MarIA está digitando...');

        try {
            // Envia a mensagem simulada para a sua API PHP via POST
            const response = await axios.post(API_URL, {
                mensagem: mensagem,
                origem: 'whatsapp', // Tag para o seu PHP saber que veio do Wpp
                telefone: '5511999999999' // RA ou número fake de teste
            });

            // Exibe a resposta que a sua inteligência em PHP gerou
            // O PHP retorna um JSON com um array 'respostas'
            const textoResposta = response.data.respostas ? response.data.respostas.join('\n') : 'Sem resposta';
            console.log(`\n🤖 MarIA responde: ${textoResposta}\n`);
            console.log('----------------------------------------------------');

        } catch (error) {
            console.log('\n❌ Erro ao conectar com o seu XAMPP/PHP.');
            console.log(`Verifique se o Apache está ligado ou se a URL está correta.`);
            console.log(`Detalhes: ${error.message}\n`);
        }

        // Mantém o loop do chat ativo no terminal
        iniciarChat();
    });
}

// Inicia o loop
iniciarChat();
