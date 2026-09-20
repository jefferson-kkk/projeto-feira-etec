<?php

/*
 * Copie este arquivo para config/claude.php (esse SIM fica de fora do
 * Git) e preencha com uma chave real da API do Claude, gerada em
 * https://console.anthropic.com/settings/keys
 *
 * Sem esse arquivo (ou com a chave vazia), o Sadag Assist continua
 * funcionando na interface, mas avisa que não conseguiu responder —
 * ele nunca inventa uma resposta fingindo ser a IA.
 */

return [
    'api_key' => 'COLOQUE_SUA_CHAVE_AQUI',
    'model' => 'claude-sonnet-5',
    'max_tokens' => 600,
];
