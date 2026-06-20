<?php
require_once __DIR__ . '/../models/DelegacaoModel.php';
require_once __DIR__ . '/../config.php';

/**
 * Use Case: Visualizar Senha Delegada (AES-256-CBC)
 * 
 * Verifica as credenciais corporativas do usuário e descriptografa 
 * a senha temporária de uma delegação ativa.
 * 
 * @category UseCase
 */
class VisualizarSenhaUseCase {
    /**
     * @var DelegacaoModel
     */
    private $delegacaoModel;

    public function __construct(DelegacaoModel $delegacaoModel) {
        $this->delegacaoModel = $delegacaoModel;
    }

    /**
     * Valida credenciais e retorna a senha temporária descriptografada.
     *
     * @param int $id_delegacao O ID do registro da delegação.
     * @param string $id_usuario_logado O ID de quem está tentando acessar.
     * @param string $login_fatec O nome de usuário inserido no modal.
     * @param string $senha_fatec A senha de login inserida no modal.
     * @return string A senha temporária descriptografada.
     * @throws Exception Se qualquer validação falhar.
     */
    public function execute(int $id_delegacao, string $id_usuario_logado, string $login_fatec, string $senha_fatec): string {
        // 1. Verifica se as credenciais digitadas estão corretas e pertencem a quem está logado
        $id_autenticado = $this->delegacaoModel->verificarCredenciaisUsuario($login_fatec, $senha_fatec);
        
        if (!$id_autenticado || $id_autenticado !== $id_usuario_logado) {
            throw new Exception("Credenciais corporativas inválidas ou não pertencem ao seu usuário.");
        }

        // 2. Busca a senha criptografada assegurando que o usuário logado é o delegado e que está Ativa
        $senha_hash = $this->delegacaoModel->buscarSenhaCriptografada($id_delegacao, $id_usuario_logado);
        
        if (!$senha_hash) {
            throw new Exception("A delegação não foi encontrada, está expirada ou não foi concedida a você.");
        }

        // 3. Tenta descriptografar usando AES-256-CBC via Helper
        require_once __DIR__ . '/../helpers/CryptoHelper.php';
        try {
            $senha_descriptografada = CryptoHelper::descriptografarAES($senha_hash);
        } catch (Exception $e) {
            throw new Exception("Esta senha foi gerada no padrão antigo (BCRYPT) ou está corrompida. Solicite uma nova delegação.");
        }

        return $senha_descriptografada;
    }
}
