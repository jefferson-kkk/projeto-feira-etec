
/* =========================================================
   LOADER
========================================================= */
window.addEventListener('load', () => {
  const loader = document.getElementById('loader');
  setTimeout(() => {
    loader.classList.add('hide');
    setTimeout(() => loader.remove(), 900);
  }, 2000);
});

/* =========================================================
   HEADER SCROLL
========================================================= */
const header = document.getElementById('header');
window.addEventListener('scroll', () => {
  header.classList.toggle('scrolled', window.scrollY > 50);
});

/* =========================================================
   REVEAL ON SCROLL
========================================================= */
const revealEls = document.querySelectorAll('.reveal');
const revealObs = new IntersectionObserver(entries => {
  entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('active'); });
}, { threshold: 0.08 });
revealEls.forEach(el => revealObs.observe(el));

/* =========================================================
   PARTICLES
========================================================= */
(function() {
  const canvas = document.getElementById('particles');
  const ctx = canvas.getContext('2d');
  function resize() {
    canvas.width  = window.innerWidth;
    canvas.height = window.innerHeight;
  }
  resize();
  window.addEventListener('resize', resize);

  const pts = Array.from({ length: 100 }, () => ({
    x: Math.random() * canvas.width,
    y: Math.random() * canvas.height,
    r: Math.random() * 1.5 + 0.3,
    sx: (Math.random() - .5) * .25,
    sy: (Math.random() - .5) * .25,
  }));

  function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    pts.forEach(p => {
      p.x += p.sx; p.y += p.sy;
      if (p.x < 0) p.x = canvas.width;
      if (p.x > canvas.width) p.x = 0;
      if (p.y < 0) p.y = canvas.height;
      if (p.y > canvas.height) p.y = 0;
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(255,255,255,.2)';
      ctx.fill();
    });
    requestAnimationFrame(draw);
  }
  draw();
})();

/* =========================================================
   DEVICE PARALLAX
========================================================= */
const device = document.getElementById('device');
if (device) {
  document.addEventListener('mousemove', e => {
    const rx = (e.clientX / window.innerWidth  - .5) * 16;
    const ry = (e.clientY / window.innerHeight - .5) * 16;
    device.style.transform = `rotateY(${rx}deg) rotateX(${-ry}deg)`;
  });
}

/* =========================================================
   DASHBOARD: PPM SLIDER
========================================================= */
const slider    = document.getElementById('ppmSlider');
const dashPpm   = document.getElementById('dashPpm');
const dashPill  = document.getElementById('dashPill');
const dashPillDot  = document.getElementById('dashPillDot');
const dashPillText = document.getElementById('dashPillText');
const sliderVal = document.getElementById('sliderVal');

// Uptime counter
let startTime = Date.now();
let readCount = 0;
const uptimeEl   = document.getElementById('uptimeVal');
const readCountEl = document.getElementById('readCount');

setInterval(() => {
  const secs = Math.floor((Date.now() - startTime) / 1000);
  const mm = String(Math.floor(secs / 60)).padStart(2, '0');
  const ss = String(secs % 60).padStart(2, '0');
  if (uptimeEl) uptimeEl.textContent = `${mm}:${ss}`;
  readCount++;
  if (readCountEl) readCountEl.textContent = readCount;
}, 500);

// History
const history = Array(20).fill(150);
const canvas  = document.getElementById('historyCanvas');
const hctx    = canvas ? canvas.getContext('2d') : null;

function drawHistory() {
  if (!hctx) return;
  const W = canvas.offsetWidth || 300;
  const H = 80;
  canvas.width  = W * window.devicePixelRatio;
  canvas.height = H * window.devicePixelRatio;
  hctx.scale(window.devicePixelRatio, window.devicePixelRatio);

  hctx.clearRect(0, 0, W, H);

  const step = W / (history.length - 1);
  const pts  = history.map((v, i) => ({
    x: i * step,
    y: H - ((v - 50) / 950) * (H - 10) - 5
  }));

  // Gradient fill
  const grad = hctx.createLinearGradient(0, 0, W, 0);
  grad.addColorStop(0, 'rgba(0,212,255,.15)');
  grad.addColorStop(1, 'rgba(124,58,237,.15)');

  hctx.beginPath();
  pts.forEach((p, i) => i === 0 ? hctx.moveTo(p.x, p.y) : hctx.lineTo(p.x, p.y));
  hctx.lineTo(W, H); hctx.lineTo(0, H); hctx.closePath();
  hctx.fillStyle = grad; hctx.fill();

  // Line
  const lineGrad = hctx.createLinearGradient(0, 0, W, 0);
  lineGrad.addColorStop(0, '#00d4ff');
  lineGrad.addColorStop(1, '#7c3aed');

  hctx.beginPath();
  pts.forEach((p, i) => i === 0 ? hctx.moveTo(p.x, p.y) : hctx.lineTo(p.x, p.y));
  hctx.strokeStyle = lineGrad;
  hctx.lineWidth = 2;
  hctx.stroke();

  // Last dot
  const last = pts[pts.length - 1];
  hctx.beginPath();
  hctx.arc(last.x, last.y, 4, 0, Math.PI * 2);
  hctx.fillStyle = '#00d4ff';
  hctx.shadowBlur = 8;
  hctx.shadowColor = '#00d4ff';
  hctx.fill();
  hctx.shadowBlur = 0;
}

function updateDash(ppm) {
  dashPpm.textContent = ppm;
  sliderVal.textContent = ppm + ' PPM';

  // Push history
  history.push(ppm);
  if (history.length > 20) history.shift();
  drawHistory();

  if (ppm < 400) {
    dashPpm.style.color = 'var(--green)';
    dashPill.style.background = 'rgba(16,185,129,.12)';
    dashPill.style.borderColor = 'rgba(16,185,129,.25)';
    dashPill.style.color       = 'var(--green)';
    dashPillDot.style.background = 'var(--green)';
    dashPillText.textContent   = '✅ AMBIENTE SEGURO';
  } else if (ppm < 700) {
    dashPpm.style.color = 'var(--yellow)';
    dashPill.style.background = 'rgba(245,158,11,.12)';
    dashPill.style.borderColor = 'rgba(245,158,11,.25)';
    dashPill.style.color       = 'var(--yellow)';
    dashPillDot.style.background = 'var(--yellow)';
    dashPillText.textContent   = '⚠️ NÍVEL DE ATENÇÃO';
  } else {
    dashPpm.style.color = 'var(--red)';
    dashPill.style.background = 'rgba(239,68,68,.12)';
    dashPill.style.borderColor = 'rgba(239,68,68,.35)';
    dashPill.style.color       = 'var(--red)';
    dashPillDot.style.background = 'var(--red)';
    dashPillText.textContent   = '🚨 NÍVEL DE PERIGO';
  }
}

if (slider) {
  slider.addEventListener('input', () => updateDash(parseInt(slider.value)));
  updateDash(150);
  drawHistory();
  window.addEventListener('resize', drawHistory);
}
