# Testes automatizados do Sadag

Testes simples em PHP puro (sem framework, sem Composer -- o projeto
não depende de nenhum dos dois de propósito). Servem para checar, em
poucos segundos, se o essencial do sistema continua funcionando antes
de uma apresentação, depois de mexer no código, ou depois de trocar de
computador.

## Como rodar

**Mais fácil:** dê duplo clique em `executar-testes.bat`.

**Pelo terminal:**
```
C:\php\php.exe tests\run_all.php
```

Isso roda primeiro os testes unitários (rápidos, não precisam do
servidor rodando) e depois os de integração (esses sim precisam do
servidor no ar -- rode `tools\iniciar-servidor.bat` antes).

Para também testar login de verdade e o chat com a IA, defina a senha
da conta de teste antes de rodar:
```
set SADAG_TEST_PASSWORD=sua_senha_aqui
C:\php\php.exe tests\run_all.php
```
Sem isso, esses testes específicos aparecem como `[PULADO]` -- não
falham, só ficam sem cobertura naquela execução.

## O que cada arquivo testa

- **`unit_tests.php`** -- funções isoladas, sem precisar do banco nem
  do servidor: hash de chave de API, hash de senha, o
  mini-assistente por palavra-chave, geração de username a partir do
  email, e as regras de classificação normal/atenção/perigo.

- **`integration_tests.php`** -- testa o sistema rodando de verdade:
  login, proteção de rotas que exigem sessão, o endpoint que recebe
  leituras do ESP32 (`api/receive.php`), o chat de suporte com a IA, e
  o schema do banco de dados.

  Vários desses testes existem porque cobrem **bugs reais que já
  aconteceram neste projeto** -- são testes de regressão:
  - `Connection.php` usando `127.0.0.1` em vez de `localhost` (sem
    isso, toda página do site ficava ~2s mais lenta no Windows).
  - `curl.cainfo` configurado no `php.ini` (sem isso, toda chamada
    HTTPS pra API do Gemini falhava).
  - As colunas que os DAOs criam sozinhos na primeira execução
    (`pos_x`, `raw_adc`, `escalated` etc.) precisam bater com o que
    está em `api/iot.schema.sql`.

## Antes da feira

Rode `executar-testes.bat` a cada mudança maior no código, e
principalmente na noite anterior e na manhã da apresentação, com o
notebook e a rede que vocês vão usar de verdade -- é a forma mais
rápida de descobrir se algo quebrou antes que alguém da banca
descubra por vocês.
