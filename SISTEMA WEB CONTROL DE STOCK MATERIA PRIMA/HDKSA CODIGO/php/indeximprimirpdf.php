<?php mysqli_data_seek($query, 0);
    mysqli_data_seek($query2, 0);

 ?>



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







<div style="display: none;" >
<div id="contenido">
  <p >Control De tanques</p>
  <p class="lead" >Informacion Principal</p>


<table class="table table-responsive">
          <thead>
            <tr class="titulos">
              <th>Tanque</th>
              <th>Medicion</th>
              <th>Kilos</th>
            </tr>
          </thead>
           <tbody>
      <?php


      $totalstock=0;
        while($row=mysqli_fetch_array($query)){


       $tanque = $row['tanque'];
              $medicion = $row['medicion'];
             $sumadekilogramos=0;
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

             $resultado = $medicion * $multiplicador ;
              $totalstock= $resultado +  $totalstock;
      ?>



               <tr>
                 <td><?php echo $row['tanque'];?></td>
                  <td><?php echo $medicion;?></td>
                  <td><?php echo $medicion." x ".$multiplicador." = ".$resultado;?></td>
                </tr>  

      <?php
                        
                           }

          $sumahueso=0;
         while($row2=mysqli_fetch_array($query2)){     
         $hueso = $row2['hueso']*0.10 ;
         $sumahueso=$hueso + $sumahueso;


          }

          $totalstock=$totalstock + $sumahueso;             
      ?>
      </tbody>
</table>
              <h5 class="TotalesKilos">Kilos totales: <?php echo $totalstock;?></h5>


<style>
    .table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1rem;
    color: #212529;
    font-size: 0.9rem;
  }
  
  .table th, .table td {
    padding: 0.75rem;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
  }
  


  .table .titulos {
    background-color: #e9ecef;
  }
  

.TotalesKilos{
position: absolute;
  right: 400px;
}
</style>
</div>
</div>



