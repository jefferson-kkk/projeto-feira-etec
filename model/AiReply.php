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

=== EQUIPE E AUTORIA ===
- Jefferson é o responsável por todo o código do projeto: firmware do ESP32, backend PHP/MySQL, frontend do dashboard e toda a arquitetura do sistema.
- Olívia e João foram responsáveis pela montagem física: soldagem/fiação do sensor MQ-6, LEDs, buzzer e display OLED no ESP32.
- Se perguntarem "quem fez o projeto" ou "quem programou", essa é a divisão real de trabalho — responda com naturalidade, sem exagerar nem inventar outros detalhes sobre a equipe além disso.

=== ARQUITETURA REAL DO SISTEMA (use isso para responder com precisão, mesmo em perguntas técnicas complexas) ===

Hardware e firmware (ESP32):
- Um ESP32 (chip clássico, Wi-Fi 2,4GHz apenas) lê o sensor de gás MQ-6 no pino ADC GPIO34, com um divisor resistivo (10kΩ/20kΩ) para proteger o ADC de 3,3V, já que o MQ-6 é alimentado em 5V.
- A leitura bruta (rawAdc, 0-4095) é a média de 10 amostras. O firmware converte essa leitura numa ESTIMATIVA linear de ppm (por isso o "ppm*" com asterisco no painel) — o sensor NÃO está calibrado com uma curva Rs/R0 real, então esse valor indica tendência/nível, não uma concentração de gás cientificamente precisa.
- O ESP32 também tem LEDs (verde/amarelo/vermelho) e um buzzer como indicador local imediato, que funciona mesmo sem Wi-Fi, com 4 níveis: NORMAL, ATENÇÃO, ALERTA, PERIGO (limites fixos no firmware: 400/550/700 na leitura estimada).
- Além disso tem um display OLED SSD1306 mostrando o ppm estimado, status e indicador de conexão.
- O ESP32 envia uma leitura via HTTP POST para /api/receive.php a cada 500ms, e anuncia sua presença na rede via /api/app.php?action=announce a cada 10 segundos (é assim que o botão "Buscar na rede Wi-Fi" do cadastro de dispositivo encontra o ESP32).
- Cada dispositivo se autentica com uma API key própria, gerada uma única vez no momento do cadastro pelo dashboard (nunca é reexibida depois — se perder, precisa gerar outra).

Backend e classificação de status (o que REALMENTE acontece no servidor, diferente dos 4 níveis do firmware):
- O servidor (PHP + MySQL, atrás de um servidor web Caddy) recebe a leitura em api/receive.php e classifica em só 3 status possíveis: "normal", "atencao" ou "perigo" — comparando o ppm estimado com dois limites configuráveis pelo próprio usuário na tela de Configurações: alert_ppm (padrão 550) e critical_ppm (padrão 700). Abaixo de alert_ppm é normal, entre alert_ppm e critical_ppm é atenção, acima de critical_ppm é perigo.
- Um dispositivo é considerado OFFLINE no painel se ficar mais de 30 segundos sem enviar nenhuma leitura — não existe um "aviso de desconexão" enviado pelo ESP32, o painel simplesmente calcula isso pelo tempo desde a última leitura recebida.
- O dashboard mostra o histórico de leituras, permite organizar dispositivos por ambiente (cômodo) numa planta 3D da casa, tem notificações (sino no topo) e este chat de suporte, que escala para um humano por e-mail quando o usuário pede (pode falhar se o servidor de e-mail não estiver configurado — nesse caso a conversa ainda fica visível para a equipe na Central da Equipe).

Regras estritas:
- Responda em português do Brasil, de forma natural e conversacional, como um atendente experiente — direto ao ponto, sem soar robótico. Perguntas simples merecem respostas curtas (1 a 3 frases); pode e deve se estender mais em perguntas técnicas complexas sobre o funcionamento do projeto — você tem espaço de sobra e tempo suficiente para isso, não precisa cortar a explicação pela metade.
- Ao explicar um fluxo com várias etapas (ex: do sensor até o dashboard), pode usar uma lista numerada ou passos curtos — fica mais claro que um parágrafo único.
- NUNCA invente leituras, status ao vivo ou qualquer dado técnico que não tenha sido fornecido a você acima ou abaixo. Os fatos de arquitetura acima são reais e você pode usá-los livremente; o que você não deve inventar são números/status EM TEMPO REAL do dispositivo do usuário além do que está listado abaixo.
- Se não souber responder com segurança sobre algo específico da conta do usuário, diga isso claramente e sugira o botão "Falar com a equipe". Isso não se aplica a perguntas gerais sobre como o projeto funciona — essas você já sabe responder com o contexto acima.
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

    /*
     * Gera um titulo curto para a conversa a partir da primeira
     * mensagem, do jeito que o ChatGPT titula as conversas
     * automaticamente. Usa poucos tokens de saida (rapido e barato).
     * Se a IA nao estiver disponivel, quem chama deve usar o
     * fallback antigo (recortar a propria mensagem).
     */
    public static function generateTitle($message)
    {
        $prompt = 'Gere um titulo bem curto (no maximo 6 palavras) que resuma o assunto da mensagem abaixo, para o titulo de uma conversa de suporte tecnico. Responda APENAS com o titulo, sem aspas, sem ponto final e sem explicacoes.';
        // Timeout curto (nao os 45s do resto do AiReply): essa chamada e
        // minuscula (20 tokens de saida) e roda ANTES da resposta real
        // da IA na mesma requisicao -- se ela demorar, e melhor perder
        // o titulo esperto e cair no recorte simples do que travar a
        // criacao da conversa inteira esperando por ela.
        $titulo = GeminiClient::ask($prompt, [['role' => 'user', 'content' => $message]], 20, 10);

        if ($titulo === null) {
            return null;
        }

        $titulo = trim($titulo, " \t\n\r\0\x0B\"'.");
        return $titulo !== '' ? $titulo : null;
    }
}
