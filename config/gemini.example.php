<?php

/*
 * Copie este arquivo para config/gemini.php (esse SIM fica de fora do
 * Git) e preencha com uma chave gratuita da API do Google Gemini,
 * gerada em https://aistudio.google.com/apikey (só precisa de uma
 * conta Google, sem cartão de crédito).
 *
 * O Sadag Assist tenta usar o Gemini primeiro (gratuito). Se esse
 * arquivo não existir, ele cai para um modo simples de respostas
 * (sem IA real) — nunca finge que respondeu com IA sem ter respondido.
 */

return [
    'api_key' => 'COLOQUE_SUA_CHAVE_AQUI',
    'model' => 'gemini-3.5-flash-lite',
    'max_tokens' => 500,
];
