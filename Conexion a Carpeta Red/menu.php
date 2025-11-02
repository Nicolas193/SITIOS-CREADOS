<?php 
// Incluir el autenticador de usuario
include("AutenticadorUser.php");

// Comprobar el tipo de usuario en la sesión para depuración
if (isset($_SESSION['tipo'])) {
    $userType = $_SESSION['tipo'];
} else {
    $userType = 'Invitado'; // Si no está definido, se asigna un valor por defecto
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" type="text/css" href="../../Estilos/menu.css">
    <link rel="shortcut icon" href="../../imagen/presentacion.ico" />
    <title>Inicio</title>
</head>
<body>
    <div class="sidebar">
        <div class="navbar">

            <!-- Usuario y perfil -->
            <a id="mi-tarea" href="mistareas.php" onclick="changeHighlight(event)" class="user-profile">
                <span class="user-icon">&#x1F464;</span>
                <div class="user-details">
                    <span class="username">
                        <?php
                        if (isset($_SESSION['username'])) {
                            echo htmlspecialchars($_SESSION['username']);
                        }
                        ?>
                    </span>
                    <span class="user-role">
                        <?php
                        if (isset($_SESSION['tipo'])) {
                            echo '(' . htmlspecialchars($_SESSION['tipo']) . ')';
                        }
                        ?>
                    </span>
                </div>
            </a>

            <!-- Búsqueda en el menú -->
            <div class="menu-search-container">
                <input type="text" id="menu-search" onkeyup="filtrarMenu()" placeholder="Buscar en el menú...">
            </div>

            <!-- INGRESO DE DATOS -->
            <div class="menu-section">
                <p onclick="toggleMenu(this)">Ingreso de datos</p>
                <div class="submenu">
                    <a id="tareas-pendientes" href="pendientes.php" onclick="changeHighlight(event)">
                        <i class="fas fa-tasks"></i> Lista de Tareas
                    </a>
                    <a id="gestor-otc" href="registro.php" onclick="changeHighlight(event)">
                        <i class="fas fa-briefcase"></i> Gestor OTC
                    </a>
                    <a id="estados-tareas" href="informeregistro.php" onclick="changeHighlight(event)">
                        <i class="fas fa-chart-line"></i> Estados de tareas
                    </a>
                </div>
            </div>

            <!-- REGISTRO DE TAREAS (solo Admin) -->
            <?php if ($userType === 'Administrador') { ?>
            <div class="menu-section">
                <p onclick="toggleMenu(this)">Registro de Tareas</p>
                <div class="submenu">
                    <a id="reporte-tareas-local" href="reportedetareas.php" onclick="changeHighlight(event)">
                        <i class="fas fa-file-alt"></i> Movimientos
                    </a>
                    <a id="registro-tareas" href="registrotareas.php" onclick="changeHighlight(event)">
                        <i class="fas fa-clipboard-check"></i> Último Estado
                    </a>
                    <a id="carga-tareas" href="cargatareas.php" onclick="changeHighlight(event)">
                        <i class="fas fa-upload"></i> Carga de Tareas
                    </a>
                </div>
            </div>
            <?php } ?>

            <!-- CARPETA COMPARTIDA -->
            <div class="menu-section">
                <p onclick="toggleMenu(this)">Carpeta Compartida</p>
                <div class="submenu">
                    <a id="archivos" href="../../Carpeta Compartida/PHP/compartido.php" onclick="changeHighlight(event)">
                        <i class="fas fa-folder"></i> Archivos
                    </a>
                </div>
            </div>

            <!-- ENLACES EXTERNOS -->
            <div class="menu-section">
                <p onclick="toggleMenu(this)">Enlaces externos</p>
                <div class="submenu">
                    <a id="web-otc" href="https://otcepcdad.seguridadciudad.gob.ar/" onclick="changeHighlight(event)" target="_blank">
                        <i class="fas fa-globe"></i> Web OTC
                    </a>
                </div>
            </div>

            <!-- CONFIGURACIÓN -->
            <div class="menu-section">
                <p onclick="toggleMenu(this)">Configuración</p>
                <div class="submenu">
                    <?php if ($userType === 'Administrador') { ?>
                    <a id="admin-user" href="AdministradorUser.php" onclick="changeHighlight(event)">
                        <i class="fas fa-users-cog"></i> Administrador de Usuarios
                    </a>
                     <a id="admin-user" href="cargatareas.php" onclick="changeHighlight(event)">
                        <i class="fas fa-users-cog"></i> Carga de Tareas
                    </a>
                    <?php } ?>
                </div>
            </div>

            <!-- Cerrar sesión -->
            <a id="cerrar-sesion" href="../../cerrar.php" onclick="localStorage.removeItem('highlightedLink')">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>

        </div>
        <div class="footer">
            V2.3
        </div>
    </div>

    <script>
        function toggleMenu(element) {
            // Cerrar todos los submenús primero (comportamiento tipo acordeón)
            document.querySelectorAll('.submenu').forEach(menu => {
                if (menu !== element.nextElementSibling) {
                    menu.classList.remove('show');
                }
            });

            // Alternar visibilidad del submenu actual
            const submenu = element.nextElementSibling;
            submenu.classList.toggle('show');
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

                    // Asegurarse de que el submenú donde está el link esté desplegado
                    const submenu = link.closest('.submenu');
                    if (submenu) submenu.classList.add('show');
                }
            }
        }

        window.onload = loadHighlight;
    </script>

    <script>
        function filtrarMenu() {
            const input = document.getElementById('menu-search');
            const filtro = input.value.toLowerCase();
            const secciones = document.querySelectorAll('.menu-section');

            secciones.forEach(section => {
                const titulo = section.querySelector('p');
                const submenu = section.querySelector('.submenu');
                const links = submenu ? submenu.querySelectorAll('a') : [];
                let hayCoincidencia = false;

                if (filtro === '') {
                    // Mostrar todo si la búsqueda está vacía
                    section.style.display = 'block';
                    if (submenu) {
                        submenu.classList.remove('show');
                        links.forEach(link => link.style.display = 'block');
                    }
                } else {
                    // Búsqueda activa
                    links.forEach(link => {
                        const texto = link.textContent.toLowerCase();
                        const coincide = texto.includes(filtro);
                        link.style.display = coincide ? 'block' : 'none';
                        if (coincide) hayCoincidencia = true;
                    });

                    if (hayCoincidencia) {
                        section.style.display = 'block';
                        if (submenu) submenu.classList.add('show');
                    } else {
                        section.style.display = 'none';
                    }
                }
            });
        }
    </script>
</body>
</html>
