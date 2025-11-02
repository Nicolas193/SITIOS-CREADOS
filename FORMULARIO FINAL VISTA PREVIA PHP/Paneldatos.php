<?php
	include("conexion1.php");
	$con=conectar();
	$sql="SELECT * FROM preguntaske";
	$query=mysqli_query ($con,$sql);
	$row=mysqli_fetch_array($query);
?>

<?php

	$op1_1si="SELECT 
	(SELECT COUNT(*) FROM preguntaske WHERE op1=1) AS op1,
	(SELECT COUNT(*) FROM preguntaske WHERE op2=1) AS op2,
	(SELECT COUNT(*) FROM preguntaske WHERE op3=1) AS op3,
	(SELECT COUNT(*) FROM preguntaske WHERE op12=1) AS op12,
	(SELECT COUNT(*) FROM preguntaske WHERE op12=2) AS op13,
	(SELECT COUNT(*) FROM preguntaske WHERE op12=3) AS op14
	 FROM preguntaske";

	$conop1=mysqli_query ($con,$op1_1si);
	$op1=mysqli_fetch_array($conop1);
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet"	type="text/css" href="pane.css">
	<link rel="stylesheet"	type="text/css" href="normalize.css">
	<script src="https://kit.fontawesome.com/019bb635e7.js" crossorigin="anonymous"></script>
	<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
	<title>KeEncuesta</title>
</head>
<body>
<div class="portada">
	<div class="logo"><img src="logoke.png" class="logo-img" data-aos="flip-left"
     data-aos-easing="ease-out-cubic"
     data-aos-duration="2000"></div>
	<div class="titulo" data-aos="zoom-in"><b>PANEL ADMINISTRADOR</b></div>
<div class="contador_1">
	<?php
			if($op1=mysqli_fetch_array($conop1)){
			?>
			<tr class="contador">
			<div class="contador-titulo">usuarios que son agronomias</div>
				<div class="contador-1"><?php echo $op1['op1']?></div>
			<div class="contador-titulo">usuarios que son productores</div>
				<div class="contador-1"><?php echo $op1['op2']?></div>
			<div class="contador-titulo">usuarios que corresponde a otro sector</div>
				<div class="contador-1"><?php echo $op1['op3']?></div>
			<div class="contador-titulo">usuarios que le resultaron util la app</div>
				<div class="contador-1"><?php echo $op1['op12']?></div>
			<div class="contador-titulo">usuarios que NO le resultaron util la app</div>
				<div class="contador-1"><?php echo $op1['op13']?></div>
			<div class="contador-titulo">usuarios Tal vez le resulto Util</div>
				<div class="contador-1"><?php echo $op1['op14']?></div>
			</tr>

			<?php
				}
	?>
</div>
		<table class="tabla-success">
		<div class="tabla">
		<thead class="table-success table-striped"> 
			<tr class="fila1">
				<th class="fila1-1">id</th>
				<th class="fila1-1">pregunta 1 opcion 1</th>
				<th class="fila1-1">pregunta 1 opcion 2</th>
				<th class="fila1-1">pregunta 1 opcion 3</th>
				<th class="fila1-1">pregunta 2</th>
				<th class="fila1-2">pregunta 3</th>
				<th class="fila1-2">pregunta 4</th>
			</tr>
		</thead>
	</div>	
		<tbody>
			<?php
				while($row=mysqli_fetch_array($query)){
			?>
				<tr>
					<th><?php echo $row['ID']?></th>
					<th><?php echo $row['op1']?></th>
					<th><?php echo $row['op2']?></th>
					<th><?php echo $row['op3']?></th>
					<th><?php echo $row['op12']?></th>  
					<th><?php echo $row['pregunta3']?></th>
					<th><?php echo $row['pregunta4']?></th>  

				</tr>
			<?php
				}
			?>
				
		</tbody>

	</table>
		
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
	<script>
  AOS.init();
</script>

</body>
</html>