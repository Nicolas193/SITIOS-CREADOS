<?php 	include("copiado.php") ?>
</br>

<?php
	include("conexion.php"); 	#llama a la funcion donde se conecta con la base de datos
	$con=conectar();
	$sql="SELECT * FROM users"; #trae la tabla de datos
	$query=mysqli_query ($con,$sql);
	$row=mysqli_fetch_array($query);


?>
<div class="container">
	<div class="row">
		<div class="col-mid-6">

	<br/>
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




