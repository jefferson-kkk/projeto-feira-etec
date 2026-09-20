<?php

/*
 * Modo de reserva do Sadag Assist: reconhecimento simples por
 * palavras-chave, sem nenhuma IA de verdade por trás. É usado só
 * quando nenhuma API de IA (Gemini/Claude) está configurada.
 *
 * Cobre apenas os temas mais comuns do projeto. Para qualquer
 * mensagem que não bata com nenhum tema conhecido, devolve null — e
 * o Sadag Assist é honesto sobre não saber responder, em vez de
 * inventar uma resposta genérica.
 */
class MiniAssistant
{
    public static function respond($message, $deviceStatusHuman = '')
    {
        $m = strtolower($message);

        $temas = [
            [
                'palavras' => ['offline', 'desconectado', 'nao conecta', 'não conecta', 'caiu', 'perdeu conexao', 'perdeu conexão'],
                'resposta' => "Se o dispositivo aparece offline: 1) confira se o ESP32 está ligado (LED aceso); 2) confira se ele está na mesma rede Wi-Fi do roteador (o nome da rede diferencia maiúsculas de minúsculas); 3) o painel só marca como offline se ele ficar mais de 30 segundos sem enviar nenhuma leitura.",
            ],
            [
                'palavras' => ['sensor', 'mq-6', 'mq6', 'gas', 'gás', 'ppm'],
                'resposta' => "O sensor usado é o MQ-6, que mede a concentração de gás no ar. O ESP32 lê esse valor bruto e converte para uma estimativa em ppm — por isso o painel mostra 'ppm*' com asterisco: é uma estimativa, não uma leitura calibrada em laboratório.",
            ],
            [
                'palavras' => ['painel', 'dashboard', 'nao mostra', 'não mostra', 'sem dados', 'nao atualiza', 'não atualiza'],
                'resposta' => "Se o painel não mostra dados: confira se o dispositivo certo está selecionado no topo do Dashboard, se ele está online e se já enviou pelo menos uma leitura. O painel atualiza sozinho, sem precisar recarregar a página.",
            ],
            [
                'palavras' => ['conectar', 'wifi', 'wi-fi', 'parear', 'cadastrar dispositivo'],
                'resposta' => "Para conectar um novo dispositivo: clique em '+ Conectar dispositivo' no Dashboard e use 'Buscar na rede Wi-Fi' para encontrá-lo automaticamente, ou cadastre manualmente com o ESP32 ID e o código do fabricante.",
            ],
            [
                'palavras' => ['senha', 'conta', 'perfil', 'email', 'e-mail', 'login'],
                'resposta' => "Para alterar dados da conta, use o menu de perfil (ícone no topo do painel) ou a tela de Configurações. Para redefinir a senha, use o link 'Esqueceu a senha?' na tela de login.",
            ],
            [
                'palavras' => ['notifica'],
                'resposta' => "As notificações aparecem no sino no topo do painel — elas avisam sobre respostas da equipe de suporte e mudanças no status do dispositivo.",
            ],
            [
                'palavras' => ['equipe', 'suporte', 'humano', 'atendente'],
                'resposta' => "Use o botão 'Falar com a equipe' logo abaixo desta conversa para abrir um chamado direto com a equipe Sadag.",
            ],
        ];

        foreach ($temas as $tema) {
            foreach ($tema['palavras'] as $palavra) {
                if (strpos($m, $palavra) !== false) {
                    return $tema['resposta'] . ($deviceStatusHuman !== '' ? "\n\n{$deviceStatusHuman}" : '');
                }
            }
        }

        return null;
    }
}
