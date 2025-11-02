<?php 
  include("../../AutenticadorUser.php"); 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-n/Mm/DCN4BlIzxrR58ot7g/7NxEcCGT5P8pH3eEuQQBIsGG7bYV20Lo6kiI3B8CJrKViFyOJtsT3ITbmDzbcSQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" type="text/css" href="../../Estilos/menu.css">
    <link rel="shortcut icon" href="../../imagen/presentacion.ico" />
    <title>Inicio</title>
</head>
<body>
    <div class="sidebar">
        <div class="navbar">
            <div class="user-container">
                 <a href="mistareas.php"  onclick="changeHighlight(event)">
                <span class="user-icon">&#x1F464;</span> <!-- Icono de usuario -->
              <?php
              // Verificar si el usuario está autenticado
              if (isset($_SESSION['username'])) {
                  echo '<span class="username">' . $_SESSION['username'] . '</span>';
              }
              ?>
        
                 </a>
            </div>
           <a href="pendientes.php" onclick="changeHighlight(event)"><i>&#x1F4DD;</i>Lista de Tareas</a> <!-- Icono de Tareas -->
            <a id="gestor-otc" href="registro.php" onclick="changeHighlight(event)"><i>&#x1F4BC;</i>Gestor OTC</a> <!-- Icono de gestión -->
            <a id="estados-tareas" href="informeregistro.php" onclick="changeHighlight(event)">Estados de tareas</a> <!-- Icono de subgestión -->
            <a id="archivos" href="../../PHP/compartido.php"  onclick="changeHighlight(event)"><i>&#x1F4C1;</i>Archivos</a> <!-- Icono de carpeta -->
            <br> <br> 
            <p>Registro de Tareas</p>
            <a id="Reportetareas" href="reportedetareas.php" onclick="changeHighlight(event)"><i>&#x1F4CA;</i>Movimientos</a> <!-- Icono de análisis -->
            <a id="Registrotareas" href="registrotareas.php" onclick="changeHighlight(event)"><i>&#x1F4CA;</i>Ultimo Estado</a> <!-- Icono de análisis -->
            <br> <br> <br>
            <a id="Reportetareas" href="https://otcepcdad.seguridadciudad.gob.ar/" onclick="changeHighlight(event)" target="_blank"><i>&#x1F30E;</i>Web OTC</a> <!-- Icono de análisis -->
            <br> <br>
           <a id="cerrar-sesion" href="../../cerrar.php" onclick="changeHighlight(event)"><i>&#x1F6AA;</i>Cerrar Sesión</a>
        </div>
        V2.3
    </div>

    <script>
        function changeHighlight(event) {
            // Obtener el enlace que se ha hecho clic
            var link = event.target.closest('a');
            // Obtener todos los enlaces dentro de la barra de navegación
            var links = document.querySelectorAll('.navbar a');
            // Eliminar la clase 'highlight' de todos los enlaces
            links.forEach(function(link) {
                link.classList.remove('highlight');
            });
            // Agregar la clase 'highlight' al enlace que se ha hecho clic
            link.classList.add('highlight');
            // Almacenar el ID del enlace resaltado en el almacenamiento local
            localStorage.setItem('highlightedLink', link.id);
        }

        // Función para cargar el resaltado almacenado
        function loadHighlight() {
            // Obtener el ID del enlace resaltado almacenado
            var highlightedLinkId = localStorage.getItem('highlightedLink');
            if (highlightedLinkId) {
                // Obtener el enlace resaltado por su ID
                var highlightedLink = document.getElementById(highlightedLinkId);
                if (highlightedLink) {
                    // Agregar la clase 'highlight' al enlace resaltado
                    highlightedLink.classList.add('highlight');
                }
            }
        }

        // Llamar a la función para cargar el resaltado al cargar la página
        window.onload = loadHighlight;
    </script>
</body>
</html>
