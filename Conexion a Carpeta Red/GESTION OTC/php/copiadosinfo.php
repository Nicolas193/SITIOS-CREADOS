<!DOCTYPE html>
<html>
<head>

  <meta charset="utf-8">

	<meta name="viewport" content="width=device-width, initial-scale=1">

 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous"> 

     <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

	<link rel="stylesheet"	type="text/css" href="../css/copia.css">

	<link rel="shortcut icon" href="../imagenes/presentacion.ico" />

	<!-- bustrap es para mejor los estilos -->

	<title>SISTEMA DE GESTION OTC</title>

</head>

<body>

<div class="contenedor">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">

  <div class="container-fluid">

    <div class="collapse navbar-collapse" id="navbarNav">

      <ul class="navbar-nav">

      	    <div class="logo"><a>OTCE GESTION</a></div>

        <li class="nav-item">

          <a  class="btn btn-primary" href="https://otcepcdad.seguridadciudad.gob.ar/"><b>OTCE</b></a>

        </li>

        <li class="nav-item">

          <a class="nav-link" href="index.php"><b>Tareas Finalizadas</b></a>

        </li>

        <li class="nav-item">

          <a class="nav-link" href="registro.php"><b>Carga de Tareas</b></a>

        </li>

        <li class="nav-item">

          <a class="nav-link" href="pendientes.php"><b>Tareas Pendientes</b></a>

        </li>

        <li class="nav-item">
          
          <a class="nav-link" href="informeregistro.php"><b>Estado de Tarea</b></a>

        </li>

        <li class="nav-item">

          <a class="nav-link" href="cerrar.php"><b>Cerrar</b></a>

        </li>

      </ul>

    </div>

  </div>

</nav>



</div>

<br><br><br>

<script>

const toggleMenuButton = document.createElement('button');

toggleMenuButton.classList.add('navbar-toggler');

toggleMenuButton.type = 'button';

toggleMenuButton.dataset.bsToggle = 'collapse';

toggleMenuButton.dataset.bsTarget = '#navbarNav';

toggleMenuButton.ariaControls = 'navbarNav';

toggleMenuButton.ariaExpanded = 'false';

toggleMenuButton.ariaLabel = 'Toggle navigation';



const toggleMenuIcon = document.createElement('span');

toggleMenuIcon.classList.add('navbar-toggler-icon');

toggleMenuButton.appendChild(toggleMenuIcon);



const navbar = document.querySelector('.navbar');

navbar.appendChild(toggleMenuButton);



const menu = document.querySelector('.navbar-collapse');



toggleMenuButton.addEventListener('click', function() {

  if (menu.classList.contains('show')) {

    menu.classList.remove('show');

    toggleMenuIcon.classList.remove('open');

  } else {

    menu.classList.add('show');

    toggleMenuIcon.classList.add('open');

  }

});

</script>

</body>

</html>



