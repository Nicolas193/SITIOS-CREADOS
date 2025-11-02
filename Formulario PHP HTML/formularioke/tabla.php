<?php
	include("conexion.php");
	$con=conectar();

	$sql="SELECT * FROM formulario";
	$query=mysqli_query ($con,$sql);

	$row=mysqli_fetch_array($query);
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Formulario</title>
	<link rel="stylesheet"	type="text/css" href="formulario.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
</head>
<body>
	<div class="Formulario">
		<form method="POST" action="insertar.php" class="formulario">
       <div class="textomail">ESCRIBENOS Y EN BREVE NOS PONDREMOS EN CONTACTO CONTIGO </div>
       <label for="nombre">Nombre:</label>
       <input id="nombre" name="Nombre" placeholder="Nombre completo">
       <label for="email">Email:</label>
       <input id="email" name="Email" type="email" placeholder="ejemplo@email.com">
       <label for="mensaje">Mensaje:</label>
       <textarea id="mensaje" name="Comentario" placeholder="Danos tu mensaje"></textarea>
       <input id="submit" name="submit" type="submit" value="Enviar">
       </form>
	</div>

	<table class="tabla">
		<thead class="table-success table-striped"> 
			<tr>
				<th>Nombre</th>
				<th>Email</th>
				<th>comentario</th>
			</tr>
		</thead>

		<tbody>
			<?php
				while($row=mysqli_fetch_array($query)){
			?>
				<tr>
					<th><?php echo $row['Nombre']?></th>
					<th><?php echo $row['Email']?></th>
					<th><?php echo $row['Comentario']?></th>
					<th><a href="actualizarboton.php?id=<?php echo $row['ID'] ?>" class="btn btn-info">Editar</a></th>
                    <th><a href="delete.php?id=<?php echo $row['ID'] ?>" class="btn btn-danger">Eliminar</a></th>                                        
				</tr>
			<?php
				}
			?>
				
		</tbody>

	</table>


</body>
</html>