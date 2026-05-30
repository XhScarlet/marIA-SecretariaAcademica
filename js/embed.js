/**
 * MarIA - Snippet de Integração (Widget)
 * 
 * Este script injeta a interface do chat da Mari em qualquer site externo através
 * de um Iframe isolado, garantindo proteção contra conflitos de CSS (como Bootstrap).
 */
(function() {
    // 1. Configurações Dinâmicas baseadas no host atual
    // Usamos o domínio onde este script está hospedado para inferir os caminhos
    const scriptTag = document.currentScript;
    const baseUrl = scriptTag ? scriptTag.src.substring(0, scriptTag.src.lastIndexOf('/js/')) : 'http://localhost/MarIA_v5';
    
    const urlImagemMari = `${baseUrl}/avatar/marisemfundo1.png`;
    const urlSistemaChat = `${baseUrl}/widget.html`;

    // 2. Criar o container principal flutuante
    const container = document.createElement('div');
    container.id = 'mari-widget-container';
    container.style.position = 'fixed';
    container.style.bottom = '20px';
    container.style.right = '20px';
    container.style.zIndex = '999999';
    container.style.display = 'flex';
    container.style.flexDirection = 'column';
    container.style.alignItems = 'flex-end';
    container.style.fontFamily = 'Arial, sans-serif';

    // 3. Criar a janela do chat (Iframe)
    const chatWindow = document.createElement('iframe');
    chatWindow.id = 'mari-chat-window';
    chatWindow.src = urlSistemaChat;
    chatWindow.style.width = '360px';
    chatWindow.style.height = '520px';
    chatWindow.style.border = 'none';
    chatWindow.style.borderRadius = '20px';
    chatWindow.style.boxShadow = '0 10px 40px rgba(0,0,0,0.3)';
    chatWindow.style.marginBottom = '15px';
    chatWindow.style.display = 'none';
    chatWindow.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
    chatWindow.style.opacity = '0';
    chatWindow.style.transform = 'translateY(20px)';
    chatWindow.style.backgroundColor = 'transparent';

    // 4. Criar o botão flutuante (Avatar)
    const avatarButton = document.createElement('div');
    avatarButton.id = 'mari-avatar-btn';
    avatarButton.style.width = '65px';
    avatarButton.style.height = '65px';
    avatarButton.style.borderRadius = '50%';
    avatarButton.style.backgroundImage = `url(${urlImagemMari})`;
    avatarButton.style.backgroundSize = '120%';
    avatarButton.style.backgroundPosition = 'center top';
    avatarButton.style.backgroundColor = '#003366'; // Cor de fundo da fatec
    avatarButton.style.border = '3px solid #ffffff';
    avatarButton.style.boxShadow = '0 4px 15px rgba(0,0,0,0.3)';
    avatarButton.style.cursor = 'pointer';
    avatarButton.style.transition = 'transform 0.2s ease';
    
    // Efeito Hover
    avatarButton.onmouseover = function() { this.style.transform = 'scale(1.1)'; }
    avatarButton.onmouseout = function() { this.style.transform = 'scale(1)'; }

    // 5. Lógica de Abrir/Fechar
    avatarButton.onclick = function() {
        if (chatWindow.style.display === 'none') {
            // Abrir
            chatWindow.style.display = 'block';
            setTimeout(() => {
                chatWindow.style.opacity = '1';
                chatWindow.style.transform = 'translateY(0)';
            }, 10);
            avatarButton.style.boxShadow = '0 0 15px rgba(255,0,0,0.5)'; // Brilho quando ativo
        } else {
            // Fechar
            chatWindow.style.opacity = '0';
            chatWindow.style.transform = 'translateY(20px)';
            avatarButton.style.boxShadow = '0 4px 15px rgba(0,0,0,0.3)';
            setTimeout(() => {
                chatWindow.style.display = 'none';
            }, 300); // Aguarda a animação
        }
    };

    // 6. Injetar na página
    container.appendChild(chatWindow);
    container.appendChild(avatarButton);
    document.body.appendChild(container);

})();
