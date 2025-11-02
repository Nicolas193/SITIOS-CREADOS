class MyComponent extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: 'open' });

        // Crear un contenedor para el contenido
        const template = document.createElement('template');
        template.innerHTML = `
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-n/Mm/DCN4BlIzxrR58ot7g/7NxEcCGT5P8pH3eEuQQBIsGG7bYV20Lo6kiI3B8CJrKViFyOJtsT3ITbmDzbcSQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" type="text/css" href="../css/menu.css">
      <link rel="icon" href="../images/LogoBA-accesos.png" type="image/x-icon">
    <title>Inicio</title>
</head>
<body>
    <div class="sidebar" style="text-align: center;">
        <div class="navbar">
        <input type="text" id="searchInput" placeholder="Buscar...">
           <a id="puntajeperfil" href="puntuacionperfil.html" onclick="changeHighlight(event)"><i>&#x1F464;</i>Perfil Puntaje Relacion</a>
            <a id="puntajeperfilcomp" href="puntuacionperfilcomp.html" onclick="changeHighlight(event)"><i>&#x1F464;</i>Perfil Puntaje</a> 
            <a id="ViolenciaInstitu" href="Violencia_institucional_2024.html" onclick="changeHighlight(event)">Violencia Institucional</a> 
            <a id="EnfrentamientoArmandoSanty" href="EnfrentamientoArmandoSanty.html" onclick="changeHighlight(event)">Enfrentamiento Armado Chamorro</a> 
             <a id="robohurtolamatanza" href="maparobohurtolamatanza.html" onclick="changeHighlight(event)">Robo y Hurtos Mapa La Matanza</a> 
        </div>
        V2.3
    </div>
</body>
</html>
        `;
        this.shadowRoot.appendChild(template.content.cloneNode(true));

        // Cargar el resaltado almacenado al crear el componente
        this.loadHighlight();
        
        // Añadir listeners a los enlaces para manejar el resaltado
        this.addEventListeners();
        
        // Añadir el listener para el campo de búsqueda
        this.addSearchListener();
    }

    addEventListeners() {
        const links = this.shadowRoot.querySelectorAll('.navbar a');
        links.forEach(link => {
            link.addEventListener('click', (event) => this.changeHighlight(event));
        });
    }

    changeHighlight(event) {
        event.preventDefault();
        const link = event.target.closest('a');
        const links = this.shadowRoot.querySelectorAll('.navbar a');
        links.forEach(link => link.classList.remove('highlight'));
        link.classList.add('highlight');
        localStorage.setItem('highlightedLink', link.id);
        window.location.href = link.href; // Navegar a la URL del enlace
    }

    loadHighlight() {
        const highlightedLinkId = localStorage.getItem('highlightedLink');
        if (highlightedLinkId) {
            const highlightedLink = this.shadowRoot.getElementById(highlightedLinkId);
            if (highlightedLink) {
                highlightedLink.classList.add('highlight');
            }
        }
    }

    addSearchListener() {
        const searchInput = this.shadowRoot.getElementById('searchInput');
        searchInput.addEventListener('input', () => {
            const filter = searchInput.value.toLowerCase();
            const links = this.shadowRoot.querySelectorAll('.navbar a');
            links.forEach(link => {
                const text = (link.textContent || link.innerText).toLowerCase();
                if (text.includes(filter)) {
                    link.classList.remove('hidden');
                } else {
                    link.classList.add('hidden');
                }
            });
        });
    }
}

// Define el nuevo elemento
customElements.define('my-component', MyComponent);