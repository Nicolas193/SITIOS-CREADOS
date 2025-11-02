<?php
  include("conexion.php");  #llama a la funcion donde se conecta con la base de datos
  $con=conectar();
  $sql="SELECT * FROM medicionesdetanques ORDER BY id DESC"; #trae la tabla de datos
  $query=mysqli_query ($con,$sql);
  $row=mysqli_fetch_array($query);


?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet"  type="text/css" href="../css/registro.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="shortcut icon" href="../imagenes/presentacion.ico" />
  <!-- bustrap es para mejor los estilos -->

        <script>
        function abrirCalculadora() {
            window.open("calculadora.html", "Calculadora", "width=300, height=400");
        }
    </script>
  <title>Formulario</title>
</head>
<body>

<nav id="menu">
    <div>
<svg xmlns="http://www.w3.org/2000/svg" width="40" height="30" fill="currentColor" class="bi bi-arrow-left-circle" viewBox="0 0 16 16" onclick="window.history.back()">
  <path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8zm15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-4.5-.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/>
</svg>
<svg xmlns="http://www.w3.org/2000/svg" width="40" height="30" fill="currentColor" class="bi bi-arrow-right-circle" viewBox="0 0 16 16"  onclick="window.history.forward()">Adelante">
  <path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8zm15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM4.5 7.5a.5.5 0 0 0 0 1h5.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5H4.5z"/>
</svg>
 </div>
    
    <a href="" ><b class="hdksa">HDK SA</b></a>
  <a href="informeregistro.php"><b>Informes</b></a>
</nav>

      <a href="#" onclick="window.open('../php/calculadora.html','Calculadora','width=350,height=450');" class="boton-calculadora">
  <img class="imagencalculadora" src="../imagenes/calculadora.png">
</a>
      <div class="container">
    <div class="wrapper">
      <ul class="steps">
        <li class="is-active">Tanque</li>
        <li>Medida</li>
      </ul>
      <form class="form-wrapper" action="insertar.php" method="POST" enctype="multipart/form-data">
        <fieldset class="section is-active">
          <h3>Seleccione el tipo de tanque</h3>
           <img src="../imagenes/tanque.jpg" class="imagtanque" id="img-tanque"></img>
 <div class="row cf">
            <div class="four col">
              <input type="radio" name="tanque" id="r1" value="1">
              <label for="r1">
                <h4>1</h4>
              </label>
            </div>
            <div class="four col">
              <input type="radio" name="tanque" id="r2" value="2"><label for="r2" >
                <h4>2</h4>
              </label>
            </div>
            <div class="four col">
               <input type="radio" name="tanque" id="r3" value="3"><label for="r3" >
                <h4>3</h4>
              </label>
            </div>
            <div class="four col">
              <input type="radio" name="tanque" id="r4" value="4"><label for="r4"  >
                <h4>4</h4>
              </label>
            </div>
             <div class="four col">
              <input type="radio" name="tanque" id="r5" value="A"><label for="r5"  >
                <h4>A</h4>
              </label>
            </div>
             <div class="four col">
              <input type="radio" name="tanque" id="r6" value="B"><label for="r6"  >
                <h4>B</h4>
              </label>
            </div>

            <div class="four col">
              <input type="radio" name="tanque" id="r7" value="C"><label for="r7"  >
                <h4>C</h4>
              </label>
            </div>

             <div class="four col">
              <input type="radio" name="tanque" id="r8" value="E"><label for="r8"  >
                <h4>E</h4>
              </label>
            </div>

            <div class="four col">
              <input type="radio" name="tanque" id="r9" value="11"><label for="r9"  >
                <h4>11</h4>
              </label>
            </div>

             <div class="four col">
              <input type="radio" name="tanque" id="r10" value="14"><label for="r10"  >
                <h4>14</h4>
              </label>
            </div>

           <div class="four col">
              <input type="radio" name="tanque" id="r11" value="15"><label for="r11"  >
                <h4>15</h4>
              </label>
            </div>
                       <div class="four col">
              <input type="radio" name="tanque" id="r24" value="16"><label for="r24"  >
                <h4>16</h4>
              </label>
            </div>
              <div class="four col">
                          <input type="radio" name="tanque" id="r12" value="18"><label for="r12"  >
                <h4>18</h4>
              </label>
            </div>
              <div class="four col">
                          <input type="radio" name="tanque" id="r13" value="19"><label for="r13"  >
                <h4>19</h4>
              </label>
            </div>

           <div class="four col">
              <input type="radio" name="tanque" id="r14" value="21"><label for="r14"  >
                <h4>21</h4>
              </label>
            </div>

              <div class="four col">
              <input type="radio" name="tanque" id="r15" value="22"><label for="r15"  >
                <h4>22</h4>
              </label>
            </div>

       <div class="four col">
              <input type="radio" name="tanque" id="r16" value="23"><label for="r16"  >
                <h4>23</h4>
              </label>
            </div>

          <div class="four col">
              <input type="radio" name="tanque" id="r17" value="24"><label for="r17"  >
                <h4>24</h4>
              </label>
            </div>
              <div class="four col">
              <input type="radio" name="tanque" id="r18" value="25"><label for="r18"  >
                <h4>25</h4>
              </label>
            </div>

              <div class="four col">
              <input type="radio" name="tanque" id="r19" value="26"><label for="r19"  >
                <h4>26</h4>
              </label>
            </div>
              <div class="four col">
                          <input type="radio" name="tanque" id="r25" value="30"><label for="r25">
                <h4>30</h4>
              </label>
            </div>
              <div class="four col">
                          <input type="radio" name="tanque" id="r20" value="31"><label for="r20"  >
                <h4>31</h4>
              </label>
            </div>
              <div class="four col">
                          <input type="radio" name="tanque" id="r21" value="32"><label for="r21"  >
                <h4>32</h4>
              </label>
            </div>
              <div class="four col">
                <input type="radio" name="tanque" id="r22" value="33"><label for="r22"  >
                <h4>33</h4>
              </label>
            </div>
              <div class="four col">
                <input type="radio" name="tanque" id="r23" value="34"><label for="r23"  >
                <h4>34</h4>
              </label>
            </div>
          <div class="button">Siguiente</div>
        </fieldset>
        <fieldset class="section">
          <h3>Ingrese la medicion del tanque</h3>

          <div class="row cf">
            <input type="number" id="price" name="medicion"  required class="form-control" required>
          </div>



          <h3>Fecha de medicion</h3>


          <input type="datetime-local" id="start_time" name="fecharegistro" class="calendario" required>
           <br><br><br> 


          <h3>Responsable de la medicion</h3>

          <input type="text" name="responsablemedicion"  placeholder="Responsable" class="reponsable" required><br><br>
          <div class="back-button">
  <span class="back-arrow">&#8592;</span> Volver
</div>

          <input class="submit button" type="submit" value="Siguiente" name="submit">


</fieldset>
        <fieldset class="section">
          <h3>ERROR EN LA CARGA DE DATOS</h3>
          <p>Porfavor verifique que todos los campos hallan sido completados correctamente</p><br><br><br>
         <div class="restablecerdatos"> <div class="button">Verificar</div></div>
        </fieldset>

      </form>
        </div>
  </div>

   <script src="../js/registro.js"></script>

</body>
</html>



