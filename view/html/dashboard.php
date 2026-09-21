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
.settings-layout{display:grid;grid-template-columns:200px minmax(0,1fr);gap:12px}.settings-nav{padding:10px;display:grid;gap:5px;height:max-content}.settings-tab{border:0;background:transparent;color:var(--muted);text-align:left;padding:10px;border-radius:8px;cursor:pointer;font-size:11px;font-weight:700}.settings-tab.active,.settings-tab:hover{background:rgba(32,215,178,.08);color:var(--accent)}.settings-pane{display:none}.settings-pane.active{display:block}.form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}.full{grid-column:1/-1}.switch-list{display:grid;gap:8px}.switch{display:flex;justify-content:space-between;gap:15px;align-items:center;padding:13px;border:1px solid var(--border);border-radius:9px;background:rgba(255,255,255,.02)}.switch strong{font-size:11px}.switch span{display:block;color:var(--muted);font-size:9px;font-weight:400;margin-top:3px}.switch input{appearance:none;width:40px;height:22px;border-radius:99px;background:#263244;position:relative;cursor:pointer}.switch input:before{content:'';position:absolute;width:16px;height:16px;left:3px;top:3px;border-radius:50%;background:#9aa8bd;transition:.2s}.switch input:checked{background:rgba(32,215,178,.35)}.switch input:checked:before{left:21px;background:var(--accent)}
.profile{display:flex;align-items:center;gap:9px;position:relative}.avatar{width:34px;height:34px;border-radius:10px;border:1px solid var(--border2);background:rgba(255,255,255,.04);display:grid;place-items:center;font-size:17px;cursor:pointer}.profile-name{display:grid}.profile-name strong{font-size:11px}.profile-name span{font-size:9px;color:var(--muted)}.profile-menu{position:absolute;right:0;top:44px;width:210px;padding:8px;display:none;z-index:30}.profile-menu.open{display:block}.profile-menu button{width:100%;border:0;background:transparent;text-align:left;padding:9px;border-radius:8px;cursor:pointer;font-size:11px}.profile-menu button:hover{background:rgba(255,255,255,.05)}
.notification{position:relative}.bell{width:34px;height:34px;border:1px solid var(--border2);border-radius:10px;background:rgba(255,255,255,.04);cursor:pointer}.badge{position:absolute;right:-3px;top:-4px;min-width:15px;height:15px;border-radius:99px;background:var(--red);color:#fff;font:700 8px var(--mono);display:grid;place-items:center;padding:0 4px}.notification-panel{position:absolute;right:0;top:44px;width:320px;max-height:420px;overflow:auto;padding:10px;display:none;z-index:30}.notification-panel.open{display:block}.notice{padding:10px;border-bottom:1px solid var(--border)}.notice:last-child{border-bottom:0}.notice strong{font-size:10px}.notice p{margin:3px 0;color:var(--muted);font-size:9px;line-height:1.5}.notice time{color:var(--faint);font:9px var(--mono)}
.support-layout{display:grid;grid-template-columns:250px minmax(0,1fr) 290px;gap:12px}.tickets{display:grid;gap:6px;margin-top:12px}.ticket{border:1px solid var(--border);background:transparent;border-radius:9px;padding:10px;text-align:left;cursor:pointer}.ticket.active,.ticket:hover{background:rgba(32,215,178,.06);border-color:rgba(32,215,178,.2)}.ticket strong{font-size:10px;display:block}.ticket span{display:block;color:var(--muted);font-size:9px;margin-top:3px}.messages{height:420px;overflow:auto;padding:16px;display:grid;align-content:start;gap:10px}.message{max-width:78%;padding:10px;border-radius:10px;background:rgba(255,255,255,.04);border:1px solid var(--border)}.message.me{margin-left:auto;background:rgba(32,215,178,.08);border-color:rgba(32,215,178,.18)}.message strong{font-size:9px}.message p{font-size:10px;line-height:1.55;margin:4px 0}.message small{color:var(--faint);font-size:8px}.compose{display:grid;grid-template-columns:1fr auto;gap:8px;padding:12px;border-top:1px solid var(--border)}.contact{display:grid;gap:8px}.contact .meta strong{font-size:10px}
.modal{position:fixed;inset:0;background:rgba(0,0,0,.68);backdrop-filter:blur(7px);display:none;place-items:center;padding:18px;z-index:50}.modal.open{display:grid}.modal-card{width:min(760px,96vw);max-height:90vh;overflow:auto}.modal-title{font:800 18px var(--display);margin:0}.modal-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:4px}.icon-grid{display:grid;grid-template-columns:repeat(8,1fr);gap:7px}.icon-choice{border:1px solid var(--border);background:rgba(255,255,255,.025);border-radius:9px;padding:10px;cursor:pointer;font-size:20px}.icon-choice.active,.icon-choice:hover{border-color:rgba(32,215,178,.4);background:rgba(32,215,178,.08)}
.toast{position:fixed;right:20px;bottom:20px;z-index:100;background:#0a111d;border:1px solid var(--border2);border-radius:10px;padding:11px 14px;box-shadow:0 15px 40px rgba(0,0,0,.4);font-size:11px;transform:translateY(20px);opacity:0;pointer-events:none;transition:.2s}.toast.show{opacity:1;transform:none}
@media(max-width:1150px){:root{--sidebar:190px}.dashboard-grid{grid-template-columns:1fr}.metrics{grid-template-columns:repeat(2,1fr)}.support-layout{grid-template-columns:1fr 1fr}.support-layout>:last-child{grid-column:1/-1}}
@media(max-width:780px){.app{grid-template-columns:1fr}.sidebar{position:static;height:auto;padding:10px;display:block}.brand{margin-bottom:8px}.nav{grid-template-columns:repeat(3,1fr)}.nav-button{justify-content:center;padding:8px;font-size:10px}.nav-button span:last-child{display:none}.sidebar-bottom{display:none}.main{padding:14px}.topbar{align-items:flex-start;flex-direction:column}.toolbar{justify-content:flex-start}.device-bar,.filters,.settings-layout,.support-layout,.form-grid{grid-template-columns:1fr}.metrics,.sensor-grid{grid-template-columns:1fr 1fr}.summary{grid-template-columns:1fr 1fr}.summary-cell{border-bottom:1px solid var(--border)}.profile-name{display:none}.notification-panel,.profile-menu{right:-5px;width:min(320px,calc(100vw - 28px))}.icon-grid{grid-template-columns:repeat(4,1fr)}}
@media(max-width:500px){.metrics,.sensor-grid{grid-template-columns:1fr}.title p{max-width:90vw}}

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
.brand-mark{width:40px;height:40px;border-radius:13px;background:linear-gradient(145deg,#48e89a,#1ab96d);color:#06110b;border:0;box-shadow:0 12px 30px rgba(53,220,135,.16);font-size:18px;object-fit:cover;display:block}
.brand small{font-size:8px;color:#71817a;letter-spacing:.2em}.brand strong{font-size:16px;letter-spacing:-.025em}
.nav{gap:3px}.nav:before{content:"NAVEGAÇÃO";padding:0 12px 6px;color:#52615b;font:700 8px var(--body);letter-spacing:.18em}
.nav-button{position:relative;border-radius:11px;padding:11px 12px;color:#7f8e88;font-size:11px;letter-spacing:-.01em}
.nav-button:after{content:"";position:absolute;right:9px;width:4px;height:4px;border-radius:50%;background:transparent;transition:.18s}
.nav-button:hover{background:rgba(255,255,255,.035);color:#e4ece8}.nav-button.active{background:linear-gradient(90deg,rgba(53,220,135,.13),rgba(53,220,135,.025));color:#a9f5c9;box-shadow:inset 2px 0 #35dc87}.nav-button.active:after{background:#35dc87;box-shadow:0 0 12px rgba(53,220,135,.65)}
.nav-icon{width:29px;height:29px;border-radius:9px;background:rgba(255,255,255,.025);border-color:rgba(255,255,255,.07);font-size:11px}.nav-button.active .nav-icon{background:rgba(53,220,135,.09);border-color:rgba(53,220,135,.2);color:#63e99a}
.sidebar-bottom{gap:12px}.system-box{border-radius:13px;background:linear-gradient(145deg,rgba(53,220,135,.065),rgba(255,255,255,.018));padding:13px;border-color:rgba(53,220,135,.13)}.system-box small{font-size:8px;letter-spacing:.14em;text-transform:uppercase}.live-line{font-size:11px}.version{font-size:9px}
.main{padding:28px 32px 44px;position:relative;min-width:0}
.main:before{content:"SADAG";position:absolute;top:15px;right:32px;color:#34423d;font:700 8px var(--mono);letter-spacing:.16em}
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
.events{gap:6px;position:relative;padding-left:4px}.events:before{content:"";position:absolute;left:7.5px;top:6px;bottom:6px;width:1px;background:linear-gradient(180deg,rgba(229,255,243,.16),rgba(229,255,243,.02))}.event{padding:8px 0;border-bottom:0;position:relative}.event i{position:relative;z-index:1;box-shadow:0 0 0 3px #0c1917}.event strong{font-size:10px}.event p{font-size:9px}.event time{font-size:8px}
.sensor-grid{gap:8px}.sensor{padding:12px;border-radius:12px;background:rgba(255,255,255,.018)}.sensor h3{font-size:10px}.reading strong{font-size:21px}.sensor-foot{font-size:8px}
.table-card{border-radius:14px;max-height:min(64vh,620px)}.table{min-width:780px}.table thead th{position:sticky;top:0;z-index:2;background:#0e1512;box-shadow:0 1px 0 rgba(229,255,243,.09)}.table th{font-size:8px;color:#718079}.table td{font-size:10px;padding:12px 13px}.table tr:nth-child(even) td{background:rgba(255,255,255,.012)}.table tr:hover td{background:rgba(53,220,135,.035)}
.settings-layout{grid-template-columns:190px minmax(0,1fr);gap:10px}.settings-nav{padding:8px}.settings-tab{font-size:10px}.switch{border-radius:11px;background:rgba(255,255,255,.015)}
.empty{min-height:68vh}.empty-card{padding:60px 44px;border-radius:20px;position:relative;overflow:hidden}.empty-card:before{content:"";position:absolute;inset:auto -20% -65% -20%;height:280px;background:radial-gradient(circle,rgba(53,220,135,.1),transparent 67%);pointer-events:none}.empty-icon{width:72px;height:72px;border-radius:22px;color:#5eea9c;background:rgba(53,220,135,.055)}
.notification-panel,.profile-menu{background:rgba(12,19,17,.97)}
.modal{background:rgba(2,6,5,.78)}.modal-card{border-color:rgba(53,220,135,.12)}
.toast{background:#0b1210;border-color:rgba(53,220,135,.18)}
/* Other views inherit the same product language */
#view-sensores .card.pad,#view-historico .card,#view-configuracoes .card,#view-suporte .card{box-shadow:0 20px 60px rgba(0,0,0,.13)}
#view-sensores .topbar,#view-historico .topbar,#view-configuracoes .topbar,#view-suporte .topbar{padding-bottom:4px}
#view-sensores .table-card,#view-historico .table-card{background:linear-gradient(145deg,rgba(255,255,255,.025),rgba(255,255,255,.01)),rgba(10,16,14,.9)}
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

/* =========================================================
   CONTROL CENTER — a casa 3D vira o centro real da tela, sem
   card/borda em volta. Painéis finos de Segurança/Sistema ao
   lado, separados por linha, não por caixas. Nenhum elemento
   com ID usado pelo JS foi removido — só reposicionado.
   ========================================================= */
.cc-dot{width:7px;height:7px;border-radius:50%;background:currentColor;display:inline-block;margin-right:7px;color:var(--muted)}
.title-eyebrow{display:block;font:700 9px var(--mono);letter-spacing:.16em;text-transform:uppercase;color:#5eea9c;margin-bottom:6px}
.title-eyebrow i{font-style:normal;color:var(--faint);margin:0 5px}
.title-eyebrow span{color:#7f9289}
.topbar-status{display:flex;align-items:center;border:1px solid var(--border2);border-radius:9px;padding:0 12px;height:38px;font-size:11px;font-weight:700;color:var(--text);white-space:nowrap}
.topbar-clock{display:flex;align-items:center;border:1px solid var(--border2);border-radius:9px;padding:0 12px;height:38px;font:700 12px var(--mono);color:var(--muted);white-space:nowrap}
/* Estados explícitos da interface (seção 20 do pedido): nunca
   deixar um espaço vazio sem dizer por quê. Cada estado tem cor e
   ícone próprios, calculados a partir de dados reais. */
.state-banner{display:flex;align-items:center;gap:10px;padding:11px 16px;border-radius:12px;margin-bottom:14px;font-size:12px;font-weight:600;border:1px solid;animation:stateBannerIn .3s ease}
@keyframes stateBannerIn{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:none}}
.state-banner .sb-icon{font-size:15px;line-height:1;flex-shrink:0}
.state-banner small{display:block;font-weight:500;color:inherit;opacity:.72;margin-top:2px}
.state-banner.sb-offline{background:rgba(148,163,184,.08);border-color:rgba(148,163,184,.25);color:#c3ccd6}
.state-banner.sb-nodata{background:rgba(118,168,255,.08);border-color:rgba(118,168,255,.25);color:#a9c4ff}
.state-banner.sb-nolocation{background:rgba(233,189,94,.08);border-color:rgba(233,189,94,.28);color:#f0cb84}
.state-banner.sb-reconnected{background:rgba(53,220,135,.09);border-color:rgba(53,220,135,.3);color:#9cf0c0}
.state-banner.sb-alert{background:rgba(255,102,102,.1);border-color:rgba(255,102,102,.35);color:#ffb3b3}
@media(prefers-reduced-motion:reduce){.state-banner{animation:none}}

/* KPIs: número grande + rótulo técnico, no espírito das
   referências (nada de velocímetro gigante ocupando a tela). */
.kpi-row{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px}
.kpi{padding:16px 17px;display:flex;flex-direction:column;gap:5px;transition:transform .2s ease,border-color .2s ease}
.kpi:hover{transform:translateY(-2px);border-color:rgba(53,220,135,.22)}
.kpi-label{font:700 9px var(--mono);letter-spacing:.1em;text-transform:uppercase;color:var(--faint)}
.kpi-head{display:flex;align-items:center;justify-content:space-between}
.kpi-ring{position:relative;width:36px;height:36px;flex-shrink:0}
.kpi-ring svg{width:100%;height:100%;transform:rotate(-90deg)}
.ring-bg{fill:none;stroke:rgba(255,255,255,.07);stroke-width:3}
.ring-fg{fill:none;stroke:var(--status-color,var(--green));stroke-width:3;stroke-linecap:round;stroke-dasharray:100.5 100.5;stroke-dashoffset:100.5;transition:stroke-dashoffset .6s cubic-bezier(.22,.61,.36,1),stroke .3s ease}
@media(prefers-reduced-motion:reduce){.ring-fg{transition:none}}
.kpi-badge{position:absolute;inset:0;display:grid;place-items:center;font-size:12px;color:var(--status-color,var(--green))}
.kpi-value-row{display:flex;align-items:baseline;gap:6px}
.kpi-value{font:800 34px var(--mono);line-height:1;color:var(--text)}
.kpi-value-sm{font-size:20px}
.kpi-unit{font-size:11px;color:var(--muted)}
.kpi-sub{font-size:11px;font-weight:700;color:var(--status-color,var(--green))}
.kpi-msg{font-size:10px;color:var(--faint)}
.kpi-primary{border-color:var(--status-color,rgba(53,220,135,.3))}

/* Layout principal: coluna larga (gráfico + leituras + eventos) e
   coluna lateral compacta (mapa do ambiente + detalhes), como nas
   referências — não mais uma casa dominando a tela inteira. */
.main-grid{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:14px;align-items:start}
.main-col{display:grid;gap:14px}
.side-col{display:grid;gap:14px}
.chart-empty{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;color:var(--muted);font:700 12px var(--display);letter-spacing:.06em;text-align:center;text-transform:uppercase}
.chart-empty small{font:600 9px var(--mono);color:var(--faint);letter-spacing:.1em;text-transform:none}
.cc-row{display:flex;flex-direction:column;gap:3px;padding:10px 0;border-bottom:1px solid rgba(229,255,243,.06)}
.cc-row:last-child{border-bottom:0}
.cc-row span{font-size:9px;letter-spacing:.08em;text-transform:uppercase;color:#657871}
.cc-row strong{font-size:13px;color:#e7f1ec;overflow-wrap:anywhere}

/* Cadeia de saúde do sistema (seção 12): reforça visualmente a
   arquitetura real do Sadag, sensor -> ESP32 -> Wi-Fi -> API
   -> banco -> dashboard, com cor derivada de estado real. */
.health-chain{display:flex;flex-direction:column;margin-top:6px}
.health-node{display:grid;grid-template-columns:16px 1fr auto;align-items:center;gap:9px;padding:8px 0}
.health-dot{width:8px;height:8px;border-radius:50%;background:currentColor;color:var(--faint)}
.health-dot.good{color:var(--green)}
.health-name{font:700 10px var(--mono);letter-spacing:.05em;text-transform:uppercase;color:var(--muted)}
.health-node strong{font-size:11px;color:var(--text)}
.health-line{width:1px;height:12px;background:rgba(229,255,243,.14);margin-left:7.5px}
@media(max-width:1250px){.main-grid{grid-template-columns:1fr}.side-col{order:-1}}
@media(max-width:780px){.kpi-row{grid-template-columns:repeat(2,1fr)}.topbar-clock{display:none}}
@media(max-width:500px){.kpi-row{grid-template-columns:1fr}.kpi-value{font-size:28px}.topbar-status span:last-child{max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}}
</style>
</head>
<body>
<div class="app">
<button type="button" id="sidebarToggle" class="sidebar-toggle" title="Mostrar/ocultar menu" aria-label="Mostrar/ocultar menu">☰</button>
<aside class="sidebar">
  <a class="brand" href="#dashboard" data-view="dashboard"><img class="brand-mark" src="/view/html/sadag-logo.png" alt="Sadag"><span><small>Monitoramento IoT</small><strong>Sadag</strong></span></a>
  <nav class="nav">
    <button class="nav-button active" data-view="dashboard"><span class="nav-icon">▦</span><span data-i18n="dashboard">Dashboard</span></button>
    <button class="nav-button" data-view="sensores"><span class="nav-icon">◉</span><span data-i18n="sensors">Sensores</span></button>
    <button class="nav-button" data-view="historico"><span class="nav-icon">≋</span><span data-i18n="history">Histórico</span></button>
    <button class="nav-button" data-view="configuracoes"><span class="nav-icon">⚙</span><span data-i18n="settings">Configurações</span></button>
    <button class="nav-button" data-view="suporte"><span class="nav-icon">?</span><span data-i18n="support">Suporte</span></button>
  </nav>
  <div class="sidebar-bottom"><div class="system-box"><small>Sistema</small><div class="live-line"><i class="dot"></i><strong id="systemText">Aguardando dispositivo</strong></div><div class="version">Sadag · 2.0</div></div></div>
</aside>
<main class="main">
  <section class="view active" id="view-dashboard">
    <div class="topbar">
      <div class="title"><span class="title-eyebrow">Sadag <i>·</i> <span id="titleUser"><?= $userName ?></span></span><h1>Monitoramento em tempo real</h1></div>
      <div class="toolbar">
        <div class="topbar-status" title="Estado geral do sistema"><i class="cc-dot" id="globalStatusDot"></i><span id="globalStatusLabel">Aguardando leitura</span></div>
        <div class="topbar-clock" id="ccClock">--:--:--</div>
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

      <div class="state-banner" id="stateBanner" hidden></div>

      <!-- Linha de KPIs, no espírito das referências: números
           grandes + rótulo técnico + contexto, não velocímetro
           gigante. Todo valor vem de dados reais já carregados. -->
      <div class="kpi-row">
        <article class="card kpi kpi-primary" id="statusCard">
          <div class="kpi-head"><span class="kpi-label">PPM ATUAL</span><span class="kpi-ring"><svg viewBox="0 0 40 40"><circle class="ring-bg" cx="20" cy="20" r="16"/><circle class="ring-fg" id="kpiRing" cx="20" cy="20" r="16"/></svg><span class="kpi-badge" id="statusIcon">✓</span></span></div>
          <div class="kpi-value-row"><strong class="kpi-value" id="currentPpm">--</strong><span class="kpi-unit">ppm</span></div>
          <span class="kpi-sub" id="statusLabel">Seguro</span>
          <span class="kpi-msg" id="statusMessage">Aguardando leitura</span>
        </article>
        <article class="card kpi">
          <span class="kpi-label">LEITURAS HOJE</span>
          <strong class="kpi-value" id="kpiReadingsToday">--</strong>
          <span class="kpi-sub" id="kpiReadingsTodaySub">--</span>
        </article>
        <article class="card kpi">
          <span class="kpi-label">LEITURAS NORMAIS</span>
          <strong class="kpi-value" id="kpiNormalPct">--</strong>
          <span class="kpi-sub">do total registrado hoje</span>
        </article>
        <article class="card kpi">
          <span class="kpi-label">ÚLTIMA COMUNICAÇÃO</span>
          <strong class="kpi-value kpi-value-sm" id="syncAge">sincronizando...</strong>
          <span class="kpi-sub" id="lastReadingTime">--</span>
        </article>
      </div>

      <!-- hidden: mantidos só porque o JS ainda escreve neles
           (gauge antigo e campos que viraram redundantes depois da
           reorganização), sem nenhuma leitura visual perdida. -->
      <span hidden>
        <svg viewBox="0 0 320 210"><path id="gaugeNeedle" d="M160 164 L160 62"/></svg>
        <span id="gaugeMax">700+ ppm</span>
        <span id="selectedDeviceLabel">--</span>
        <span id="systemState">--</span>
      </span>

      <div class="main-grid">
        <div class="main-col">
          <article class="card chart-card">
            <div class="card-head chart-head"><div><h2 class="card-title">Concentração de gás (PPM)</h2><p class="card-sub">Leituras reais gravadas pelo ESP32.</p></div><div class="chart-actions"><button class="btn small range active" data-range="today">Hoje</button><button class="btn small range" data-range="24h">24h</button><button class="btn small range" data-range="all">Tudo</button></div></div>
            <div class="chart-wrap" id="chartWrap"><svg id="mainChart" viewBox="0 0 920 310" preserveAspectRatio="none"><defs><linearGradient id="chartAreaGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#20d7b2" stop-opacity=".28"/><stop offset="1" stop-color="#20d7b2" stop-opacity="0"/></linearGradient></defs><g id="chartGrid"></g><path id="chartArea" fill="url(#chartAreaGradient)"></path><path id="chartLine" fill="none" stroke="#20d7b2" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path><g id="chartPoints"></g><line id="hoverLine" y1="18" y2="280" stroke="rgba(255,255,255,.22)" stroke-dasharray="3 4" opacity="0"/><circle id="hoverDot" r="5" fill="#20d7b2" stroke="#fff" stroke-width="2" opacity="0"/></svg><div class="chart-tip" id="chartTip"></div><div class="chart-empty" id="chartEmptyMsg" hidden>AGUARDANDO DADOS DO DISPOSITIVO<br><small>As leituras do ESP32 aparecem aqui assim que chegarem.</small></div></div>
            <div class="summary"><div class="summary-cell"><span>Mínimo</span><strong id="statMin">--</strong> <small>ppm</small></div><div class="summary-cell"><span>Média</span><strong id="statAvg">--</strong> <small>ppm</small></div><div class="summary-cell"><span>Máximo</span><strong id="statMax">--</strong> <small>ppm</small></div><div class="summary-cell"><span>Última leitura</span><strong id="statLast">--</strong> <small>ppm</small></div></div>
          </article>

          <article class="card pad">
            <div class="card-head"><div><h2 class="card-title">Leituras recentes</h2><p class="card-sub">Últimos registros gravados no banco.</p></div><button class="btn small" data-view="historico">Ver histórico completo</button></div>
            <div class="table-card" style="border:0;box-shadow:none;background:transparent;margin-top:10px"><table class="table"><thead><tr><th>Horário</th><th>Ambiente</th><th>PPM</th><th>Status</th><th>Dispositivo</th></tr></thead><tbody id="recentReadingsTable"></tbody></table></div>
          </article>

          <article class="card pad">
            <div class="card-head"><h2 class="card-title">Eventos de atenção/alerta</h2><button class="btn small" data-view="historico">Ver tudo</button></div>
            <div class="events" id="eventList" style="margin-top:14px"></div>
          </article>
        </div>

        <div class="side-col">
          <article class="card pad">
            <div class="card-head"><h2 class="card-title">Detalhes do dispositivo</h2></div>
            <div class="cc-row"><span>Ambiente</span><strong id="selectedLocationLabel">--</strong></div>
            <div class="cc-row"><span>Sensor</span><strong id="ccSensorType">--</strong></div>
            <div class="cc-row"><span>Wi-Fi (RSSI)</span><strong id="sysRssi">--</strong></div>
            <div class="cc-row"><span>IP</span><strong id="ipAddress">--</strong></div>
            <div class="cc-row"><span>Última comunicação</span><strong id="houseLastSeen">--</strong></div>
          </article>

          <article class="card pad">
            <div class="card-head"><h2 class="card-title">System Health</h2><p class="card-sub">Cadeia real de comunicação do Sadag.</p></div>
            <div class="health-chain">
              <div class="health-node"><i class="health-dot" id="healthEsp32Dot"></i><span class="health-name">ESP32</span><strong id="houseWifi">--</strong></div>
              <div class="health-line"></div>
              <div class="health-node"><i class="health-dot" id="healthServerDot"></i><span class="health-name">Servidor</span><strong id="sysServer">Verificando</strong></div>
              <div class="health-line"></div>
              <div class="health-node"><i class="health-dot" id="healthApiDot"></i><span class="health-name">API</span><strong id="sysApi">Verificando</strong></div>
              <div class="health-line"></div>
              <div class="health-node"><i class="health-dot" id="healthDbDot"></i><span class="health-name">Banco</span><strong id="sysDb">Verificando</strong></div>
              <div class="health-line"></div>
              <div class="health-node"><i class="health-dot good"></i><span class="health-name">Dashboard</span><strong>Ativo</strong></div>
            </div>
          </article>

          <div class="metrics metrics-2">
            <article class="card metric"><div class="metric-top"><div class="metric-icon">PPM</div><div><span>Média</span><strong id="metricAvg">--</strong><small>hoje</small></div></div><div class="progress"><i id="avgProgress" style="width:0"></i></div></article>
            <article class="card metric"><div class="metric-top"><div class="metric-icon">Wi</div><div><span>Comunicação</span><strong id="metricWifi">--</strong><small>rede Wi-Fi</small></div></div><div class="progress"><i id="wifiProgress" style="width:0"></i></div></article>
          </div>
        </div>
      </div>
    </div>
    <div id="dashboardEmpty" class="empty"><div class="card empty-card"><div class="empty-icon">⌁</div><h2>Conecte seu primeiro dispositivo</h2><p>O dashboard completo aparece assim que um ESP32 for encontrado na rede Wi-Fi e vinculado à sua conta. Depois disso, o dispositivo, o ambiente, o histórico e as leituras ficam integrados em uma única tela.</p><div class="empty-actions"><button class="btn primary" id="connectEmpty">🔗 Procurar dispositivo na rede</button><button class="btn" data-view="sensores">Ver dispositivos</button></div></div></div>
  </section>

  <section class="view" id="view-sensores"><div class="topbar"><div class="title"><h1>Sensores</h1><p>Descubra ESP32 pela presença Wi-Fi, vincule o dispositivo e defina o ambiente instalado.</p></div><div class="toolbar"><button class="btn primary" id="discoverButton">⌁ Procurar na rede</button><button class="btn" id="newLocationButton">＋ Novo ambiente</button></div></div><div class="card pad" style="margin-bottom:12px"><div class="card-head"><div><h2 class="card-title">Dispositivos da sua conta</h2><p class="card-sub">Tudo aqui é persistido no MySQL.</p></div><span class="pill" id="deviceCountPill">0 dispositivos</span></div></div><div class="card table-card"><table class="table"><thead><tr><th>Nome</th><th>ESP32</th><th>Ambiente</th><th>Sensor</th><th>Status</th><th>Última leitura</th><th>Ações</th></tr></thead><tbody id="sensorTable"></tbody></table></div></section>

  <section class="view" id="view-historico"><div class="topbar"><div class="title"><h1>Histórico</h1><p>Cada leitura recebida do ESP32 permanece registrada no banco de dados.</p></div><div class="toolbar"><button class="btn" id="historyExport">Exportar CSV</button></div></div><div class="filters"><select class="input" id="historyDay"><option value="today">Hoje</option><option value="yesterday">Ontem</option><option value="custom">Escolher data...</option><option value="">Todos os dias</option></select><input class="input" id="historyDate" type="date" hidden><input class="input" id="historySearch" placeholder="Pesquisar dispositivo ou evento"><select class="input" id="historyStatus"><option value="">Todos os status</option><option value="normal">Normal</option><option value="atencao">Atenção</option><option value="perigo">Perigo</option></select><input class="input" id="historyMin" type="number" placeholder="PPM mínimo"><input class="input" id="historyMax" type="number" placeholder="PPM máximo"></div><div class="card table-card"><table class="table"><thead><tr><th>Data/Hora</th><th>Dispositivo</th><th>Ambiente</th><th>PPM</th><th>Status</th><th>RSSI</th></tr></thead><tbody id="historyTable"></tbody></table><div class="pagination"><span id="historyCount">0 registros</span><div><button class="btn small" id="historyPrev">Anterior</button> <span id="historyPage">1/1</span> <button class="btn small" id="historyNext">Próxima</button></div></div></div></section>

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
const state={devices:[],locations:[],selected:null,readings:[],history:[],range:'today',historyPage:1,historyPerPage:20,tickets:[],ticketId:null,lastMsgId:0,supportPoll:null,syncing:false,messages:[],thinking:false,notifications:[],profile:null,settings:null,selectedIcon:'👤',lastDataAt:null,wasOnline:null,displayedPpm:null};
const icons=['👤','🛡️','🧪','🔬','🏠','🏭','🛰️','⚙️','🟢','🔵','🟣','⭐','🧰','💡','📡','🧠'];
const fallbackThresholds={safe:400,alert:550,critical:700};
const motionQuery=matchMedia('(prefers-reduced-motion: reduce)');
let REDUCED_MOTION=motionQuery.matches;
motionQuery.addEventListener('change',e=>{REDUCED_MOTION=e.matches});
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
function activateView(name){document.querySelectorAll('.view').forEach(v=>v.classList.toggle('active',v.id===`view-${name}`));document.querySelectorAll('[data-view]').forEach(b=>b.classList.toggle('active',b.dataset.view===name));history.replaceState(null,'',`#${name}`);if(name!=='suporte')stopSupportPoll();if(name==='historico')renderHistory();if(name==='suporte'){loadTickets();updateChatHeader();renderChat();if(state.ticketId)startSupportPoll();}if(name==='configuracoes')loadSettings();}
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
function tickSyncAge(){const clk=document.getElementById('ccClock');if(clk)clk.textContent=new Date().toLocaleTimeString('pt-BR');const el=document.getElementById('syncAge');const fresh=!!(state.lastDataAt&&(Date.now()-state.lastDataAt)/1000<=8);['sysServer','sysApi','sysDb'].forEach(id=>{const e=document.getElementById(id);if(!e)return;e.textContent=fresh?(id==='sysDb'?'Sincronizado':'Operacional'):(state.lastDataAt?'Instável':'Verificando');e.style.color=fresh?'var(--green)':'var(--yellow)'});const dotColor=fresh?'var(--green)':'var(--yellow)';['healthServerDot','healthApiDot','healthDbDot'].forEach(id=>{const dot=document.getElementById(id);if(dot)dot.style.color=dotColor});if(!el)return;if(!state.lastDataAt){el.textContent='sincronizando...';return}const s=Math.max(0,Math.round((Date.now()-state.lastDataAt)/1000));el.textContent=s<=1?'sincronizado agora':`sincronizado há ${s}s`;el.style.color=s<=3?'#8fe8b6':'#e8c98f'}
async function refresh(){try{await Promise.all([loadLocations(),loadSettings(),loadProfile(),loadDevices()]);await loadData();await loadNotifications();await loadAudit();await loadTickets();updateHome()}catch(e){toast(e.message);console.error('refresh() falhou:',e)}}
function updateHome(){const has=state.devices.length>0;document.getElementById('dashboardConnected').hidden=!has;document.getElementById('dashboardEmpty').style.display=has?'none':'grid';document.getElementById('systemText').textContent=has?'Operacional':'Aguardando dispositivo';}
function updateDeviceState(){updateHome();const d=selectedDevice();document.getElementById('selectedDeviceLabel').textContent=d?.name||'--';document.getElementById('selectedLocationLabel').textContent=d?locName(d.location_id):'--';document.getElementById('networkStatus').textContent=d?.status==='online'?'Online':'Offline';document.getElementById('networkStatusBox').style.color=d?.status==='online'?'var(--green)':'var(--red)';document.getElementById('ipAddress').textContent=d?.last_seen_ip||d?.wifi_status||'--';document.getElementById('houseWifi').textContent=d?.status==='online'?'Online':'Offline';const esp32Dot=document.getElementById('healthEsp32Dot');if(esp32Dot)esp32Dot.style.color=d?.status==='online'?'var(--green)':'var(--faint)';document.getElementById('houseLastSeen').textContent=state.readings.at(-1)?fmtDate(state.readings.at(-1).time):'--';const rssiEl=document.getElementById('sysRssi');if(rssiEl)rssiEl.textContent=state.readings.at(-1)?.rssi!=null?state.readings.at(-1).rssi+' dBm':'--';const sensorEl=document.getElementById('ccSensorType');if(sensorEl)sensorEl.textContent=d?.sensor_type||'--';updateStateBanner(d)}
/* Estados explícitos (seção 20): o usuário nunca deve ver um
   espaço em branco sem entender por quê. Prioridade: alerta de gás
   > offline > sem ambiente definido > sem leitura ainda > acabou de
   reconectar > tudo normal (banner some). */
function updateStateBanner(d){
  const el=document.getElementById('stateBanner');
  if(!el)return;
  if(!d){el.hidden=true;state.wasOnline=null;return}
  const nowOnline=d.status==='online';
  const reconnected=state.wasOnline===false&&nowOnline;
  state.wasOnline=nowOnline;
  function show(cls,icon,title,sub){el.hidden=false;el.className='state-banner '+cls;el.innerHTML=`<span class="sb-icon">${icon}</span><span>${esc(title)}${sub?`<small>${esc(sub)}</small>`:''}</span>`}
  if(!nowOnline){show('sb-offline','⭘','Dispositivo offline.',state.readings.length?`Última comunicação: ${fmtDate(state.readings.at(-1).time)}`:'Nenhuma comunicação registrada ainda.');return}
  const ppm=state.readings.at(-1)?.value;
  if(ppm!=null&&statusFor(Number(ppm)).key==='perigo'){show('sb-alert','⚠','Concentração de gás em nível de alerta!',`${Math.round(ppm)} ppm em ${locName(d.location_id)} — verifique o ambiente imediatamente.`);return}
  if(!d.location_id){show('sb-nolocation','⚠','Este dispositivo ainda não tem um ambiente definido.','Vá em Sensores e escolha onde ele está instalado pra ver a casa completa.');return}
  if(!state.readings.length){show('sb-nodata','⏳','Aguardando a primeira leitura do sensor...','O ESP32 está online — os dados devem aparecer em instantes.');return}
  if(reconnected){show('sb-reconnected','✓','Dispositivo reconectado.','A comunicação com o ESP32 voltou ao normal.');clearTimeout(state._reconnectTimer);state._reconnectTimer=setTimeout(()=>{el.hidden=true},6000);return}
  el.hidden=true;
}
function updateDashboard(){updateDeviceState();const d=selectedDevice();const last=state.readings.at(-1);renderGauge(last?.value??d?.latest_ppm??NaN);document.getElementById('lastReadingTime').textContent=last?fmtDate(last.time):'--';document.getElementById('metricAvg').textContent=d?.stats?.average_ppm!=null?Math.round(d.stats.average_ppm):'--';document.getElementById('avgProgress').style.width=d?.stats?.average_ppm?`${Math.min(100,d.stats.average_ppm/thresholds().critical*100)}%`:'0%';document.getElementById('metricWifi').textContent=d?.status==='online'?'Online':'Offline';document.getElementById('wifiProgress').style.width=d?.status==='online'?'100%':'15%';renderChart();renderEvents();renderSensors();updateKpis();renderRecentReadings()}
/* KPIs de topo: só números reais já calculados a partir do que já
   foi carregado (state.history) — nenhuma requisição nova. */
function updateKpis(){
  const todayStart=new Date();todayStart.setHours(0,0,0,0);
  const today=state.history.filter(r=>new Date(String(r.created_at).replace(' ','T'))>=todayStart);
  const elReadings=document.getElementById('kpiReadingsToday');
  if(elReadings)elReadings.textContent=today.length;
  const counts={normal:0,atencao:0,perigo:0};
  today.forEach(r=>{if(counts[r.reading_status]!=null)counts[r.reading_status]++});
  const elSub=document.getElementById('kpiReadingsTodaySub');
  if(elSub)elSub.textContent=today.length?`${counts.atencao+counts.perigo} fora da faixa normal`:'nenhuma leitura hoje ainda';
  const elNormal=document.getElementById('kpiNormalPct');
  if(elNormal)elNormal.textContent=today.length?Math.round(counts.normal/today.length*100)+'%':'--';
}
function renderRecentReadings(){
  const tb=document.getElementById('recentReadingsTable');
  if(!tb)return;
  const rows=state.history.slice(0,6);
  tb.innerHTML=rows.length?rows.map(r=>`<tr><td>${fmtDate(r.created_at)}</td><td>${esc(r.location)}</td><td><strong>${Math.round(r.ppm)} ppm</strong></td><td><span class="status ${r.reading_status}">${esc(r.reading_status)}</span></td><td>${esc(r.device)}</td></tr>`).join(''):'<tr><td colspan="5">Nenhuma leitura registrada ainda.</td></tr>';
}
function setGlobalStatus(st){const dot=document.getElementById('globalStatusDot'),lbl=document.getElementById('globalStatusLabel');if(!dot||!lbl)return;if(!st){dot.style.color='var(--muted)';lbl.textContent='Aguardando leitura';return}dot.style.color=st.color;lbl.textContent=st.key==='perigo'?'ALERTA — CONCENTRAÇÃO ELEVADA':st.key==='atencao'?'ATENÇÃO — ALTERAÇÃO DETECTADA':'AMBIENTE SEGURO'}
function animatePpmTo(target){
  const el=document.getElementById('currentPpm');
  if(!el)return;
  const from=state.displayedPpm??target;
  cancelAnimationFrame(state._ppmRaf);
  if(REDUCED_MOTION||Math.abs(target-from)<1){el.textContent=Math.round(target);state.displayedPpm=target;return}
  const start=performance.now(),dur=550;
  function step(now){
    const t=Math.min(1,(now-start)/dur),eased=1-Math.pow(1-t,3);
    el.textContent=Math.round(from+(target-from)*eased);
    if(t<1)state._ppmRaf=requestAnimationFrame(step);else state.displayedPpm=target;
  }
  state._ppmRaf=requestAnimationFrame(step);
}
function renderGauge(v){const n=Number(v);const ring=document.getElementById('kpiRing');if(!Number.isFinite(n)){document.getElementById('currentPpm').textContent='--';state.displayedPpm=null;setGlobalStatus(null);if(ring)ring.style.strokeDashoffset='100.5';return}const st=statusFor(n),root=document.getElementById('statusCard');root.style.setProperty('--status-color',st.color);document.getElementById('statusIcon').textContent=st.icon;document.getElementById('statusLabel').textContent=st.label;document.getElementById('statusMessage').textContent=st.msg;animatePpmTo(n);if(ring)ring.style.strokeDashoffset=String(100.5*(1-Math.min(1,n/thresholds().critical)));document.getElementById('gaugeNeedle').setAttribute('transform',`rotate(${-55+Math.min(1,n/thresholds().critical)*110} 160 164)`);document.getElementById('systemState').textContent=st.label;document.getElementById('gaugeMax').textContent=thresholds().critical+'+ ppm';document.body.classList.toggle('critical-flash',st.key==='perigo');setGlobalStatus(st)}
function series(){const now=Date.now();if(state.range==='today'){const d=new Date();d.setHours(0,0,0,0);return state.readings.filter(x=>x.time>=d)}if(state.range==='24h')return state.readings.filter(x=>x.time>=new Date(now-86400000));return state.readings}
function renderChart(){const arr=series();const svg=document.getElementById('mainChart');const grid=document.getElementById('chartGrid');const emptyEl=document.getElementById('chartEmptyMsg');if(!arr.length){if(emptyEl)emptyEl.hidden=false;svg.style.opacity='0';document.getElementById('chartLine').setAttribute('d','');document.getElementById('chartArea').setAttribute('d','');grid.innerHTML='';['statMin','statAvg','statMax','statLast'].forEach(i=>document.getElementById(i).textContent='--');return}if(emptyEl)emptyEl.hidden=true;svg.style.opacity='1';const W=920,H=310,L=42,R=12,T=18,B=280,max=Math.max(thresholds().critical*1.15,100,...arr.map(x=>x.value));const pts=arr.map((p,i)=>({x:L+(i/Math.max(1,arr.length-1))*(W-L-R),y:B-(p.value/max)*(B-T),p}));let d=`M ${pts[0].x} ${pts[0].y}`;for(let i=1;i<pts.length;i++){const a=pts[i-1],b=pts[i],cx=(a.x+b.x)/2;d+=` Q ${a.x} ${a.y} ${cx} ${(a.y+b.y)/2}`}d+=` T ${pts.at(-1).x} ${pts.at(-1).y}`;document.getElementById('chartLine').setAttribute('d',d);document.getElementById('chartArea').setAttribute('d',`${d} L ${pts.at(-1).x} ${B} L ${pts[0].x} ${B} Z`);const y=v=>B-(v/max)*(B-T);const lines=[0,Math.round(max*.25),Math.round(max*.5),Math.round(max*.75),Math.round(max)];grid.innerHTML=lines.map(v=>`<line x1="${L}" x2="${W-R}" y1="${y(v)}" y2="${y(v)}" stroke="rgba(255,255,255,.06)"/><text x="5" y="${y(v)+4}" fill="#7e8ca1" font-size="10">${v}</text>`).join('')+`<line x1="${L}" x2="${W-R}" y1="${y(thresholds().alert)}" y2="${y(thresholds().alert)}" stroke="#ffc857" stroke-dasharray="6 6"/><line x1="${L}" x2="${W-R}" y1="${y(thresholds().critical)}" y2="${y(thresholds().critical)}" stroke="#ff5757" stroke-dasharray="6 6"/>`;document.getElementById('chartPoints').innerHTML=pts.map((p,i)=>i%Math.max(1,Math.floor(pts.length/12))===0||i===pts.length-1?`<circle cx="${p.x}" cy="${p.y}" r="3" fill="#20d7b2"/>`:'').join('');const vals=arr.map(x=>x.value);document.getElementById('statMin').textContent=Math.round(Math.min(...vals));document.getElementById('statAvg').textContent=Math.round(vals.reduce((a,b)=>a+b,0)/vals.length);document.getElementById('statMax').textContent=Math.round(Math.max(...vals));document.getElementById('statLast').textContent=Math.round(vals.at(-1));svg._points=pts;svg._series=arr;bindChartHover()}
/* O gráfico já tinha a linha de hover e o tooltip no HTML, mas
   nada realmente movia eles ao passar o mouse — a interação nunca
   tinha sido terminada. */
function bindChartHover(){
  const wrap=document.getElementById('chartWrap'),svg=document.getElementById('mainChart'),tip=document.getElementById('chartTip'),hoverLine=document.getElementById('hoverLine'),hoverDot=document.getElementById('hoverDot');
  if(!wrap||wrap.dataset.bound)return;
  wrap.dataset.bound='1';
  wrap.addEventListener('pointermove',e=>{
    const pts=svg._points;
    if(!pts||!pts.length)return;
    const rect=svg.getBoundingClientRect();
    const vb=svg.viewBox.baseVal;
    const svgX=((e.clientX-rect.left)/rect.width)*vb.width+vb.x;
    let nearest=pts[0],best=Infinity;
    pts.forEach(p=>{const dist=Math.abs(p.x-svgX);if(dist<best){best=dist;nearest=p}});
    hoverLine.setAttribute('x1',nearest.x);hoverLine.setAttribute('x2',nearest.x);hoverLine.setAttribute('opacity',1);
    hoverDot.setAttribute('cx',nearest.x);hoverDot.setAttribute('cy',nearest.y);hoverDot.setAttribute('opacity',1);
    tip.classList.add('show');
    tip.style.left=Math.min(88,Math.max(2,(nearest.x/vb.width)*100))+'%';
    tip.style.top=Math.max(2,(nearest.y/vb.height)*100-14)+'%';
    tip.innerHTML=`<strong>${Math.round(nearest.p.value)} ppm</strong><small>${fmtDate(nearest.p.time)}</small>`;
  });
  wrap.addEventListener('pointerleave',()=>{
    hoverLine.setAttribute('opacity',0);hoverDot.setAttribute('opacity',0);tip.classList.remove('show');
  });
}
function renderEvents(){const el=document.getElementById('eventList');const ev=state.history.filter(r=>r.reading_status!=='normal').slice(0,8);el.innerHTML=ev.length?ev.map(r=>`<div class="event ${r.reading_status==='perigo'?'bad':'warn'}"><i></i><div><strong>${r.reading_status==='perigo'?'Perigo':'Atenção'} · ${esc(r.device)}</strong><p>${esc(r.ppm)} ppm registrado no ambiente ${esc(r.location)}</p></div><time>${fmtDate(r.created_at)}</time></div>`).join(''):'<div class="card-sub">Nenhum evento de alerta registrado.</div>'}
function renderSensors(){document.getElementById('sensorTable').innerHTML=state.devices.map(d=>`<tr><td><strong>${esc(d.name)}</strong></td><td>${esc(d.esp32_id)}</td><td>${esc(locName(d.location_id))}</td><td>${esc(d.sensor_type||'MQ-6')}</td><td><span class="status ${d.status==='online'?'normal':'perigo'}">${esc(d.status)}</span></td><td>${d.latest_ppm!=null?Math.round(d.latest_ppm)+' ppm':'--'}</td><td><button class="btn small" data-select="${d.id}">Selecionar</button> <button class="btn small danger" data-delete-device="${d.id}">Excluir</button></td></tr>`).join('')||'<tr><td colspan="7">Nenhum dispositivo.</td></tr>';document.getElementById('deviceCountPill').textContent=`${state.devices.length} dispositivo${state.devices.length===1?'':'s'}`}
async function deleteDevice(id){const d=state.devices.find(x=>Number(x.id)===Number(id));if(!d)return;if(!confirm(`Excluir o dispositivo "${d.name}"? As leituras registradas dele também serão apagadas. Essa ação não pode ser desfeita.`))return;try{await api('manage.php',{method:'POST',body:JSON.stringify({action:'delete_device',id})});if(Number(state.selected)===Number(id))state.selected=null;await loadDevices();await loadData();updateHome();toast('Dispositivo removido.')}catch(e){toast(e.message)}}
function dayKey(d){return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`}
function dayFilterMatch(createdAt){
  const day=document.getElementById('historyDay').value;
  if(!day)return true;
  const d=new Date(String(createdAt).replace(' ','T'));
  if(day==='today')return dayKey(d)===dayKey(new Date());
  if(day==='yesterday'){const y=new Date();y.setDate(y.getDate()-1);return dayKey(d)===dayKey(y)}
  if(day==='custom'){const v=document.getElementById('historyDate').value;return !!v&&dayKey(d)===v}
  return true;
}
function filteredHistory(){const q=document.getElementById('historySearch').value.toLowerCase(),st=document.getElementById('historyStatus').value,min=Number(document.getElementById('historyMin').value||-Infinity),max=Number(document.getElementById('historyMax').value||Infinity);return state.history.filter(r=>(!q||`${r.device} ${r.location}`.toLowerCase().includes(q))&&(!st||r.reading_status===st)&&Number(r.ppm)>=min&&Number(r.ppm)<=max&&dayFilterMatch(r.created_at))}
function renderHistory(){const rows=filteredHistory(),pages=Math.max(1,Math.ceil(rows.length/state.historyPerPage));state.historyPage=Math.min(state.historyPage,pages);const start=(state.historyPage-1)*state.historyPerPage,view=rows.slice(start,start+state.historyPerPage);document.getElementById('historyTable').innerHTML=view.map(r=>`<tr><td>${fmtDate(r.created_at)}</td><td>${esc(r.device)}</td><td>${esc(r.location)}</td><td><strong>${Math.round(r.ppm)} ppm</strong></td><td><span class="status ${r.reading_status}">${esc(r.reading_status)}</span></td><td>${r.wifi_rssi??'--'} dBm</td></tr>`).join('')||'<tr><td colspan="6">Nenhuma leitura encontrada.</td></tr>';document.getElementById('historyCount').textContent=`${rows.length} registros`;document.getElementById('historyPage').textContent=`${state.historyPage}/${pages}`;document.getElementById('historyPrev').disabled=state.historyPage<=1;document.getElementById('historyNext').disabled=state.historyPage>=pages}
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
async function saveLocation(e){e.preventDefault();try{await api('manage.php',{method:'POST',body:JSON.stringify({action:'create_location',name:document.getElementById('locationName').value.trim(),sector:document.getElementById('locationSector').value.trim(),floor:document.getElementById('locationFloor').value.trim(),description:document.getElementById('locationDescription').value.trim()})});closeModal('locationModal');document.getElementById('locationForm').reset();await loadLocations();toast('Ambiente registrado.')}catch(e){toast(e.message)}}

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
function bind(){document.querySelectorAll('[data-view]').forEach(b=>b.addEventListener('click',e=>{if(b.tagName==='A')e.preventDefault();activateView(b.dataset.view)}));document.querySelectorAll('[data-close]').forEach(b=>b.addEventListener('click',()=>closeModal(b.dataset.close)));document.getElementById('deviceSelector').addEventListener('change',async e=>{state.selected=Number(e.target.value);await loadData()});document.getElementById('connectButton').addEventListener('click',()=>{openModal('connectModal');scanNetwork()});document.getElementById('connectEmpty').addEventListener('click',()=>{openModal('connectModal');scanNetwork()});document.getElementById('discoverButton').addEventListener('click',()=>{openModal('connectModal');scanNetwork()});document.getElementById('scanNetwork').addEventListener('click',scanNetwork);document.getElementById('connectForm').addEventListener('submit',connectDevice);document.getElementById('joinForm').addEventListener('submit',joinDevice);document.getElementById('locationForm').addEventListener('submit',saveLocation);document.getElementById('newLocationButton').addEventListener('click',()=>openModal('locationModal'));document.getElementById('saveSettings').addEventListener('click',saveSettings);document.getElementById('language').addEventListener('change',e=>applyLanguage(e.target.value));document.querySelectorAll('.settings-tab').forEach(b=>b.addEventListener('click',()=>{document.querySelectorAll('.settings-tab').forEach(x=>x.classList.toggle('active',x===b));document.querySelectorAll('.settings-pane').forEach(p=>p.classList.toggle('active',p.id===`settings-${b.dataset.settings}`))}));document.getElementById('profileAvatar').addEventListener('click',()=>document.getElementById('profileMenu').classList.toggle('open'));document.getElementById('openProfile').addEventListener('click',()=>{document.getElementById('profileMenu').classList.remove('open');openModal('profileModal')});document.getElementById('profileForm').addEventListener('submit',saveProfile);document.getElementById('iconGrid').addEventListener('click',e=>{const b=e.target.closest('[data-icon]');if(!b)return;state.selectedIcon=b.dataset.icon;renderIcons()});document.getElementById('bellButton').addEventListener('click',()=>document.getElementById('notificationPanel').classList.toggle('open'));document.getElementById('readNotifications').addEventListener('click',async()=>{await api('app.php?action=read_notifications',{method:'POST',body:'{}'});await loadNotifications()});document.getElementById('historyExport').addEventListener('click',()=>exportRows(filteredHistory(),'aeris-historico.csv'));document.getElementById('historyDay').addEventListener('change',e=>{document.getElementById('historyDate').hidden=e.target.value!=='custom';state.historyPage=1;renderHistory()});document.getElementById('historyDate').addEventListener('input',()=>{state.historyPage=1;renderHistory()});['historySearch','historyStatus','historyMin','historyMax'].forEach(id=>document.getElementById(id).addEventListener('input',()=>{state.historyPage=1;renderHistory()}));document.getElementById('historyPrev').addEventListener('click',()=>{state.historyPage--;renderHistory()});document.getElementById('historyNext').addEventListener('click',()=>{state.historyPage++;renderHistory()});document.querySelectorAll('[data-range]').forEach(b=>b.addEventListener('click',()=>{state.range=b.dataset.range;document.querySelectorAll('[data-range]').forEach(x=>x.classList.toggle('active',x===b));renderChart()}));document.getElementById('newConvo').addEventListener('click',newConversation);document.getElementById('closeTicket').addEventListener('click',closeCurrentTicket);document.getElementById('escalateButton').addEventListener('click',escalateConversation);document.getElementById('convoList').addEventListener('click',e=>{const nb=e.target.closest('[data-new]');if(nb){newConversation();return}const b=e.target.closest('[data-ticket]');if(b)loadMessages(Number(b.dataset.ticket))});document.getElementById('chatForm').addEventListener('submit',e=>{e.preventDefault();const input=document.getElementById('chatInput');const text=input.value.trim();if(!text)return;input.value='';sendChat(text)});document.getElementById('chatBody').addEventListener('click',e=>{const b=e.target.closest('[data-suggest]');if(b)sendChat(b.dataset.suggest)});document.body.addEventListener('click',e=>{const s=e.target.closest('[data-select]');if(s){state.selected=Number(s.dataset.select);document.getElementById('deviceSelector').value=state.selected;activateView('dashboard');loadData()}const dd=e.target.closest('[data-delete-device]');if(dd)deleteDevice(Number(dd.dataset.deleteDevice));const cf=e.target.closest('.connect-found');if(cf){const card=cf.closest('.found-card');card.querySelector('.mini-connect').hidden=false;cf.hidden=true;}const mc=e.target.closest('.mini-cancel');if(mc){const card=mc.closest('.found-card');card.querySelector('.mini-connect').hidden=true;card.querySelector('.connect-found').hidden=false;}const ok=e.target.closest('.mini-confirm');if(ok){connectFoundDevice(ok.closest('.found-card'));}const notif=e.target.closest('[data-open-ticket]');if(notif){document.getElementById('notificationPanel').classList.remove('open');activateView('suporte');loadTickets().then(()=>loadMessages(Number(notif.dataset.openTicket)))}});document.getElementById('logoutButton').addEventListener('click',()=>{location.href='logout.php'});document.getElementById('sidebarToggle').addEventListener('click',()=>{const collapsed=document.querySelector('.app').classList.toggle('sidebar-collapsed');try{localStorage.setItem('sadag_sidebar_collapsed',collapsed?'1':'0')}catch(e){}})}
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
