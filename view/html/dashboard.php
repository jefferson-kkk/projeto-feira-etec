<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user_id']) && empty($_SESSION['id']) && empty($_SESSION['login_id'])) {
    header('Location: acesso.php');
    exit;
}
$userName = htmlspecialchars($_SESSION['usuario'] ?? 'Usuário', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sadag — Monitoramento</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
<style>
:root{color-scheme:dark;--bg:#070b13;--bg2:#0b1220;--panel:#111827;--panel2:#0e1625;--border:rgba(255,255,255,.08);--border2:rgba(255,255,255,.14);--text:#f4f7fb;--muted:#96a5bb;--faint:#5d6b80;--accent:#20d7b2;--accent2:#42e8c8;--green:#36d67a;--yellow:#ffc857;--orange:#ff8b42;--red:#ff5757;--blue:#5b8cff;--sidebar:228px;--radius:12px;--mono:'JetBrains Mono',monospace;--display:'Sora',sans-serif;--body:'Inter',sans-serif}
*{box-sizing:border-box}html,body{margin:0;min-height:100%;background:radial-gradient(circle at 15% 0,rgba(32,215,178,.09),transparent 28%),radial-gradient(circle at 90% 10%,rgba(91,140,255,.08),transparent 28%),linear-gradient(180deg,var(--bg2),var(--bg))}body{color:var(--text);font-family:var(--body);overflow-x:hidden}button,input,select,textarea{font:inherit}button{color:inherit}a{text-decoration:none;color:inherit}
.sidebar-toggle{position:fixed;top:14px;left:14px;z-index:80;width:34px;height:34px;border-radius:9px;background:rgba(17,24,39,.92);border:1px solid var(--border);color:var(--text);font-size:15px;line-height:1;cursor:pointer;backdrop-filter:blur(10px);box-shadow:0 8px 20px rgba(0,0,0,.25)}
.sidebar-toggle:hover{background:rgba(255,255,255,.1)}
.app.sidebar-collapsed{grid-template-columns:0 minmax(0,1fr)}
.app.sidebar-collapsed .sidebar{padding:0;overflow:hidden;border-right:0;opacity:0;pointer-events:none}
.app{transition:grid-template-columns .25s ease}
.sidebar{transition:opacity .15s ease}
@media(prefers-reduced-motion:reduce){.app,.sidebar{transition:none}}
.app{min-height:100vh;display:grid;grid-template-columns:var(--sidebar) minmax(0,1fr)}.sidebar{position:sticky;top:0;height:100vh;padding:18px 14px;border-right:1px solid var(--border);background:rgba(5,10,18,.82);backdrop-filter:blur(22px);display:flex;flex-direction:column;gap:20px}.brand{display:flex;align-items:center;gap:10px;padding:5px 8px;font-family:var(--display);font-weight:800}.brand-mark{width:34px;height:34px;border-radius:10px;background:rgba(32,215,178,.1);border:1px solid rgba(32,215,178,.35);display:grid;place-items:center;color:var(--accent);box-shadow:0 0 24px rgba(32,215,178,.12)}.brand small{display:block;color:var(--muted);font:600 9px var(--body);letter-spacing:.16em;text-transform:uppercase}.brand strong{display:block;font-size:15px}
.nav{display:grid;gap:5px}.nav-button{border:0;background:transparent;border-radius:10px;padding:11px 12px;display:flex;align-items:center;gap:11px;color:var(--muted);cursor:pointer;font-weight:600;text-align:left;transition:.18s}.nav-button:hover{background:rgba(255,255,255,.04);color:var(--text)}.nav-button.active{background:linear-gradient(90deg,rgba(32,215,178,.16),rgba(32,215,178,.035));color:var(--accent);box-shadow:inset 2px 0 var(--accent)}.nav-icon{width:25px;height:25px;border:1px solid var(--border2);border-radius:8px;display:grid;place-items:center;font-size:12px;background:rgba(255,255,255,.025)}
.sidebar-bottom{margin-top:auto;display:grid;gap:10px}.system-box{border:1px solid var(--border);border-radius:var(--radius);padding:12px;background:rgba(255,255,255,.025)}.system-box small{color:var(--muted);display:block;margin-bottom:7px}.live-line{display:flex;align-items:center;gap:8px;color:var(--green);font-size:12px;font-weight:700}.dot{width:7px;height:7px;border-radius:50%;background:currentColor;box-shadow:0 0 0 5px rgba(54,214,122,.09)}.version{margin-top:10px;padding-top:10px;border-top:1px solid var(--border);font-size:11px;color:var(--faint)}
.main{min-width:0;padding:18px 22px 30px}.view{display:none;animation:enter .25s ease}.view.active{display:block}@keyframes enter{from{opacity:0;transform:translateY(5px)}to{opacity:1;transform:none}}
.topbar{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:16px}.title h1{font:800 clamp(22px,2vw,30px)/1.1 var(--display);margin:0 0 5px;letter-spacing:-.03em}.title p{margin:0;color:var(--muted);font-size:12px}.toolbar{display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:flex-end}.btn{border:1px solid var(--border2);border-radius:9px;background:rgba(255,255,255,.04);padding:9px 12px;color:var(--text);cursor:pointer;font-size:12px;font-weight:700;transition:.18s;display:inline-flex;align-items:center;justify-content:center;gap:7px}.btn:hover{border-color:rgba(32,215,178,.38);background:rgba(32,215,178,.07);transform:translateY(-1px)}.btn.primary{background:linear-gradient(135deg,var(--accent),#30e0a9);border-color:transparent;color:#03150f}.btn.danger{color:#ffb0b0;border-color:rgba(255,87,87,.3);background:rgba(255,87,87,.08)}.btn.small{padding:7px 9px;font-size:11px}.btn:disabled{opacity:.5;cursor:not-allowed;transform:none}
.card{background:linear-gradient(180deg,rgba(255,255,255,.035),transparent),rgba(14,22,37,.88);border:1px solid var(--border);border-radius:var(--radius);box-shadow:0 20px 60px rgba(0,0,0,.18);backdrop-filter:blur(18px)}.pad{padding:18px}.card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}.card-title{font-size:13px;text-transform:uppercase;letter-spacing:.04em;margin:0;font-weight:800}.card-sub{color:var(--muted);font-size:11px;margin:5px 0 0}.pill{display:inline-flex;align-items:center;gap:7px;border:1px solid var(--border);background:rgba(255,255,255,.035);padding:6px 9px;border-radius:999px;font-size:10px;color:var(--muted);white-space:nowrap}.pill.live{color:var(--green)}.pill.warn{color:var(--yellow)}.pill.bad{color:var(--red)}
.device-bar{display:grid;grid-template-columns:minmax(220px,1fr) auto auto;gap:10px;align-items:end;margin-bottom:14px}.field{display:grid;gap:6px}.field label{font-size:10px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.05em}.input{width:100%;border:1px solid var(--border);border-radius:9px;background:#0b1321;color:var(--text);padding:10px 11px;outline:none}.input:focus{border-color:rgba(32,215,178,.55);box-shadow:0 0 0 4px rgba(32,215,178,.08)}textarea.input{min-height:110px;resize:vertical}.device-status{display:flex;align-items:center;gap:8px;height:38px;padding:0 12px;border:1px solid var(--border);border-radius:9px;color:var(--muted);font-size:11px}
.empty{min-height:68vh;display:grid;place-items:center}.empty-card{max-width:680px;text-align:center;padding:48px 34px}.empty-icon{width:68px;height:68px;margin:0 auto 18px;border-radius:20px;border:1px solid rgba(32,215,178,.25);background:rgba(32,215,178,.08);display:grid;place-items:center;font-size:30px}.empty-card h2{font:800 25px var(--display);margin:0 0 8px}.empty-card p{max-width:540px;margin:0 auto 22px;color:var(--muted);line-height:1.7;font-size:13px}.empty-actions{display:flex;justify-content:center;gap:9px;flex-wrap:wrap}
.dashboard-grid{display:grid;grid-template-columns:365px minmax(0,1fr);gap:14px;align-items:start}.stack{display:grid;gap:14px}.status-card{min-height:450px}.gauge{position:relative;width:min(100%,320px);margin:10px auto 0}.gauge svg{width:100%;height:auto;overflow:visible}.gauge-center{position:absolute;left:50%;top:92px;transform:translateX(-50%);text-align:center;width:220px}.status-icon{width:40px;height:40px;border-radius:50%;display:grid;place-items:center;margin:0 auto 7px;background:rgba(54,214,122,.08);font-size:18px;color:var(--status-color,var(--green))}.status-label{font:800 16px var(--display);text-transform:uppercase;color:var(--status-color,var(--green))}.status-message{color:var(--muted);font-size:11px;margin:5px 0 9px}.ppm{font:800 45px var(--mono);line-height:1}.ppm-unit{color:var(--muted);font-size:12px;margin-top:3px}.gauge-scale{display:flex;justify-content:space-between;padding:0 32px;color:var(--faint);font-size:10px;margin-top:-12px}.meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px}.meta{border:1px solid var(--border);background:rgba(255,255,255,.025);border-radius:9px;padding:10px}.meta span{display:block;color:var(--muted);font-size:9px;text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px}.meta strong{font-size:11px;overflow-wrap:anywhere}
.chart-card{padding:0;overflow:hidden}.chart-head{padding:17px 18px 0}.chart-actions{display:flex;gap:6px;align-items:center;flex-wrap:wrap}.range.active{color:var(--accent);border-color:rgba(32,215,178,.35);background:rgba(32,215,178,.07)}.chart-wrap{height:310px;padding:12px 14px 0;position:relative}.chart-wrap svg{width:100%;height:100%}.chart-tip{position:absolute;display:none;pointer-events:none;padding:7px 9px;background:#070c15;border:1px solid var(--border2);border-radius:8px;font-size:10px;box-shadow:0 12px 30px rgba(0,0,0,.35)}.chart-tip.show{display:grid;gap:2px}.chart-tip strong{font:700 12px var(--mono)}.summary{display:grid;grid-template-columns:repeat(4,1fr);border-top:1px solid var(--border)}.summary-cell{padding:12px 14px;border-right:1px solid var(--border)}.summary-cell:last-child{border-right:0}.summary-cell span{display:block;color:var(--muted);font-size:9px;text-transform:uppercase}.summary-cell strong{font:700 18px var(--mono)}.summary-cell small{color:var(--muted)}
.metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}.metrics-2{grid-template-columns:repeat(2,1fr)}.metric{padding:13px;display:grid;gap:8px}.metric-top{display:flex;gap:9px;align-items:center}.metric-icon{width:30px;height:30px;border-radius:9px;display:grid;place-items:center;background:rgba(32,215,178,.08);color:var(--accent);font:700 10px var(--mono)}.metric span{color:var(--muted);font-size:10px}.metric strong{display:block;font:700 18px var(--mono);margin-top:2px}.metric small{color:var(--faint);font-size:9px}.progress{height:4px;background:rgba(255,255,255,.06);border-radius:99px;overflow:hidden}.progress i{display:block;height:100%;background:linear-gradient(90deg,var(--accent),var(--blue));border-radius:inherit}
.events{display:grid;gap:9px}.event{display:grid;grid-template-columns:8px 1fr auto;gap:9px;align-items:start}.event i{width:7px;height:7px;border-radius:50%;margin-top:4px;background:var(--green);box-shadow:0 0 0 5px rgba(54,214,122,.08)}.event.warn i{background:var(--yellow)}.event.bad i{background:var(--red)}.event strong{font-size:11px}.event p{margin:2px 0 0;color:var(--muted);font-size:10px}.event time{color:var(--faint);font:500 9px var(--mono)}
.sensor-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.sensor{padding:13px}.sensor header{display:flex;justify-content:space-between;gap:8px;align-items:center}.sensor h3{font-size:12px;margin:0}.reading{display:flex;align-items:baseline;gap:5px;margin:9px 0}.reading strong{font:700 24px var(--mono)}.reading span{font-size:10px;color:var(--muted)}.sensor-foot{display:flex;justify-content:space-between;align-items:center;color:var(--muted);font-size:9px}.online{color:var(--green)}.offline{color:var(--red)}
.table-card{overflow:auto}.table{width:100%;border-collapse:collapse;min-width:720px}.table th,.table td{padding:11px 13px;border-bottom:1px solid var(--border);text-align:left;font-size:11px}.table th{font-size:9px;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);background:rgba(255,255,255,.025);cursor:default}.table tr:hover td{background:rgba(255,255,255,.018)}.status{display:inline-flex;padding:5px 8px;border-radius:999px;font-size:9px;font-weight:800;text-transform:uppercase}.status.normal{color:var(--green);background:rgba(54,214,122,.08)}.status.atencao{color:var(--yellow);background:rgba(255,200,87,.08)}.status.perigo{color:var(--red);background:rgba(255,87,87,.08)}.filters{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:8px;margin-bottom:12px}.pagination{display:flex;align-items:center;justify-content:space-between;padding:12px;color:var(--muted);font-size:10px}
.report-summary{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:12px}.report-box{padding:13px}.report-box span{display:block;color:var(--muted);font-size:9px;text-transform:uppercase}.report-box strong{font:700 19px var(--mono);display:block;margin-top:5px}.report-table{margin-top:12px}
.settings-layout{display:grid;grid-template-columns:200px minmax(0,1fr);gap:12px}.settings-nav{padding:10px;display:grid;gap:5px;height:max-content}.settings-tab{border:0;background:transparent;color:var(--muted);text-align:left;padding:10px;border-radius:8px;cursor:pointer;font-size:11px;font-weight:700}.settings-tab.active,.settings-tab:hover{background:rgba(32,215,178,.08);color:var(--accent)}.settings-pane{display:none}.settings-pane.active{display:block}.form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}.full{grid-column:1/-1}.switch-list{display:grid;gap:8px}.switch{display:flex;justify-content:space-between;gap:15px;align-items:center;padding:13px;border:1px solid var(--border);border-radius:9px;background:rgba(255,255,255,.02)}.switch strong{font-size:11px}.switch span{display:block;color:var(--muted);font-size:9px;font-weight:400;margin-top:3px}.switch input{appearance:none;width:40px;height:22px;border-radius:99px;background:#263244;position:relative;cursor:pointer}.switch input:before{content:'';position:absolute;width:16px;height:16px;left:3px;top:3px;border-radius:50%;background:#9aa8bd;transition:.2s}.switch input:checked{background:rgba(32,215,178,.35)}.switch input:checked:before{left:21px;background:var(--accent)}
.profile{display:flex;align-items:center;gap:9px;position:relative}.avatar{width:34px;height:34px;border-radius:10px;border:1px solid var(--border2);background:rgba(255,255,255,.04);display:grid;place-items:center;font-size:17px;cursor:pointer}.profile-name{display:grid}.profile-name strong{font-size:11px}.profile-name span{font-size:9px;color:var(--muted)}.profile-menu{position:absolute;right:0;top:44px;width:210px;padding:8px;display:none;z-index:30}.profile-menu.open{display:block}.profile-menu button{width:100%;border:0;background:transparent;text-align:left;padding:9px;border-radius:8px;cursor:pointer;font-size:11px}.profile-menu button:hover{background:rgba(255,255,255,.05)}
.notification{position:relative}.bell{width:34px;height:34px;border:1px solid var(--border2);border-radius:10px;background:rgba(255,255,255,.04);cursor:pointer}.badge{position:absolute;right:-3px;top:-4px;min-width:15px;height:15px;border-radius:99px;background:var(--red);color:#fff;font:700 8px var(--mono);display:grid;place-items:center;padding:0 4px}.notification-panel{position:absolute;right:0;top:44px;width:320px;max-height:420px;overflow:auto;padding:10px;display:none;z-index:30}.notification-panel.open{display:block}.notice{padding:10px;border-bottom:1px solid var(--border)}.notice:last-child{border-bottom:0}.notice strong{font-size:10px}.notice p{margin:3px 0;color:var(--muted);font-size:9px;line-height:1.5}.notice time{color:var(--faint);font:9px var(--mono)}
.plan-layout{display:grid;grid-template-columns:1fr 1fr;gap:13px;align-items:start}
.plan-card{min-height:480px;display:flex;flex-direction:column}
.plan-editor{position:relative;margin-top:12px;flex:1;min-height:400px;border:1px solid var(--border);border-radius:12px;background:
  linear-gradient(rgba(255,255,255,.035) 1px,transparent 1px),
  linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px),
  #0b1110;background-size:32px 32px;overflow:hidden;touch-action:none}
.plan-room{fill:rgba(53,220,135,.14);stroke:rgba(53,220,135,.55);stroke-width:1.5;cursor:grab}
.plan-room:active{cursor:grabbing}
.plan-room-label{fill:#dce8e2;font:700 11px "Inter",sans-serif;pointer-events:none}
.plan-room-handle{fill:#35dc87;cursor:nwse-resize}
.plan-device-dot{stroke:#06120b;stroke-width:1.5}
.plan-3d{position:relative;margin-top:12px;flex:1;min-height:400px;border:1px solid var(--border);border-radius:12px;overflow:hidden;background:#070b0a}
.plan-3d canvas{display:block;width:100%;height:100%}
.plan-tooltip{position:absolute;pointer-events:none;padding:6px 10px;border-radius:8px;background:rgba(7,14,11,.92);border:1px solid rgba(53,220,135,.3);font-size:10px;color:#dce8e2;display:none;white-space:nowrap;z-index:5}
@media(max-width:1100px){.plan-layout{grid-template-columns:1fr}}
.house-overview{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(220px,1fr);gap:14px;margin-bottom:14px}
.house-card{display:flex;flex-direction:column;padding:16px}
.plan-3d-fill{width:100%;height:100%}
.house-card .plan-3d{margin-top:12px;flex:1;min-height:340px}
.house-cards{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;align-content:start}
.house-mini{display:flex;flex-direction:column;gap:6px}
.house-mini span{font-size:10.5px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em}
.house-mini strong{font-size:16px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
@media(max-width:1100px){.house-overview{grid-template-columns:1fr}.house-cards{grid-template-columns:repeat(3,1fr)}}
@media(max-width:640px){.house-cards{grid-template-columns:repeat(2,1fr)}}
.support-layout{display:grid;grid-template-columns:250px minmax(0,1fr) 290px;gap:12px}.tickets{display:grid;gap:6px;margin-top:12px}.ticket{border:1px solid var(--border);background:transparent;border-radius:9px;padding:10px;text-align:left;cursor:pointer}.ticket.active,.ticket:hover{background:rgba(32,215,178,.06);border-color:rgba(32,215,178,.2)}.ticket strong{font-size:10px;display:block}.ticket span{display:block;color:var(--muted);font-size:9px;margin-top:3px}.messages{height:420px;overflow:auto;padding:16px;display:grid;align-content:start;gap:10px}.message{max-width:78%;padding:10px;border-radius:10px;background:rgba(255,255,255,.04);border:1px solid var(--border)}.message.me{margin-left:auto;background:rgba(32,215,178,.08);border-color:rgba(32,215,178,.18)}.message strong{font-size:9px}.message p{font-size:10px;line-height:1.55;margin:4px 0}.message small{color:var(--faint);font-size:8px}.compose{display:grid;grid-template-columns:1fr auto;gap:8px;padding:12px;border-top:1px solid var(--border)}.contact{display:grid;gap:8px}.contact .meta strong{font-size:10px}
.modal{position:fixed;inset:0;background:rgba(0,0,0,.68);backdrop-filter:blur(7px);display:none;place-items:center;padding:18px;z-index:50}.modal.open{display:grid}.modal-card{width:min(760px,96vw);max-height:90vh;overflow:auto}.modal-title{font:800 18px var(--display);margin:0}.modal-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:4px}.icon-grid{display:grid;grid-template-columns:repeat(8,1fr);gap:7px}.icon-choice{border:1px solid var(--border);background:rgba(255,255,255,.025);border-radius:9px;padding:10px;cursor:pointer;font-size:20px}.icon-choice.active,.icon-choice:hover{border-color:rgba(32,215,178,.4);background:rgba(32,215,178,.08)}
.toast{position:fixed;right:20px;bottom:20px;z-index:100;background:#0a111d;border:1px solid var(--border2);border-radius:10px;padding:11px 14px;box-shadow:0 15px 40px rgba(0,0,0,.4);font-size:11px;transform:translateY(20px);opacity:0;pointer-events:none;transition:.2s}.toast.show{opacity:1;transform:none}
@media(max-width:1150px){:root{--sidebar:190px}.dashboard-grid{grid-template-columns:1fr}.metrics{grid-template-columns:repeat(2,1fr)}.support-layout{grid-template-columns:1fr 1fr}.support-layout>:last-child{grid-column:1/-1}.report-summary{grid-template-columns:repeat(3,1fr)}}
@media(max-width:780px){.app{grid-template-columns:1fr}.sidebar{position:static;height:auto;padding:10px;display:block}.brand{margin-bottom:8px}.nav{grid-template-columns:repeat(3,1fr)}.nav-button{justify-content:center;padding:8px;font-size:10px}.nav-button span:last-child{display:none}.sidebar-bottom{display:none}.main{padding:14px}.topbar{align-items:flex-start;flex-direction:column}.toolbar{justify-content:flex-start}.device-bar,.filters,.settings-layout,.support-layout,.form-grid{grid-template-columns:1fr}.metrics,.sensor-grid,.report-summary{grid-template-columns:1fr 1fr}.summary{grid-template-columns:1fr 1fr}.summary-cell{border-bottom:1px solid var(--border)}.profile-name{display:none}.notification-panel,.profile-menu{right:-5px;width:min(320px,calc(100vw - 28px))}.icon-grid{grid-template-columns:repeat(4,1fr)}}
@media(max-width:500px){.metrics,.sensor-grid,.report-summary{grid-template-columns:1fr}.title p{max-width:90vw}}

/* =========================================================
   SADAG — PRODUCT UI / VISUAL SYSTEM REFRESH
   Design direction: calm density + editorial hierarchy +
   technical atmosphere. No functional selectors changed.
   ========================================================= */
:root{
  --bg:#070b0a;
  --bg2:#0b1110;
  --panel:#101715;
  --panel2:#0d1412;
  --panel3:#121b18;
  --border:rgba(229,255,243,.085);
  --border2:rgba(229,255,243,.15);
  --text:#f1f6f3;
  --muted:#93a39d;
  --faint:#5d6c66;
  --accent:#35dc87;
  --accent2:#79efad;
  --green:#35dc87;
  --yellow:#e9bd5e;
  --orange:#ef8b52;
  --red:#ff6666;
  --blue:#76a8ff;
  --sidebar:248px;
  --radius:16px;
  --mono:'JetBrains Mono',monospace;
  --display:'Sora',sans-serif;
  --body:'Inter',sans-serif;
}
html,body{
  background:
    radial-gradient(700px 420px at 0% 0%,rgba(53,220,135,.085),transparent 68%),
    radial-gradient(700px 500px at 100% 100%,rgba(53,220,135,.055),transparent 70%),
    linear-gradient(135deg,#070b0a 0%,#0a100e 48%,#070b0a 100%);
}
body{position:relative;isolation:isolate}
body:before{
  content:"";position:fixed;inset:0;pointer-events:none;z-index:-1;opacity:.28;
  background-image:linear-gradient(rgba(255,255,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.025) 1px,transparent 1px);
  background-size:42px 42px;
  mask-image:linear-gradient(to bottom,rgba(0,0,0,.9),transparent 86%);
}
body:after{
  content:"";position:fixed;right:-160px;bottom:-180px;width:520px;height:520px;border-radius:50%;pointer-events:none;z-index:-1;
  background:radial-gradient(circle,rgba(53,220,135,.10),transparent 66%);filter:blur(10px);
}
.app{grid-template-columns:var(--sidebar) minmax(0,1fr);max-width:1720px;margin:0 auto;min-height:100vh}
.sidebar{
  position:sticky;top:0;height:100vh;padding:24px 16px 18px;
  background:linear-gradient(180deg,rgba(8,14,12,.93),rgba(7,11,10,.76));
  border-right:1px solid rgba(229,255,243,.075);backdrop-filter:blur(24px);
  box-shadow:18px 0 60px rgba(0,0,0,.12);gap:24px;z-index:20;
}
.sidebar:after{content:"";position:absolute;left:16px;right:16px;bottom:128px;height:1px;background:linear-gradient(90deg,transparent,rgba(53,220,135,.2),transparent)}
.brand{padding:4px 8px 14px;gap:12px;border-bottom:1px solid rgba(229,255,243,.06)}
.brand-mark{width:40px;height:40px;border-radius:13px;background:linear-gradient(145deg,#48e89a,#1ab96d);color:#06110b;border:0;box-shadow:0 12px 30px rgba(53,220,135,.16);font-size:18px}
.brand small{font-size:8px;color:#71817a;letter-spacing:.2em}.brand strong{font-size:16px;letter-spacing:-.025em}
.nav{gap:3px}.nav:before{content:"NAVEGAÇÃO";padding:0 12px 6px;color:#52615b;font:700 8px var(--body);letter-spacing:.18em}
.nav-button{position:relative;border-radius:11px;padding:11px 12px;color:#7f8e88;font-size:11px;letter-spacing:-.01em}
.nav-button:after{content:"";position:absolute;right:9px;width:4px;height:4px;border-radius:50%;background:transparent;transition:.18s}
.nav-button:hover{background:rgba(255,255,255,.035);color:#e4ece8}.nav-button.active{background:linear-gradient(90deg,rgba(53,220,135,.13),rgba(53,220,135,.025));color:#a9f5c9;box-shadow:inset 2px 0 #35dc87}.nav-button.active:after{background:#35dc87;box-shadow:0 0 12px rgba(53,220,135,.65)}
.nav-icon{width:29px;height:29px;border-radius:9px;background:rgba(255,255,255,.025);border-color:rgba(255,255,255,.07);font-size:11px}.nav-button.active .nav-icon{background:rgba(53,220,135,.09);border-color:rgba(53,220,135,.2);color:#63e99a}
.sidebar-bottom{gap:12px}.system-box{border-radius:13px;background:linear-gradient(145deg,rgba(53,220,135,.065),rgba(255,255,255,.018));padding:13px;border-color:rgba(53,220,135,.13)}.system-box small{font-size:8px;letter-spacing:.14em;text-transform:uppercase}.live-line{font-size:11px}.version{font-size:9px}
.main{padding:28px 32px 44px;position:relative;min-width:0}
.main:before{content:"SADAG / CONTROL SURFACE";position:absolute;top:15px;right:32px;color:#34423d;font:700 8px var(--mono);letter-spacing:.16em}
.topbar{margin-bottom:18px;min-height:58px}.title{min-width:0}.title h1{font-size:clamp(26px,2.4vw,35px);letter-spacing:-.055em}.title p{font-size:12px;max-width:650px;line-height:1.7;color:#81918a}.toolbar{gap:9px}
.btn{border-radius:10px;padding:10px 13px;background:rgba(255,255,255,.025);border-color:rgba(229,255,243,.105);font-size:10px;box-shadow:0 8px 24px rgba(0,0,0,.08)}.btn:hover{background:rgba(53,220,135,.06);border-color:rgba(53,220,135,.28);box-shadow:0 10px 28px rgba(0,0,0,.15)}.btn.primary{background:linear-gradient(135deg,#43e696,#20c777);box-shadow:0 12px 30px rgba(53,220,135,.12);color:#06120b}.btn.primary:hover{filter:saturate(1.05);box-shadow:0 16px 38px rgba(53,220,135,.2)}
.card{background:linear-gradient(145deg,rgba(255,255,255,.038),rgba(255,255,255,.012) 55%,rgba(53,220,135,.018)),rgba(12,19,17,.92);border-color:rgba(229,255,243,.075);border-radius:var(--radius);box-shadow:0 24px 70px rgba(0,0,0,.16);backdrop-filter:blur(18px)}
.card:hover{border-color:rgba(229,255,243,.105)}
.card-title{font-size:11px;letter-spacing:.08em}.card-sub{font-size:10px;line-height:1.55;color:#73827c}
.device-bar{padding:10px;border:1px solid rgba(229,255,243,.07);border-radius:14px;background:rgba(255,255,255,.018);grid-template-columns:minmax(240px,1fr) auto auto;gap:9px;margin-bottom:13px;box-shadow:0 14px 40px rgba(0,0,0,.1)}
.field label{font-size:8px;letter-spacing:.14em;color:#6f7e78}.input{border-radius:10px;background:#0b1110;border-color:rgba(229,255,243,.085);padding:11px 12px;font-size:11px}.input:focus{border-color:rgba(53,220,135,.48);box-shadow:0 0 0 4px rgba(53,220,135,.065)}.device-status{height:40px;border-radius:10px;background:rgba(53,220,135,.025);border-color:rgba(53,220,135,.11);padding:0 13px}
/* Whole-page overview: establishes hierarchy before any individual widget */
.aeris-overview{position:relative;display:grid;grid-template-columns:minmax(0,1fr) minmax(360px,.75fr);gap:20px;align-items:center;margin:0 0 14px;padding:14px 22px;border:1px solid rgba(229,255,243,.07);border-radius:16px;overflow:hidden;background:linear-gradient(110deg,rgba(53,220,135,.055),rgba(255,255,255,.018) 48%,rgba(53,220,135,.025));box-shadow:0 24px 70px rgba(0,0,0,.14)}
.overview-main h2{margin:4px 0 0;font:700 18px/1.1 var(--display);letter-spacing:-.03em;color:#dce8e2}.overview-main h2 em{font-style:normal;color:#57e899}
.sync-age{margin-left:10px;padding:2px 8px;border-radius:99px;background:rgba(53,220,135,.1);color:#8fe8b6;font:700 8px var(--mono);letter-spacing:.06em;text-transform:none}
.aeris-overview:before{content:"";position:absolute;width:280px;height:280px;left:-130px;top:-150px;border-radius:50%;background:radial-gradient(circle,rgba(53,220,135,.13),transparent 68%);pointer-events:none}.aeris-overview:after{content:"01";position:absolute;right:22px;top:16px;color:rgba(143,163,153,.12);font:700 58px/1 var(--mono);letter-spacing:-.08em}
.overview-main{position:relative;z-index:1}.overview-kicker{display:flex;align-items:center;gap:8px;color:#7f9289;font:700 8px var(--mono);letter-spacing:.16em;margin-bottom:9px}.live-dot{width:6px;height:6px;border-radius:50%;background:#35dc87;box-shadow:0 0 0 5px rgba(53,220,135,.08),0 0 14px rgba(53,220,135,.45)}
.overview-main h2{margin:0;font:700 clamp(21px,2.2vw,31px)/1.08 var(--display);letter-spacing:-.055em;max-width:760px}.overview-main h2 em{font-style:normal;color:#57e899}.overview-main p{margin:9px 0 0;color:#84938d;font-size:11px;line-height:1.65;max-width:690px}.overview-facts{position:relative;z-index:1;display:grid;grid-template-columns:repeat(3,1fr);gap:7px;align-self:stretch}.overview-fact{min-width:0;padding:11px 12px;border:1px solid rgba(229,255,243,.065);border-radius:11px;background:rgba(5,10,8,.22);display:flex;flex-direction:column;justify-content:center}.overview-fact span{font:700 7px var(--mono);color:#61716a;letter-spacing:.14em;text-transform:uppercase}.overview-fact strong{margin-top:6px;color:#dce8e2;font:600 11px var(--body);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.dashboard-grid{grid-template-columns:minmax(310px,390px) minmax(0,1fr);gap:13px}.stack{gap:13px}.status-card{min-height:420px;background:radial-gradient(circle at 50% 48%,rgba(53,220,135,.045),transparent 35%),linear-gradient(145deg,rgba(255,255,255,.035),transparent 55%),rgba(12,19,17,.94)}
.gauge{width:min(100%,295px);margin:5px auto 0}.gauge-center{top:86px}.status-icon{width:36px;height:36px}.status-label{font-size:14px}.ppm{font-size:41px}.gauge-scale{font-size:9px}.meta-grid{gap:7px}.meta{padding:9px;border-radius:10px}.meta span{font-size:8px;color:#687770}.meta strong{font-size:10px}
.chart-card{min-height:405px}.chart-head{padding:19px 20px 0}.chart-wrap{height:295px;padding:14px 18px 0}.summary-cell{padding:12px 15px}.summary-cell strong{font-size:17px}
.metrics{gap:9px}.metric{padding:13px;border-radius:14px;min-height:98px}.metric-icon{width:28px;height:28px;border-radius:8px;background:rgba(53,220,135,.06);border-color:rgba(53,220,135,.1)}.metric span{font-size:9px}.metric strong{font-size:17px}.progress{background:rgba(255,255,255,.045)}.progress i{background:linear-gradient(90deg,#35dc87,#8ee8b3)}
.events{gap:6px}.event{padding:8px 0;border-bottom:1px solid rgba(255,255,255,.045)}.event:last-child{border-bottom:0}.event strong{font-size:10px}.event p{font-size:9px}.event time{font-size:8px}
.sensor-grid{gap:8px}.sensor{padding:12px;border-radius:12px;background:rgba(255,255,255,.018)}.sensor h3{font-size:10px}.reading strong{font-size:21px}.sensor-foot{font-size:8px}
.table-card{border-radius:14px}.table{min-width:780px}.table th{background:rgba(255,255,255,.02);font-size:8px;color:#718079}.table td{font-size:10px;padding:12px 13px}.table tr:hover td{background:rgba(53,220,135,.025)}
.report-summary{gap:9px}.report-box{padding:15px;min-height:88px}.report-box span{font-size:8px}.report-box strong{font-size:20px}
.settings-layout{grid-template-columns:190px minmax(0,1fr);gap:10px}.settings-nav{padding:8px}.settings-tab{font-size:10px}.switch{border-radius:11px;background:rgba(255,255,255,.015)}
.empty{min-height:68vh}.empty-card{padding:60px 44px;border-radius:20px;position:relative;overflow:hidden}.empty-card:before{content:"";position:absolute;inset:auto -20% -65% -20%;height:280px;background:radial-gradient(circle,rgba(53,220,135,.1),transparent 67%);pointer-events:none}.empty-icon{width:72px;height:72px;border-radius:22px;color:#5eea9c;background:rgba(53,220,135,.055)}
.notification-panel,.profile-menu{background:rgba(12,19,17,.97)}
.modal{background:rgba(2,6,5,.78)}.modal-card{border-color:rgba(53,220,135,.12)}
.toast{background:#0b1210;border-color:rgba(53,220,135,.18)}
/* Other views inherit the same product language */
#view-sensores .card.pad,#view-historico .card,#view-relatorios .card,#view-configuracoes .card,#view-suporte .card{box-shadow:0 20px 60px rgba(0,0,0,.13)}
#view-sensores .topbar,#view-historico .topbar,#view-relatorios .topbar,#view-configuracoes .topbar,#view-suporte .topbar{padding-bottom:4px}
#view-sensores .table-card,#view-historico .table-card{background:linear-gradient(145deg,rgba(255,255,255,.025),rgba(255,255,255,.01)),rgba(10,16,14,.9)}
#view-relatorios .report-summary .card:nth-child(1){background:linear-gradient(145deg,rgba(53,220,135,.08),rgba(255,255,255,.015))}
/* responsive: reorganize rather than simply shrink */
@media(max-width:1250px){
  .main{padding:25px 24px 40px}.aeris-overview{grid-template-columns:1fr}.overview-facts{grid-template-columns:repeat(3,1fr)}
  .dashboard-grid{grid-template-columns:1fr}.status-card{min-height:0}.gauge{max-width:310px}
}
@media(max-width:900px){
  :root{--sidebar:205px}.main{padding:22px 18px 34px}.main:before{display:none}.aeris-overview{padding:20px}.overview-main h2{font-size:17px}.overview-facts{grid-template-columns:repeat(3,1fr)}
  .metrics{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:780px){
  .app{display:block}.sidebar{position:sticky;top:0;height:auto;padding:9px 10px;display:block;border-right:0;border-bottom:1px solid rgba(229,255,243,.08);box-shadow:0 12px 30px rgba(0,0,0,.16)}.sidebar:after{display:none}.brand{border:0;margin:0;padding:3px 4px 9px}.brand-mark{width:34px;height:34px}.brand strong{font-size:14px}.nav{grid-template-columns:repeat(6,1fr);gap:4px}.nav:before{display:none}.nav-button{justify-content:center;padding:7px 4px;min-height:40px}.nav-button span:last-child{display:none}.nav-icon{width:27px;height:27px}.sidebar-bottom{display:none}.main{padding:16px 13px 30px}.topbar{margin-bottom:13px}.title h1{font-size:27px}.title p{font-size:11px}.aeris-overview{margin-bottom:11px;padding:18px 17px}.overview-main h2{font-size:16px}.overview-facts{gap:5px}.overview-fact{padding:9px}.overview-fact strong{font-size:10px}.device-bar{grid-template-columns:1fr 1fr}.device-bar .field{grid-column:1/-1}.device-bar .btn{width:100%}.dashboard-grid{gap:11px}.chart-card{min-height:0}.chart-wrap{height:260px}.summary{grid-template-columns:repeat(4,1fr)}.summary-cell{padding:10px 8px}.summary-cell strong{font-size:14px}.metrics{gap:7px}.metric{min-height:92px}.support-layout,.settings-layout,.form-grid{grid-template-columns:1fr}
}
@media(max-width:520px){
  .nav{grid-template-columns:repeat(3,1fr)}.nav-button{min-height:38px}.nav-icon{width:25px;height:25px}.aeris-overview:after{font-size:42px;right:14px}.overview-main p{font-size:10px}.overview-facts{grid-template-columns:1fr}.overview-fact{min-height:50px}.device-bar{grid-template-columns:1fr}.device-status{width:100%}.metrics{grid-template-columns:1fr 1fr}.summary{grid-template-columns:1fr 1fr}.summary-cell{border-bottom:1px solid var(--border)}.chart-wrap{height:225px}.status-card{padding:15px}.empty-card{padding:44px 22px}.notification-panel,.profile-menu{right:-5px;width:min(320px,calc(100vw - 24px))}
}
@media(prefers-reduced-motion:reduce){body:before{display:none}.aeris-overview,.card{transition:none!important}}

/* Busca de dispositivo na rede: spinner + mini card de conexão */
.scan-loading{display:flex;align-items:center;gap:12px;padding:16px}
.spinner{width:20px;height:20px;border-radius:50%;border:3px solid rgba(53,220,135,.18);border-top-color:#35dc87;animation:spin .7s linear infinite;flex-shrink:0}
@keyframes spin{to{transform:rotate(360deg)}}
.found-card{border:1px solid rgba(53,220,135,.3);background:rgba(53,220,135,.06)}
.found-card .found-head{display:flex;align-items:center;gap:8px;margin-bottom:2px}
.found-card .live-dot{width:7px;height:7px;border-radius:50%;background:#35dc87;box-shadow:0 0 0 4px rgba(53,220,135,.15);flex-shrink:0}
.mini-connect{margin-top:10px;padding-top:10px;border-top:1px dashed rgba(53,220,135,.25);display:grid;gap:8px}
[hidden]{display:none!important}
/* Alerta visual de perigo — não depende de som, importante para
   quem não ouve o buzzer/alarme (surdos) e reforça o aviso pra
   todo mundo. Pisca uma borda vermelha na tela inteira. */
body.critical-flash{animation:criticalFlash 1s ease-in-out infinite}
@keyframes criticalFlash{0%,100%{box-shadow:inset 0 0 0 0 rgba(255,87,87,0)}50%{box-shadow:inset 0 0 0 8px rgba(255,87,87,.6)}}
@media(prefers-reduced-motion:reduce){body.critical-flash{animation:none;box-shadow:inset 0 0 0 8px rgba(255,87,87,.6)}}
@media(prefers-reduced-motion:reduce){.spinner{animation:none;border-top-color:rgba(53,220,135,.18)}}
.chat-shell{display:grid;grid-template-columns:230px 1fr;gap:0;height:min(720px,78vh);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;background:var(--panel2);max-width:1600px;margin:0 auto}
.chat-sidebar{border-right:1px solid var(--border);padding:10px;overflow-y:auto;display:flex;flex-direction:column;gap:3px}
.chat-convo-sep{margin:12px 4px 4px;font-size:10px;letter-spacing:.06em;text-transform:uppercase;color:var(--faint)}
.chat-convo{display:flex;align-items:center;gap:9px;width:100%;text-align:left;background:transparent;border:1px solid transparent;border-radius:10px;padding:8px 9px;cursor:pointer;color:var(--text)}
.chat-convo:hover{background:rgba(255,255,255,.04)}
.chat-convo.active{background:rgba(32,215,178,.12);border-color:rgba(32,215,178,.28)}
.convo-avatar{width:30px;height:30px;border-radius:50%;background:rgba(255,255,255,.06);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px}
.convo-info{display:flex;flex-direction:column;min-width:0}
.convo-info strong{font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.convo-info small{font-size:10.5px;color:var(--muted)}
.chat-main{display:flex;flex-direction:column;min-width:0}
.chat-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 18px;border-bottom:1px solid var(--border)}
.chat-head strong{font-size:14.5px;display:block}
.chat-head-actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.chat-body{flex:1;overflow-y:auto;padding:28px 30px}
.assist-messages,.messages{display:flex;flex-direction:column;gap:24px;max-width:820px;margin:0 auto}
.chat-bubble{display:flex;gap:11px;max-width:74%}
.chat-bubble.me,.chat-bubble.team{margin-left:auto;flex-direction:row-reverse}
.bubble-avatar{width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,.06);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:13px;margin-top:2px}
.bubble-body{background:rgba(255,255,255,.045);border:1px solid var(--border);border-radius:16px;padding:12px 16px;font-size:13.5px;line-height:1.65;white-space:pre-wrap}
.chat-bubble.me .bubble-body,.chat-bubble.team .bubble-body{background:rgba(32,215,178,.13);border-color:rgba(32,215,178,.28)}
.bubble-body small{display:block;color:var(--muted);font-size:10px;margin-top:6px}
.chat-bubble.typing .bubble-body{padding:11px 15px}
.thinking-text{background:linear-gradient(90deg,var(--muted) 0%,var(--text) 50%,var(--muted) 100%);background-size:200% auto;background-clip:text;-webkit-background-clip:text;color:transparent;animation:thinkingShimmer 1.6s linear infinite;font-size:13px}
@keyframes thinkingShimmer{0%{background-position:200% center}100%{background-position:-200% center}}
.chat-bubble.arrived .bubble-body{animation:arrivedFlash 2s ease-out}
@keyframes arrivedFlash{0%{box-shadow:0 0 0 2px rgba(32,215,178,.55)}100%{box-shadow:0 0 0 0 rgba(32,215,178,0)}}
@media(prefers-reduced-motion:reduce){.thinking-text{animation:none;color:var(--muted)}.chat-bubble.arrived .bubble-body{animation:none}}
.assist-suggestions{display:flex;flex-wrap:wrap;gap:8px;margin:20px auto 0;max-width:820px}
.assist-suggestions button{background:rgba(255,255,255,.04);border:1px solid var(--border);color:var(--text);border-radius:999px;padding:6px 12px;font-size:12px;cursor:pointer}
.assist-suggestions button:hover{background:rgba(255,255,255,.08)}
.chat-main .compose{border-top:1px solid var(--border);padding:12px 16px;margin:0}
@media(max-width:760px){.chat-shell{grid-template-columns:1fr;height:auto}.chat-sidebar{border-right:0;border-bottom:1px solid var(--border);flex-direction:row;flex-wrap:wrap;max-height:160px}.chat-body{max-height:50vh}}
.notice.notice-clickable{cursor:pointer}
.notice.notice-clickable:hover{background:rgba(255,255,255,.04)}
</style>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/build/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
</head>
<body>
<div class="app">
<button type="button" id="sidebarToggle" class="sidebar-toggle" title="Mostrar/ocultar menu" aria-label="Mostrar/ocultar menu">☰</button>
<aside class="sidebar">
  <a class="brand" href="#dashboard" data-view="dashboard"><span class="brand-mark">S</span><span><small>Enterprise IoT</small><strong>Sadag</strong></span></a>
  <nav class="nav">
    <button class="nav-button active" data-view="dashboard"><span class="nav-icon">▦</span><span data-i18n="dashboard">Dashboard</span></button>
    <button class="nav-button" data-view="sensores"><span class="nav-icon">◉</span><span data-i18n="sensors">Sensores</span></button>
    <button class="nav-button" data-view="planta"><span class="nav-icon">▦</span><span data-i18n="floorplan">Planta 3D</span></button>
    <button class="nav-button" data-view="historico"><span class="nav-icon">≋</span><span data-i18n="history">Histórico</span></button>
    <button class="nav-button" data-view="relatorios"><span class="nav-icon">▤</span><span data-i18n="reports">Relatórios</span></button>
    <button class="nav-button" data-view="configuracoes"><span class="nav-icon">⚙</span><span data-i18n="settings">Configurações</span></button>
    <button class="nav-button" data-view="suporte"><span class="nav-icon">?</span><span data-i18n="support">Suporte</span></button>
  </nav>
  <div class="sidebar-bottom"><div class="system-box"><small>Sistema</small><div class="live-line"><i class="dot"></i><strong id="systemText">Aguardando dispositivo</strong></div><div class="version">Sadag · 2.0</div></div></div>
</aside>
<main class="main">
  <section class="view active" id="view-dashboard">
    <div class="topbar">
      <div class="title"><h1>Bem-vindo, <?= $userName ?>.</h1><p>Centro de controle do Sadag. Os dados exibidos abaixo vêm do banco e do ESP32.</p></div>
      <div class="toolbar">
        <div class="notification"><button class="bell" id="bellButton" title="Notificações">🔔</button><span class="badge" id="notificationBadge" hidden>0</span><div class="card notification-panel" id="notificationPanel"><div class="card-head"><strong class="card-title">Notificações</strong><button class="btn small" id="readNotifications">Marcar como lidas</button></div><div id="notificationList" style="margin-top:8px"></div></div></div>
        <div class="profile"><button class="avatar" id="profileAvatar">👤</button><div class="profile-name"><strong id="profileName">Usuário</strong><span id="profileEmail">--</span></div><div class="card profile-menu" id="profileMenu"><button id="openProfile">👤 Meu perfil</button><button data-view="configuracoes">⚙ Configurações</button><button id="logoutButton">↪ Sair</button></div></div>
      </div>
    </div>

    <div id="dashboardConnected" hidden>
      <div class="device-bar">
        <div class="field"><label>Dispositivo ativo</label><select class="input" id="deviceSelector"></select></div>
        <div class="device-status" id="networkStatusBox"><i class="dot"></i><span id="networkStatus">Offline</span></div>
        <button class="btn primary" id="connectButton">＋ Conectar dispositivo</button>
      </div>

      <div class="aeris-overview" aria-label="Resumo do sistema">
        <div class="overview-main">
          <div class="overview-kicker"><span class="live-dot"></span> MONITORAMENTO EM TEMPO REAL <span class="sync-age" id="syncAge">sincronizando...</span></div>
          <h2>Sadag <em>ao vivo</em></h2>
        </div>
      </div>
      <div class="house-overview">
        <article class="card house-card">
          <div class="card-head"><div><h2 class="card-title">Sua casa em tempo real</h2><p class="card-sub">Passe o mouse num dispositivo pra ver o status; clique pra selecionar.</p></div></div>
          <div class="plan-3d" id="dashboard3dSlot"><div class="plan-3d-fill" id="plan3dHost"></div></div>
        </article>
        <div class="house-cards">
          <article class="card pad house-mini"><span>Dispositivo</span><strong id="houseDeviceName">--</strong></article>
          <article class="card pad house-mini"><span>Ambiente</span><strong id="houseLocation">--</strong></article>
          <article class="card pad house-mini"><span>PPM agora</span><strong id="houseCurrentPpm">--</strong></article>
          <article class="card pad house-mini"><span>Status</span><strong id="houseStatus">--</strong></article>
          <article class="card pad house-mini"><span>Conexão</span><strong id="houseWifi">--</strong></article>
          <article class="card pad house-mini"><span>Última leitura</span><strong id="houseLastSeen">--</strong></article>
        </div>
      </div>
      <div class="dashboard-grid">
        <div class="stack">
          <article class="card pad status-card" id="statusCard">
            <div class="card-head"><div><h2 class="card-title">Status geral</h2><p class="card-sub">Dispositivo: <b id="selectedDeviceLabel">--</b></p></div><span class="pill live"><i class="dot"></i>Ao vivo</span></div>
            <div class="gauge">
              <svg viewBox="0 0 320 210"><defs><linearGradient id="gaugeGradient" x1="36" y1="164" x2="284" y2="164" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#20d7b2"/><stop offset=".48" stop-color="#36d67a"/><stop offset=".7" stop-color="#ffc857"/><stop offset=".84" stop-color="#ff8b42"/><stop offset="1" stop-color="#ff5757"/></linearGradient></defs><path d="M42 164 A118 118 0 0 1 278 164" fill="none" stroke="rgba(255,255,255,.07)" stroke-width="12" stroke-linecap="round"/><path d="M42 164 A118 118 0 0 1 278 164" fill="none" stroke="url(#gaugeGradient)" stroke-width="12" stroke-linecap="round"/><path id="gaugeNeedle" d="M160 164 L160 62" stroke="var(--status-color,#36d67a)" stroke-width="4" stroke-linecap="round"/><circle cx="160" cy="164" r="8" fill="var(--status-color,#36d67a)"/></svg>
              <div class="gauge-center"><div class="status-icon" id="statusIcon">✓</div><div class="status-label" id="statusLabel">Seguro</div><div class="status-message" id="statusMessage">Aguardando leitura</div><div class="ppm" id="currentPpm">--</div><div class="ppm-unit">ppm</div></div>
            </div><div class="gauge-scale"><span>0 ppm</span><span id="gaugeMax">700+ ppm</span></div>
            <div class="meta-grid"><div class="meta"><span>Última leitura</span><strong id="lastReadingTime">--</strong></div><div class="meta"><span>Local</span><strong id="selectedLocationLabel">--</strong></div><div class="meta"><span>IP / Wi-Fi</span><strong id="ipAddress">--</strong></div><div class="meta"><span>Estado</span><strong id="systemState">--</strong></div></div>
          </article>
          <article class="card pad"><div class="card-head"><h2 class="card-title">Eventos recentes</h2><button class="btn small" data-view="historico">Ver histórico</button></div><div class="events" id="eventList" style="margin-top:14px"></div></article>
        </div>
        <div class="stack">
          <article class="card chart-card"><div class="card-head chart-head"><div><h2 class="card-title">Análise de concentração de GLP (ppm)</h2><p class="card-sub">Leituras reais gravadas pelo ESP32.</p></div><div class="chart-actions"><button class="btn small range active" data-range="today">Hoje</button><button class="btn small range" data-range="24h">24h</button><button class="btn small range" data-range="all">Tudo</button></div></div><div class="chart-wrap" id="chartWrap"><svg id="mainChart" viewBox="0 0 920 310" preserveAspectRatio="none"><g id="chartGrid"></g><path id="chartArea" fill="rgba(32,215,178,.10)"></path><path id="chartLine" fill="none" stroke="#20d7b2" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path><g id="chartPoints"></g><line id="hoverLine" y1="18" y2="280" stroke="rgba(255,255,255,.2)" opacity="0"/><circle id="hoverDot" r="5" fill="#20d7b2" stroke="#fff" stroke-width="2" opacity="0"/></svg><div class="chart-tip" id="chartTip"></div></div><div class="summary"><div class="summary-cell"><span>Mínimo</span><strong id="statMin">--</strong> <small>ppm</small></div><div class="summary-cell"><span>Média</span><strong id="statAvg">--</strong> <small>ppm</small></div><div class="summary-cell"><span>Máximo</span><strong id="statMax">--</strong> <small>ppm</small></div><div class="summary-cell"><span>Última leitura</span><strong id="statLast">--</strong> <small>ppm</small></div></div></article>
          <div class="metrics metrics-2"><article class="card metric"><div class="metric-top"><div class="metric-icon">PPM</div><div><span>Média</span><strong id="metricAvg">--</strong><small>hoje</small></div></div><div class="progress"><i id="avgProgress" style="width:0"></i></div></article><article class="card metric"><div class="metric-top"><div class="metric-icon">Wi</div><div><span>Comunicação</span><strong id="metricWifi">--</strong><small>rede Wi-Fi</small></div></div><div class="progress"><i id="wifiProgress" style="width:0"></i></div></article></div>
        </div>
      </div>
    </div>
    <div id="dashboardEmpty" class="empty"><div class="card empty-card"><div class="empty-icon">⌁</div><h2>Conecte seu primeiro dispositivo</h2><p>O dashboard completo aparece assim que um ESP32 for encontrado na rede Wi-Fi e vinculado à sua conta. Depois disso, o dispositivo, o ambiente, o histórico e as leituras ficam integrados em uma única tela.</p><div class="empty-actions"><button class="btn primary" id="connectEmpty">🔗 Procurar dispositivo na rede</button><button class="btn" data-view="sensores">Ver dispositivos</button></div></div></div>
  </section>

  <section class="view" id="view-sensores"><div class="topbar"><div class="title"><h1>Sensores</h1><p>Descubra ESP32 pela presença Wi-Fi, vincule o dispositivo e defina o ambiente instalado.</p></div><div class="toolbar"><button class="btn primary" id="discoverButton">⌁ Procurar na rede</button><button class="btn" id="newLocationButton">＋ Novo ambiente</button></div></div><div class="card pad" style="margin-bottom:12px"><div class="card-head"><div><h2 class="card-title">Dispositivos da sua conta</h2><p class="card-sub">Tudo aqui é persistido no MySQL.</p></div><span class="pill" id="deviceCountPill">0 dispositivos</span></div></div><div class="card table-card"><table class="table"><thead><tr><th>Nome</th><th>ESP32</th><th>Ambiente</th><th>Sensor</th><th>Status</th><th>Última leitura</th><th>Ações</th></tr></thead><tbody id="sensorTable"></tbody></table></div></section>

  <section class="view" id="view-planta">
    <div class="topbar"><div class="title"><h1>Planta 3D</h1><p>Desenhe os cômodos da sua casa (arraste para mover, puxe o canto para redimensionar) e veja os dispositivos na posição real, em 3D.</p></div><div class="toolbar"><button class="btn" id="plantaNewRoom">＋ Novo ambiente</button></div></div>
    <div class="plan-layout">
      <article class="card pad plan-card">
        <div class="card-head"><h2 class="card-title">Editor da planta (visto de cima)</h2><span class="card-sub">1 quadrado = 1 metro</span></div>
        <div class="plan-editor" id="planEditor">
          <svg id="planSvg" width="100%" height="100%"></svg>
        </div>
      </article>
      <article class="card pad plan-card">
        <div class="card-head"><h2 class="card-title">Visualização 3D</h2><span class="card-sub">Arraste para girar · role para aproximar</span></div>
        <div class="plan-3d" id="planta3dSlot"></div>
      </article>
    </div>
  </section>

  <section class="view" id="view-historico"><div class="topbar"><div class="title"><h1>Histórico</h1><p>Cada leitura recebida do ESP32 permanece registrada no banco de dados.</p></div><div class="toolbar"><button class="btn" id="historyExport">Exportar CSV</button></div></div><div class="filters"><input class="input" id="historySearch" placeholder="Pesquisar dispositivo ou evento"><select class="input" id="historyStatus"><option value="">Todos os status</option><option value="normal">Normal</option><option value="atencao">Atenção</option><option value="perigo">Perigo</option></select><input class="input" id="historyMin" type="number" placeholder="PPM mínimo"><input class="input" id="historyMax" type="number" placeholder="PPM máximo"></div><div class="card table-card"><table class="table"><thead><tr><th>Data/Hora</th><th>Dispositivo</th><th>Ambiente</th><th>PPM</th><th>Status</th><th>RSSI</th></tr></thead><tbody id="historyTable"></tbody></table><div class="pagination"><span id="historyCount">0 registros</span><div><button class="btn small" id="historyPrev">Anterior</button> <span id="historyPage">1/1</span> <button class="btn small" id="historyNext">Próxima</button></div></div></div></section>

  <section class="view" id="view-relatorios"><div class="topbar"><div class="title"><h1>Relatórios</h1><p>Resumo tabular calculado diretamente das leituras registradas.</p></div><div class="toolbar"><button class="btn" id="reportExport">Exportar CSV</button></div></div><div class="report-summary"><article class="card report-box"><span>Leituras</span><strong id="reportTotal">0</strong></article><article class="card report-box"><span>Média</span><strong id="reportAvg">--</strong></article><article class="card report-box"><span>Máximo</span><strong id="reportMax">--</strong></article><article class="card report-box"><span>Alertas</span><strong id="reportAlerts">0</strong></article><article class="card report-box"><span>Perigos</span><strong id="reportCritical">0</strong></article></div><div class="card table-card"><table class="table"><thead><tr><th>Data</th><th>Dispositivo</th><th>Ambiente</th><th>Leituras</th><th>Média</th><th>Mínimo</th><th>Máximo</th><th>Alertas</th></tr></thead><tbody id="reportTable"></tbody></table></div></section>

  <section class="view" id="view-configuracoes"><div class="topbar"><div class="title"><h1>Configurações</h1><p>Preferências do aplicativo e regras que serão utilizadas pelos alertas.</p></div><div class="toolbar"><button class="btn primary" id="saveSettings">Salvar configurações</button></div></div><div class="settings-layout"><div class="card settings-nav"><button class="settings-tab active" data-settings="geral">Geral</button><button class="settings-tab" data-settings="sensores">Sensores</button><button class="settings-tab" data-settings="notificacoes">Notificações</button><button class="settings-tab" data-settings="seguranca">Segurança</button><button class="settings-tab" data-settings="auditoria">Auditoria</button></div><article class="card pad">
    <div class="settings-pane active" id="settings-geral"><div class="form-grid"><div class="field"><label>Idioma</label><select class="input" id="language"><option value="pt-BR">Português (Brasil)</option><option value="en">English</option><option value="es">Español</option></select></div><div class="field"><label>Fuso horário</label><select class="input" id="timezone"><option>America/Sao_Paulo</option><option>UTC</option></select></div></div></div>
    <div class="settings-pane" id="settings-sensores"><div class="form-grid"><div class="field"><label>Faixa normal abaixo de (ppm)</label><input class="input" id="safePpm" type="number"></div><div class="field"><label>Alerta a partir de (ppm)</label><input class="input" id="alertPpm" type="number"></div><div class="field"><label>Perigo a partir de (ppm)</label><input class="input" id="criticalPpm" type="number"></div><div class="field"><label>Intervalo de leitura</label><input class="input" id="readingInterval" type="number" min="1" max="3600"></div></div></div>
    <div class="settings-pane" id="settings-notificacoes"><div class="switch-list"><label class="switch"><span><strong>Email</strong><span>Receber alertas importantes por email.</span></span><input type="checkbox" id="notifyEmail"></label><label class="switch"><span><strong>Segurança</strong><span>Registrar avisos de login e segurança.</span></span><input type="checkbox" id="notifySecurity"></label><label class="switch"><span><strong>Sistema</strong><span>Receber atualizações operacionais.</span></span><input type="checkbox" id="notifySystem"></label><label class="switch"><span><strong>Projeto</strong><span>Receber eventos de dispositivos e conexão.</span></span><input type="checkbox" id="notifyProject"></label><label class="switch"><span><strong>Alertas de gás</strong><span>Gerar notificações quando o nível mudar para atenção/perigo.</span></span><input type="checkbox" id="notifyAlerts"></label></div></div>
    <div class="settings-pane" id="settings-seguranca"><div class="card pad" style="background:rgba(255,255,255,.02)"><h3 class="card-title">Proteção</h3><p class="card-sub">O sistema registra alterações e acessos importantes em <b>audit_logs</b>. A troca de senha continua sendo feita pelo módulo de autenticação existente.</p></div></div>
    <div class="settings-pane" id="settings-auditoria"><div class="card table-card"><table class="table"><thead><tr><th>Data</th><th>Ação</th><th>Entidade</th><th>Detalhes</th></tr></thead><tbody id="auditTable"></tbody></table></div></div>
  </article></div></section>

  <section class="view" id="view-suporte"><div class="topbar"><div class="title"><h1>Suporte</h1><p>O Sadag Assist está presente em toda conversa. Peça para falar com a equipe quando precisar.</p></div><div class="toolbar"><a class="btn" id="teamCenterLink" href="central-equipe.php" hidden>🛠 Central da equipe</a><button class="btn primary" id="newConvo">＋ Nova conversa</button></div></div>
  <div class="chat-shell">
    <aside class="chat-sidebar" id="convoList"></aside>
    <article class="chat-main">
      <div class="chat-head">
        <div><strong id="chatHeadTitle">Nova conversa</strong><span class="card-sub" id="chatHeadSub">Sadag Assist</span></div>
        <div class="chat-head-actions">
          <button class="btn small" type="button" id="escalateButton">💬 Falar com a equipe</button>
          <a class="btn small" id="replyByEmail" href="#" hidden>✉ Responder por email</a>
          <button class="btn small" id="closeTicket" hidden>Encerrar</button>
        </div>
      </div>
      <div class="chat-body" id="chatBody">
        <div class="assist-suggestions" id="assistSuggestions">
          <button type="button" data-suggest="Meu dispositivo está offline">Meu dispositivo está offline</button>
          <button type="button" data-suggest="Como funciona o sensor?">Como funciona o sensor?</button>
          <button type="button" data-suggest="Meu painel não mostra dados">Meu painel não mostra dados</button>
          <button type="button" data-suggest="Como conectar o dispositivo?">Como conectar o dispositivo?</button>
        </div>
      </div>
      <form class="compose" id="chatForm"><input class="input" id="chatInput" placeholder="Digite sua mensagem..." maxlength="2000" required><button class="btn primary">Enviar</button></form>
    </article>
  </div>
  </section>
</main></div>

<div class="modal" id="connectModal"><article class="card modal-card pad"><div class="card-head"><div><h2 class="modal-title">Conectar dispositivo Wi-Fi</h2><p class="card-sub">Busca ESP32 que anunciaram presença nos últimos 30s <b>e</b> faz uma varredura ao vivo na rede local.</p></div><button class="btn small" data-close="connectModal">✕</button></div><div class="toolbar" style="justify-content:flex-start;margin:14px 0"><button class="btn primary" id="scanNetwork">⌁ Buscar na rede Wi-Fi</button></div><div id="discoveredList" class="stack"></div><form id="connectForm" class="form-grid" style="margin-top:15px"><div class="field"><label>Nome no painel</label><input class="input" id="connectName" required placeholder="Sensor Cozinha"></div><div class="field"><label>ESP32 ID</label><input class="input" id="connectEsp" required></div><div class="field"><label>Código do fabricante</label><input class="input" id="connectCode" required placeholder="AERIS-MQ6-001"></div><div class="field"><label>Ambiente instalado</label><select class="input" id="connectLocation"></select></div><div class="field full"><label>Descrição</label><input class="input" id="connectDescription" placeholder="Ex.: próximo ao fogão"></div><div class="modal-actions full"><button class="btn" type="button" data-close="connectModal">Cancelar</button><button class="btn primary">Cadastrar dispositivo novo</button></div></form><div class="form-divider" style="margin:18px 0;border-top:1px solid var(--border)"></div><details><summary style="cursor:pointer;color:var(--muted);font-size:11px;font-weight:700">Já tem esse dispositivo cadastrado por outra conta?</summary><p class="card-sub" style="margin:8px 0">Se você sabe o ESP32 ID e a API key (mostrada uma vez no cadastro original), pode acompanhar o mesmo dispositivo físico pela sua conta, sem duplicar o cadastro.</p><form id="joinForm" class="form-grid"><div class="field"><label>ESP32 ID</label><input class="input" id="joinEsp" required placeholder="ESP32-MQ6-001"></div><div class="field"><label>API key do dispositivo</label><input class="input" id="joinApiKey" required></div><div class="modal-actions full"><button class="btn primary" type="submit">Vincular à minha conta</button></div></form></details></article></div>
<div class="modal" id="locationModal"><article class="card modal-card pad"><div class="card-head"><div><h2 class="modal-title">Novo ambiente</h2><p class="card-sub">O local também será registrado no MySQL.</p></div><button class="btn small" data-close="locationModal">✕</button></div><form id="locationForm" class="form-grid" style="margin-top:15px"><div class="field full"><label>Nome</label><input class="input" id="locationName" required placeholder="Cozinha"></div><div class="field"><label>Setor</label><input class="input" id="locationSector"></div><div class="field"><label>Andar</label><input class="input" id="locationFloor"></div><div class="field full"><label>Descrição</label><textarea class="input" id="locationDescription"></textarea></div><div class="modal-actions full"><button class="btn" type="button" data-close="locationModal">Cancelar</button><button class="btn primary">Salvar ambiente</button></div></form></article></div>
<div class="modal" id="profileModal"><article class="card modal-card pad"><div class="card-head"><div><h2 class="modal-title">Meu perfil</h2><p class="card-sub">O ícone escolhido fica salvo no campo avatar do usuário.</p></div><button class="btn small" data-close="profileModal">✕</button></div><form id="profileForm" class="form-grid" style="margin-top:15px"><div class="field"><label>Nome</label><input class="input" id="profileFormName"></div><div class="field"><label>Email</label><input class="input" id="profileFormEmail" disabled></div><div class="field"><label>Telefone</label><input class="input" id="profilePhone"></div><div class="field"><label>Nome exibido</label><input class="input" id="profileDisplay"></div><div class="field full"><label>Ícone do perfil</label><div class="icon-grid" id="iconGrid"></div></div><div class="field full"><label>Biografia</label><textarea class="input" id="profileBio"></textarea></div><div class="modal-actions full"><button class="btn" type="button" data-close="profileModal">Cancelar</button><button class="btn primary">Salvar perfil</button></div></form></article></div><div class="toast" id="toast"></div>
<script>
const API='../../api';
const state={devices:[],locations:[],selected:null,readings:[],history:[],range:'today',historyPage:1,historyPerPage:20,tickets:[],ticketId:null,lastMsgId:0,supportPoll:null,syncing:false,messages:[],thinking:false,notifications:[],profile:null,settings:null,selectedIcon:'👤',lastDataAt:null};
const icons=['👤','🛡️','🧪','🔬','🏠','🏭','🛰️','⚙️','🟢','🔵','🟣','⭐','🧰','💡','📡','🧠'];
const fallbackThresholds={safe:400,alert:550,critical:700};
const I18N={
 'pt-BR':{dashboard:'Dashboard',sensors:'Sensores',history:'Histórico',reports:'Relatórios',settings:'Configurações',support:'Suporte'},
 en:{dashboard:'Dashboard',sensors:'Sensors',history:'History',reports:'Reports',settings:'Settings',support:'Support'},
 es:{dashboard:'Panel',sensors:'Sensores',history:'Historial',reports:'Informes',settings:'Configuración',support:'Soporte'}
};
function esc(v){return String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
function fmtDate(v){if(!v)return'--';const d=new Date(String(v).replace(' ','T'));return isNaN(d)?v:d.toLocaleString('pt-BR');}
function time(v){if(!v)return'--';const d=new Date(v);return d.toLocaleTimeString('pt-BR');}
function toast(m){const t=document.getElementById('toast');t.textContent=m;t.classList.add('show');clearTimeout(toast.t);toast.t=setTimeout(()=>t.classList.remove('show'),3200)}
async function api(path,opt={}){const r=await fetch(`${API}/${path}`,{...opt,headers:{'Content-Type':'application/json',...(opt.headers||{})}});const d=await r.json().catch(()=>({success:false,message:'Resposta inválida'}));if(!r.ok||d.success===false)throw new Error(d.message||`HTTP ${r.status}`);return d}
function openModal(id){document.getElementById(id).classList.add('open')};function closeModal(id){document.getElementById(id).classList.remove('open')}
function selectedDevice(){return state.devices.find(d=>Number(d.id)===Number(state.selected))||null}
function locName(id){return state.locations.find(l=>Number(l.id)===Number(id))?.name||'Sem ambiente'}
function thresholds(){const s=state.settings?.settings;return{safety:Number(s?.safe_ppm??fallbackThresholds.safe),alert:Number(s?.alert_ppm??fallbackThresholds.alert),critical:Number(s?.critical_ppm??fallbackThresholds.critical)}}
function statusFor(v){const t=thresholds();if(v>=t.critical)return{key:'perigo',label:'Perigo',msg:'Concentração crítica detectada',color:'var(--red)',icon:'!'};if(v>=t.alert)return{key:'atencao',label:'Atenção',msg:'Concentração acima do nível de alerta',color:'var(--yellow)',icon:'!'};return{key:'normal',label:'Seguro',msg:'Nível dentro da faixa configurada',color:'var(--green)',icon:'✓'}}
function activateView(name){document.querySelectorAll('.view').forEach(v=>v.classList.toggle('active',v.id===`view-${name}`));document.querySelectorAll('[data-view]').forEach(b=>b.classList.toggle('active',b.dataset.view===name));history.replaceState(null,'',`#${name}`);if(name!=='suporte')stopSupportPoll();if(name==='historico')renderHistory();if(name==='relatorios')renderReports();if(name==='suporte'){loadTickets();updateChatHeader();renderChat();if(state.ticketId)startSupportPoll();}if(name==='configuracoes')loadSettings();if(name==='dashboard'){initPlan3D();moveHouseTo('dashboard3dSlot');renderPlan3D();}if(name==='planta'){initPlan3D();moveHouseTo('planta3dSlot');renderPlan2D();renderPlan3D();}}
function moveHouseTo(slotId){const host=document.getElementById('plan3dHost'),slot=document.getElementById(slotId);if(!host||!slot)return;if(host.parentElement!==slot)slot.appendChild(host);if(plan3d)setTimeout(()=>plan3d.resize(),50)}
function applyLanguage(lang){const map=I18N[lang]||I18N['pt-BR'];document.querySelectorAll('[data-i18n]').forEach(e=>e.textContent=map[e.dataset.i18n]||e.textContent);document.documentElement.lang=lang==='en'?'en':lang==='es'?'es':'pt-BR'}
async function loadProfile(){const d=await api('app.php?action=profile');state.profile=d.profile;document.getElementById('profileName').textContent=d.profile.display_name||d.profile.nome||'Usuário';document.getElementById('profileEmail').textContent=d.profile.email||'--';document.getElementById('profileAvatar').textContent=d.profile.avatar||'👤';document.getElementById('profileFormName').value=d.profile.nome||'';document.getElementById('profileFormEmail').value=d.profile.email||'';document.getElementById('profileDisplay').value=d.profile.display_name||'';document.getElementById('profilePhone').value=d.profile.phone||'';document.getElementById('profileBio').value=d.profile.biography||'';state.selectedIcon=d.profile.avatar||'👤';renderIcons();document.getElementById('teamCenterLink').hidden=!Number(d.profile.is_admin)}
function renderIcons(){document.getElementById('iconGrid').innerHTML=icons.map(i=>`<button type="button" class="icon-choice ${state.selectedIcon===i?'active':''}" data-icon="${i}">${i}</button>`).join('')}
async function loadSettings(){const d=await api('app.php?action=settings');state.settings=d;const s=d.settings,n=d.notifications;document.getElementById('language').value=s.language||'pt-BR';document.getElementById('safePpm').value=s.safe_ppm;document.getElementById('alertPpm').value=s.alert_ppm;document.getElementById('criticalPpm').value=s.critical_ppm;document.getElementById('readingInterval').value=s.reading_interval;['email','security','system','project','alerts'].forEach(k=>document.getElementById(`notify${k[0].toUpperCase()+k.slice(1)}`).checked=Number(n[`notify_${k}`])===1);applyLanguage(s.language||'pt-BR');renderGauge(state.readings.at(-1)?.value??0)}
async function saveSettings(){const p={language:document.getElementById('language').value,safe_ppm:Number(document.getElementById('safePpm').value),alert_ppm:Number(document.getElementById('alertPpm').value),critical_ppm:Number(document.getElementById('criticalPpm').value),reading_interval:Number(document.getElementById('readingInterval').value),notify_email:document.getElementById('notifyEmail').checked,notify_security:document.getElementById('notifySecurity').checked,notify_system:document.getElementById('notifySystem').checked,notify_project:document.getElementById('notifyProject').checked,notify_alerts:document.getElementById('notifyAlerts').checked};try{await api('app.php?action=save_settings',{method:'POST',body:JSON.stringify(p)});await loadSettings();toast('Configurações salvas no MySQL.')}catch(e){toast(e.message)}}
async function loadLocations(){const d=await api('locations.php');state.locations=d.locations||[];fillLocations()}
function fillLocations(){const opts='<option value="">Sem ambiente</option>'+state.locations.map(l=>`<option value="${l.id}">${esc(l.name)}${l.sector?' — '+esc(l.sector):''}</option>`).join('');document.getElementById('connectLocation').innerHTML=opts}
async function loadDevices(){const d=await api('devices.php');state.devices=d.devices||[];if(!state.selected&&state.devices.length)state.selected=state.devices[0].id;renderDeviceSelector();renderSensors();updateDeviceState()}
function renderDeviceSelector(){const s=document.getElementById('deviceSelector');s.innerHTML=state.devices.length?state.devices.map(d=>`<option value="${d.id}">${esc(d.name)} — ${esc(d.esp32_id)}</option>`).join(''):'<option value="">Nenhum</option>';if(state.selected)s.value=state.selected}
async function loadData(){const d=selectedDevice();if(!d){state.readings=[];state.history=[];disconnectStream();return}const x=await api(`data.php?device_id=${encodeURIComponent(d.id)}&limit=1000`);state.readings=(x.history||[]).slice().reverse().map(r=>({time:new Date(String(r.created_at).replace(' ','T')),value:Number(r.ppm),status:r.reading_status,rssi:r.wifi_rssi}));state.history=(x.history||[]).map(r=>({...r,device:d.name,location:locName(d.location_id)}));d.latest_ppm=x.latest?.ppm??null;d.stats=x.stats||{};d.status=x.device?.status||d.status;d.wifi_status=x.device?.wifi_status||d.wifi_status;state.lastDataAt=Date.now();updateDashboard();connectStream(d.id)}

let sseSource=null,sseDeviceId=null;
function disconnectStream(){if(sseSource){sseSource.close();sseSource=null}sseDeviceId=null}
function connectStream(deviceId){
  if(sseDeviceId===deviceId&&sseSource&&sseSource.readyState!==2)return; // ja conectado e vivo
  disconnectStream();
  sseDeviceId=deviceId;
  sseSource=new EventSource(`${API}/stream.php?device_id=${encodeURIComponent(deviceId)}`);
  sseSource.addEventListener('reading',e=>{
    let r;try{r=JSON.parse(e.data)}catch(err){return}
    const d=selectedDevice();
    if(!d||Number(d.id)!==Number(deviceId))return;
    const t=new Date(String(r.created_at).replace(' ','T'));
    const last=state.readings.at(-1);
    if(!last||last.time.getTime()!==t.getTime()){
      state.readings.push({time:t,value:Number(r.ppm),status:r.reading_status,rssi:r.wifi_rssi});
      if(state.readings.length>2000)state.readings.shift();
      state.history.unshift({id:r.id,device_id:deviceId,ppm:r.ppm,raw_adc:r.raw_adc,reading_status:r.reading_status,wifi_rssi:r.wifi_rssi,created_at:r.created_at,device:d.name,location:locName(d.location_id)});
    }
    d.latest_ppm=r.ppm;
    d.status=r.device_status;
    state.lastDataAt=Date.now();
    updateDashboard();
  });
  sseSource.onerror=()=>{/* EventSource tenta reconectar sozinho */};
}
function tickSyncAge(){const el=document.getElementById('syncAge');if(!el)return;if(!state.lastDataAt){el.textContent='sincronizando...';return}const s=Math.max(0,Math.round((Date.now()-state.lastDataAt)/1000));el.textContent=s<=1?'sincronizado agora':`sincronizado há ${s}s`;el.style.color=s<=3?'#8fe8b6':'#e8c98f'}
async function refresh(){try{await Promise.all([loadLocations(),loadSettings(),loadProfile(),loadDevices()]);await loadData();await loadNotifications();await loadAudit();await loadTickets();updateHome()}catch(e){toast(e.message);console.error('refresh() falhou:',e)}}
function updateHome(){const has=state.devices.length>0;document.getElementById('dashboardConnected').hidden=!has;document.getElementById('dashboardEmpty').style.display=has?'none':'grid';document.getElementById('systemText').textContent=has?'Operacional':'Aguardando dispositivo'}
function updateDeviceState(){updateHome();const d=selectedDevice();document.getElementById('selectedDeviceLabel').textContent=d?.name||'--';document.getElementById('selectedLocationLabel').textContent=d?locName(d.location_id):'--';document.getElementById('networkStatus').textContent=d?.status==='online'?'Online':'Offline';document.getElementById('networkStatusBox').style.color=d?.status==='online'?'var(--green)':'var(--red)';document.getElementById('ipAddress').textContent=d?.last_seen_ip||d?.wifi_status||'--';const hn=document.getElementById('houseDeviceName');if(!hn)return;hn.textContent=d?.name||'--';document.getElementById('houseLocation').textContent=d?locName(d.location_id):'--';const ppm=state.readings.at(-1)?.value??d?.latest_ppm;document.getElementById('houseCurrentPpm').textContent=ppm!=null&&!Number.isNaN(Number(ppm))?Math.round(ppm)+' ppm*':'--';const st=d&&ppm!=null&&!Number.isNaN(Number(ppm))?statusFor(Number(ppm)):null;document.getElementById('houseStatus').textContent=st?st.label:'--';document.getElementById('houseWifi').textContent=d?.status==='online'?'Online':'Offline';document.getElementById('houseLastSeen').textContent=state.readings.at(-1)?fmtDate(state.readings.at(-1).time):'--'}
function updateDashboard(){updateDeviceState();const d=selectedDevice();const last=state.readings.at(-1);renderGauge(last?.value??d?.latest_ppm??NaN);document.getElementById('lastReadingTime').textContent=last?fmtDate(last.time):'--';document.getElementById('metricAvg').textContent=d?.stats?.average_ppm!=null?Math.round(d.stats.average_ppm):'--';document.getElementById('avgProgress').style.width=d?.stats?.average_ppm?`${Math.min(100,d.stats.average_ppm/thresholds().critical*100)}%`:'0%';document.getElementById('metricWifi').textContent=d?.status==='online'?'Online':'Offline';document.getElementById('wifiProgress').style.width=d?.status==='online'?'100%':'15%';renderChart();renderEvents();renderSensors();if(plan3d){renderPlan3D();if(document.getElementById('view-planta').classList.contains('active'))renderPlan2D()}}
function renderGauge(v){const n=Number(v);if(!Number.isFinite(n)){document.getElementById('currentPpm').textContent='--';return}const st=statusFor(n),root=document.getElementById('statusCard');root.style.setProperty('--status-color',st.color);document.getElementById('statusIcon').textContent=st.icon;document.getElementById('statusLabel').textContent=st.label;document.getElementById('statusMessage').textContent=st.msg;document.getElementById('currentPpm').textContent=Math.round(n);document.getElementById('gaugeNeedle').setAttribute('transform',`rotate(${-55+Math.min(1,n/thresholds().critical)*110} 160 164)`);document.getElementById('systemState').textContent=st.label;document.getElementById('gaugeMax').textContent=thresholds().critical+'+ ppm';document.body.classList.toggle('critical-flash',st.key==='perigo')}
function series(){const now=Date.now();if(state.range==='today'){const d=new Date();d.setHours(0,0,0,0);return state.readings.filter(x=>x.time>=d)}if(state.range==='24h')return state.readings.filter(x=>x.time>=new Date(now-86400000));return state.readings}
function renderChart(){const arr=series();const svg=document.getElementById('mainChart');const grid=document.getElementById('chartGrid');if(!arr.length){document.getElementById('chartLine').setAttribute('d','');document.getElementById('chartArea').setAttribute('d','');grid.innerHTML='';['statMin','statAvg','statMax','statLast'].forEach(i=>document.getElementById(i).textContent='--');return}const W=920,H=310,L=42,R=12,T=18,B=280,max=Math.max(thresholds().critical*1.15,100,...arr.map(x=>x.value));const pts=arr.map((p,i)=>({x:L+(i/Math.max(1,arr.length-1))*(W-L-R),y:B-(p.value/max)*(B-T),p}));let d=`M ${pts[0].x} ${pts[0].y}`;for(let i=1;i<pts.length;i++){const a=pts[i-1],b=pts[i],cx=(a.x+b.x)/2;d+=` Q ${a.x} ${a.y} ${cx} ${(a.y+b.y)/2}`}d+=` T ${pts.at(-1).x} ${pts.at(-1).y}`;document.getElementById('chartLine').setAttribute('d',d);document.getElementById('chartArea').setAttribute('d',`${d} L ${pts.at(-1).x} ${B} L ${pts[0].x} ${B} Z`);const y=v=>B-(v/max)*(B-T);const lines=[0,Math.round(max*.25),Math.round(max*.5),Math.round(max*.75),Math.round(max)];grid.innerHTML=lines.map(v=>`<line x1="${L}" x2="${W-R}" y1="${y(v)}" y2="${y(v)}" stroke="rgba(255,255,255,.06)"/><text x="5" y="${y(v)+4}" fill="#7e8ca1" font-size="10">${v}</text>`).join('')+`<line x1="${L}" x2="${W-R}" y1="${y(thresholds().alert)}" y2="${y(thresholds().alert)}" stroke="#ffc857" stroke-dasharray="6 6"/><line x1="${L}" x2="${W-R}" y1="${y(thresholds().critical)}" y2="${y(thresholds().critical)}" stroke="#ff5757" stroke-dasharray="6 6"/>`;document.getElementById('chartPoints').innerHTML=pts.map((p,i)=>i%Math.max(1,Math.floor(pts.length/12))===0||i===pts.length-1?`<circle cx="${p.x}" cy="${p.y}" r="3" fill="#20d7b2"/>`:'').join('');const vals=arr.map(x=>x.value);document.getElementById('statMin').textContent=Math.round(Math.min(...vals));document.getElementById('statAvg').textContent=Math.round(vals.reduce((a,b)=>a+b,0)/vals.length);document.getElementById('statMax').textContent=Math.round(Math.max(...vals));document.getElementById('statLast').textContent=Math.round(vals.at(-1));svg._points=pts;svg._series=arr}
function renderEvents(){const el=document.getElementById('eventList');const ev=state.history.filter(r=>r.reading_status!=='normal').slice(0,8);el.innerHTML=ev.length?ev.map(r=>`<div class="event ${r.reading_status==='perigo'?'bad':'warn'}"><i></i><div><strong>${r.reading_status==='perigo'?'Perigo':'Atenção'} · ${esc(r.device)}</strong><p>${esc(r.ppm)} ppm registrado no ambiente ${esc(r.location)}</p></div><time>${fmtDate(r.created_at)}</time></div>`).join(''):'<div class="card-sub">Nenhum evento de alerta registrado.</div>'}
function renderSensors(){document.getElementById('sensorTable').innerHTML=state.devices.map(d=>`<tr><td><strong>${esc(d.name)}</strong></td><td>${esc(d.esp32_id)}</td><td>${esc(locName(d.location_id))}</td><td>${esc(d.sensor_type||'MQ-6')}</td><td><span class="status ${d.status==='online'?'normal':'perigo'}">${esc(d.status)}</span></td><td>${d.latest_ppm!=null?Math.round(d.latest_ppm)+' ppm':'--'}</td><td><button class="btn small" data-select="${d.id}">Selecionar</button></td></tr>`).join('')||'<tr><td colspan="7">Nenhum dispositivo.</td></tr>';document.getElementById('deviceCountPill').textContent=`${state.devices.length} dispositivo${state.devices.length===1?'':'s'}`}
function filteredHistory(){const q=document.getElementById('historySearch').value.toLowerCase(),st=document.getElementById('historyStatus').value,min=Number(document.getElementById('historyMin').value||-Infinity),max=Number(document.getElementById('historyMax').value||Infinity);return state.history.filter(r=>(!q||`${r.device} ${r.location}`.toLowerCase().includes(q))&&(!st||r.reading_status===st)&&Number(r.ppm)>=min&&Number(r.ppm)<=max)}
function renderHistory(){const rows=filteredHistory(),pages=Math.max(1,Math.ceil(rows.length/state.historyPerPage));state.historyPage=Math.min(state.historyPage,pages);const start=(state.historyPage-1)*state.historyPerPage,view=rows.slice(start,start+state.historyPerPage);document.getElementById('historyTable').innerHTML=view.map(r=>`<tr><td>${fmtDate(r.created_at)}</td><td>${esc(r.device)}</td><td>${esc(r.location)}</td><td><strong>${Math.round(r.ppm)} ppm</strong></td><td><span class="status ${r.reading_status}">${esc(r.reading_status)}</span></td><td>${r.wifi_rssi??'--'} dBm</td></tr>`).join('')||'<tr><td colspan="6">Nenhuma leitura encontrada.</td></tr>';document.getElementById('historyCount').textContent=`${rows.length} registros`;document.getElementById('historyPage').textContent=`${state.historyPage}/${pages}`;document.getElementById('historyPrev').disabled=state.historyPage<=1;document.getElementById('historyNext').disabled=state.historyPage>=pages}
function renderReports(){const rows=state.history,total=rows.length,vals=rows.map(r=>Number(r.ppm));document.getElementById('reportTotal').textContent=total;document.getElementById('reportAvg').textContent=total?Math.round(vals.reduce((a,b)=>a+b,0)/total)+' ppm':'--';document.getElementById('reportMax').textContent=total?Math.round(Math.max(...vals))+' ppm':'--';document.getElementById('reportAlerts').textContent=rows.filter(r=>r.reading_status==='atencao').length;document.getElementById('reportCritical').textContent=rows.filter(r=>r.reading_status==='perigo').length;const groups={};rows.forEach(r=>{const day=String(r.created_at).slice(0,10);const key=`${day}|${r.device}`;if(!groups[key])groups[key]={day,device:r.device,location:r.location,values:[],alerts:0};groups[key].values.push(Number(r.ppm));if(r.reading_status!=='normal')groups[key].alerts++});document.getElementById('reportTable').innerHTML=Object.values(groups).sort((a,b)=>b.day.localeCompare(a.day)).map(g=>`<tr><td>${g.day}</td><td>${esc(g.device)}</td><td>${esc(g.location)}</td><td>${g.values.length}</td><td>${Math.round(g.values.reduce((a,b)=>a+b,0)/g.values.length)} ppm</td><td>${Math.round(Math.min(...g.values))} ppm</td><td>${Math.round(Math.max(...g.values))} ppm</td><td>${g.alerts}</td></tr>`).join('')||'<tr><td colspan="8">Nenhum dado.</td></tr>'}
async function loadNotifications(){const d=await api('app.php?action=notifications');state.notifications=d.notifications||[];const unread=state.notifications.filter(n=>Number(n.is_read)===0).length;const b=document.getElementById('notificationBadge');b.textContent=unread;b.hidden=!unread;document.getElementById('notificationList').innerHTML=state.notifications.length?state.notifications.map(n=>`<div class="notice ${n.type==='support_reply'&&n.related_id?'notice-clickable':''}" ${n.type==='support_reply'&&n.related_id?`data-open-ticket="${n.related_id}"`:''}><strong>${esc(n.title)}</strong><p>${esc(n.message)}</p><time>${fmtDate(n.created_at)}</time></div>`).join(''):'<div class="card-sub">Nenhuma notificação.</div>'}
async function loadAudit(){const d=await api('app.php?action=audit');document.getElementById('auditTable').innerHTML=(d.logs||[]).map(x=>`<tr><td>${fmtDate(x.created_at)}</td><td>${esc(x.action)}</td><td>${esc(x.entity||'')}</td><td>${esc(x.details||'')}</td></tr>`).join('')||'<tr><td colspan="4">Nenhum registro de auditoria.</td></tr>'}
async function scanNetwork(){
  const list=document.getElementById('discoveredList');
  const btn=document.getElementById('scanNetwork');
  btn.disabled=true;
  list.innerHTML='<div class="card pad scan-loading"><span class="spinner"></span><div><strong>Procurando dispositivos...</strong><p class="card-sub" style="margin:2px 0 0">Checando anúncios recentes e varrendo a rede Wi-Fi local. Pode levar alguns segundos.</p></div></div>';
  let d;
  try{
    d=await api('app.php?action=discover');
  }catch(e){
    btn.disabled=false;
    list.innerHTML=`<div class="card pad"><strong>Não foi possível buscar agora.</strong><p class="card-sub">${esc(e.message)}</p></div>`;
    return;
  }
  btn.disabled=false;
  const found=d.devices||[];
  list.innerHTML=found.length?found.map(x=>{
    const esp=esc(x.esp32_id);
    const nomeSugerido=esc(x.name||x.hostname||x.esp32_id);
    const codigoSugerido=esc(x.manufacturer_code||'AERIS-MQ6-001');
    return `<div class="card pad found-card" data-esp32="${esp}" data-code="${codigoSugerido}" data-nome="${nomeSugerido}">
      <div class="found-head"><span class="live-dot"></span><strong>${nomeSugerido}</strong></div>
      <p class="card-sub">ESP32: ${esp} · IP: ${esc(x.ip_address||'--')} · RSSI: ${esc(x.wifi_rssi??'--')} dBm ${x.via==='scan_ao_vivo'?' · achado agora na rede':''}</p>
      <button type="button" class="btn primary small connect-found">＋ Conectar este dispositivo</button>
      <div class="mini-connect" hidden>
        <div class="field"><label>Nome no painel</label><input class="input mini-name" value="${nomeSugerido}"></div>
        <div class="field"><label>Ambiente instalado</label><select class="input mini-location">${document.getElementById('connectLocation').innerHTML}</select></div>
        <div style="display:flex;gap:8px"><button type="button" class="btn primary small mini-confirm">Confirmar conexão</button><button type="button" class="btn small mini-cancel">Cancelar</button></div>
      </div>
    </div>`;
  }).join(''):'<div class="card pad"><strong>Nenhum ESP32 encontrado.</strong><p class="card-sub">Nem anúncio recente, nem resposta na varredura da rede local. Confira se o ESP32 está ligado, na mesma rede Wi-Fi, e se o roteador não isola os dispositivos entre si (comum em redes de escola/evento).</p></div>';
}

async function connectFoundDevice(card){
  const esp32Id=card.dataset.esp32;
  const manufacturerCode=card.dataset.code;
  const nameInput=card.querySelector('.mini-name');
  const locationSelect=card.querySelector('.mini-location');
  const confirmBtn=card.querySelector('.mini-confirm');
  const name=nameInput.value.trim();
  if(!name){toast('Informe um nome para o dispositivo.');return}
  confirmBtn.disabled=true;
  confirmBtn.textContent='Conectando...';
  try{
    const d=await api('manage.php',{method:'POST',body:JSON.stringify({action:'create_device',name,esp32_id:esp32Id,manufacturer_code:manufacturerCode,location_id:locationSelect.value||null,sensor_type:'MQ-6'})});
    closeModal('connectModal');
    await loadDevices();
    state.selected=d.device_id;
    await loadData();
    updateHome();
    alert('Dispositivo vinculado!\n\nAPI KEY do ESP32:\n'+d.api_key+'\n\nGuarde essa chave. Ela será usada para enviar as leituras.');
    toast('Dispositivo registrado no MySQL.');
  }catch(e){
    confirmBtn.disabled=false;
    confirmBtn.textContent='Confirmar conexão';
    toast(e.message);
  }
}
async function connectDevice(e){e.preventDefault();const p={action:'create_device',name:document.getElementById('connectName').value.trim(),esp32_id:document.getElementById('connectEsp').value.trim(),manufacturer_code:document.getElementById('connectCode').value.trim(),location_id:document.getElementById('connectLocation').value||null,description:document.getElementById('connectDescription').value.trim(),sensor_type:'MQ-6'};try{const d=await api('manage.php',{method:'POST',body:JSON.stringify(p)});closeModal('connectModal');await loadDevices();state.selected=d.device_id;await loadData();updateHome();alert('Dispositivo vinculado!\n\nAPI KEY do ESP32:\n'+d.api_key+'\n\nGuarde essa chave. Ela será usada para enviar as leituras.');toast('Dispositivo registrado no MySQL.')}catch(e){toast(e.message)}}
async function joinDevice(e){e.preventDefault();const p={action:'join_device',esp32_id:document.getElementById('joinEsp').value.trim(),api_key:document.getElementById('joinApiKey').value.trim()};try{await api('manage.php',{method:'POST',body:JSON.stringify(p)});closeModal('connectModal');document.getElementById('joinForm').reset();await loadDevices();await loadData();updateHome();toast('Dispositivo vinculado à sua conta.')}catch(e){toast(e.message)}}
async function saveLocation(e){e.preventDefault();try{await api('manage.php',{method:'POST',body:JSON.stringify({action:'create_location',name:document.getElementById('locationName').value.trim(),sector:document.getElementById('locationSector').value.trim(),floor:document.getElementById('locationFloor').value.trim(),description:document.getElementById('locationDescription').value.trim()})});closeModal('locationModal');document.getElementById('locationForm').reset();await loadLocations();if(document.getElementById('view-planta').classList.contains('active')){renderPlan2D();renderPlan3D()}toast('Ambiente registrado.')}catch(e){toast(e.message)}}

/* =========================================================
   PLANTA 3D — editor 2D (arrastar/redimensionar) + cena 3D
   ========================================================= */
const PLAN_SCALE=32;
const SVGNS='http://www.w3.org/2000/svg';
let plan3d=null;

function statusColorHex(device){
  if(!device||device.status!=='online')return 0x5b6b62;
  const ppm=device.latest_ppm;
  if(ppm==null)return 0x5b6b62;
  const st=statusFor(Number(ppm));
  return st.key==='perigo'?0xff5757:st.key==='atencao'?0xffc857:0x35dc87;
}
function statusColorCss(device){return '#'+statusColorHex(device).toString(16).padStart(6,'0')}

function renderPlan2D(){
  const svg=document.getElementById('planSvg');
  if(!svg)return;
  svg.innerHTML='';
  state.locations.forEach(loc=>{
    const x=Number(loc.pos_x)*PLAN_SCALE,y=Number(loc.pos_y)*PLAN_SCALE,w=Number(loc.width)*PLAN_SCALE,h=Number(loc.depth)*PLAN_SCALE;
    const g=document.createElementNS(SVGNS,'g');
    g.dataset.locationId=loc.id;
    const rect=document.createElementNS(SVGNS,'rect');
    rect.setAttribute('x',x);rect.setAttribute('y',y);rect.setAttribute('width',w);rect.setAttribute('height',h);
    rect.setAttribute('class','plan-room');rect.dataset.role='move';
    const label=document.createElementNS(SVGNS,'text');
    label.setAttribute('x',x+6);label.setAttribute('y',y+16);label.setAttribute('class','plan-room-label');
    label.textContent=loc.name;
    const handle=document.createElementNS(SVGNS,'rect');
    handle.setAttribute('x',x+w-9);handle.setAttribute('y',y+h-9);handle.setAttribute('width',9);handle.setAttribute('height',9);
    handle.setAttribute('class','plan-room-handle');handle.dataset.role='resize';
    g.appendChild(rect);g.appendChild(label);g.appendChild(handle);
    const devicesHere=state.devices.filter(d=>Number(d.location_id)===Number(loc.id));
    devicesHere.forEach((d,i)=>{
      const dot=document.createElementNS(SVGNS,'circle');
      dot.setAttribute('cx',x+w/2+(i-(devicesHere.length-1)/2)*14);
      dot.setAttribute('cy',y+h-14);
      dot.setAttribute('r',5);
      dot.setAttribute('class','plan-device-dot');
      dot.setAttribute('fill',statusColorCss(d));
      g.appendChild(dot);
    });
    svg.appendChild(g);
  });
}

function bindPlanEditor(){
  const svg=document.getElementById('planSvg');
  if(!svg||svg.dataset.bound)return;
  svg.dataset.bound='1';
  svg.addEventListener('pointerdown',e=>{
    const g=e.target.closest('g[data-location-id]');
    if(!g)return;
    const locId=Number(g.dataset.locationId);
    const loc=state.locations.find(l=>Number(l.id)===locId);
    if(!loc)return;
    const role=e.target.dataset.role||'move';
    const startX=e.clientX,startY=e.clientY;
    const orig={x:Number(loc.pos_x),y:Number(loc.pos_y),w:Number(loc.width),h:Number(loc.depth)};
    svg.setPointerCapture(e.pointerId);
    function onMove(ev){
      const dx=(ev.clientX-startX)/PLAN_SCALE,dy=(ev.clientY-startY)/PLAN_SCALE;
      if(role==='resize'){
        loc.width=Math.max(0.6,orig.w+dx);
        loc.depth=Math.max(0.6,orig.h+dy);
      }else{
        loc.pos_x=Math.max(0,orig.x+dx);
        loc.pos_y=Math.max(0,orig.y+dy);
      }
      renderPlan2D();
      renderPlan3D();
    }
    function onUp(){
      svg.removeEventListener('pointermove',onMove);
      api('manage.php',{method:'POST',body:JSON.stringify({action:'update_location_layout',id:locId,pos_x:loc.pos_x,pos_y:loc.pos_y,width:loc.width,depth:loc.depth})}).catch(err=>toast(err.message));
    }
    svg.addEventListener('pointermove',onMove);
    svg.addEventListener('pointerup',onUp,{once:true});
  });
}

function initPlan3D(){
  const host=document.getElementById('plan3dHost');
  if(!host||plan3d||typeof THREE==='undefined')return;
  const scene=new THREE.Scene();
  scene.background=new THREE.Color(0x070b0a);
  const camera=new THREE.PerspectiveCamera(50,host.clientWidth/Math.max(1,host.clientHeight),0.1,200);
  camera.position.set(9,9,11);
  const renderer=new THREE.WebGLRenderer({antialias:true});
  renderer.setSize(host.clientWidth,host.clientHeight);
  host.style.position='relative';
  host.appendChild(renderer.domElement);
  const tooltip=document.createElement('div');
  tooltip.className='plan-tooltip';
  host.appendChild(tooltip);

  scene.add(new THREE.AmbientLight(0xffffff,0.55));
  const dir=new THREE.DirectionalLight(0xffffff,0.85);
  dir.position.set(6,12,6);
  scene.add(dir);

  const floor=new THREE.Mesh(new THREE.PlaneGeometry(60,60),new THREE.MeshStandardMaterial({color:0x0b1110,roughness:1}));
  floor.rotation.x=-Math.PI/2;
  scene.add(floor);
  scene.add(new THREE.GridHelper(60,60,0x1c2b24,0x121a16));

  const controls=new THREE.OrbitControls(camera,renderer.domElement);
  controls.enableDamping=true;
  controls.autoRotate=true;
  controls.autoRotateSpeed=0.6;
  controls.addEventListener('start',()=>{controls.autoRotate=false});

  const raycaster=new THREE.Raycaster();
  const mouse=new THREE.Vector2();
  renderer.domElement.addEventListener('pointermove',e=>{
    const rect=host.getBoundingClientRect();
    mouse.x=((e.clientX-rect.left)/rect.width)*2-1;
    mouse.y=-((e.clientY-rect.top)/rect.height)*2+1;
    raycaster.setFromCamera(mouse,camera);
    const hits=raycaster.intersectObjects(Object.values(plan3d.deviceMeshes));
    if(hits.length){
      const dv=hits[0].object.userData.device;
      tooltip.style.display='block';
      tooltip.style.left=(e.clientX-rect.left+12)+'px';
      tooltip.style.top=(e.clientY-rect.top+12)+'px';
      tooltip.innerHTML=`<strong>${esc(dv.name)}</strong><br>${esc(locName(dv.location_id))} · ${dv.latest_ppm!=null?Math.round(dv.latest_ppm):'--'} ppm* · ${dv.status==='online'?'Online':'Offline'}`;
      renderer.domElement.style.cursor='pointer';
    }else{
      tooltip.style.display='none';
      renderer.domElement.style.cursor='default';
    }
  });
  renderer.domElement.addEventListener('click',e=>{
    const rect=host.getBoundingClientRect();
    const mx=((e.clientX-rect.left)/rect.width)*2-1;
    const my=-((e.clientY-rect.top)/rect.height)*2+1;
    raycaster.setFromCamera(new THREE.Vector2(mx,my),camera);
    const hits=raycaster.intersectObjects(Object.values(plan3d.deviceMeshes));
    if(!hits.length)return;
    const dv=hits[0].object.userData.device;
    state.selected=dv.id;
    document.getElementById('deviceSelector').value=dv.id;
    loadData();
  });

  function resize(){
    if(!host.clientWidth||!host.clientHeight)return;
    camera.aspect=host.clientWidth/host.clientHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(host.clientWidth,host.clientHeight);
  }
  window.addEventListener('resize',resize);

  function animate(){
    requestAnimationFrame(animate);
    controls.update();
    const t=Date.now()/450;
    Object.values(plan3d.deviceMeshes).forEach(m=>{const s=1+Math.sin(t)*0.14;m.scale.set(s,s,s)});
    renderer.render(scene,camera);
  }

  plan3d={scene,camera,renderer,controls,roomGroups:{},deviceMeshes:{},resize};
  animate();
  bindPlanEditor();
}

function renderPlan3D(){
  if(!plan3d)return;
  const {scene}=plan3d;
  Object.values(plan3d.roomGroups).forEach(g=>scene.remove(g));
  Object.values(plan3d.deviceMeshes).forEach(m=>scene.remove(m));
  plan3d.roomGroups={};plan3d.deviceMeshes={};

  state.locations.forEach(loc=>{
    const w=Math.max(0.6,Number(loc.width)),d=Math.max(0.6,Number(loc.depth)),h=2.4;
    const group=new THREE.Group();
    const box=new THREE.Mesh(new THREE.BoxGeometry(w,h,d),new THREE.MeshStandardMaterial({color:0x1a2b22,transparent:true,opacity:0.32,emissive:0x0d1f16}));
    group.add(box);
    const edges=new THREE.LineSegments(new THREE.EdgesGeometry(box.geometry),new THREE.LineBasicMaterial({color:0x35dc87,transparent:true,opacity:0.55}));
    group.add(edges);
    group.position.set(Number(loc.pos_x)+w/2,h/2,Number(loc.pos_y)+d/2);
    scene.add(group);
    plan3d.roomGroups[loc.id]=group;

    const devicesHere=state.devices.filter(dv=>Number(dv.location_id)===Number(loc.id));
    devicesHere.forEach((dv,i)=>{
      const color=statusColorHex(dv);
      const sphere=new THREE.Mesh(new THREE.SphereGeometry(0.22,18,18),new THREE.MeshStandardMaterial({color,emissive:color,emissiveIntensity:0.85}));
      const offsetX=(i-(devicesHere.length-1)/2)*0.6;
      sphere.position.set(Number(loc.pos_x)+w/2+offsetX,1.2,Number(loc.pos_y)+d/2);
      sphere.userData.device=dv;
      scene.add(sphere);
      plan3d.deviceMeshes[dv.id]=sphere;
    });
  });

  const semLocal=state.devices.filter(dv=>!dv.location_id);
  semLocal.forEach((dv,i)=>{
    const color=statusColorHex(dv);
    const sphere=new THREE.Mesh(new THREE.SphereGeometry(0.22,18,18),new THREE.MeshStandardMaterial({color,emissive:color,emissiveIntensity:0.85}));
    sphere.position.set(-2,1.2,i*1.2);
    sphere.userData.device=dv;
    scene.add(sphere);
    plan3d.deviceMeshes[dv.id]=sphere;
  });
}
async function saveProfile(e){e.preventDefault();try{await api('app.php?action=save_profile',{method:'POST',body:JSON.stringify({nome:document.getElementById('profileFormName').value.trim(),display_name:document.getElementById('profileDisplay').value.trim(),phone:document.getElementById('profilePhone').value.trim(),biography:document.getElementById('profileBio').value.trim(),avatar:state.selectedIcon})});closeModal('profileModal');await loadProfile();toast('Perfil atualizado.')}catch(e){toast(e.message)}}
const SUPPORT_EMAIL_ADDR='sadag.suporte@gmail.com';
const ASSIST_SUGGESTIONS_HTML='<div class="assist-suggestions" id="assistSuggestions"><button type="button" data-suggest="Meu dispositivo está offline">Meu dispositivo está offline</button><button type="button" data-suggest="Como funciona o sensor?">Como funciona o sensor?</button><button type="button" data-suggest="Meu painel não mostra dados">Meu painel não mostra dados</button><button type="button" data-suggest="Como conectar o dispositivo?">Como conectar o dispositivo?</button></div>';
function messageBubble(m){const cls=m.sender_type==='user'?'me':(m.sender_type==='support'?'team':'ai');const avatar=m.sender_type==='user'?'🧑':(m.sender_type==='support'?'🛠️':'🤖');const who=m.sender_type==='user'?'Você':(m.sender_type==='support'?'Equipe Sadag':'Sadag Assist');return `<div class="chat-bubble ${cls}${m._arrived?' arrived':''}"><span class="bubble-avatar">${avatar}</span><div class="bubble-body">${esc(m.message)}<small>${who} · ${fmtDate(m.created_at)}${Number(m.email_sent)?' · email enviado':''}</small></div></div>`}
function renderChat(){const box=document.getElementById('chatBody');const empty=!state.ticketId&&!state.messages.length;const greet=empty?'<div class="chat-bubble ai"><span class="bubble-avatar">🤖</span><div class="bubble-body">Olá! Sou o Sadag Assist — estou presente em todas as suas conversas. Como posso ajudar?<small>Sadag Assist</small></div></div>':'';const bubbles=state.messages.map(messageBubble).join('');const typing=state.thinking?'<div class="chat-bubble ai typing"><span class="bubble-avatar">🤖</span><div class="bubble-body"><span class="thinking-text">Sadag Assist está pensando...</span></div></div>':'';box.innerHTML=greet+bubbles+typing+(empty?ASSIST_SUGGESTIONS_HTML:'');box.scrollTop=box.scrollHeight;box.querySelectorAll('.arrived').forEach(el=>setTimeout(()=>el.classList.remove('arrived'),2200))}
function renderConvoList(){const box=document.getElementById('convoList');const novo=`<button type="button" class="chat-convo ${!state.ticketId?'active':''}" data-new="1"><span class="convo-avatar">🤖</span><span class="convo-info"><strong>Nova conversa</strong><small>Sadag Assist</small></span></button>`;const items=state.tickets.map(t=>`<button type="button" class="chat-convo ${Number(t.id)===Number(state.ticketId)?'active':''}" data-ticket="${t.id}"><span class="convo-avatar">${Number(t.escalated)?'🛠️':'🤖'}</span><span class="convo-info"><strong>${esc(t.subject)}</strong><small>${Number(t.escalated)?'Com a equipe':'Sadag Assist'} · ${esc(t.status)}</small></span></button>`).join('');box.innerHTML=novo+items}
async function loadTickets(){try{const d=await api('app.php?action=support_list');state.tickets=d.tickets||[];renderConvoList()}catch(e){console.error(e)}}
function updateChatHeader(){const t=state.ticketId?state.tickets.find(x=>Number(x.id)===Number(state.ticketId)):null;document.getElementById('chatHeadTitle').textContent=t?t.subject:'Nova conversa';document.getElementById('chatHeadSub').textContent=t?(Number(t.escalated)?'Com a equipe · '+t.status:'Sadag Assist · '+t.status):'Sadag Assist';document.getElementById('escalateButton').hidden=!t||!!Number(t.escalated);document.getElementById('replyByEmail').hidden=!t||!Number(t.escalated);document.getElementById('closeTicket').hidden=!t;if(t)document.getElementById('replyByEmail').href=`mailto:${SUPPORT_EMAIL_ADDR}?subject=${encodeURIComponent('[AG-'+t.id+'] Re: Atendimento')}`}
/*
 * Fonte única de verdade pra evitar mensagem duplicada: em vez de
 * "adivinhar" localmente a mensagem que acabamos de mandar (e depois
 * o polling trazer ela de novo do banco), toda escrita no chat é
 * seguida de uma sincronização real com o servidor, sempre a partir
 * do último id já visto. O mutex "syncing" evita que o polling e uma
 * sincronização manual rodem ao mesmo tempo e tragam o mesmo lote.
 */
async function syncMessages(){if(!state.ticketId||state.syncing)return;state.syncing=true;try{const d=await api(`app.php?action=support_messages&ticket_id=${state.ticketId}&after_id=${state.lastMsgId||0}`);if(d.messages&&d.messages.length){const gotTeam=d.messages.some(m=>m.sender_type==='support');d.messages.forEach(m=>m._arrived=m.sender_type==='support');state.messages.push(...d.messages);state.lastMsgId=Math.max(state.lastMsgId||0,...d.messages.map(m=>m.id));renderChat();if(gotTeam)toast('💬 Nova mensagem do técnico recebida.')}}catch(e){}state.syncing=false}
function stopSupportPoll(){if(state.supportPoll){clearInterval(state.supportPoll);state.supportPoll=null}}
function startSupportPoll(){stopSupportPoll();state.supportPoll=setInterval(syncMessages,800)}
function newConversation(){state.ticketId=null;state.lastMsgId=0;state.messages=[];stopSupportPoll();renderConvoList();updateChatHeader();renderChat();document.getElementById('chatInput').focus()}
async function loadMessages(id){state.ticketId=id;state.lastMsgId=0;state.messages=[];renderConvoList();const d=await api(`app.php?action=support_messages&ticket_id=${id}`);state.messages=d.messages||[];if(state.messages.length)state.lastMsgId=Math.max(...state.messages.map(m=>m.id));updateChatHeader();renderChat();startSupportPoll()}
async function sendChat(text){text=text.trim();if(!text)return;const isNew=!state.ticketId;const existing=isNew?null:state.tickets.find(x=>Number(x.id)===Number(state.ticketId));const escalated=existing&&Number(existing.escalated);state.messages.push({sender_type:'user',message:text,created_at:new Date().toISOString(),_pending:true});state.thinking=!escalated;renderChat();try{if(isNew){const d=await api('app.php?action=support_create',{method:'POST',body:JSON.stringify({message:text})});state.ticketId=d.ticket_id;state.lastMsgId=0;await loadTickets()}else{await api('app.php?action=support_send',{method:'POST',body:JSON.stringify({ticket_id:state.ticketId,message:text})})}state.messages=state.messages.filter(m=>!m._pending);state.thinking=false;await syncMessages();updateChatHeader();if(!state.supportPoll)startSupportPoll();renderConvoList()}catch(e){state.messages=state.messages.filter(m=>!m._pending);state.thinking=false;toast(e.message)}renderChat()}
async function escalateConversation(){if(!state.ticketId)return;try{const d=await api('app.php?action=support_escalate',{method:'POST',body:JSON.stringify({ticket_id:state.ticketId})});await syncMessages();await loadTickets();updateChatHeader();toast(d.email_sent?'Equipe avisada por email.':'Equipe registrada; email indisponível no momento.')}catch(e){toast(e.message)}}
async function closeCurrentTicket(){if(!state.ticketId)return;try{await api('app.php?action=support_close',{method:'POST',body:JSON.stringify({ticket_id:state.ticketId})});await loadTickets();updateChatHeader();toast('Conversa encerrada.')}catch(e){toast(e.message)}}
function exportRows(rows,name){const csv=['data_hora,dispositivo,ambiente,ppm,status,rssi',...rows.map(r=>`"${fmtDate(r.created_at).replaceAll('"','""')}","${String(r.device||'').replaceAll('"','""')}","${String(r.location||'').replaceAll('"','""')}",${r.ppm},"${r.reading_status}","${r.wifi_rssi??''}"`)].join('\n');const blob=new Blob([csv],{type:'text/csv;charset=utf-8'}),a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=name;a.click();URL.revokeObjectURL(a.href)}
function bind(){document.querySelectorAll('[data-view]').forEach(b=>b.addEventListener('click',e=>{if(b.tagName==='A')e.preventDefault();activateView(b.dataset.view)}));document.querySelectorAll('[data-close]').forEach(b=>b.addEventListener('click',()=>closeModal(b.dataset.close)));document.getElementById('deviceSelector').addEventListener('change',async e=>{state.selected=Number(e.target.value);await loadData()});document.getElementById('connectButton').addEventListener('click',()=>{openModal('connectModal');scanNetwork()});document.getElementById('connectEmpty').addEventListener('click',()=>{openModal('connectModal');scanNetwork()});document.getElementById('discoverButton').addEventListener('click',()=>{openModal('connectModal');scanNetwork()});document.getElementById('scanNetwork').addEventListener('click',scanNetwork);document.getElementById('connectForm').addEventListener('submit',connectDevice);document.getElementById('joinForm').addEventListener('submit',joinDevice);document.getElementById('locationForm').addEventListener('submit',saveLocation);document.getElementById('newLocationButton').addEventListener('click',()=>openModal('locationModal'));document.getElementById('plantaNewRoom').addEventListener('click',()=>openModal('locationModal'));document.getElementById('saveSettings').addEventListener('click',saveSettings);document.getElementById('language').addEventListener('change',e=>applyLanguage(e.target.value));document.querySelectorAll('.settings-tab').forEach(b=>b.addEventListener('click',()=>{document.querySelectorAll('.settings-tab').forEach(x=>x.classList.toggle('active',x===b));document.querySelectorAll('.settings-pane').forEach(p=>p.classList.toggle('active',p.id===`settings-${b.dataset.settings}`))}));document.getElementById('profileAvatar').addEventListener('click',()=>document.getElementById('profileMenu').classList.toggle('open'));document.getElementById('openProfile').addEventListener('click',()=>{document.getElementById('profileMenu').classList.remove('open');openModal('profileModal')});document.getElementById('profileForm').addEventListener('submit',saveProfile);document.getElementById('iconGrid').addEventListener('click',e=>{const b=e.target.closest('[data-icon]');if(!b)return;state.selectedIcon=b.dataset.icon;renderIcons()});document.getElementById('bellButton').addEventListener('click',()=>document.getElementById('notificationPanel').classList.toggle('open'));document.getElementById('readNotifications').addEventListener('click',async()=>{await api('app.php?action=read_notifications',{method:'POST',body:'{}'});await loadNotifications()});document.getElementById('historyExport').addEventListener('click',()=>exportRows(filteredHistory(),'aeris-historico.csv'));document.getElementById('reportExport').addEventListener('click',()=>exportRows(state.history,'aeris-relatorio.csv'));['historySearch','historyStatus','historyMin','historyMax'].forEach(id=>document.getElementById(id).addEventListener('input',()=>{state.historyPage=1;renderHistory()}));document.getElementById('historyPrev').addEventListener('click',()=>{state.historyPage--;renderHistory()});document.getElementById('historyNext').addEventListener('click',()=>{state.historyPage++;renderHistory()});document.querySelectorAll('[data-range]').forEach(b=>b.addEventListener('click',()=>{state.range=b.dataset.range;document.querySelectorAll('[data-range]').forEach(x=>x.classList.toggle('active',x===b));renderChart()}));document.getElementById('newConvo').addEventListener('click',newConversation);document.getElementById('closeTicket').addEventListener('click',closeCurrentTicket);document.getElementById('escalateButton').addEventListener('click',escalateConversation);document.getElementById('convoList').addEventListener('click',e=>{const nb=e.target.closest('[data-new]');if(nb){newConversation();return}const b=e.target.closest('[data-ticket]');if(b)loadMessages(Number(b.dataset.ticket))});document.getElementById('chatForm').addEventListener('submit',e=>{e.preventDefault();const input=document.getElementById('chatInput');const text=input.value.trim();if(!text)return;input.value='';sendChat(text)});document.getElementById('chatBody').addEventListener('click',e=>{const b=e.target.closest('[data-suggest]');if(b)sendChat(b.dataset.suggest)});document.body.addEventListener('click',e=>{const s=e.target.closest('[data-select]');if(s){state.selected=Number(s.dataset.select);document.getElementById('deviceSelector').value=state.selected;activateView('dashboard');loadData()}const cf=e.target.closest('.connect-found');if(cf){const card=cf.closest('.found-card');card.querySelector('.mini-connect').hidden=false;cf.hidden=true;}const mc=e.target.closest('.mini-cancel');if(mc){const card=mc.closest('.found-card');card.querySelector('.mini-connect').hidden=true;card.querySelector('.connect-found').hidden=false;}const ok=e.target.closest('.mini-confirm');if(ok){connectFoundDevice(ok.closest('.found-card'));}const notif=e.target.closest('[data-open-ticket]');if(notif){document.getElementById('notificationPanel').classList.remove('open');activateView('suporte');loadTickets().then(()=>loadMessages(Number(notif.dataset.openTicket)))}});document.getElementById('logoutButton').addEventListener('click',()=>{location.href='logout.php'});document.getElementById('sidebarToggle').addEventListener('click',()=>{const collapsed=document.querySelector('.app').classList.toggle('sidebar-collapsed');try{localStorage.setItem('sadag_sidebar_collapsed',collapsed?'1':'0')}catch(e){}})}
async function init(){
  bind();
  try{if(localStorage.getItem('sadag_sidebar_collapsed')==='1')document.querySelector('.app').classList.add('sidebar-collapsed')}catch(e){}
  const hash=location.hash.replace('#','');
  activateView(hash||'dashboard');
  await refresh();

  // Contador "sincronizado há Xs": roda por conta própria, no ritmo do
  // relógio do navegador, então prova visualmente que está ao vivo mesmo
  // entre uma requisição e outra.
  setInterval(tickSyncAge,1000);

  // A atualização instantânea do PPM agora vem do canal ao vivo
  // (connectStream/SSE, aberto dentro de loadData()). Esse intervalo
  // vira só uma rede de segurança: recalcula estatísticas/histórico
  // completos e reconecta o canal se ele cair por algum motivo.
  setInterval(async()=>{
    try{
      if(state.selected)await loadData();
    }catch(e){console.error(e)}
  },6000);

  // Lista de dispositivos/notificações: muda pouco, não precisa do mesmo ritmo.
  setInterval(async()=>{
    try{
      await loadNotifications();
      await loadDevices();
    }catch(e){console.error(e)}
  },6000);
}
init();
</script>

<!-- VLibras: widget oficial do governo brasileiro que traduz o conteúdo da página para Libras (Língua Brasileira de Sinais), para usuários surdos. -->
<div vw class="enabled"><div vw-access-button class="active"></div><div vw-plugin-wrapper><div class="vw-plugin-top-wrapper"></div></div></div>
<script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
<script>(function(){if(window.VLibras)new window.VLibras.Widget('https://vlibras.gov.br/app')})();</script>
</body>
</html>
