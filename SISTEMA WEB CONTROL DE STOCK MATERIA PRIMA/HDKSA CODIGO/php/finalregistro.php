<?php

  include("conexion.php");  #llama a la funcion donde se conecta con la base de datos
  $con=conectar();
  $sql="SELECT * FROM medicionesdetanques"; #trae la tabla de datos
  $query=mysqli_query ($con,$sql);
  $row=mysqli_fetch_array($query);

?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet"  type="text/css" href="../css/registro.css">
  <title></title>
</head>
<body>
<nav id="menu">
  <a href="registro.php"><b>Volver</b></a>
      <a href="" ><b class="hdksa">HDK SA</b></a>
    <a href="table.php"><b>Panel</b></a>
</nav>

      <div class="container">
    <div class="wrapper">
      <ul class="steps">
      </ul>
      <form class="form-wrapper" action="insertar.php" method="POST" enctype="multipart/form-data">
        <fieldset class="section is-active">
           <h3>Datos registrados!</h3>
          <p>Se registraron todos los datos correctamente.</p><br><br><br>
<a href="registro.php" class="restablecerdatos mi-clase"> <div class="dato" >Agregar nuevo reporte de tanques</div></a>
<a href="informeregistro.php" class="clase-enlace mi-clase"> <div class="data">Editar Registros</div></a>


        </fieldset>
        
      </form>
    </div>
  </div>



    </div>
  </div>

    </div>
  </div>
</div>

</body>
</html>