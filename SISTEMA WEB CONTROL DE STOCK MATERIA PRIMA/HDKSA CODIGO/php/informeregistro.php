<?php
  include("conexion.php");  #llama a la funcion donde se conecta con la base de datos

  $tanque = isset($_POST['tanque']) ? $_POST['tanque'] : null;
  $fecha_desde = isset($_POST['fecha_desde']) ? $_POST['fecha_desde'] : null;
  $hora_desde = isset($_POST['hora_desde']) ? $_POST['hora_desde'] : "00:00:00";
  $fecha_hasta = isset($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : null;
  $hora_hasta = isset($_POST['hora_hasta']) ? $_POST['hora_hasta'] : "23:59:59";
  $motivo = isset($_POST['motivo']) ? $_POST['motivo'] : null;
  $con = conectar();

  $sql = "SELECT * FROM medicionesdetanques";

  $where = array();

  if (!empty($tanque)) {
    $where[] = "tanque='$tanque'";
  }
if (!empty($fecha_desde)) {
  $fecha_desde = date('Y-m-d H:i:s', strtotime("$fecha_desde $hora_desde"));
}
if (!empty($fecha_hasta)) {
  $fecha_hasta = date('Y-m-d H:i:s', strtotime("$fecha_hasta $hora_hasta"));

}
  if (!empty($motivo)) {
    $where[] = "motivo='$motivo'";
  }

  if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
  }

  $sql .= " ORDER BY id DESC"; #ordena los datos por id

  $query = mysqli_query($con, $sql);
?>
<?php include("copiadosinfo.php"); ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet"  type="text/css" href="../css/informeregistro.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="shortcut icon" href="../imagenes/presentacion.ico" />
  <!-- bustrap es para mejor los estilos -->
  <title>Editar Registros</title>
</head>
<body>



    <form action="informeregistro.php" method="post">
     <div> 
    <label>Ingrese el nombre del tanque:</label>
    <input type="text" name="tanque" value="<?php echo isset($tanque) ? $tanque : ''; ?>">
  </div>
  <div>
    <label>Desde:</label>
   <input type="date" name="fecha_desde" value="<?php echo isset($fecha_desde) ? $fecha_desde : ''; ?>">
  </div>
  <div>
    <label>Hasta:</label>
    <input type="date" name="fecha_hasta" value="<?php echo isset($fecha_hasta) ? $fecha_hasta : ''; ?>">
  </div>
  <div>
    <label>Ingrese el motivo:</label>
      <select name="motivo" class="select-motivo">
    <option value="">Todos</option>
    <option value="venta">Venta</option>
    <option value="gvc">GVC</option>
    <option value="trasbordo">tranbordo</option>
    <option value="limpieza">Limpieza</option>
    <option value="fason">Fason</option>
    <option value="produccion">Producción</option>
    <option value="transbordo">Transbordo</option>
    <option value="devolucion">Devolución</option>
  </select>
</div>
    <input type="submit" value="Filtrar">

  </form>

  <div class="container-fluid">
      <div class="row">
        <div class="col-mid-6">
          <br/>
          <table class="table table-responsive">
            <thead>
               <tr>
              
                <th>tanque</th>
                <th>medicion</th>
                <th>fecharegistro</th>
                <th>responsablemedicion</th>
                <th>Motivo</th>
                <th>Motivo Fason</th>
                <th>opciones</th>
              </tr>
            </thead>
            <tbody>

              <?php
                while($row=mysqli_fetch_array($query)){
           if($row["fecharegistro"]>=$fecha_desde && $row["fecharegistro"]<=$fecha_hasta || $fecha_hasta==""){
              ?>
                <tr>
              
                  <td><?php echo $row['tanque'];?></td>
                  <td><?php echo $row['medicion'];?></td>
                  <td><?php echo $row['fecharegistro'];?></td>
                   <td><?php echo $row['responsablemedicion'];?></td>
                    <td><?php echo $row['motivo'];?></td>
                    <td><?php echo $row['fasonselec'];?></td>
<td>
  <a href="delete.php?id=<?php echo $row['id'] ?>" id="delete-btn" class="btn btn-danger">Eliminar</a>
  <a href="actualizar.php?id=<?php echo $row['id'] ?>" class="btn btn-actualizar">Editar</a>
</td>
</tr>
<?php

  }
             }
           ?>
</tbody>
</table>
</div>
</div>
</div>
<script>
  const deleteBtn = document.getElementById('delete-btn');
  deleteBtn.addEventListener('click', (event) => {
    if (!confirm('¿Estás seguro de que deseas eliminar este registro?')) {
      event.preventDefault();
    }
  });
</script>






</body>
</html>
