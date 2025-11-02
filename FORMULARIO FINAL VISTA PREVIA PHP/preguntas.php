<?php
	include("conexion1.php"); 	#llama a la funcion donde se conecta con la base de datos
	$con=conectar();
	$sql="SELECT * FROM preguntaske"; #trae la tabla de datos
	$query=mysqli_query ($con,$sql);
	$row=mysqli_fetch_array($query);
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet"	type="text/css" href="preguntaK.css">
	<link rel="stylesheet"	type="text/css" href="normalize.css">
	<script src="https://kit.fontawesome.com/019bb635e7.js" crossorigin="anonymous"></script>
	<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
	<title>KeEncuesta</title>
</head>
<body>

	<!--Imagen y frase para que completen la encuesta -->
<div class="portada">
	<div class="logo"><img src="logoke.png" class="logo-img" data-aos="flip-left"
     data-aos-easing="ease-out-cubic"
     data-aos-duration="2000"></div>
	<div class="titulo" data-aos="zoom-in"><b>POR FAVOR, DEDIQUE UN MOMENTO A COMPLETAR ESTA PEQUEÑA ENCUESTA, LA INFORMACION QUE NOS PROPORCIONE SERA UTILIZADA PARA MEJORAR NUESTROS SERVICIOS.</b></div>

	<!--Inicio de la encuesta-->
	<form action="insertar1.php" method="POST">	
		<p >¿Con que sector te sientes mas identificado?</p>	
		<div class="pregunta1" >
			  <input type="checkbox" name="op1"  class="pregunta1-op" value="1"> - Agronomia .
 			  <input type="checkbox" name="op1"  class="pregunta1-op" value="2"> - productor .
 			  <input type="checkbox" name="op1"  class="pregunta1-op" value="3"> - Otros   
 		</div>
 		<p>¿Le resulta de utilidad la app <b>Ke insumos ?</b> para planificar sus campañas?</p>	
		<div class="pregunta2">
			  <input type="radio" name="op12" value="1" class="pregunta2-op"> - Si .
 			  <input type="radio" name="op12" value="2" class="pregunta2-op"> - No .
 			  <input type="radio" name="op12" value="3" class="pregunta2-op"> - Tal Vez  
 		</div>

 		<p>¿Que cambios le harias a la plataforma <b>Ke insumos</b>?</p>	
		<div class="pregunta3">
			  <input type="text" name="pregunta3"  class="pregunta3-op" placeholder="Responde aqui" required> 
 		</div>

 		 		<p>Detalla tres (3) criticas a la plataforma <b>Ke insumos<b></p>	
		<div class="pregunta3">
			  <input type="text" name="pregunta4"  class="pregunta3-op" placeholder="Responde aqui" required> 
 		</div>
 		<div class="enviar">
			  <input id="submit" name="submit" type="submit" value="Enviar">
 		</div>
	</form>

</div>

<!--Animacion-->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
	<script>
  AOS.init();
</script>

</body>
</html>