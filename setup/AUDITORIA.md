# Auditoria do ambiente — Sadag (ex-Aeris Guard)

Feita analisando o código real do projeto (grep de funções usadas, arquivos de config existentes, processos rodando na máquina) — nada aqui foi assumido por convenção.

## A) Arquitetura encontrada

| Camada | Tecnologia | Onde |
|---|---|---|
| Frontend | HTML + CSS + JavaScript puro (sem framework, sem build step) | `view/html/*.php`, `view/html/teste.html` |
| Backend/API | PHP 8 (procedural + algumas classes DAO/Controller) | `api/*.php`, `Controller/`, `model/` |
| Banco | MySQL 8 (banco `aeris`) | `model/Connection.php`, `api/iot.schema.sql` |
| Servidor web | Caddy (binário próprio do projeto, não instalado globalmente) + php-cgi via FastCGI | `tools/Caddyfile`, `tools/iniciar-servidor.bat` |
| Firmware | ESP32 (Arduino framework) | `esp32-firmware/esp32-firmware.ino` |
| IA de suporte | Google Gemini via cURL (fallback: Claude via cURL, fallback: keywords locais) | `model/GeminiClient.php`, `model/ClaudeClient.php`, `model/MiniAssistant.php` |
| Email | Cliente SMTP próprio via socket raw (sem PHPMailer/Composer) | `model/Mailer.php` |
| App mobile futuro | **Não existe ainda** (nenhum `pubspec.yaml`) | — |

## B/C) Tecnologias e dependências confirmadas por uso real

- **PHP**: confirmado via `new PDO` (10 arquivos), `curl_init`/`CURLOPT` (3 arquivos), `stream_socket_enable_crypto`/openssl (Mailer.php), `new finfo()` (profile.php — upload de avatar). `mb_*` é usado só com fallback (`function_exists('mb_substr') ? ... : substr`), então é opcional.
- **MySQL**: confirmado via `Connection.php` (`host=localhost`, `user=root`, banco `aeris`, senha via env `AERIS_DB_PASSWORD` ou `senaisp`).
- **Composer**: **não usado** — não existe `composer.json` no projeto.
- **Node/npm**: **não usado** — não existe `package.json`/lockfile.
- **Flutter**: **não existe projeto ainda** — não existe `pubspec.yaml`.
- **Caddy**: binário do projeto em `tools/caddy.exe` (fora do Git), configurado via `tools/Caddyfile` fazendo proxy para `127.0.0.1:9123`.
- **ESP32**: `#include` confirmados: `WiFi.h`, `HTTPClient.h`, `WebServer.h`, `ESPmDNS.h`, `Wire.h` (todos parte do core da placa ESP32) + `Adafruit_GFX.h` + `Adafruit_SSD1306.h` (bibliotecas externas reais, para o display OLED).

## D) Versões

- PHP encontrado nesta máquina: **8.4.14** (NTS, x64). Nenhuma versão mínima é forçada pelo código; qualquer PHP 8.x com as extensões abaixo funciona.
- MySQL: serviço **MySQL80** já instalado e rodando.
- Git: **2.51.0**.
- VS Code: **1.138.0**.

## E) O que o instalador instala/configura de fato

- Extensão PHP `fileinfo` (estava desativada — **bug real encontrado**: `view/html/profile.php` usa `new finfo()` no upload de avatar e quebraria sem ela). Já corrigi manualmente nesta máquina e validei.
- Extensão VS Code **PHP Intelephense** (já estava instalada aqui).
- Inicia o serviço do MySQL se estiver parado.
- Importa `api/iot.schema.sql` **somente** se faltar alguma tabela esperada (o arquivo só usa `CREATE TABLE IF NOT EXISTS`, nunca `DROP`).
- Sobe `tools/iniciar-servidor.bat` (Caddy + php-cgi) se a porta 8000 estiver livre.

## F) O que **não** é instalado, e por quê

- **Composer** — sem `composer.json`, sem justificativa.
- **Node.js/npm/yarn/pnpm** — sem `package.json`, sem justificativa.
- **Flutter/Dart/Android SDK/Android Studio** — sem `pubspec.yaml`; nenhum app Flutter existe ainda. Preparado só sob pedido explícito (`install.bat -PrepararFlutter`), e mesmo assim o instalador só orienta os passos oficiais — baixar e instalar Flutter SDK + Android Studio manualmente, porque são vários GB e automatizar isso sem supervisão é arriscado.
- **Arduino IDE** — não é necessária para desenvolver o dashboard/API, só para gravar firmware no ESP32. Instalada só sob pedido (`install.bat -PrepararArduino`).
- **Python, Java/JDK, Docker, 7-Zip, CMake/Make** — nenhum uso encontrado em lugar nenhum do projeto.

## G) Extensões VS Code

**Necessárias** (instaladas automaticamente): PHP Intelephense.
**Opcionais** (só sugeridas no relatório, nunca instaladas sem pedido): PHP Debug (Xdebug), GitLens, Prettier.

## H) Bibliotecas Arduino necessárias (quando for compilar o firmware)

| Biblioteca | Motivo |
|---|---|
| Adafruit GFX Library | `esp32-firmware.ino` inclui `Adafruit_GFX.h` |
| Adafruit SSD1306 | `esp32-firmware.ino` inclui `Adafruit_SSD1306.h` (driver do OLED) |
| Adafruit BusIO | dependência da SSD1306 |

Placa: **ESP32 Dev Module** (core `esp32` da Espressif). URL do índice de placas: `https://raw.githubusercontent.com/espressif/arduino-esp32/gh-pages/package_esp32_index.json`.

## I) Flutter — nada a preparar hoje

Nenhum componente Flutter/Android é instalado por padrão porque não há projeto Flutter. Fica documentado o passo a passo oficial em `install.ps1` para quando o app existir.

## J) Serviços

| Serviço | Situação real nesta máquina |
|---|---|
| MySQL80 (Windows Service) | Instalado e rodando |
| Caddy + php-cgi | Processos locais, iniciados por `tools/iniciar-servidor.bat` (não são serviço Windows) |

## K) Portas

| Porta | Serviço | Endereço | Função |
|---|---|---|---|
| 8000 | Caddy | http://localhost:8000 | Entrada HTTP do dashboard e da API |
| 9123 | php-cgi | 127.0.0.1:9123 | Backend PHP atrás do Caddy |
| 3306 | MySQL | localhost:3306 | Banco `aeris` |

Nenhum conflito encontrado nesta máquina no momento da auditoria.

## L) Variáveis de ambiente

- `AERIS_DB_PASSWORD` (opcional) — sobrepõe a senha padrão `senaisp` do MySQL root, lida em `model/Connection.php`. Não definida nesta máquina (usa o fallback).

## M) Configurações

- `config/mail.php`, `config/gemini.php`, `config/claude.php` — gitignored, contêm credenciais reais (email SMTP, chave Gemini). O instalador **não cria nem lê** essas credenciais; só confirma que os `*.example.php` existem como referência.
- `php.ini` real: `C:\php\php.ini`.

## N) Possíveis conflitos encontrados (não corrigidos automaticamente, só reportados)

- `.vscode/launch.json` aponta para `http://localhost:8080`, mas o projeto roda em `http://localhost:8000`. Não alterei esse arquivo (não modifico configuração existente sem pedido), só deixo registrado.
- `.vscode/settings.json` tem `"liveServer.settings.port": 5501"` — provavelmente resquício de antes do projeto ter um backend PHP real; a extensão Live Server não é necessária aqui.

## O) Etapas do instalador

`install.bat` roda em **9 etapas**, cada uma idempotente (verifica → pula se já estiver certo → só instala/corrige o que falta):

1. Windows e privilégios
2. Git (+ GitHub CLI, opcional)
3. VS Code + extensões
4. PHP + extensões
5. Banco de dados (MySQL)
6. Caddy + PHP FastCGI (sobe o servidor local se estiver parado)
7. Node/Composer (confirma que não são necessários)
8. Flutter (opcional, `-PrepararFlutter`) e Arduino/ESP32 (opcional, `-PrepararArduino`)
9. Validação final + relatório
