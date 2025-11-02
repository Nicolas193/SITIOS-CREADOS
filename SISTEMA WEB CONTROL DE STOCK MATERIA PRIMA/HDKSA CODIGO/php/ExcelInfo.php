
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

?>

<?php
	header("Content-Type: application/xls");    
	header("Content-Disposition: attachment; filename=Tabla_Tanque_" . date('Y:m:d:m:s').".xls");
	header("Pragma: no-cache"); 
	header("Expires: 0");

	

	
	if(isset($_POST['export'])){ ?>


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
                $sumadekilogramos= 0;
                $multiplicador = 1;

            if ($tanque == "1") {
              $multiplicador = 82;
                } elseif ($tanque == "2") {
              $multiplicador = 82;
                } elseif ($tanque == "A") {
              $multiplicador = 71;
              $sumadekilogramos = 4000;
                }elseif ($tanque == "B") {
              $multiplicador = 47;
              $sumadekilogramos = 2600;
                }elseif ($tanque == "3") {
              $multiplicador = 82;
                }elseif ($tanque == "4") {
              $multiplicador = 82;
                }elseif ($tanque == "C") {
              $multiplicador = 74;
              $sumadekilogramos = 4000;
                }elseif ($tanque == "E") {
              $multiplicador = 74;
              $sumadekilogramos = 4000;
                }elseif ($tanque == "16") {
              $multiplicador = 47;
              $sumadekilogramos = 2600;
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

                     ?><td><?php echo $resultado?></td><?php
                } else {


                                  $resultado = $medicion * $multiplicador;
                               ?><td><?php echo $resultado?></td><?php
                        }


                       $totaldif=($row['medicion']- $anterior['medicion'])*$multiplicador;

                        ?>
                    <td><?php echo $totaldif;?></td>
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

<?php
	}
?> 