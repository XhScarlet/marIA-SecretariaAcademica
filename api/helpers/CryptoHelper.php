<?php
require_once __DIR__ . '/../config.php';

/**
 * Helper de Criptografia Simétrica (AES-256-CBC)
 * 
 * Centraliza a lógica de embaralhamento reversível para manter 
 * o princípio DRY e o Clean Code nas validações de senhas temporárias.
 * 
 * @category Helper
 */
class CryptoHelper {
    
    /**
     * Criptografa uma string plana gerando um hash seguro com IV.
     *
     * @param string $dado_plano A senha/texto a ser protegido.
     * @return string Retorna uma string em base64 contendo hash::iv
     */
    public static function criptografarAES(string $dado_plano): string {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($dado_plano, 'aes-256-cbc', SECRET_HASH_KEY, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    /**
     * Descriptografa um hash simétrico gerado por este helper.
     *
     * @param string $dado_criptografado O hash em base64 (hash::iv).
     * @return string A string limpa (texto puro).
     * @throws Exception Se o formato for inválido (ex: BCRYPT antigo) ou falhar na descriptografia.
     */
    public static function descriptografarAES(string $dado_criptografado): string {
        $decoded = base64_decode($dado_criptografado);
        
        if (strpos($decoded, '::') === false) {
            throw new Exception("Formato inválido ou gerado por um padrão antigo (BCRYPT).");
        }

        list($encrypted_data, $iv) = explode('::', $decoded, 2);
        
        $descriptografado = openssl_decrypt($encrypted_data, 'aes-256-cbc', SECRET_HASH_KEY, 0, $iv);
        
        if ($descriptografado === false) {
            throw new Exception("Falha de segurança na descriptografia AES-256.");
        }

        return $descriptografado;
    }
}
