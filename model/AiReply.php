<?php

require_once __DIR__ . '/GeminiClient.php';
require_once __DIR__ . '/ClaudeClient.php';
require_once __DIR__ . '/MiniAssistant.php';
require_once __DIR__ . '/DeviceDAO.php';

/*
 * O Sadag Assist não é uma conversa separada — ele está presente em
 * toda conversa (chamado) do usuário, respondendo automaticamente até
 * o momento em que a conversa é encaminhada para um técnico humano.
 * Esta classe gera essa resposta, com o contexto real dos dispositivos
 * do usuário, tentando nesta ordem: Gemini (gratuito) -> Claude (pago,
 * opcional) -> mini-assistente por palavras-chave -> null.
 */
class AiReply
{
    public static function generate($userId, $message, array $historyRows)
    {
        $devices = (new DeviceDAO(false))->getAllByUser($userId);

        if ($devices) {
            $linhas = array_map(function ($dv) {
                $online = ($dv['seconds_since_seen'] !== null && (int)$dv['seconds_since_seen'] < 30) ? 'ONLINE' : 'OFFLINE';
                $ppm = $dv['latest_ppm'] !== null ? $dv['latest_ppm'] . ' ppm*' : 'sem leitura ainda';
                $ultimaVez = $dv['seconds_since_seen'] !== null ? $dv['seconds_since_seen'] . 's atrás' : 'nunca';
                return "- {$dv['name']} (ESP32 ID: {$dv['esp32_id']}): status={$online}, última leitura estimada={$ppm}, status da leitura={$dv['latest_status']}, última comunicação há {$ultimaVez}, local={$dv['location_name']}.";
            }, $devices);
            $contextoDispositivos = "Dispositivos reais cadastrados por este usuário agora:\n" . implode("\n", $linhas);
            $primeiro = $devices[0];
            $onlineHuman = ($primeiro['seconds_since_seen'] !== null && (int)$primeiro['seconds_since_seen'] < 30) ? 'online' : 'offline';
            $ppmHuman = $primeiro['latest_ppm'] !== null ? $primeiro['latest_ppm'] . ' ppm*' : 'sem leitura ainda';
            $deviceStatusHuman = "Status real agora — {$primeiro['name']}: {$onlineHuman}, última leitura: {$ppmHuman}.";
        } else {
            $contextoDispositivos = "Este usuário ainda não tem nenhum dispositivo ESP32 cadastrado no sistema.";
            $deviceStatusHuman = "Você ainda não tem nenhum dispositivo cadastrado no sistema.";
        }

        $systemPrompt = <<<PROMPT
Você é o Sadag Assist, o assistente virtual do Sadag — um projeto de feira técnica (ETEC) de monitoramento de gás no ar. Você está presente em todas as conversas do usuário no sistema, não é uma conversa separada.

Como o sistema funciona, de verdade:
- Um ESP32 com sensor MQ-6 lê a concentração de gás e envia os dados por Wi-Fi para um servidor PHP/MySQL.
- O painel web (dashboard) mostra o status do dispositivo (online/offline), o valor estimado em ppm (marcado com um asterisco porque é uma estimativa, não uma medição calibrada), o histórico de leituras, notificações e permite falar com a equipe de suporte.
- O usuário pode cadastrar mais de um dispositivo e organizá-los por ambiente (cômodo) numa planta 3D da casa.

Regras estritas:
- Responda em português do Brasil, de forma natural e conversacional, como um atendente experiente — direto ao ponto, sem soar robótico. Perguntas simples merecem respostas curtas (1 a 3 frases); só se estenda mais quando o tema realmente exigir.
- NUNCA invente leituras, status ou qualquer dado técnico que não tenha sido fornecido a você abaixo. Use somente os dados reais fornecidos.
- Se não souber responder com segurança, diga isso claramente e sugira o botão "Falar com a equipe".
- Você não pode alterar cadastro, configurações, dispositivos ou qualquer dado — só explica e orienta.
- Não afirme ter "verificado" algo que você não pode verificar de fato.

{$contextoDispositivos}
PROMPT;

        $aiMessages = array_map(fn($r) => ['role' => $r['sender_type'] === 'ai' ? 'assistant' : 'user', 'content' => $r['message']], $historyRows);
        $aiMessages[] = ['role' => 'user', 'content' => $message];

        $reply = GeminiClient::ask($systemPrompt, $aiMessages);
        $fonte = $reply !== null ? 'gemini' : null;

        if ($reply === null) {
            $reply = ClaudeClient::ask($systemPrompt, $aiMessages);
            $fonte = $reply !== null ? 'claude' : null;
        }

        if ($reply === null) {
            $reply = MiniAssistant::respond($message, $deviceStatusHuman);
            $fonte = $reply !== null ? 'mini' : null;
        }

        return $reply !== null ? ['reply' => $reply, 'source' => $fonte] : null;
    }
}
