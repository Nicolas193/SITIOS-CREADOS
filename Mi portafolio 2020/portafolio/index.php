<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet"	type="text/css" href="index.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
	<title>Portafolio De Nicolas</title>
</head>
<body >

<div class="fondogris"><p class="textobienvenida">Gracias por Ingresar... :)<p></div>
<div class="imageninicio"><img src="inicioaportafilo.gif" alt="" class="imageninicio_imag"></div>


  <div class="wrap">
	<div class="contenedor">
	<?php 	include("copiados.php"); ?>
	<hr class="my-2">
	<h1 class="display-3">Bienvenidos</h1>
	<p class="lead"	>Portafolio de Nicolas Maciel</p>
	<hr class="my-2">
	<p>Mis Proyectos</p>
	</div> 

			<?php
				include("conexion.php"); 	#llama a la funcion donde se conecta con la base de datos
				$con=conectar();
				$sql="SELECT * FROM galeria"; #trae la tabla de datos
				$query=mysqli_query ($con,$sql);
				$row=mysqli_fetch_array($query);

			?>
	<div class="row row-cols-1 row-cols-md-3 g-4">
			<?php
				while($row=mysqli_fetch_array($query)){
			?>
				<div class="carpetas">
 					 <div class="col">
 					   <div class="card">
 					     <img src="imagenes/<?php echo $row['imagen'];?>" class="card-img-top">
 					     <hr class="my-1">
 					     <div class="card-body">
 					       <h5 class="card-title"><?php echo $row['nombre'];?></h5>
					        <p class="card-text"><?php echo $row['descripcion'];?></p>
 					     </div>
 					   </div>
 					 </div>
				</div>	
			
			</br>

			<?php
													}
			?>

	</div>



  </div>

</body>
</html>


