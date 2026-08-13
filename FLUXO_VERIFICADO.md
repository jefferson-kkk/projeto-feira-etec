# Fluxo de Comunicação - Aeris Secure

## ✅ Banco de Dados Alterado
- **Database**: `aeris` (foi alterado de `login`)
- **Host**: localhost
- **User**: root
- **Password**: senaisp

## ✅ Fluxo de Navegação (Fluido)

### 1. Login (acesso.php)
- Usuário acessa `acesso.php`
- Se já estiver logado (SESSION), redireciona para `home.php`
- Realiza login via POST com email/senha
- Cria SESSION com `user_id`
- Redireciona para `home.php`

### 2. Home Page (home.php)
- Requer autenticação (`_auth.php`)
- Se não autenticado, redireciona para `acesso.php`
- Exibe dashboard com projetos do usuário
- Menu de perfil no topo com opções:
  - My Profile (`profile.php`)
  - Account Settings
  - Security
  - Monitor Project (`project-connect.php`)
  - **Logout** (`logout.php`) ✅

### 3. Logout (logout.php)
- Destroy SESSION
- Redireciona para `acesso.php`

### 4. Conectar Projeto (project-connect.php)
- Requer autenticação
- POST com dados do projeto
- Salva no banco `projects`
- Redireciona para `home.php`

### 5. Perfil (profile.php)
- Requer autenticação
- Acesso via menu de perfil
- Menu continua disponível para voltar pra home

## ✅ Tabelas do Banco "aeris"

### Tabela: `login`
```sql
id (INT, PK, Auto)
nome (VARCHAR 255)
senha (VARCHAR 255)
email (VARCHAR 255, UNIQUE)
username (VARCHAR 255, UNIQUE)
avatar (VARCHAR 255)
phone (VARCHAR 32)
biography (TEXT)
display_name (VARCHAR 255)
language (VARCHAR 16)
timezone (VARCHAR 64)
last_login (DATETIME)
email_verified (TINYINT)
notify_* (TINYINT) - notificações
datacriacao (DATETIME)
created_at, updated_at (TIMESTAMP)
```

### Tabela: `projects`
```sql
id (INT, PK, Auto)
user_id (INT, FK → login)
name (VARCHAR 255)
manufacturer_code (VARCHAR 64, UNIQUE)
registered_at (DATETIME)
status (VARCHAR 64)
firmware_version (VARCHAR 64)
wifi_status (VARCHAR 64)
esp32_id (VARCHAR 64)
last_online (DATETIME)
```

## ✅ Controllers Implementados

### LoginController
- `criarLogin()` - Cria novo usuário
- `lerLogin()` - Lista usuários
- `getLoginById()` - Obtém por ID
- `getLoginByEmail()` - Obtém por email
- `atualizarLogin()` - Atualiza perfil
- `atualizarSenha()` - Hash e atualiza
- `atualizarUltimoLogin()` - Registra último acesso

### ProjectController
- `getProjectsByUser()` - Lista projetos do usuário
- `createProject()` - Cria novo projeto
- `getProjectByCode()` - Busca por código do dispositivo

## ✅ Comunicação do Fluxo

```
acesso.php (LOGIN)
    ↓
  [POST email/senha]
    ↓
LoginController.getLoginByEmail() → DB query
    ↓
  [Verifica senha com password_verify()]
    ↓
  [Cria SESSION]
    ↓
home.php (DASHBOARD) ← Menu com Logout
    ↓ [Clica em Conectar Projeto]
    ↓
project-connect.php
    ↓
  [POST dados projeto]
    ↓
ProjectController.createProject() → DB insert
    ↓
home.php (retorna ao dashboard)
    ↓ [Clica em Logout]
    ↓
logout.php
    ↓
  [Destroy SESSION]
    ↓
acesso.php (volta ao login) ← Fluxo completo
```

## ✅ Verificações Implementadas

- ✅ Redirecionamento automático se não autenticado
- ✅ Redirecionamento para home se já autenticado ao acessar login
- ✅ Menu com logout em todas as páginas protegidas
- ✅ Passwords com hash seguro (PASSWORD_DEFAULT)
- ✅ Prepared statements para SQL injection protection
- ✅ HTML escaping para XSS protection
- ✅ Session regenerate após login
- ✅ Comunicação fluida entre páginas

## 📋 Próximos Passos (Opcional)

Se precisar adicionar mais funcionalidades:
1. Sistema de notificações em tempo real
2. Integração com dispositivos IoT
3. Dashboard de monitoramento
4. API REST para integração
5. Autenticação OAuth2

---
**Status**: ✅ Pronto para produção
**Banco de Dados**: aeris
**Última atualização**: 2026-08-13
