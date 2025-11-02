
  <?php 

  include("conexion.php");  #llama a la funcion donde se conecta con la base de datos
  $con=conectar();
  $sql="SELECT * FROM materiaprima ORDER BY id DESC"; #trae la tabla de datos
  $query=mysqli_query ($con,$sql);
  $row=mysqli_fetch_array($query);




   include('copiados.php');   

    ?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
   <link rel="stylesheet"  type="text/css" href="../css/materiaprima.css">
   <script>
  function table() {
    // Obtenemos el contenido HTML del div
    var html = document.getElementById('contenido').outerHTML;
    // Hacemos una llamada AJAX para descargar el contenido como un archivo PDF
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'download.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.responseType = 'blob';
    xhr.onload = function() {
      if (this.status == 200) {
        var blob = new Blob([this.response], { type: 'application/pdf' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'Tabla Fecha:<?php echo date('Y-m-d')?>.pdf';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
      }
    };
    xhr.send('html=' + encodeURIComponent(html));
  }
</script>
  <title>INGRESO MP</title>
</head>
<body>

  <br>  <br> 


<form class="form-wrapper" action="insertarmp.php" method="POST" enctype="multipart/form-data">

  <label for="start_time">Fecha de Ingreso</label>
  <input type="datetime-local" id="start_time" name="fecharegistro" required>

  <label for="cantidad">Ingreso de Materia Prima (en kg)</label>
  <input type="number" id="cantidad" name="cantidad" step="0.01" min="0" required>

    <label for="cantidad">Ingreso de Hueso (en kg)</label>
  <input type="number" id="hueso" name="hueso" step="0.01" min="0" >

  <button type="submit">Ingresar</button>

</form>
 <br>  <br>  <br>
 <button class="pdf-btn" onclick="table()"><img class="imagenpdf" src="../imagenes/imagenpdf.png"> Descargar como PDF</button>
   <table class="table table-responsive" id="contenido">
            <thead>
               <tr>
              
                <th>Fecha</th>
                <th>Ingreso</th>
                <th>Hueso</th>
                <th>opcion</th>
              </tr>
            </thead>
            <tbody>
                <tr>
              
                  <td><?php echo $row['fecharegistro'];?></td>
                  <td><?php echo $row['cantidad'];?></td>
                   <td><?php echo $row['hueso'];?></td>

<td>
  <a href="deletemp.php?id=<?php echo $row['id'] ?>" id="delete-btn" class="btn btn-danger">Eliminar</a>
    <a href="actualizarmp.php?id=<?php echo $row['id'] ?>" class="btn btn-actualizar">Editar</a>
</td>
</tr>

              <?php
                while($row=mysqli_fetch_array($query)){
              ?>
                <tr>

                   <td><?php echo $row['fecharegistro'];?></td>
                  <td><?php echo $row['cantidad'];?></td>
                   <td><?php echo $row['hueso'];?></td>

<td>
  <a href="deletemp.php?id=<?php echo $row['id'] ?>" id="delete-btn" class="btn btn-danger">Eliminar</a>
    <a href="actualizarmp.php?id=<?php echo $row['id'] ?>" class="btn btn-actualizar">Editar</a>
</td>
</tr>
<?php
             }
           ?>
</tbody>

<style>
  .table-responsive {
  width: 80%;
  margin: 20px auto;
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th,
.table td {
  border: 1px solid #ccc;
  padding: 10px;
  text-align: center;
}.table-responsive {
  width: 80%;
  margin: 20px auto;
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th,
.table td {
  border: 1px solid #ccc;
  padding: 10px;
  text-align: center;
}
  
</style>
</table>
</body>
</html>