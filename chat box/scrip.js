const toggleChatButton = document.getElementById("toggleChat");
const chatBox = document.getElementById("chatBox");
const closeChatButton = document.getElementById("closeChat");
const chatContent = document.getElementById("chatContent");
let selectedOptionElement = null; // Para almacenar la opción seleccionada

toggleChatButton.addEventListener("click", () => {
    chatBox.style.display = "block";
    resetChat();
});

closeChatButton.addEventListener("click", () => {
    chatBox.style.display = "none";
});

function resetChat() {
    chatContent.innerHTML = '';
    addMessage("Hola, soy Nicolas. ¿Cómo estás? ¿En qué te puedo ayudar?", true);
    showOptions();
}

function showOptions() {
    const optionsDiv = document.createElement("div");
    optionsDiv.className = "message options";
    const ul = document.createElement("ul");
    const option1 = createOption("Opción 1: Sobre la Web");
    const option2 = createOption("Opción 2: No encuentro cierta información");
    const option3 = createOption("Opción 3: Contactos");

    ul.appendChild(option1);
    ul.appendChild(option2);
    ul.appendChild(option3);

    optionsDiv.appendChild(ul);
    chatContent.appendChild(optionsDiv);
}

function showOptions2() {
    const optionsDiv = document.createElement("div");
    optionsDiv.className = "message options";
    addMessage("Porfavor digame en que lo puedo ayudar", true);
    const ul = document.createElement("ul");
    const option1 = createOption("Opción 1: Sobre la Web");
    const option2 = createOption("Opción 2: No encuentro cierta información");
    const option3 = createOption("Opción 3: Contactos");

    ul.appendChild(option1);
    ul.appendChild(option2);
    ul.appendChild(option3);

    optionsDiv.appendChild(ul);
    chatContent.appendChild(optionsDiv);
}

function createOption(text) {
    const li = document.createElement("li");
    const link = document.createElement("a");
    link.href = "#";
    link.textContent = text;
    li.appendChild(link);

    link.addEventListener("click", (event) => {
        event.preventDefault();
        const selectedOption = event.target.textContent;
        handleOptionSelection(selectedOption, li);
    });

    return li;
}

function handleOptionSelection(option, optionElement) {
    if (selectedOptionElement) {
        // Elimina la selección anterior
        selectedOptionElement.classList.remove("selected");
    }

    // Marca la nueva opción como seleccionada
    optionElement.classList.add("selected");

    selectedOptionElement = optionElement;

    // Oculta las opciones no seleccionadas
    const options = chatContent.querySelectorAll(".message.options li");
    options.forEach((opt) => {
        if (opt !== optionElement) {
            opt.style.display = "none";
        }
    });

    switch (option) {
        case "Opción 1: Sobre la Web":
            addMessage("La página web https://otcepcdad.seguridadciudad.gob.ar fue creada con el propósito de facilitar el acceso a nuestro personal de trabajo para que puedan acceder más fácilmente a nuestros sitios y brindar cierta información de la organización y proporcionar ciertos contactos.", true);
            const option1 = createOption("Quieres saber cómo funciona nuestra plataforma?");
            const option2 = createOption("Opción 2: No encuentro cierta información");
            showPlatformOptions();
            break;

        case "Quieres saber cómo funciona nuestra plataforma?":
            addMessage("Este es un manual instructivo de las distintas secciones de la página web (archivo .pdf).", true);
            showChangeContentOptions();
            break;

        case "¿Desea cambiar algún contenido o agregar alguna función al sitio?":
            addMessage("Si desea agregar algún contenido o agregar algo al sitio por favor comuníquese vía mail con: garcostaborda@buenosaires.gob.ar o nicolasignaciomaciel@buenosaires.gob.ar", true);
            showChangeContentOptions();
            break;

        case "Genial!":
            addMessage("¿Te puedo ayudar en algo más?", true);
            showPlatformOptions2();
            break;
        case "Si":
            showOptions2();
            break;

        case "No":
            addMessage("Muchas Gracias por hablar conmigo, que tengas un buen día", true);
            break;
        case "Opción 2: No encuentro cierta información":
            addMessage("que Informacion estas buscando?", true);
             showKeywordInput();
            break;

        case "Deseo buscar otra cosa":
            addMessage("Genial!, Dime que mas quieres buscar?", true);
             showKeywordInput();
            break;
        // Implementa lógica para otras opciones aquí
        case "Ya encotre la Informacion Gracias!":
            addMessage("¿Te puedo ayudar en algo más?", true);
            showPlatformOptions2();
            break;  
        case "Opción 3: Contactos":
            addMessage("A quien quieres contactar?", true);
            showKeywordInput2();
            break;   
        case "Ya encotre el contacto Gracias!":
            addMessage("¿Te puedo ayudar en algo más?", true);
            showPlatformOptions2();
         break;
         case "Deseo buscar otro contacto":
            addMessage("Genial, a quien mas deseas buscar?", true);
            showKeywordInput2();
         break;
        default:
            // Opción desconocida, muestra opciones nuevamente
            showOptions();
            break;
    }
}

function showKeywordInput() {
    const inputDiv = document.createElement("div");
    inputDiv.className = "message";
    const keywordInput = document.createElement("input");
    keywordInput.type = "text";
    keywordInput.placeholder = "Ingrese la palabra clave";
    const searchButton = document.createElement("button");
    searchButton.textContent = "Buscar";

    inputDiv.appendChild(keywordInput);
    inputDiv.appendChild(searchButton);
    chatContent.appendChild(inputDiv);

    searchButton.addEventListener("click", () => {
        const keyword = keywordInput.value.trim();
        if (keyword !== "") {
            // Define tu array de búsqueda aquí
            addMessage("Esto es lo que pude encontrar:", true);
            const dataArray = {
                "Manual de licencias": "lo podes encontrar en la ventana compuesta",
                "Manual instructivo": "lo podes encontrar en mas informacion",
                "Compas": "lo podes encontrar en el sector compras",
                "Ventas": "lo podes encontrar en el inicio",
            };

            const matchingResults = [];

            // Busca todas las coincidencias en el array
            for (const key in dataArray) {
                if (key.toLowerCase().includes(keyword.toLowerCase())) {
                    matchingResults.push(`${key}: ${dataArray[key]}`);
                }
            }

            if (matchingResults.length > 0) {
                addMessage(matchingResults.join("<br>"), true);
            } else {
                addMessage("Disculpe no puedo encontrar esa información en mi sistema, por favor comuníquese con garcostaborda@buenosaires.gob.ar o nicolasignaciomaciel@buenosaires.gob.ar.", true);
            }
        }
        addMessage("Necesitas Buscar otra cosa?", true);
        showPlatformOptions3();
    });
}
 // contacto
function showKeywordInput2() {
    const inputDiv = document.createElement("div");
    inputDiv.className = "message";
    const keywordInput = document.createElement("input");
    keywordInput.type = "text";
    keywordInput.placeholder = "Ingrese la palabra clave";
    const searchButton = document.createElement("button");
    searchButton.textContent = "Buscar";

    inputDiv.appendChild(keywordInput);
    inputDiv.appendChild(searchButton);
    chatContent.appendChild(inputDiv);

    searchButton.addEventListener("click", () => {
        const keyword = keywordInput.value.trim();
        if (keyword !== "") {
            // Define tu array de búsqueda aquí
            addMessage("Esto es lo que pude encontrar:", true);
            const dataArray = {
                "juan": "lo podes encontrar en la ventana compuesta",
                "Manual instructivo": "lo podes encontrar en mas informacion",
                "Compas": "lo podes encontrar en el sector compras",
                "Ventas": "lo podes encontrar en el inicio",
            };

            const matchingResults = [];

            // Busca todas las coincidencias en el array
            for (const key in dataArray) {
                if (key.toLowerCase().includes(keyword.toLowerCase())) {
                    matchingResults.push(`${key}: ${dataArray[key]}`);
                }
            }

            if (matchingResults.length > 0) {
                addMessage(matchingResults.join("<br>"), true);
            } else {
                addMessage("Disculpe no puedo encontrar a ese contacto garcostaborda@buenosaires.gob.ar o nicolasignaciomaciel@buenosaires.gob.ar.", true);
            }
        }
        addMessage("Necesitas Buscar otro contacto?", true);
        showPlatformOptions4();
    });
}

function showPlatformOptions() {
    const platformOptionsDiv = document.createElement("div");
    platformOptionsDiv.className = "message options";
    const ul = document.createElement("ul");

    const option1 = createOption("Quieres saber cómo funciona nuestra plataforma?");
    const option2 = createOption("¿Desea cambiar algún contenido o agregar alguna función al sitio?");

    ul.appendChild(option1);
    ul.appendChild(option2);

    platformOptionsDiv.appendChild(ul);
    chatContent.appendChild(platformOptionsDiv);
}

function showPlatformOptions2() {
    const platformOptionsDiv = document.createElement("div");
    platformOptionsDiv.className = "message options";
    const ul = document.createElement("ul");

    const option1 = createOption("Si");
    const option2 = createOption("No");

    ul.appendChild(option1);
    ul.appendChild(option2);

    platformOptionsDiv.appendChild(ul);
    chatContent.appendChild(platformOptionsDiv);
}

function showPlatformOptions3() {
    const platformOptionsDiv = document.createElement("div");
    platformOptionsDiv.className = "message options";
    const ul = document.createElement("ul");

    const option1 = createOption("Ya encotre la Informacion Gracias!");
    const option2 = createOption("Deseo buscar otra cosa");

    ul.appendChild(option1);
    ul.appendChild(option2);

    platformOptionsDiv.appendChild(ul);
    chatContent.appendChild(platformOptionsDiv);
}


function showPlatformOptions4() {
    const platformOptionsDiv = document.createElement("div");
    platformOptionsDiv.className = "message options";
    const ul = document.createElement("ul");

    const option1 = createOption("Ya encotre el contacto Gracias!");
    const option2 = createOption("Deseo buscar otro contacto");

    ul.appendChild(option1);
    ul.appendChild(option2);

    platformOptionsDiv.appendChild(ul);
    chatContent.appendChild(platformOptionsDiv);
}

function showChangeContentOptions() {
    const changeContentOptionsDiv = document.createElement("div");
    changeContentOptionsDiv.className = "message options";
    const ul = document.createElement("ul");

    const option1 = createOption("Genial!");
    const option2 = createOption("Tengo otra pregunta");

    ul.appendChild(option1);
    ul.appendChild(option2);

    changeContentOptionsDiv.appendChild(ul);
    chatContent.appendChild(changeContentOptionsDiv);
}

function addMessage(content, isReceived) {
    const messageDiv = document.createElement("div");
    messageDiv.className = `message ${isReceived ? "received" : "sent"}`;

    if (typeof content === "string") {
        messageDiv.innerHTML = `<p>${content}</p>`;
    } else {
        messageDiv.appendChild(content);
    }

    chatContent.appendChild(messageDiv);
    chatContent.scrollTop = chatContent.scrollHeight; // Asegura que los últimos mensajes sean visibles
}
