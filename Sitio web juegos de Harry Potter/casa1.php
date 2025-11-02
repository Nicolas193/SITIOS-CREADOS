



<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet"	type="text/css" href="casa1.css">
	<title>CV de Nicolas</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="   sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">  
	<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
	<script src="https://kit.fontawesome.com/019bb635e7.js" crossorigin="anonymous"></script>
	<title>casas</title>
</head>
<body>
	<div class="imageninicio"><img src="hufflepuff.gif" alt="" class="imageninicio_imag"></div>
	<h1>NOVEDADES DE TU CASA </h1>


</body>
</html>

<?php 	include("copiados2.0.php") ?>
<?php include("conexion.php") ?>

	<table class="table">
		<thead>
			<tr>
				<th>ID</th>
				<th>Nombre</th>
				<th>Imagen</th>
				<th>descripcion</th>
			</tr>
		</thead>
		<tbody>
			<?php
				while($row=mysqli_fetch_array($query)){
			?>
				<tr>
					<td><?php echo $row['id'];?></td>
					<td><?php echo $row['nombre'];?></td>
					<td><img style="width:200px; height: auto" src="imagenes/<?php echo $row['imagen'];?>"></td>
					<td><?php echo $row['descripcion'];?></td>
					 <td><a href="delete.php?id=<?php echo $row['id'] ?>" class="btn btn-danger">Eliminar</a></td>
				</tr>
			<?php
				}
			?>
		</div>
	</div>
</div>




