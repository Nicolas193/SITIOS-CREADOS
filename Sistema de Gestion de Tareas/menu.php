<?php 
// Incluir el autenticador de usuario
include("AutenticadorUser.php");

// Tipo de usuario
$userType = isset($_SESSION['tipo']) ? $_SESSION['tipo'] : 'Invitado';

$sectorType = isset($_SESSION['sector']) ? $_SESSION['sector'] : 'Invitado';

// Estilo del menú
$menuEstilo = isset($_SESSION['sitiocolor']) ? $_SESSION['sitiocolor'] : 1;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Iconos -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" crossorigin="anonymous" />

  <!-- Estilo del menú según configuración -->
  <?php if ($menuEstilo == 1): ?>
    <link rel="stylesheet" type="text/css" href="../../Estilos/menu.css">
  <?php elseif ($menuEstilo == 2): ?>
    <link rel="stylesheet" type="text/css" href="../../Estilos/menu2.css">
  <?php elseif ($menuEstilo == 3): ?>
    <link rel="stylesheet" type="text/css" href="../../Estilos/menu3.css">
  <?php endif; ?>


  <!-- Otros estilos y scripts -->
  <link rel="stylesheet" href="../../Estilos/estilochat.css">
  <link rel="shortcut icon" href="../../imagen/presentacion.ico" />
  <script src="../js/chatbot.js"></script>
  <script src="../js/notificaciones.js"></script>
</head>

<body>
<div id="notification-container" style="position:fixed; top:20px; right:20px; z-index:9999; font-family:'Segoe UI', sans-serif;">
  <div id="notification-icon" style="position:relative; cursor:pointer;">
    <i class="fas fa-bell fa-2x" style="color:#531f5d;"></i>
    <span id="notification-count" style="position:absolute; top:-6px; right:-6px; background:#DC2626; color:white; border-radius:9999px; padding:3px 7px; font-size:11px; font-weight:bold; box-shadow:0 0 4px rgba(0,0,0,0.2); display:none;">0</span>
  </div>

  <div id="notification-list" style="display:none; background:#fff; border:1px solid #531f5d; border-radius:10px; box-shadow:0 8px 24px rgba(0,0,0,0.1); width:300px; max-height:350px; overflow-y:auto; margin-top:12px; padding:15px 15px 10px;">
    <div style="font-weight:600; font-size:16px; color:#1F2937; margin-bottom:10px;">🔔 Notificaciones</div>
    <ul id="notification-items" style="list-style:none; padding:0; margin:0;">
    </ul>
  </div>
</div>

<!-- Botón flotante de chat -->
<button id="toggleChat">
  <img src="../../imagen/Iconos tareas/chat.svg" alt="Chat" class="chat-icon">
  Abrir Chat
</button>

<!-- Caja del Chat -->
<div id="chatBox">
  <div id="chatHeader">
    <h2>ChatBot</h2>
    <button id="closeChat">Cerrar</button>
  </div>

  <div id="chatContent"></div>

  <div id="chatInputContainer">
    <input id="chatInput" type="text" placeholder="Escribe tu pregunta...">
    <button id="sendButton">Enviar</button>
  </div>
</div>


<div class="sidebar">
  <div class="navbar">

<a id="mi-tarea" href="miusuario.php" onclick="changeHighlight(event)" class="user-profile" style="display:flex; align-items:center; text-decoration:none;">
  <img src="../../imagen/Iconos tareas/usuario.svg" alt="Usuario" class="menu-perfil">
  <div class="user-details" style="display:flex; flex-direction:column; line-height:1.2;">
    <span class="username" style="font-weight:600;">
      <?php if (isset($_SESSION['username'])) echo htmlspecialchars($_SESSION['username']); ?>
    </span>
    <span class="user-role">
      <?php if (isset($_SESSION['cargo'])) echo htmlspecialchars($_SESSION['cargo']); ?>
    </span>
    <span class="user-role">
      <?php if (isset($_SESSION['tipo'])) echo '(' . htmlspecialchars($_SESSION['tipo']) . ')'; ?>
    </span>
  </div>
</a>


    <div class="menu-search-container">
      <input type="text" id="menu-search" onkeyup="filtrarMenu()" placeholder="Buscar en el menú...">
    </div>

<?php if ($sectorType === 'GOCI') { ?>
<div class="menu-section">
  <p onclick="toggleMenu(this)" style="display:flex;align-items:center;gap:6px;cursor:pointer;">
    <!-- Icono SVG externo -->
    <img src="../../imagen/Iconos tareas/Registro de Tareas.svg" alt="icono GOCI" class="menu-carpeta">
    GOCI
  </p>

  <div class="submenu">
    <a id="consultaddjj" href="ddjjconsulta.php" onclick="changeHighlight(event)">
      <img src="../../imagen/Iconos tareas/Registro de Tareas.svg" alt="icon" class="menu-icon"> Ingreso de Consulta DDJJ
    </a>
    <a id="vernomina" href="vernomina.php" onclick="changeHighlight(event)">
      <img src="../../imagen/Iconos tareas/Mis tareas Finalizadas.svg" alt="icon" class="menu-icon"> Tablero Consulta DDJJ
    </a>
    <a id="menuddjj" href="editarmenu_ddjj.php" onclick="changeHighlight(event)">
      <img src="../../imagen/Iconos tareas/configuracion.svg" alt="icon" class="menu-icon"> Editar Menús Formulario DDJJ
    </a>
  </div>
</div>

<?php } ?>


<div class="menu-section">
    <p onclick="toggleMenu(this)" style="display:flex;align-items:center;gap:6px;cursor:pointer;">
    <!-- Icono SVG externo -->
    <img src="../../imagen/Iconos tareas/Tareas.svg" alt="icono GOCI" class="menu-carpeta">
    TAREAS
  </p>
  <div class="submenu">
    <?php if ($userType === 'administrador'  || $userType === 'secretario') { ?>
      <a id="Asignacion-Secretario" href="asignaciontareassecretario.php" onclick="changeHighlight(event)">
        <img src="../../imagen/Iconos tareas/selector.svg" alt="Asignar" class="menu-icon">>
        Asignación Secretario
      </a>
    <?php } ?>

    <a id="Mis Tareas" href="mistareas.php" onclick="changeHighlight(event)">
      <img src="../../imagen/Iconos tareas/Mis tareas Finalizadas.svg" alt="Finalizadas" class="menu-icon">
      Mis Tareas Finalizadas
    </a>

    <a id="tareas-pendientes" href="pendientes.php" onclick="changeHighlight(event)">
      <img src="../../imagen/Iconos tareas/Mis Tareas Pendientes.svg" alt="Pendientes" class="menu-icon">
      Mis Tareas Pendientes
    </a>

    <a id="gestor-otc" href="registro.php" onclick="changeHighlight(event)">
      <img src="../../imagen/Iconos tareas/Enviar Tareas.svg" alt="Enviar" class="menu-icon">
      Enviar Tareas
    </a>

    <a id="estados-tareas" href="respuestatareas.php" onclick="changeHighlight(event)">
      <img src="../../imagen/Iconos tareas/Respuesta de Tareas.svg" alt="Respuesta" class="menu-icon">
      Respuesta De Tareas
    </a>
  </div>
</div>


<div class="menu-section">
   <p onclick="toggleMenu(this)" style="display:flex;align-items:center;gap:6px;cursor:pointer;">
    <!-- Icono SVG externo -->
    <img src="../../imagen/Iconos tareas/Registro de Tareas.svg" alt="icono GOCI" class="menu-carpeta">
    REGISTRO DE TAREAS
  </p>
  <div class="submenu">
    <a id="Movimientos de las tareas" href="movimientomiusuarios.php" onclick="changeHighlight(event)">
      <img src="../../imagen/Iconos tareas/Mis Movimientos.svg" alt="Mis Movimientos" class="menu-icon">
      Mis Movimientos
    </a>

    <a id="panelenviotareas" href="dashboard.php" onclick="changeHighlight(event)">
      <img src="../../imagen/Iconos tareas/Panel de Envio de tareas.svg" alt="Panel de Envío" class="menu-icon">
      Panel de Envío de Tareas
    </a>

    <a id="panerdemistareas" href="dashboardresponsable.php" onclick="changeHighlight(event)">
      <img src="../../imagen/Iconos tareas/tareas delegadas.svg" alt="Tareas Delegadas" class="menu-icon">
      Panel Mis Tareas Delegadas
    </a>

    <?php if ($userType === 'administrador'  || $userType === 'gestor') { ?>
      <a id="Diagrama de gantt" href="gantt.php" onclick="changeHighlight(event)">
        <img src="../../imagen/Iconos tareas/idea.svg" alt="Gantt"class="menu-icon">
        Diagrama de Gantt
      </a>

      <a id="reporte-tareas-local" href="reportedetareas.php" onclick="changeHighlight(event)">
        <img src="../../imagen/Iconos tareas/Registro de Tareas.svg" alt="Movimientos" class="menu-icon">
        Movimientos
      </a>

      <a id="registro-tareas" href="registrotareas.php" onclick="changeHighlight(event)">
        <img src="../../imagen/Iconos tareas/reloj de area.svg" alt="Último Estado" class="menu-icon">
        Último Estado
      </a>
    <?php } ?>
  </div>
</div>

<div class="menu-section">
     <p onclick="toggleMenu(this)" style="display:flex;align-items:center;gap:6px;cursor:pointer;">
    <!-- Icono SVG externo -->
    <img src="../../imagen/Iconos tareas/Enlaces Externos.svg" alt="icono GOCI" class="menu-carpeta">
    ENLACES EXTERNOS
  </p>
  <div class="submenu">
    <a id="web-otc" href="https://otcepcdad.seguridadciudad.gob.ar/" onclick="changeHighlight(event)" target="_blank">
      <img src="../../imagen/Iconos tareas/Web OTCE.svg" alt="Web OTCE" class="menu-icon">
      Web OTCE
    </a>

    <a id="direccionesdetrabajo" href="direccionesdetrabajo.php" onclick="changeHighlight(event)">
      <img src="../../imagen/Iconos tareas/Mis Direcciones.svg" alt="Mis Direcciones" class="menu-icon">
      Mis Direcciones
    </a>
  </div>
</div>

<?php if ($userType === 'administrador') { ?>
<div class="menu-section">
       <p onclick="toggleMenu(this)" style="display:flex;align-items:center;gap:6px;cursor:pointer;">
    <!-- Icono SVG externo -->
    <img src="../../imagen/Iconos tareas/configuracion.svg" alt="icono GOCI" class="menu-carpeta">
    CONFIGURACION
  </p>
  <div class="submenu">
    <a id="admin-user" href="AdministradorUser.php" onclick="changeHighlight(event)">
      <img src="../../imagen/Iconos tareas/usuario.svg" alt="Usuarios"class="menu-icon">
      Administrador de Usuarios
    </a>

    <a id="carga-tareas" href="cargatareas.php" onclick="changeHighlight(event)">
      <img src="../../imagen/Iconos tareas/guardar.svg" alt="Carga de Tareas"class="menu-icon">
      Carga de Tareas
    </a>

    <a id="estado-tareas" href="estadotareas.php" onclick="changeHighlight(event)">
      <img src="../../imagen/Iconos tareas/selector.svg" alt="Carga de Estados"class="menu-icon">
      Carga de Estados
    </a>

    <a id="preguntasrespuesta" href="preguntasresp.php" onclick="changeHighlight(event)">
      <img src="../../imagen/Iconos tareas/idea.svg" alt="Preguntas Frecuentes" class="menu-icon">
      Preguntas Frecuentes
    </a>

    <a id="comentariosregistrados" href="controlcomentarios.php" onclick="changeHighlight(event)">
      <img src="../../imagen/Iconos tareas/notificacion.svg" alt="Comentarios Registrados"class="menu-icon">
      Comentarios Registrados
    </a>
  </div>
</div>
<?php } ?>

    <a id="cerrar-sesion" href="../../cerrar.php" onclick="localStorage.removeItem('highlightedLink')"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
    V4.8

  </div>

  <div class="footer"></div>
</div>
  <button id="sidebarToggle" class="toggle-arrow">❮</button>
<script>
  // -------- MENÚ LATERAL ----------
  function toggleMenu(element) {
    document.querySelectorAll('.submenu').forEach(menu => {
      if (menu !== element.nextElementSibling) {
        menu.classList.remove('show');
      }
    });
    element.nextElementSibling.classList.toggle('show');
  }

  function changeHighlight(event) {
    const link = event.target.closest('a');
    if (!link || !link.id) return;
    document.querySelectorAll('.navbar a').forEach(el => el.classList.remove('highlight'));
    link.classList.add('highlight');
    localStorage.setItem('highlightedLink', link.id);
  }

  function loadHighlight() {
    const highlightedLinkId = localStorage.getItem('highlightedLink');
    if (highlightedLinkId) {
      const link = document.getElementById(highlightedLinkId);
      if (link) {
        link.classList.add('highlight');
        const submenu = link.closest('.submenu');
        if (submenu) submenu.classList.add('show');
      }
    }
  }

  function filtrarMenu() {
    const input = document.getElementById('menu-search').value.toLowerCase();
    const secciones = document.querySelectorAll('.menu-section');
    secciones.forEach(section => {
      const submenu = section.querySelector('.submenu');
      const links = submenu ? submenu.querySelectorAll('a') : [];
      let visible = false;
      links.forEach(link => {
        const match = link.textContent.toLowerCase().includes(input);
        link.style.display = match ? 'block' : 'none';
        if (match) visible = true;
      });
      section.style.display = visible || input === '' ? 'block' : 'none';
      if (visible && input !== '') submenu.classList.add('show');
      else if (input === '') submenu.classList.remove('show');
    });
  }

  const toggleBtn = document.getElementById('sidebarToggle');
  const sidebar = document.querySelector('.sidebar');

  const savedSidebarState = localStorage.getItem('sidebarCollapsed');
  if (savedSidebarState === 'true') {
    sidebar.classList.add('collapsed');
    toggleBtn.textContent = '❯';
  } else {
    sidebar.classList.remove('collapsed');
    toggleBtn.textContent = '❮';
  }

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    const isCollapsed = sidebar.classList.contains('collapsed');
    toggleBtn.textContent = isCollapsed ? '❯' : '❮';
    localStorage.setItem('sidebarCollapsed', isCollapsed);
  });

  // ---------- CHAT ----------
  window.addEventListener("DOMContentLoaded", () => {
    const toggleChat = document.getElementById("toggleChat");
    const closeChat = document.getElementById("closeChat");
    const chatBox = document.getElementById("chatBox");
    const chatContent = document.getElementById("chatContent");
    const chatInput = document.getElementById("chatInput");
    const sendButton = document.getElementById("sendButton");

    let ultimaPalabra = "";

    const isChatOpen = localStorage.getItem("chatOpen") === "true";
    chatBox.style.display = isChatOpen ? "flex" : "none";
    toggleChat.style.display = isChatOpen ? "none" : "block";

    if (isChatOpen && !chatContent.children.length) {
      addMessage("Hola, soy tu asistente inteligente. ¿En qué puedo ayudarte?", "bot");
    }

    toggleChat.addEventListener("click", () => {
      chatBox.style.display = "flex";
      toggleChat.style.display = "none";
      localStorage.setItem("chatOpen", "true");

      if (!chatContent.children.length) {
        addMessage("Hola, soy tu asistente inteligente. ¿En qué puedo ayudarte?", "bot");
      }
      chatInput.focus();
    });

    closeChat.addEventListener("click", () => {
      chatBox.style.display = "none";
      toggleChat.style.display = "block";
      localStorage.setItem("chatOpen", "false");
    });

    sendButton.addEventListener("click", sendMessage);
    chatInput.addEventListener("keypress", e => {
      if (e.key === "Enter") sendMessage();
    });

    function addMessage(text, sender) {
      const msg = document.createElement("div");
      msg.className = `message ${sender}`;
      msg.textContent = text;
      chatContent.appendChild(msg);
      chatContent.scrollTop = chatContent.scrollHeight;
    }

    async function sendMessage() {
      const text = chatInput.value.trim();
      if (!text) return;
      ultimaPalabra = text;
      addMessage(text, "user");
      chatInput.value = "";
      addMessage("Buscando información...", "bot");

      try {
        const res = await fetch("buscar.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ palabra: text })
        });
        const textoCrudo = await res.text();
        const data = JSON.parse(textoCrudo);

        const pending = Array.from(chatContent.children)
          .reverse()
          .find(el => el.classList.contains("bot") && el.textContent === "Buscando información...");
        if (pending) chatContent.removeChild(pending);

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
        addMessage("Ocurrió un error al buscar la información.", "bot");
      }
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

    async function seleccionarOpcion(tipo) {
      addMessage(`Quiero ver información de: ${tipo}`, "user");
      addMessage("Buscando información...", "bot");

      try {
        const res = await fetch("buscar.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ palabra: ultimaPalabra, tabla: tipo })
        });
        const textoCrudo = await res.text();
        const data = JSON.parse(textoCrudo);

        const pending = Array.from(chatContent.children)
          .reverse()
          .find(el => el.classList.contains("bot") && el.textContent === "Buscando información...");
        if (pending) chatContent.removeChild(pending);

        if (data.resultados && data.resultados.length) {
          data.resultados.forEach(item => addMessage(item.contenido, "bot"));
        } else {
          addMessage("No hay resultados en esa categoría.", "bot");
        }
      } catch (e) {
        console.error("Error en seleccionarOpcion:", e);
        addMessage("Error al obtener datos de esa categoría.", "bot");
      }
    }
  });

  // Cargar resaltado al final
  window.onload = () => {
    loadHighlight();
  };
</script>


</body>
</html>
