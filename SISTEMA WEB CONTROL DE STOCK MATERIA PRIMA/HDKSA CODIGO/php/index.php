<?php
include("conexion.php");

$con = conectar();
$tanque = isset($_POST['tanque']) ? $_POST['tanque'] : null;

// Construir la consulta SQL
$sql = "SELECT 
  m.id,
  m.tanque, 
  CASE 
    WHEN m.fasonselec IN ('venta unilever', 'venta insugra', 'venta alicorp') THEN (
      SELECT medicion 
      FROM medicionesdetanques 
      WHERE tanque = m.tanque AND fecharegistro < m.fecharegistro 
      AND fasonselec NOT IN ('venta unilever', 'venta insugra', 'venta alicorp')
      ORDER BY fecharegistro DESC LIMIT 1
    )
    ELSE m.medicion
  END AS medicion,
  m.fecharegistro  
FROM medicionesdetanques m
INNER JOIN (
  SELECT tanque, MAX(fecharegistro) AS ultima_fecha
  FROM medicionesdetanques
  GROUP BY tanque
) ultima_fecha_por_tanque 
ON m.tanque = ultima_fecha_por_tanque.tanque AND m.fecharegistro = ultima_fecha_por_tanque.ultima_fecha";

$sql2 ="SELECT fecharegistro,cantidad,hueso FROM materiaprima";
$where = array();



if (!empty($tanque)) {
  $where[] = "m.tanque='$tanque'";
}

if (!empty($where)) {
  $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY m.tanque ASC";
$sql2 .=" ORDER BY fecharegistro ASC";
// Ejecutar la consulta
$query = mysqli_query($con, $sql);
$query2 = mysqli_query($con, $sql2);
?>
<?php include("copiados.php"); ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet"  type="text/css" href="../css/indexx.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
   <link rel="shortcut icon" href="../imagenes/presentacion.ico" />
  <!-- bustrap es para mejor los estilos -->
  <title>Control de Tanques</title>
</head>
<body >


    <form action="index.php" method="post">
    <label>Ingrese el nombre del tanque:</label>
    <input type="text" name="tanque" value="<?php echo isset($tanque) ? $tanque : ''; ?>">
    <input type="submit" value="Filtrar">

  </form>
<button class="pdf-btn" onclick="table()"><img class="imagenpdf" src="../imagenes/imagenpdf.png"> Descargar como PDF</button>
  <div class="wrap">
	<div class="contenedor">
	<hr class="my-2">
	<h1 class="display-3">Control De tanques</h1>
	<p class="lead"	>Informacion Principal</p>
	<hr class="my-2">
	<p>Informes</p>
	</div> 

<br><br><br><br><br><br>

	<div class="row row-cols-1 row-cols-md-3 g-4">



			<?php
      $totalstock=0;
				while($row=mysqli_fetch_array($query)){


					    $tanque = $row['tanque'];
              $medicion = $row['medicion'];

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


              $resultado = $medicion * $multiplicador;
                $totalstock= $resultado +  $totalstock;
			?>

				<div class="carpetas">
 					 <div class="col">
 					   <div class="card">
 					     <img src="../imagenes/<?php echo $row['tanque'];?>.jpg" class="card-img-top">
 					     <hr class="my-1">
 					     <div class="card-body">
 					 <h5 class="card-title">Tanque: <?php echo $row['tanque'];?></h5>
               <h5 class="card-title">Medicion: <?php echo $medicion;?></h5>
 					       <h5 class="card-title">Kilos: <?php echo $medicion." x ".$multiplicador." = ".$resultado;?></h5>

 					     </div>
 					   </div>
 					 </div>
				</div>	
			
			</br>

			<?php
       									
                           }
         $sumahueso=0;
         while($row2=mysqli_fetch_array($query2)){     
         $hueso = $row2['hueso']*0.10 ;
         $sumahueso=$hueso + $sumahueso;


          }

          $totalstock=$totalstock + $sumahueso;             
      ?>
              <h5 class="TotalesKilos">Kilos totales: <?php echo $totalstock;?></h5>
  </div>

  </div>
</body>
</html>

<?php 

include("indeximprimirpdf.php");

?>
