<?php

/*
 * Cliente mínimo para a API do Claude (Anthropic), via cURL puro — sem
 * SDK. A chave fica em config/claude.php (fora do Git), nunca chega ao
 * navegador: o front-end só fala com api/assistant.php, que é quem
 * chama esta classe no servidor.
 */
class ClaudeClient
{
    public static function ask($systemPrompt, array $messages)
    {
        $configFile = __DIR__ . '/../config/claude.php';

        if (!is_file($configFile)) {
            return null;
        }

        $config = require $configFile;

        if (empty($config['api_key']) || strpos($config['api_key'], 'COLOQUE_') !== false) {
            return null;
        }

        $payload = json_encode([
            'model' => $config['model'] ?? 'claude-sonnet-5',
            'max_tokens' => (int)($config['max_tokens'] ?? 600),
            'system' => $systemPrompt,
            'messages' => $messages,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'content-type: application/json',
                'x-api-key: ' . $config['api_key'],
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 45,
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log('ClaudeClient: falha de conexão - ' . $curlError);
            return null;
        }

        $data = json_decode($response, true);

        if ($status !== 200 || !is_array($data)) {
            error_log('ClaudeClient: HTTP ' . $status . ' - ' . substr($response, 0, 500));
            return null;
        }

        $text = '';
        foreach ($data['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= $block['text'];
            }
        }

        return $text !== '' ? $text : null;
    }
}
