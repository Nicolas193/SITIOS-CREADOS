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
    <style>
        /* Estilos personalizados para mejorar los íconos */
        .sidebar a i {
            font-size: 18px; /* Aumentamos el tamaño de los íconos */
            margin-right: 10px; /* Añadimos un margen para separar el icono del texto */
            transition: color 0.3s ease; /* Efecto de transición para el color al pasar el mouse */
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            color: #fff;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        /* Efecto de color de fondo y color del texto cuando se pasa el mouse */
        .sidebar a:hover {
            background-color: #2c3e50;
            color: #f39c12;
        }

        /* Estilo para los enlaces destacados */
        .sidebar a.highlight {
            background-color: #f39c12;
            color: #000;
        }

        .footer {
            text-align: center;
            color: #fff;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="navbar">
            <div class="user-container">
                <a id="mi-tarea" href="../../GESTION OTC/php/mistareas.php" onclick="changeHighlight(event)">
                    <span class="user-icon">&#x1F464;</span>
                    <?php
                    if (isset($_SESSION['username'])) {
                        echo '<span class="username">' . $_SESSION['username'] . '</span>';
                        if (isset($_SESSION['tipo'])) {
                            echo '<br><span class="user-role">(' . $_SESSION['tipo'] . ')</span>';
                        }
                    }
                    ?>
                </a>
            </div>
            
            <!-- Menú de opciones -->
            <a href="../../GESTION OTC/php/registro.php" onclick="changeHighlight(event)">
                <i class="fas fa-cogs"></i>Gestor OTC
            </a>
            <a href="compartido.php" class="highlight" onclick="changeHighlight(event)">
                <i class="fas fa-folder"></i>Archivos
            </a>
            <a id="Reportetareas" href="https://otcepcdad.seguridadciudad.gob.ar/" onclick="changeHighlight(event)" target="_blank">
                <i class="fas fa-globe"></i>Web OTC
            </a>
            <a href="../../cerrar.php" onclick="changeHighlight(event)">
                <i class="fas fa-sign-out-alt"></i>Cerrar Sesión
            </a>
            
            <br><br>
            <div class="footer">
                V2.3
            </div>
        </div>
    </div>

    <script>
        function changeHighlight(event) {
            // Obtener todos los elementos <a> dentro de la barra de navegación
            var links = document.querySelectorAll('.navbar a');
            
            // Eliminar la clase 'highlight' de todos los elementos <a>
            links.forEach(function(link) {
                link.classList.remove('highlight');
            });
            
            // Agregar la clase 'highlight' al elemento <a> que se ha hecho clic
            event.target.classList.add('highlight');
        }
    </script>
</body>
</html>
