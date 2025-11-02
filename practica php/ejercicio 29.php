<?php

$pregunta1="";
$pregunta2="";

if ($_POST){

	$pregunta1= (isset($_POST['pregunta1']))?$_POST['pregunta1']:""; // si hay informacion en esta pregutna asiganala 
	$pregunta1= (isset($_POST['pregunta2']))?$_POST['pregunta2']:""; // si hay informacion en esta pregutna 
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
	<?php echo $pregunta1;
	?>
		<form accept="ejercicio 29.php" method="post">
			ingrese la primera pregunta
			<input type="text" name="texto1" id="pregunta1">
			ingrese la segunra pregunta 
			<input type="text" name="Texto2" id="preguntas2">
			<input type="submit" name="enviar" type="enviar datos a base">
			


		</form>
</body>
</html>

