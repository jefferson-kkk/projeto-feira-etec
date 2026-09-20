<?php

/*
 * Cliente mínimo para a API gratuita do Google Gemini, via cURL puro.
 * A chave fica em config/gemini.php (fora do Git), nunca chega ao
 * navegador — só api/assistant.php fala com esta classe, no servidor.
 */
class GeminiClient
{
    public static function ask($systemPrompt, array $messages)
    {
        $configFile = __DIR__ . '/../config/gemini.php';

        if (!is_file($configFile)) {
            return null;
        }

        $config = require $configFile;

        if (empty($config['api_key']) || strpos($config['api_key'], 'COLOQUE_') !== false) {
            return null;
        }

        // O Gemini usa "model" no lugar de "assistant" para o papel da IA.
        $contents = array_map(function ($m) {
            return [
                'role' => $m['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $m['content']]],
            ];
        }, $messages);

        $payload = json_encode([
            'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => $contents,
            /*
             * Modelos "flash" normais (não -lite) pensam antes de
             * responder, e esse raciocínio consome o próprio
             * maxOutputTokens — em teste real isso comeu quase todo o
             * limite e cortou a resposta pela metade. Por isso o
             * modelo padrão configurado é um "-lite" (sem essa etapa
             * escondida): mais rápido e sem gastar cota gratuita com
             * pensamento invisível. Um "-lite" rejeita o parâmetro
             * thinkingConfig (erro 400), então ele não é enviado aqui.
             */
            'generationConfig' => [
                'maxOutputTokens' => (int)($config['max_tokens'] ?? 500),
                'temperature' => 0.5,
            ],
        ], JSON_UNESCAPED_UNICODE);

        $model = $config['model'] ?? 'gemini-3.5-flash-lite';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($config['api_key']);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['content-type: application/json'],
            // Força IPv4: em redes com IPv6 mal configurado, tentar IPv6
            // primeiro trava por vários segundos antes de cair pra IPv4.
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log('GeminiClient: falha de conexão - ' . $curlError);
            return null;
        }

        $data = json_decode($response, true);

        if ($status !== 200 || !is_array($data)) {
            error_log('GeminiClient: HTTP ' . $status . ' - ' . substr($response, 0, 500));
            return null;
        }

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        return $text !== '' ? $text : null;
    }
}
