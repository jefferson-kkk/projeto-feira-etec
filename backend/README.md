Backend folder — propósito e roteiro

Pastas sugeridas:
- api/       : rotas HTTP / pontos de entrada (Express, Fastify, etc.)
- controllers/: lógica de orquestração entre requests e modelos
- models/    : definições de entidades (Prisma schema, Sequelize models)
- db/        : scripts de conexão, migrações e seeds
- services/  : integrações externas (e.g. MQTT, sensores)

Roteiro rápido para começar (focado em virar backend):

1) JavaScript básico (1-2 semanas)
   - Variáveis, tipos, funções, objetos, arrays
   - Controle de fluxo (if/for/while)
   - Funções assíncronas, Promises, async/await

2) DOM e Fetch (prático, 1 semana)
   - Enviar formulários com `fetch()` para um endpoint local

3) Node.js (2 semanas)
   - `node` e `npm`/`pnpm`
   - Módulos (`require`/`import`), `process`, `fs`

4) Express (2 semanas)
   - Criar servidor, rotas, middleware, tratamento de erros
   - `express.json()` para receber JSON

5) Banco de dados (Postgres) e ORM (Prisma) (2-3 semanas)
   - Modelagem simples (users, tickets, atendimentos)
   - Conectar, migrar, inserir, consultar

6) Autenticação e autorização (JWT / session)

7) Boas práticas
   - Estrutura de pastas, variáveis de ambiente (.env), logging
   - Testes (unitários e integração)

Exemplo mínimo de fluxo para integrar o formulário de atendimento:
- Frontend (`fetch('/api/atendimentos', {method:'POST', body: JSON})`)
- API Express: rota POST /api/atendimentos -> valida dados -> chama controller
- Controller: transforma dados e chama model/service para salvar no DB
- Model/ORM: insere registro em tabela `atendimentos`

Links úteis:
- Node.js: https://nodejs.org/
- Express: https://expressjs.com/
- Prisma: https://www.prisma.io/
- Postgres: https://www.postgresql.org/

Boa prática: comece com endpoints simples e com testes manuais via `curl` ou `HTTPie`.
