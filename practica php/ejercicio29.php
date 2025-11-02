<?php

$texto1="";

if ($_POST){

	$texto1= (isset($_POST['texto1']))?$_POST['texto1']:""; // si hay informacion en esta pregutna
}

?>



<<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Formulario</title>
</head>
<body>
	<strong> Hola	</strong>: <?php echo $texto1;
	?>
		<form accept="ejercicio29.php" method="post">
			ingrese la primera pregunta
			<input type="text" name="texto1" id="pregunta1">
			<input type="submit" name="enviar" type="enviar datos a base">
			


		</form>
</body>
</html>

