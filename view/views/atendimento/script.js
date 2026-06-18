// Script inicial para `atendimento`

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('formAtendimento');

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    // Coletar dados do formulário
    const data = {
      nome: form.nome.value,
      email: form.email.value,
      telefone: form.telefone.value,
      assunto: form.assunto.value,
      mensagem: form.mensagem.value,
    };

    // Aqui você poderá chamar sua API (fetch) para enviar os dados ao backend
    // Exemplo básico (comente por enquanto):
    // fetch('/api/atendimento', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) })

    console.log('Dados do formulário:', data);
    alert('Solicitação preparada. Implemente envio ao backend quando estiver pronto.');
    form.reset();
  });
});

/*
  Dicas de estudo rápidas para começar em JS (frente a backend):
  1) Dom & Eventos — manipular formulários e enviar dados.
  2) Fetch API — fazer requisições a endpoints REST.
  3) Promises / async-await — controle assíncrono.
  4) Node.js básico — criar um servidor e rotas.
  5) Express.js — rotas, middleware e parsing de JSON.
  6) Bancos (Postgres / MySQL) e ORM (Prisma / Sequelize).
*/
