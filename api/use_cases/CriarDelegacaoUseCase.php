<?php
require_once __DIR__ . '/../models/DelegacaoModel.php';

/**
 * Use Case: Criar Delegação de Assinatura
 * 
 * Centraliza a regra de negócio da criação do Token (Senha Temporária).
 * Garante que a geração criptográfica segura e a conversão de tempo 
 * fiquem independentes de rotas HTML e de queries do banco.
 * 
 * @category UseCase
 */
class CriarDelegacaoUseCase {
    /**
     * @var DelegacaoModel Instância de abstração do BD.
     */
    private $delegacaoModel;

    /**
     * @param DelegacaoModel $delegacaoModel
     */
    public function __construct(DelegacaoModel $delegacaoModel) {
        $this->delegacaoModel = $delegacaoModel;
    }

    /**
     * Executa a regra de criação da senha temporária.
     *
     * @param string $id_origem O ID do usuário logado (dono).
     * @param string $id_delegado O ID de quem receberá o token.
     * @param int|null $horas_validade Validade estipulada em horas (pode ser nulo se usar data exata).
     * @param string|null $data_exata Data exata de expiração (formato Y-m-d\TH:i ou similar).
     * @return string Retorna a senha "limpa" para ser exibida na UI.
     * @throws Exception Se não for possível salvar no banco.
     */
    public function execute(string $id_origem, string $id_delegado, ?int $horas_validade = null, ?string $data_exata = null): string {
        // 1. Geração criptograficamente segura da senha limpa (8 caracteres hex)
        $senha_limpa = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        
        // 2. Criptografia Simétrica (AES-256-CBC) isolada no Helper
        require_once __DIR__ . '/../helpers/CryptoHelper.php';
        $senha_hash = CryptoHelper::criptografarAES($senha_limpa);
        
        // 3. Define a expiração baseada no tipo de entrada
        if (!empty($data_exata)) {
            $data_expiracao = date('Y-m-d H:i:s', strtotime($data_exata));
        } else {
            $horas = $horas_validade ?: 2; // Fallback para 2 horas
            $data_expiracao = date('Y-m-d H:i:s', strtotime("+{$horas} hours"));
        }
        
        // 4. Salva no banco de dados via Model
        $sucesso = $this->delegacaoModel->criar($id_origem, $id_delegado, $senha_hash, $data_expiracao);
        
        if (!$sucesso) {
            throw new Exception("Falha ao salvar a delegação no banco de dados.");
        }
        
        return $senha_limpa;
    }
}
