# 🔌 Configuração do Banco de Dados com Extensão MySQL do VSCode

## Pré-requisitos
- VSCode com extensão MySQL instalada
- MySQL Server rodando
- Credenciais: root / senaisp

## Passo 1: Adicionar Conexão no VSCode

1. Abra VSCode
2. Vá para a aba **MySQL** (extensão)
3. Clique em **+** para adicionar nova conexão
4. Preencha com:
   - **Host**: localhost
   - **Port**: 3306
   - **Username**: root
   - **Password**: senaisp
   - **Default Database**: (deixe vazio por enquanto)

## Passo 2: Criar o Banco "aeris"

Execute este comando SQL:

```sql
CREATE DATABASE IF NOT EXISTS `aeris` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `aeris`;
```

## Passo 3: Criar Tabela de Usuários

Execute este comando:

```sql
CREATE TABLE IF NOT EXISTS `login` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(255) NOT NULL,
    `senha` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `username` VARCHAR(255) UNIQUE,
    `display_name` VARCHAR(255),
    `avatar` VARCHAR(255),
    `phone` VARCHAR(32),
    `biography` TEXT,
    `language` VARCHAR(16) NOT NULL DEFAULT 'en',
    `timezone` VARCHAR(64) NOT NULL DEFAULT 'UTC',
    `last_login` DATETIME,
    `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `notify_email` TINYINT(1) NOT NULL DEFAULT 1,
    `notify_security` TINYINT(1) NOT NULL DEFAULT 1,
    `notify_system` TINYINT(1) NOT NULL DEFAULT 1,
    `notify_project` TINYINT(1) NOT NULL DEFAULT 1,
    `notify_alerts` TINYINT(1) NOT NULL DEFAULT 1,
    `datacriacao` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE UNIQUE INDEX `unique_login_username` ON `login` (`username`);
```

## Passo 4: Criar Tabela de Projetos

Execute este comando:

```sql
CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `manufacturer_code` VARCHAR(64) NOT NULL UNIQUE,
    `registered_at` DATETIME NOT NULL,
    `status` VARCHAR(64) NOT NULL DEFAULT 'offline',
    `firmware_version` VARCHAR(64),
    `wifi_status` VARCHAR(64),
    `esp32_id` VARCHAR(64),
    `last_online` DATETIME,
    FOREIGN KEY (`user_id`) REFERENCES `login` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

## Passo 5: Testar a Conexão

Abra no navegador:
```
http://localhost/projeto-feira-etec/verificar_banco.php
```

Você verá:
- ✅ Conexão com MySQL estabelecida
- ✅ Banco atual: **aeris**
- ✅ Tabelas criadas
- ✅ Estrutura de colunas

## Passo 6: Acessar a Aplicação

Abra:
```
http://localhost/projeto-feira-etec/view/html/acesso.php
```

## 🔐 Fluxo de Autenticação

```
1. Usuário acessa acesso.php (login)
   ↓
2. Submete email e senha via POST
   ↓
3. LoginController.getLoginByEmail() busca no banco
   ↓
4. Verifica senha com password_verify()
   ↓
5. Cria SESSION com user_id
   ↓
6. Redireciona para home.php (dashboard)
   ↓
7. Usuário vê seus projetos e menu de perfil
   ↓
8. Pode clicar "Conectar projeto" para adicionar novos
   ↓
9. Clica "Logout" para sair (destroy SESSION)
   ↓
10. Volta a acesso.php
```

## 📊 Gerenciar Dados no VSCode

Com a extensão MySQL instalada:

1. Clique na conexão criada
2. Expanda o banco `aeris`
3. Clique com botão direito na tabela
4. Opções:
   - **Show Table Record**: Ver dados
   - **Edit Record**: Editar
   - **New Record**: Inserir novo
   - **Delete Table**: Apagar tabela
   - **Run Query**: Executar SQL

## 🆘 Troubleshooting

**Erro: "Connection refused"**
- Verifique se MySQL está rodando
- Windows: `services.msc` e procure por MySQL

**Erro: "Access denied for user 'root'@'localhost'**
- Verifique a senha (senaisp)
- Resetar senha do root:
  ```powershell
  cd C:\Program Files\MySQL\MySQL Server 8.0\bin
  .\mysqld.exe --skip-grant-tables
  # Em outro terminal:
  mysql -u root
  FLUSH PRIVILEGES;
  ALTER USER 'root'@'localhost' IDENTIFIED BY 'senaisp';
  EXIT;
  ```

**Banco não aparece na extensão**
- Clique em "Refresh" na extensão MySQL
- Ou desconecte e reconecte

---
**Banco**: aeris ✅
**Status**: Pronto para uso
