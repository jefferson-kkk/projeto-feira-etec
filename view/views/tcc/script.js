// Arquivo inicial para funcionalidades do TCC
// Aqui você pode inicializar canvas de partículas, gráficos, etc.

document.addEventListener('DOMContentLoaded', () => {
  // Inicialização mínima: desenhar um pequeno placeholder no canvas
  const canvas = document.getElementById('bg-canvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  function resize(){
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
  }
  resize();
  window.addEventListener('resize', resize);
  // exemplo simples de desenho
  ctx.fillStyle = 'rgba(255,255,255,0.02)';
  ctx.fillRect(0,0,canvas.width,canvas.height);
});
