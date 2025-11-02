document.addEventListener('DOMContentLoaded', () => {
    // Slides y modal
    const slides = {
        about:      document.getElementById('about-us-slide'),
        rrhh:       document.getElementById('rrhh-slide'),
        paginas:    document.getElementById('paginas-slide'),
        contacto:   document.getElementById('contacto-slide'),
        carpeta:    document.getElementById('carpeta-slide'),
        sade:       document.getElementById('modal-sade')
    };



function abrirSlide(name) {
    cerrarTodos();
    const s = slides[name];
    if (!s) return;

    if (name === 'sade') {
        s.style.display = 'flex';
        s.style.justifyContent = 'center';
        s.style.alignItems = 'center';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    else {
        s.style.display = 'flex';
        s.style.position = 'fixed';
        s.style.top = '50px';
        s.style.left = '0';
        s.style.width = '100%';
        s.style.height = 'calc(100% - 120px)';
        s.style.zIndex = '2000';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function cerrarTodos() {
    Object.values(slides).forEach(s => { if (s) s.style.display = 'none'; });
    
    // Restaurar scroll al cerrar
    document.body.style.overflow = 'auto';
}
    const navMap = {
        'nav-about-us': 'about',
        'nav-rrhh': 'rrhh',
        'nav-paginas': 'paginas',
        'nav-contacto': 'contacto',
        'nav-carpeta': 'carpeta',
        'nav-sadeportal': 'sade'
    };

    Object.keys(navMap).forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                abrirSlide(navMap[id]);
            });
        }
    });

    const cerrarBtns = [
        ...document.querySelectorAll('.btn-close-about'),
        document.getElementById('btn-close-carpeta'),
        document.getElementById('btn-close-modal')
    ];

    cerrarBtns.forEach(btn => {
        if (btn) {
            btn.addEventListener('click', e => {
                e.preventDefault();
                cerrarTodos();
            });
        }
    });

    // Cerrar modal SADE clickeando fuera del contenido
    const modalSade = document.getElementById('modal-sade');
    if (modalSade) {
        modalSade.addEventListener('click', e => {
            if (e.target === modalSade) cerrarTodos();
        });
    }
});
