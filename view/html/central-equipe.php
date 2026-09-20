<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user_id'])) {
    header('Location: acesso.php');
    exit;
}
require_once __DIR__ . '/../../model/Connection.php';
$db = Connection::getConnection();
$stmt = $db->prepare("SELECT is_admin FROM login WHERE id=:id");
$stmt->execute([':id' => $_SESSION['user_id']]);
if (!(bool)$stmt->fetchColumn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sadag — Central da equipe</title>
<style>
:root{color-scheme:dark;--bg:#070b13;--bg2:#0b1220;--panel:#111827;--border:rgba(255,255,255,.08);--text:#f4f7fb;--muted:#96a5bb;--accent:#20d7b2;--green:#36d67a;--yellow:#ffc857;--red:#ff5757}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;background:linear-gradient(180deg,var(--bg2),var(--bg));color:var(--text);font-family:'Inter',sans-serif}
header{padding:18px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
header strong{font-size:15px}
header a{color:var(--muted);font-size:13px}
.layout{display:grid;grid-template-columns:300px 1fr;height:calc(100vh - 61px)}
.list{border-right:1px solid var(--border);overflow-y:auto;padding:10px}
.ticket{display:block;width:100%;text-align:left;background:transparent;border:1px solid transparent;border-radius:10px;padding:10px 12px;margin-bottom:6px;cursor:pointer;color:var(--text)}
.ticket:hover{background:var(--panel)}
.ticket.active{background:var(--panel);border-color:var(--border)}
.ticket strong{display:block;font-size:13px}
.ticket span{display:block;font-size:11px;color:var(--muted);margin-top:3px}
.thread{display:flex;flex-direction:column;height:100%}
.thread-head{padding:16px 22px;border-bottom:1px solid var(--border)}
.thread-head h2{margin:0 0 4px;font-size:16px}
.thread-head p{margin:0;color:var(--muted);font-size:12px}
.messages{flex:1;overflow-y:auto;padding:20px 22px;display:flex;flex-direction:column;gap:10px}
.message{max-width:70%;padding:10px 13px;border-radius:12px;background:var(--panel);border:1px solid var(--border);font-size:13px}
.message.support{align-self:flex-end;background:rgba(32,215,178,.12);border-color:rgba(32,215,178,.3)}
.message strong{display:block;font-size:11px;color:var(--muted);margin-bottom:4px}
.message small{display:block;color:var(--muted);font-size:10px;margin-top:6px}
.compose{display:flex;gap:10px;padding:16px 22px;border-top:1px solid var(--border)}
.compose textarea{flex:1;background:var(--panel);border:1px solid var(--border);border-radius:10px;color:var(--text);padding:10px 12px;font:inherit;resize:none;min-height:44px}
.compose button{background:var(--accent);border:none;border-radius:10px;padding:0 18px;color:#062018;font-weight:700;cursor:pointer}
.empty{padding:40px;color:var(--muted);text-align:center}
@media(max-width:760px){.layout{grid-template-columns:1fr;height:auto}.list{border-right:0;border-bottom:1px solid var(--border);max-height:220px}.thread{height:auto}.messages{max-height:50vh}.message{max-width:88%}}
</style>
</head>
<body>
<header><strong>Central da equipe — Sadag</strong><a href="dashboard.php">← Voltar ao painel</a></header>
<div class="layout">
  <aside class="list" id="ticketList"></aside>
  <section class="thread">
    <div class="thread-head"><h2 id="threadTitle">Selecione um chamado</h2><p id="threadMeta"></p></div>
    <div class="messages" id="messages"><div class="empty">Nenhum chamado selecionado.</div></div>
    <form class="compose" id="replyForm" hidden>
      <textarea id="replyInput" placeholder="Digite sua resposta..." required></textarea>
      <button type="submit">Enviar</button>
    </form>
  </section>
</div>
<script>
const state={tickets:[],ticketId:null,lastId:0,poll:null};
async function api(path,opts){const r=await fetch(path,{headers:{'Content-Type':'application/json'},...opts});const d=await r.json().catch(()=>({}));if(!r.ok||d.success===false)throw new Error(d.message||'Erro na requisição');return d}
function esc(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}
function fmtDate(s){if(!s)return'';return new Date(s.replace(' ','T')).toLocaleString('pt-BR')}
async function loadTickets(){const d=await api('../../api/app.php?action=support_admin_list');state.tickets=d.tickets||[];renderTickets()}
function renderTickets(){document.getElementById('ticketList').innerHTML=state.tickets.length?state.tickets.map(t=>`<button class="ticket ${Number(t.id)===Number(state.ticketId)?'active':''}" data-id="${t.id}"><strong>#AG-${t.id} · ${esc(t.subject)}</strong><span>${esc(t.nome)} · ${esc(t.status)}</span></button>`).join(''):'<div class="empty">Nenhum chamado ainda.</div>'}
function renderMessages(list,append){const box=document.getElementById('messages');const html=list.map(m=>`<div class="message ${m.sender_type==='support'?'support':''}"><strong>${m.sender_type==='support'?'Equipe Sadag':m.sender_type==='ai'?'Sadag Assist':'Usuário'}</strong>${esc(m.message)}<small>${fmtDate(m.created_at)}</small></div>`).join('');if(append)box.insertAdjacentHTML('beforeend',html);else box.innerHTML=html||'<div class="empty">Nenhuma mensagem.</div>';box.scrollTop=box.scrollHeight;if(list.length)state.lastId=Math.max(state.lastId,...list.map(m=>m.id))}
async function openTicket(id){state.ticketId=id;state.lastId=0;renderTickets();document.getElementById('replyForm').hidden=false;const d=await api(`../../api/app.php?action=support_admin_messages&ticket_id=${id}`);document.getElementById('threadTitle').textContent=`#AG-${d.ticket.id} · ${d.ticket.subject}`;document.getElementById('threadMeta').textContent=`${d.ticket.nome} (${d.ticket.email}) · ${d.ticket.category} · status: ${d.ticket.status}`;renderMessages(d.messages,false);startPolling()}
function startPolling(){if(state.poll)clearInterval(state.poll);state.poll=setInterval(async()=>{if(!state.ticketId)return;try{const d=await api(`../../api/app.php?action=support_admin_messages&ticket_id=${state.ticketId}&after_id=${state.lastId}`);if(d.messages&&d.messages.length)renderMessages(d.messages,true)}catch(e){}},800)}
document.getElementById('ticketList').addEventListener('click',e=>{const b=e.target.closest('[data-id]');if(b)openTicket(Number(b.dataset.id))});
document.getElementById('replyForm').addEventListener('submit',async e=>{e.preventDefault();const input=document.getElementById('replyInput');const message=input.value.trim();if(!message||!state.ticketId)return;try{await api('../../api/app.php?action=support_admin_reply',{method:'POST',body:JSON.stringify({ticket_id:state.ticketId,message})});input.value='';const d=await api(`../../api/app.php?action=support_admin_messages&ticket_id=${state.ticketId}&after_id=${state.lastId}`);renderMessages(d.messages,true);await loadTickets()}catch(err){alert(err.message)}});
loadTickets();
setInterval(loadTickets,5000);
</script>
</body>
</html>
