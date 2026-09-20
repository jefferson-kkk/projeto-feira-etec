<?php

/*
 * Envio de email real via SMTP, sem depender do mail() do PHP (que no
 * Windows precisa de um servidor de email local configurado, e por
 * isso nunca funcionava). Implementado com sockets puros — sem
 * biblioteca externa, sem Composer.
 *
 * Configuração fica em config/mail.php (fora do Git — veja
 * config/mail.example.php para o modelo). Se o arquivo não existir
 * ou estiver incompleto, send() retorna false silenciosamente (o
 * chamador já trata isso registrando "email_sent = 0").
 */
class Mailer
{
    public static function send($to, $subject, $body, $replyTo = null)
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $configFile = __DIR__ . '/../config/mail.php';

        if (!is_file($configFile)) {
            return false;
        }

        $config = require $configFile;

        if (empty($config['host']) || empty($config['username']) || empty($config['password'])) {
            return false;
        }

        try {
            return self::smtpSend($config, $to, $subject, $body, $replyTo);
        } catch (Throwable $e) {
            error_log('Mailer: ' . $e->getMessage());
            return false;
        }
    }

    private static function smtpSend($config, $to, $subject, $body, $replyTo)
    {
        $host = $config['host'];
        $port = (int)($config['port'] ?? 587);
        $timeout = 10;

        $socket = stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, $timeout);

        if (!$socket) {
            throw new Exception("Falha ao conectar em {$host}:{$port} — {$errstr}");
        }

        stream_set_timeout($socket, $timeout);

        $read = function () use ($socket) {
            $data = '';
            while (($line = fgets($socket, 515)) !== false) {
                $data .= $line;
                if (isset($line[3]) && $line[3] === ' ') {
                    break;
                }
            }
            return $data;
        };

        $write = function ($cmd) use ($socket) {
            fwrite($socket, $cmd . "\r\n");
        };

        $expect = function ($code) use ($read) {
            $resp = $read();
            if (substr($resp, 0, 3) !== (string)$code) {
                throw new Exception("SMTP esperava {$code}, recebeu: {$resp}");
            }
            return $resp;
        };

        $expect(220);
        $write('EHLO sadag.local');
        $expect(250);

        if ($port == 587) {
            $write('STARTTLS');
            $expect(220);

            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception('Falha ao negociar TLS (STARTTLS).');
            }

            $write('EHLO sadag.local');
            $expect(250);
        }

        $write('AUTH LOGIN');
        $expect(334);
        $write(base64_encode($config['username']));
        $expect(334);
        $write(base64_encode($config['password']));
        $expect(235);

        $fromEmail = $config['from_email'] ?? $config['username'];
        $fromName = $config['from_name'] ?? 'Sadag';

        $write("MAIL FROM:<{$fromEmail}>");
        $expect(250);
        $write("RCPT TO:<{$to}>");
        $expect(250);
        $write('DATA');
        $expect(354);

        $headers = "From: {$fromName} <{$fromEmail}>\r\n";
        $headers .= "To: <{$to}>\r\n";
        $headers .= 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n";

        if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $headers .= "Reply-To: {$replyTo}\r\n";
        }

        // Corpo em base64: como o alfabeto base64 não usa ".", nenhuma
        // linha pode começar com ponto por acidente (o que encerraria
        // a mensagem cedo demais no protocolo SMTP).
        $message = $headers . "\r\n" . chunk_split(base64_encode($body));

        $write($message . "\r\n.");
        $expect(250);

        $write('QUIT');
        fclose($socket);

        return true;
    }
}
