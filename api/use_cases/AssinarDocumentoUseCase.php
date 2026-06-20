<?php
require_once __DIR__ . '/../models/DelegacaoModel.php';
require_once __DIR__ . '/../helpers/CryptoHelper.php';

/**
 * Use Case: Assinar Documento via Delegação
 * 
 * Implementa a regra de negócio para injetar assinaturas de terceiros.
 * Valida a senha temporária digitada com as senhas ativas do delegado.
 * 
 * @category UseCase
 */
class AssinarDocumentoUseCase {
    /**
     * @var DelegacaoModel
     */
    private $delegacaoModel;

    public function __construct(DelegacaoModel $delegacaoModel) {
        $this->delegacaoModel = $delegacaoModel;
    }

    /**
     * Valida a senha temporária e retorna a assinatura de quem autorizou.
     *
     * @param string $id_delegado_logado O ID de quem está tentando usar a assinatura.
     * @param string $senha_temporaria_digitada A senha digitada (ex: A1B2C3D4).
     * @return array Um array contendo ['id_origem', 'nome_origem', 'assinatura_img'].
     * @throws Exception Se a senha for inválida ou não houver delegação ativa.
     */
    public function execute(string $id_delegado_logado, string $senha_temporaria_digitada): array {
        // 1. Busca todas as delegações ativas para quem está logado
        $delegacoes = $this->delegacaoModel->buscarDelegacoesAtivasPorDelegado($id_delegado_logado);
        
        if (empty($delegacoes)) {
            throw new Exception("Você não possui nenhuma delegação de assinatura ativa.");
        }

        // 2. Varre as delegações tentando fazer o 'Match' com a senha digitada
        foreach ($delegacoes as $delegacao) {
            try {
                // Descriptografa a senha salva no banco
                $senha_banco = CryptoHelper::descriptografarAES($delegacao['senha_temporaria']);
                
                // 3. Compara a senha digitada com a senha decodificada
                if ($senha_temporaria_digitada === $senha_banco) {
                    // Match Encontrado! Retorna os dados do Autor
                    return [
                        'id_origem' => $delegacao['id_usuario_origem'],
                        'nome_origem' => $delegacao['nome_origem'],
                        'assinatura_img' => $delegacao['assinatura_img']
                    ];
                }
            } catch (Exception $e) {
                // Ignora hashes do padrão legado (BCRYPT) ou corrompidos durante a busca
                continue;
            }
        }

        // Se chegou até aqui, nenhuma senha bateu
        throw new Exception("Senha temporária inválida ou expirada.");
    }
}
