<?php 
	session_start();
	if(isset($_SESSION['user'])!="Nicolas"){

		
			header("location:login.php"); #redirecciona al index

	}

?>

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
      <script>
        function abrirCalculadora() {
            window.open("calculadora.html", "Calculadora", "width=300, height=400");
        }
    </script>
	<title>Monitoreo tanques HDK S.A</title>
</head>
<body>

<div class="contenedor">
	<div class="flecha">
 <svg xmlns="http://www.w3.org/2000/svg" width="40" height="30" fill="currentColor" class="bi bi-arrow-left-circle" viewBox="0 0 16 16" onclick="window.history.back()">
    <path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8zm15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-4.5-.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/>
  </svg>
  <svg xmlns="http://www.w3.org/2000/svg" width="40" height="30" fill="currentColor" class="bi bi-arrow-right-circle" viewBox="0 0 16 16"  onclick="window.history.forward()">Adelante
    <path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8zm15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM4.5 7.5a.5.5 0 0 0 0 1h5.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5H4.5z"/>
  </svg>

 </div>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav">
      	    <div class="logo"><a>HDK SA</a></div>
        <li class="nav-item">
          <a  class="btn btn-primary" href="table.php"><b>PANEL</b></a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="vistamediciones.php"><b>Informe</b></a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="index.php"><b>Control de Tanques</b></a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="registro.php"><b>Formulario</b></a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="materiaprima.php"><b>Ingreso MP</b></a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="informeregistro.php"><b>Editar Info Tanques</b></a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="cerrar.php"><b>Cerrar</b></a>
        </li>
      </ul>
      <a href="#" onclick="window.open('../php/calculadora.html','Calculadora','width=350,height=450');" class="boton-calculadora">
  <img class="imagencalculadora" src="../imagenes/calculadora.png">
</a>
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

