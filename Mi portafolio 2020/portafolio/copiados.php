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
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="   sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">  
	<link rel="stylesheet"	type="text/css" href="copiado.css">
	<!-- bustrap es para mejor los estilos -->
	<title>Portafolio</title>
</head>
<body>
	<nav>
		<a href="cv.php" class="btn btn-primary">MI CV</a>
		<a href="index.php"><b>portafolio</b></a>
		<a href="portafolio.php"><b>Panel</b></a>
		<a href="cerrar.php"><b>Contactame</b></a>
		<a href="cerrar.php"><b>Cerrar</b></a>
	</nav>


  <div class="card-header">



  </div>




</body>
</html>