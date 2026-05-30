<?php
// API intermediária para ElevenLabs TTS (Substituindo Kokoro)
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, xi-api-key');

// Responder a preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Receber dados JSON
$jsonInput = file_get_contents('php://input');
$input = json_decode($jsonInput, true);

$texto = $input['texto'] ?? '';
// Voz padrão da ElevenLabs se não vier nada
$voiceId = "c3QefzBhE1Cx4Yl23IV3"; 

// Validar entrada
if (empty($texto)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['erro' => 'Texto vazio']);
    exit;
}

// Limitar tamanho do texto para evitar gastos excessivos ou timeouts
$texto = mb_substr($texto, 0, 1000);

// ElevenLabs API Key
$elevenLabsKey = "sk_9683693bfcde26ac3a89b7cde4eba3c08f015ada05c8cfe0";

$url = "https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}";

$payload = json_encode([
    'text' => $texto,
    'model_id' => "eleven_multilingual_v2",
    'voice_settings' => [
        'stability' => 0.5,
        'similarity_boost' => 0.8
    ]
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'xi-api-key: ' . $elevenLabsKey,
    'Content-Type: application/json',
    'accept: audio/mpeg'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$resultado = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($httpCode == 200) {
    header('Content-Type: audio/mpeg');
    echo $resultado;
    exit;
} else {
    error_log("ElevenLabs Erro ($httpCode): " . $resultado);
    
    // Fallback: Tentar DeepInfra (Kokoro) se ElevenLabs falhar (opcional, mas bom manter)
    $deepinfraToken = '34Nu2YqO0UHg1ljPUSC2qEMS1f93JYYR';
    if (!empty($deepinfraToken)) {
        $urlDI = 'https://api.deepinfra.com/v1/inference/hexgrad/Kokoro-82M';
        $payloadDI = json_encode(['text' => $texto, 'voice' => 'af_heart']);
        
        $chDI = curl_init($urlDI);
        curl_setopt($chDI, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chDI, CURLOPT_POST, true);
        curl_setopt($chDI, CURLOPT_HTTPHEADER, [
            'Authorization: bearer ' . $deepinfraToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($chDI, CURLOPT_POSTFIELDS, $payloadDI);
        $resDI = curl_exec($chDI);
        $codeDI = curl_getinfo($chDI, CURLINFO_HTTP_CODE);
        curl_close($chDI);

        if ($codeDI == 200) {
            $jsonDI = json_decode($resDI, true);
            if (!empty($jsonDI['audio'])) {
                $audioData = $jsonDI['audio'];
                if (strpos($audioData, 'data:') === 0) {
                    $parts = explode(',', $audioData, 2);
                    header('Content-Type: audio/wav');
                    echo base64_decode($parts[1]);
                    exit;
                }
            }
        }
    }

    // Se tudo falhar, retorna erro para o fallback local do navegador
    header('Content-Type: application/json');
    http_response_code(503);
    echo json_encode([
        'erro' => 'TTS ElevenLabs falhou',
        'detalhes' => json_decode($resultado, true)
    ]);
}
?>
