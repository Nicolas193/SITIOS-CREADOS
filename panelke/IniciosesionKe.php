<?php 

session_start();  #validacion
if($_POST){
	if(($_POST['user']=="Keinsumos") && ($_POST['pass']=="xdrscesg") ){

			$_SESSION['user']="Keinsumos"; #cuando ingresar al link nuevamente se borra la contra y si o si tenes que inicar seccion de nuevo 

			header("location:panelke.php"); #redirecciona al index

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
	<link rel="stylesheet" type="text/css" href="iniciosesionKe.css">

	<title>Login</title>
</head>
<body>

	<div class="logo"><img src="https://www.keinsumos.com/public/template/images/ke_insumos_logo.png" alt="Keinsumos" class="logo-imag"></div>

	<div class="container">

		<div class="row">
			<div class="col-md-4">

			</div>

			<div class="col-md-4">
					<br/>
			<div class="card">
				<div class="card-header">
					Iniciar Sesión
			</div>
			<div class="card-body">


		<form action="iniciosesionKe.php" method="post">
		
		Usuario: <input class="form-control" type="text" name="user" id="">
		Contraseña: <input class="form-control" type="password" name="pass" id="">
 			<br/>
		<button class="btn btn-success" type="submit">Entrar al portaforlio</button>

	</form>

</div>

<div class="card-footer text-muted">
	
	</div>
</div>

			</div>
						
			<div class="col-md-4">

			</div>
		</div>


</div>

</html>

