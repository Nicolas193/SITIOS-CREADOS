<?php
include("conexion.php");
$con = conectar();
$tanque = isset($_POST['tanque']) ? $_POST['tanque'] : null;
$fecha_desde = isset($_POST['fecha_desde']) ? $_POST['fecha_desde'] : null;
$hora_desde = isset($_POST['hora_desde']) ? $_POST['hora_desde'] : "00:00:00";
$fecha_hasta = isset($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : null;
$hora_hasta = isset($_POST['hora_hasta']) ? $_POST['hora_hasta'] : "23:59:59";
$motivo = isset($_POST['motivo']) ? $_POST['motivo'] : null;

// Construir la consulta SQL
$sql = "SELECT 
          id,
          tanque, 
          medicion, 
          fecharegistro,  
          responsablemedicion, 
          (medicion - LAG(medicion) OVER (PARTITION BY tanque ORDER BY fecharegistro)) AS diferencia,
          motivo,
          fasonselec
        FROM medicionesdetanques";
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

if (!empty($where)) {
  $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY fecharegistro DESC";

// Ejecutar la consulta
$query = mysqli_query($con, $sql);

include("copiados.php"); 
?>



<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet"  type="text/css" href="../css/vistamediciones.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
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
<script>
function mostrarDescargas() {
  var descargas = document.getElementById("descargas");
  if (descargas.style.display === "none") {
    descargas.style.display = "block";
  } else {
    descargas.style.display = "none";
  }
}
</script>
  <title>INICIO</title>
</head>
<body>
  <br>
  <button onclick="mostrarDescargas()" style="background-color: blue; color: white; border-radius: 10px; border: none; padding: 10px;margin-top: -10px;">Descargas</button>

  <div id="descargas" style="display: none; position: absolute; background-color:#fff; border:2px solid #6bb55c; border-radius:10px;height: 170px;">
  <div style="border:2px solid #a3a3a3; border-radius:10px ; padding:5px;margin-left:2px ; margin: 20px;">

    <form method="POST" action="ExcelInfo.php">
          <button class="btn btn-success pull-right" name="export"><span class="glyphicon glyphicon-print"></span> Exportar a Excel</button>
      <label>Tanque:</label>
    <input type="text" name="tanque" value="<?php echo isset($tanque) ? $tanque : ''; ?>">
    <label>Desde:</label>
   <input type="date" name="fecha_desde" value="<?php echo isset($fecha_desde) ? $fecha_desde : ''; ?>">
    <label>Hasta:</label>
    <input type="date" name="fecha_hasta" value="<?php echo isset($fecha_hasta) ? $fecha_hasta : ''; ?>">
    <label>Motivo:</label>
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

       </form>
    </div>   

<button class="pdf-btn" onclick="table()"   style=" margin: 20px;background-color: #2196F3; color: white; border-radius: 5px; border: none; padding: 10px; display: flex; align-items: center;"><img class="imagenpdf" src="../imagenes/imagenpdf.png" style="height: 20px; margin-right: 10px;"> Descargar como PDF</button>

</div>
  <br>
  <br>


  <form action="vistamediciones.php" method="post">
   <div> 
    <label>Tanque:</label>
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
    <label>Motivo:</label>
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
  

<div id="contenido">
  <div class="container-fluid">
<style>
  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
  }

  th {
    background-color: #f2f2f2;
    color: #333;
    font-weight: bold;
    text-align: center;
    padding: 8px;
    border: 1px solid #ccc;
  }

  td {
    text-align: center;
    padding: 8px;
    border: 1px solid #ccc;
  }

  .green {
    color: green;
    font-weight: bold;
  }

  .red {
    color: red;
    font-weight: bold;
  }

  .orange {
    color: orange;
    font-weight: bold;
  }

  .titulos{
    background-color: #b1b1b1;

  }
</style>
    <div class="row">
      <div class="col-mid-6">
        <br> <br> <br> <br> 
        <table class="table table-responsive">
          <thead>
            <tr class="titulos">
              <th>Tanque</th>
              <th>Medicion</th>
              <th>Fecha Registro</th>
              <th>Responsable</th>
              <th>Medicio X Tanque = Kilos</th>
               <th>(Actual-Anterio)*Diametro</th>
              <th>Motivo</th>
              <th>Fason</th>
            </tr>
          </thead>
          <tbody>
            <?php
             $totaldif=0;
             $anterior=0;
             $medicion1 =0;
             $medicion2=0;
              while($row = mysqli_fetch_array($query)) {
                 if($row["fecharegistro"]>=$fecha_desde && $row["fecharegistro"]<=$fecha_hasta || $fecha_hasta==""){
                $tanque = $row['tanque'];
                $medicion = $row['medicion'];
                $multiplicador = 1;

            if ($tanque == "1") {
              $multiplicador = 82;
                } elseif ($tanque == "2") {
              $multiplicador = 82;
                } elseif ($tanque == "A") {
              $multiplicador = 71;
                }elseif ($tanque == "B") {
              $multiplicador = 47;
                }elseif ($tanque == "3") {
              $multiplicador = 82;
                }elseif ($tanque == "4") {
              $multiplicador = 82;
                }elseif ($tanque == "C") {
              $multiplicador = 74;
                }elseif ($tanque == "E") {
              $multiplicador = 74;
                }elseif ($tanque == "16") {
              $multiplicador = 47;
                }elseif ($tanque == "11") {
              $multiplicador = 230;
                }elseif ($tanque == "14") {
              $multiplicador = 87.2;
                }elseif ($tanque == "15") {
              $multiplicador = 282;
                }elseif ($tanque == "21") {
              $multiplicador = 96.8;
                }elseif ($tanque == "22") {
              $multiplicador = 96.8;
                }elseif ($tanque == "23") {
              $multiplicador = 96.8;
                }elseif ($tanque == "24") {
              $multiplicador = 96.8;
                }elseif ($tanque == "25") {
              $multiplicador = 96.8;
                }elseif ($tanque == "26") {
              $multiplicador = 152.7;
                }elseif ($tanque == "30") {
              $multiplicador = 220;
                }elseif ($tanque == "31") {
              $multiplicador = 152.7;
                }elseif ($tanque == "32") {
              $multiplicador = 152.7;
                }elseif ($tanque == "33") {
              $multiplicador = 244;
               }elseif ($tanque == "34") {
              $multiplicador = 244;
                }elseif ($tanque == "18") {
              $multiplicador = 220;
               }elseif ($tanque == "19") {
              $multiplicador = 220;
                }

           // Construir la consulta SQL
$sql = "SELECT 
          id,
          tanque, 
          medicion, 
          fecharegistro,  
          responsablemedicion, 
          (medicion - LAG(medicion) OVER (PARTITION BY tanque ORDER BY fecharegistro)) AS diferencia,
          motivo,
          fasonselec
        FROM medicionesdetanques";


// Agregar cláusula WHERE para filtrar mediciones del mismo tanque con fechas anteriores a la actual
$sql .= " WHERE tanque='$tanque' AND fecharegistro<'$row[fecharegistro]' AND fasonselec NOT IN ('venta alicorp', 'venta insugra', 'venta unilever')";

// Limitar el resultado a una sola fila ordenada por fecha de registro descendente
$sql .= " ORDER BY fecharegistro DESC LIMIT 1";

// Ejecutar la consulta para obtener la última medición del mismo tanque anterior al que se está evaluando
$anterior_query = mysqli_query($con, $sql);

// Guardar el resultado de la consulta en una variable
$anterior = mysqli_fetch_array($anterior_query);


// Verificar si la primera posición tiene un valor
if (!isset($anterior['medicion'])) {
    // Si no tiene valor, establecer el valor de la primera posición como 0
    $anterior['medicion'] = 0;
}


              ?>
            <?php if($row['motivo']==$motivo||$motivo==""){?>
                <tr>
              
                  <td><?php echo $row['tanque'];?></td>
                  <td><?php echo $row['medicion'];?></td>
                  <td><?php echo $row['fecharegistro'];?></td>
                   <td><?php echo $row['responsablemedicion'];?></td>

                   <?php 
                   // Multiplicar $medicion por $multiplicador si $row['fasonselec'] es igual a "venta unilever" y $anterior está definido
                if ($row['fasonselec'] == "venta unilever" || $row['fasonselec'] == "venta insugra"  || $row['fasonselec'] == "venta alicorp") {


                          $resultado = $anterior['medicion'] * $multiplicador;

                     ?><td><?php echo $anterior['medicion']." X ".$multiplicador." = ".$resultado?></td><?php
                } else {


                                  $resultado = $medicion * $multiplicador;
                               ?><td><?php echo $medicion." X ".$multiplicador." = ".$resultado?></td><?php
                        }


                       $totaldif=($row['medicion']- $anterior['medicion'])*$multiplicador;

                        ?>
                    <td><?php echo "(".$row['medicion']." - ".$anterior['medicion'].")"."X".$multiplicador." = ". $totaldif;?></td>
                  <td><?php echo $row['motivo'];?></td>
                  <td><?php echo $row['fasonselec'];?></td>

             </tr>

<?php
 }

  }
             }

           ?>
</tbody>
</table>

</div>
</div>
</div>
</div>
</body>
</html>



 