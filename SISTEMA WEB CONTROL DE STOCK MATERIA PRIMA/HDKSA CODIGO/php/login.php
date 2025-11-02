<?php 
session_start();  #validacion
if($_POST){
	if(($_POST['user']=="HDKSA") && ($_POST['pass']=="HDKSA123") ){
		$_SESSION['user']="HDKSA"; #cuando ingresar al link nuevamente se borra la contra y si o si tenes que inicar seccion de nuevo
		header("location:bienvenida.php"); #redirecciona al index
	}else{
		echo "<script> alert('Usuario o contraseña incorrecta') </script";
	}
}
 ?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
	<link rel="stylesheet"	type="text/css" href="../css/login1.css">
	<link rel="shortcut icon" href="../imagenes/presentacion.ico" />
	<!-- bustrap es para mejor los estilos -->
	<title>Ingreso</title>
</head>
<body>
	<div class="container">
		<div class="row">
			<div class="col-md-4"></div>
			<div class="col-md-4">
					<br/>
						<img src="../imagenes/logeo.png" alt="logeo" class="imaglog">
			<div class="card">
			<div class="card-header">Iniciar Sesión</div>
			<div class="card-body">

		<form action="login.php" method="post">
		Usuario: <input class="form-control" type="text" name="user" id="">
		Contraseña: <input class="form-control" type="password" name="pass" id="">
 			<br/>
		<button class="btn btn-success" type="submit">Entrar al Panel</button>
		</form>

		</div>
			<div class="card-footer text-muted"></div>

		</div>
		</div>
		<div class="col-md-4"></div>
		</div>


<div class="marcadeagua">
    <p>Control de Tanque HDK.SA - 2023</p>
    <p> Made By:<a href="https://wa.me/541133345330"><i class="fa-brands fa-whatsapp"> </i>Nicolas Maciel :)</a></p>
</div>
</div>


</html>



