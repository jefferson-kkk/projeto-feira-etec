# ⚡ QUICK START - Aeris Secure

## 1️⃣ Setup (2 minutos)

### No VSCode - Extensão MySQL:
1. Clique em **MySQL**
2. Clique **+** → Configure: `localhost:3306 | root | senaisp`
3. Execute este SQL:

```sql
CREATE DATABASE IF NOT EXISTS `aeris` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

**Pronto!** As tabelas são criadas automaticamente.

---

## 2️⃣ Acessar (1 clique)

```
http://localhost/projeto-feira-etec/view/html/acesso.php
```

---

## 3️⃣ Fluxo Básico

| Ação | Vai Para | Banco |
|------|----------|-------|
| Cadastre-se | Dashboard | INSERT login |
| Login | Dashboard | SELECT login |
| Conectar Projeto | Dashboard | INSERT projects |
| Logout | Login | DESTROY SESSION |

---

## 🔐 Credenciais de Teste (Crie uma!)

1. Email: seu_email@exemplo.com
2. Senha: MínimoI6Car (min 6 caracteres)
3. Nome: Seu Nome Completo

---

## 📂 Estrutura

```
projeto-feira-etec/
├── view/html/
│   ├── acesso.php (Login/Cadastro)
│   ├── home.php (Dashboard)
│   ├── logout.php (Sair)
│   ├── project-connect.php (Adicionar projeto)
│   └── _auth.php (Proteção)
├── model/
│   ├── Connection.php (DB = aeris)
│   ├── LoginDAO.php (Tabela login)
│   └── ProjectDAO.php (Tabela projects)
├── Controller/
│   ├── Controller.php (Router)
│   └── ProjectController.php
├── RESUMO_FINAL.md (Este guia)
├── SETUP_BANCO_MYSQL.md (Setup detalhado)
└── verificar_banco.php (Testar banco)
```

---

## ✅ Validação

Acesse: `http://localhost/projeto-feira-etec/verificar_banco.php`

Deve aparecer:
- ✅ Conexão com MySQL estabelecida
- ✅ Banco atual: **aeris**
- ✅ Tabelas: login, projects

---

## 🛠️ Troubleshooting Rápido

| Problema | Solução |
|----------|---------|
| Banco não aparece | Clique **Refresh** na aba MySQL |
| Senha não funciona | Use min. 6 caracteres (números, letras) |
| Acesso negado (root) | Senha: `senaisp` |
| Nenhuma tabela criada | Acesse `verificar_banco.php` |
| Logout não funciona | Limpar cookies do navegador |

---

## 🎯 Próximos Passos

- ✅ Banco: **aeris** está rodando
- ⏭️ Adicionar avatar upload
- ⏭️ Integrar IoT (dispositivos ESP32)
- ⏭️ Dashboard com gráficos
- ⏭️ API REST para apps mobile

---

**Banco**: aeris ✅  
**PHP**: ✅ Funcionando  
**MySQ**L: ✅ Conectado  
**Fluxo**: ✅ Fluido

---

**Dúvidas rápidas:**
- Banco: `RESUMO_FINAL.md`
- Setup: `SETUP_BANCO_MYSQL.md`
- Validar: `verificar_banco.php`

🚀 **Pronto para usar!**
