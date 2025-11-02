document.addEventListener("DOMContentLoaded", () => {
  const toggleChat = document.getElementById("toggleChat");
  const closeChat = document.getElementById("closeChat");
  const chatBox = document.getElementById("chatBox");
  const chatContent = document.getElementById("chatContent");
  const chatInput = document.getElementById("chatInput");
  const sendButton = document.getElementById("sendButton");

  let ultimaPalabra = "";

  // Al cargar: siempre cerrado
  chatBox.style.display = "none";
  toggleChat.style.display = "block";

  toggleChat.addEventListener("click", () => {
    chatBox.style.display = "flex";
    toggleChat.style.display = "none";

    // Solo mensaje de bienvenida si no hay nada
    if (!chatContent.children.length) {
      addMessage("Hola, soy tu asistente inteligente. ¿En qué puedo ayudarte?", "bot");
    }
    chatInput.focus();
  });

  closeChat.addEventListener("click", () => {
    chatBox.style.display = "none";
    toggleChat.style.display = "block";
  });

  sendButton.addEventListener("click", sendMessage);
  chatInput.addEventListener("keypress", e => {
    if (e.key === "Enter") sendMessage();
  });

  // === Funciones auxiliares ===
  function addMessage(text, sender) {
    const msg = document.createElement("div");
    msg.className = `message ${sender}`;
    msg.textContent = text;
    chatContent.appendChild(msg);
    chatContent.scrollTop = chatContent.scrollHeight;
  }

  function addOptions(pregunta, opciones, claves) {
    addMessage(pregunta, "bot");
    const container = document.createElement("div");
    container.style.display = "flex";
    container.style.flexWrap = "wrap";
    container.style.gap = "6px";
    container.className = "message bot";

    opciones.forEach((opt, i) => {
      const btn = document.createElement("button");
      btn.textContent = opt;
      btn.style.padding = "6px 10px";
      btn.style.border = "none";
      btn.style.borderRadius = "6px";
      btn.style.cursor = "pointer";
      btn.addEventListener("click", () => seleccionarOpcion(claves[i]));
      container.appendChild(btn);
    });

    chatContent.appendChild(container);
    chatContent.scrollTop = chatContent.scrollHeight;
  }

  // === Generador de ruta absoluta ===
  function getPhpUrl(fileName) {
    // obtiene la ruta actual (sin el archivo JS)
    const currentPath = window.location.pathname;
    const baseDir = currentPath.substring(0, currentPath.lastIndexOf("/"));
    return window.location.origin + baseDir + "/php/" + fileName;
  }

  // Cuando el usuario elige una opción
  async function seleccionarOpcion(tipo) {
    addMessage(`Quiero ver información de: ${tipo}`, "user");
    addMessage("Buscando información...", "bot");

    try {
      const url = getPhpUrl("buscar.php");
      console.log("Intentando conectar con:", url);

      const res = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ palabra: ultimaPalabra, tabla: tipo })
      });

      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const textoCrudo = await res.text();
      console.log("Respuesta cruda (opción):", textoCrudo);

      let data;
      try {
        data = JSON.parse(textoCrudo);
      } catch (err) {
        console.error("Respuesta inválida del servidor:", textoCrudo);
        limpiarPendiente();
        addMessage("El servidor devolvió una respuesta inválida.", "bot");
        return;
      }

      limpiarPendiente();

      if (data.resultados && data.resultados.length) {
        data.resultados.forEach(item => addMessage(item.contenido, "bot"));
      } else if (data.error) {
        addMessage(`Error del servidor: ${data.error}`, "bot");
      } else {
        addMessage("No hay resultados en esa categoría.", "bot");
      }
    } catch (e) {
      console.error("Error en seleccionarOpcion:", e);
      limpiarPendiente();
      addMessage("Error al obtener datos de esa categoría.", "bot");
    }
  }

  // === Función principal de envío ===
  async function sendMessage() {
    const text = chatInput.value.trim();
    if (!text) return;
    ultimaPalabra = text;
    addMessage(text, "user");
    chatInput.value = "";
    addMessage("Buscando información...", "bot");

    try {
      const url = getPhpUrl("buscar.php");
      console.log("Intentando conectar con:", url);

      const res = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ palabra: text })
      });

      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const textoCrudo = await res.text();
      console.log("Respuesta cruda (búsqueda):", textoCrudo);

      let data;
      try {
        data = JSON.parse(textoCrudo);
      } catch (err) {
        console.error("Respuesta inválida del servidor:", textoCrudo);
        limpiarPendiente();
        addMessage("El servidor devolvió una respuesta inválida.", "bot");
        return;
      }

      limpiarPendiente();

      if (data.pregunta && data.opciones && data.claves) {
        addOptions(data.pregunta, data.opciones, data.claves);
        return;
      }

      const resultados = data.resultados || data;
      if (Array.isArray(resultados) && resultados.length) {
        resultados.forEach(item => {
          const texto = typeof item === "string" ? item : item.contenido;
          addMessage(texto, "bot");
        });
      } else if (data.error) {
        addMessage(`Error del servidor: ${data.error}`, "bot");
      } else {
        addMessage("No encontré coincidencias relevantes.", "bot");
      }
    } catch (e) {
      console.error("Error en sendMessage:", e);
      limpiarPendiente();
      addMessage("⚠️ No se pudo conectar con el servidor. Verificá tu conexión o la ruta del archivo PHP.", "bot");
    }
  }

  // 🔧 Quita el "Buscando información..."
  function limpiarPendiente() {
    const pending = Array.from(chatContent.children)
      .reverse()
      .find(el => el.classList.contains("bot") && el.textContent === "Buscando información...");
    if (pending) chatContent.removeChild(pending);
  }
});
