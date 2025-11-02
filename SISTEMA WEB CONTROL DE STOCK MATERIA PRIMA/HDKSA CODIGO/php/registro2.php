<?php

include("conexion.php");
$con = conectar();

// Obtener el último registro cargado
$sql_ultimo_registro = "SELECT * FROM medicionesdetanques ORDER BY id DESC LIMIT 1";
$query_ultimo_registro = mysqli_query($con, $sql_ultimo_registro);
$ultimo_registro = mysqli_fetch_array($query_ultimo_registro);

// Utilizar el valor del último registro para filtrar la consulta
$sql_tanques_iguales = "SELECT 
                          tanque, 
                          medicion, 
                          fecharegistro,  
                          responsablemedicion, 
                          (medicion - LAG(medicion) OVER (PARTITION BY tanque ORDER BY fecharegistro)) AS diferencia,
                          fasonselec 
                        FROM medicionesdetanques 
                        WHERE tanque = '".$ultimo_registro['tanque']."' 
                          AND fasonselec NOT IN ('venta alicorp', 'venta insugra', 'venta unilever')
                        ORDER BY fecharegistro DESC";
$query_tanques_iguales = mysqli_query($con, $sql_tanques_iguales);
$query_tanques_iguales2 =mysqli_query($con, $sql_tanques_iguales);

?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet"  type="text/css" href="../css/registro.css">
      <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
              <script>
        function abrirCalculadora() {
            window.open("calculadora.html", "Calculadora", "width=300, height=400");
        }
    </script>
      <link rel="shortcut icon" href="../imagenes/presentacion.ico" />
  <title>Formulario</title>
</head>
<body>
<nav id="menu">
      <a href="#" onclick="window.open('../php/calculadora.html','Calculadora','width=350,height=450');" class="boton-calculadora">
  <img class="imagencalculadora" src="../imagenes/calculadora.png">
</a>
      <a href="" ><b class="hdksa">HDK SA</b></a>
</nav>

      <div class="container">
    <div class="wrapper">
      <ul class="steps">
      </ul>
      <form class="form-wrapper" action="motivosave.php" method="POST" enctype="multipart/form-data">
        <fieldset class="section is-active">
           <h3>Ultimo Paso!</h3>

          <b>porfavor verifique que los datos se hallan ingresado correctamente y selecciona a que se debe la diferencia de carga</b>
<br><br>
          <b>Te equivocaste?</b>
          <a href="actualizarfrom.php?id=<?php echo $ultimo_registro['id'] ?>" class="actualizar-btn">Corregir</a>

<?php

// Mostrar solo las primeras dos filas de la tabla
echo '<div class="container-fluid">';
echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<table>';
echo '<thead><tr><th>Registro</th><th>Tanque</th><th>Medición</th><th>Responsable de la medición</th><th>Diferencia</th></tr></thead>';

$counter = 0; // Inicializar contador
$dif1=0;
$dif2=0;
$result=0;
while ($row = mysqli_fetch_array($query_tanques_iguales)) {
  // Imprimir solo las primeras dos filas
  if ($counter==0) {
    echo '<tr><th>'.'Actual'.'</th><td>'.$row['tanque'].'</td><td><font color="red">'.$row['medicion'].'</font></td><td>'.$row['responsablemedicion'].'</td><td>'.$row['diferencia'].'</td></tr>';
$dif1=$row['medicion'];

  } else {if($counter==1){
          echo '<tr><th>'.'Anterior'.'</th><td>'.$row['tanque'].'</td><td><font color="red">'.$row['medicion'].'</font></td><td>'.$row['responsablemedicion'].'</td><td>';
   $dif2=$row['medicion'];
  }

   break; // Detener el bucle después de imprimir las primeras dos filas
  }
  
  $counter++; // Incrementar el contador
}

echo '</table>';
echo '</div>';
echo '</div>';
echo '</div>';

$result=$dif1-$dif2;

echo 'Calculo de Mediciones: '.$dif1.' - '.$dif2.' = '.$result.'<br><br>';
// Obtener la primera fila de la tabla
$primera_fila = mysqli_fetch_array($query_tanques_iguales2);

// Determinar si la diferencia es positiva o negativa
$diferencia = $primera_fila['diferencia'];


if ($primera_fila['diferencia'] > 0) {
?>
<label for="motivo">Seleccione el motivo por el cual la medicion es mayor a la anterior medicion tomada:</label>
<select name="motivo" class="select-motivo" id="motivo">
  <option value="produccion">Producción</option>
  <option value="trasbordo">Trasbordo</option>
  <option value="devolucion">Devolución</option>
  <option value="fason">Fason</option>
  <option value="compras">Compras</option>
</select>

<div id="campo-seleccion" style="display: none;">
  <label for="seleccion">Selección:</label>
  <select name="fasonselec" class="select-motivo" id="seleccion">
    <option value="null"></option>
  </select>
</div>
<?php
} else if ($primera_fila['diferencia'] < 0) {
?>
  <label for="motivo">Seleccione el motivo por el cual la medicion es menor a la anterior medicion tomada:</label>
<select name="motivo" class="select-motivo" id="motivo">
  <option value="gvc">GVC</option>
    <option value="venta">Venta</option>
  <option value="trasbordo">Trasbordo</option>
  <option value="limpieza">Limpieza</option>
  <option value="fason">Fason</option>
</select>

<div id="campo-seleccion" style="display: none;">
  <label for="seleccion">Selección:</label>
  <select name="fasonselec" class="select-motivo" id="seleccion">
    <option value="null"></option>
  </select>
</div>


<?php
} else { // else if ($primera_fila['diferencia'] == "")
?>
  <label for="motivo">Seleccion el motivo de la medicion</label>
<select name="motivo" class="select-motivo" id="motivo">
  <option value="gvc">GVC</option>
  <option value="venta">Venta</option>
  <option value="limpieza">Limpieza</option>
  <option value="fason">Fason</option>
  <option value="produccion">Producción</option>
  <option value="trasbordo">Trasbordo</option>
  <option value="devolucion">Devolución</option>
  <option value="compras">Compras</option>
</select>

<div id="campo-seleccion" style="display: none;">
  <label for="seleccion">Selección:</label>
  <select name="fasonselec" class="select-motivo" id="seleccion">
    <option value="null"></option>
  </select>
</div>
<?php
}
?>


 <input class="submit button"  type="submit" value="Registrar motivo de la diferencia" name="submit">  <br> <br> <br> <br>
 </fieldset>

   <script src="../js/registrosubcampos.js"></script>

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


