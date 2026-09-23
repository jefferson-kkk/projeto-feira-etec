<?php

/*
 * Cliente mínimo para a API gratuita do Google Gemini, via cURL puro.
 * A chave fica em config/gemini.php (fora do Git), nunca chega ao
 * navegador — só api/assistant.php fala com esta classe, no servidor.
 */
class GeminiClient
{
    public static function ask($systemPrompt, array $messages, $maxTokensOverride = null, $timeoutOverride = null)
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
                'maxOutputTokens' => (int)($maxTokensOverride ?? $config['max_tokens'] ?? 500),
                'temperature' => 0.5,
            ],
        ], JSON_UNESCAPED_UNICODE);

        $model = $config['model'] ?? 'gemini-3.5-flash-lite';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($config['api_key']);
        $timeout = (int)($timeoutOverride ?? 45);

        /*
         * O modelo gratuito fica sobrecarregado de vez em quando
         * ("high demand" / HTTP 503) -- em teste real isso as vezes
         * nem responde dentro do timeout, as vezes responde rapido
         * com 503. Os dois casos sao tipicamente transitorios e uma
         * segunda tentativa poucos segundos depois costuma resolver,
         * entao tentamos de novo antes de desistir e cair no proximo
         * da cadeia (Claude -> mini-assistente).
         */
        $tentativas = 2;
        for ($i = 1; $i <= $tentativas; $i++) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => ['content-type: application/json'],
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => $timeout,
            ]);

            $response = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                error_log("GeminiClient: falha de conexão (tentativa $i/$tentativas) - " . $curlError);
                if ($i < $tentativas) { sleep(2); continue; }
                return null;
            }

            $data = json_decode($response, true);

            // 503 = servico sobrecarregado, 429 = limite de taxa --
            // ambos temporarios, vale tentar de novo. Qualquer outro
            // erro (ex: 400 chave invalida) e permanente, nao adianta
            // repetir.
            if (($status === 503 || $status === 429) && $i < $tentativas) {
                error_log("GeminiClient: HTTP $status (tentativa $i/$tentativas), tentando de novo - " . substr($response, 0, 300));
                sleep(3);
                continue;
            }

            if ($status !== 200 || !is_array($data)) {
                error_log('GeminiClient: HTTP ' . $status . ' - ' . substr($response, 0, 500));
                return null;
            }

            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            return $text !== '' ? $text : null;
        }

        return null;
    }
}
