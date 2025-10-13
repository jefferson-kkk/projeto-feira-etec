document.addEventListener('DOMContentLoaded', () => {

    // --- ANIMAÇÕES DE SCROLL COM GSAP ---
    gsap.registerPlugin(ScrollTrigger);
    
    // Animação para seções inteiras
    const sections = document.querySelectorAll('.section');
    sections.forEach(section => {
        gsap.from(section, {
            scrollTrigger: {
                trigger: section,
                start: 'top 80%',
                toggleActions: 'play none none none'
            },
            opacity: 0,
            y: 50,
            duration: 0.8,
            ease: 'power3.out'
        });
    });

    // Animação para itens individuais em grids (incluindo os novos team-cards)
    const cards = document.querySelectorAll('.hardware-card, .future-card, .team-card');
    cards.forEach(card => {
        gsap.from(card, {
            scrollTrigger: {
                trigger: card,
                start: 'top 85%',
                toggleActions: 'play none none none'
            },
            opacity: 0,
            y: 30,
            duration: 0.6,
            ease: 'power3.out'
        });
    });


    // --- LÓGICA DO DASHBOARD OPERACIONAL ---
    const slider = document.getElementById('ppm-slider');
    const valueText = document.getElementById('ppm-value-text');
    const statusText = document.getElementById('display-status-text');
    const indicator = document.getElementById('display-indicator');
    const globalStatus = document.getElementById('global-status');

    const STATUS = {
        NORMAL: { text: 'NORMAL', color: 'var(--color-status-ok)' },
        ATENCAO: { text: 'ATENÇÃO', color: 'var(--color-status-warn)' },
        PERIGO: { text: 'PERIGO', color: 'var(--color-status-danger)' }
    };

    const DANGER_THRESHOLD = 700;
    const WARN_THRESHOLD = 300;

    function updateDashboard(value) {
        valueText.textContent = value;
        
        let currentStatus;
        if (value >= DANGER_THRESHOLD) {
            currentStatus = STATUS.PERIGO;
        } else if (value >= WARN_THRESHOLD) {
            currentStatus = STATUS.ATENCAO;
        } else {
            currentStatus = STATUS.NORMAL;
        }

        statusText.textContent = currentStatus.text;
        indicator.style.backgroundColor = currentStatus.color;
        
        // Atualiza o status global no header
        globalStatus.textContent = currentStatus.text;
        globalStatus.style.backgroundColor = current-status.color;
    }

    slider.addEventListener('input', (e) => {
        updateDashboard(e.target.value);
    });

    // Inicializa o dashboard
    updateDashboard(slider.value);
});