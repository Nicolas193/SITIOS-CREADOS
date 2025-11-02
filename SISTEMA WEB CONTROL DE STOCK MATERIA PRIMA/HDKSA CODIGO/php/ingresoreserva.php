 <?php 

  include("conexion.php");  #llama a la funcion donde se conecta con la base de datos
  $con=conectar();
  $sql="SELECT * FROM tanquereserva ORDER BY id DESC"; #trae la tabla de datos
  $query=mysqli_query ($con,$sql);
  $row=mysqli_fetch_array($query);

    ?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap">
  <link rel="stylesheet" type="text/css" href="../css/ingresoreserva.css">
  <title>Ingreso reserva</title>
</head>
<body>
  <br>  <br> 
<form action="table.php">
  <button>VOLVER</button>
</form>

  <br>  <br> 
  <form class="form-wrapper" action="insertarreserva.php" method="POST" enctype="multipart/form-data">
    <label for="start_time">Fecha de Ingreso</label>
    <input type="datetime-local" id="start_time" name="fecharegistro" required>
    <label for="cantidad">Ingreso reserva</label>
    <input type="number" id="reserva" name="reserva" step="0.01"  required>
    <button type="submit">Ingresar Reserva</button>
  </form>

  <br>  <br>  <br>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Reserva</th>
          <th>Opción</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><?php echo $row['fecharegistro'];?></td>
          <td><?php echo $row['reserva'];?></td>
          <td>
            <a href="deletereserva.php?id=<?php echo $row['id'] ?>" id="delete-btn" class="btn btn-danger">Eliminar</a>
            <a href="actualizarreserva.php?id=<?php echo $row['id'] ?>" class="btn btn-actualizar">Editar</a>
          </td>
        </tr>
        <?php
        while($row=mysqli_fetch_array($query)){
        ?>
        <tr>
          <td><?php echo $row['fecharegistro'];?></td>
          <td><?php echo $row['reserva'];?></td>
          <td>
            <a href="deletereserva.php?id=<?php echo $row['id'] ?>" id="delete-btn" class="btn btn-danger">Eliminar</a>
            <a href="actualizarreserva.php?id=<?php echo $row['id'] ?>" class="btn btn-actualizar">Editar</a>
          </td>
        </tr>
        <?php
        }
        ?>
      </tbody>
    </table>
  </div>

</body>
</html>