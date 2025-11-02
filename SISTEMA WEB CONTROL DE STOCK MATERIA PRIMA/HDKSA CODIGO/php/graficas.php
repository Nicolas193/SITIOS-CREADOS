<?php
include("conexion.php");
$con = conectar();
$con2 = conectar();
$fecha_desde = isset($_POST['fecha_desde']) ? $_POST['fecha_desde'] : null;
$hora_desde = isset($_POST['hora_desde']) ? $_POST['hora_desde'] : "00:00:00";
$fecha_hasta = isset($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : null;
$hora_hasta = isset($_POST['hora_hasta']) ? $_POST['hora_hasta'] : "23:59:59";


if (!empty($fecha_desde)) {
  $fecha_desde = date('Y-m-d H:i:s', strtotime("$fecha_desde $hora_desde"));
}
if (!empty($fecha_hasta)) {
  $fecha_hasta = date('Y-m-d H:i:s', strtotime("$fecha_hasta $hora_hasta"));

}


date_default_timezone_set('America/Argentina/Buenos_Aires');

// Obtener la fecha actual
$fecha_actual = date('Y-m-d');

// Obtener el primer día del mes actual
$primer_dia_mes_actual = date('Y-m-01', strtotime($fecha_actual));
// Obtener el último día del mes actual
$ultimo_dia_mes_actual = date('Y-m-t', strtotime($fecha_actual));
//este año
$fecha_inicio_anio = date('Y-01-01');
// Construir la consulta SQL
// 
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


$sql2 ="SELECT fecharegistro,cantidad,hueso FROM materiaprima";

$sql3="SELECT * FROM tanquereserva";
$where = array();



$sql .= " ORDER BY fecharegistro ASC";
$sql2 .=" ORDER BY fecharegistro ASC";
$sql3 .=" ORDER BY fecharegistro ASC";

// Ejecutar la consulta
$query = mysqli_query($con, $sql);
$query2 = mysqli_query($con, $sql2);
$query3 = mysqli_query($con, $sql3);

$row=mysqli_fetch_array($query);
$fechaAntigua =   $row['fecharegistro'];
mysqli_data_seek($query, 0);

 include("copiados.php"); 


      
            $fecha=0;
            $fecha2=0;
            $stocktotal=0;
            $stockventa=0;
            $stocklimpieza=0;
            $stockgvc=0;
            $stockcompras=0;
            $stockproduccion=0;
            $totaldiftn=0;
            $stockmp=0;
            $stockhueso=0;
            $stockalicorp=0;
            $stockinsugra=0;
            $stockunilever=0;
            $stockreserva=0;
            $stockdevolucion=0;

            $stockventaunilever=0;
            $stockventainsugra=0;
            $stockventaalicorp=0;

            $ventaunileverPorDia=array();
            $ventainsugraPorDia=array();
            $ventaalicorpPorDia=array();

            $reservaPorDia = array();
            $devolucionPorDia = array();
            $ventasPorDia = array();
            $limpiezaPorDia = array();
            $gvcPorDia = array();
            $comprasPorDia = array();
            $stockPorDia = array();
            $produccionPorDia = array();
            $alicorpPorDia = array();
            $insugraPorDia = array();
            $unileverPorDia = array();
            $mpPorDia = array();
            $huesoPorDia = array();

            $primer_dia = true; // Variable para verificar el primer día válido

              while($row = mysqli_fetch_array($query)) {
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

if(date('Y-m-d', strtotime($row['fecharegistro'])) == date('Y-m-d', strtotime($fechaAntigua))){
    
    $totaldiftn=$row['diferencia']*$multiplicador;

}else{ 

 $totaldiftn=($row['medicion']- $anterior['medicion'])*$multiplicador;

  }


 $resultado = $medicion * $multiplicador ;



  $fecha=$row['fecharegistro'];



  // solo quiero que me muestre el total por dia de cada resultado de devolucion 
    if(date('Y-m-d', strtotime($row['fecharegistro'])) == date('Y-m-d', strtotime($fecha)) || $fecha==0){ 
        // Sumar al total de devolucion en este día
        if ($row['motivo'] == "devolucion") {
            if (!array_key_exists($fecha, $devolucionPorDia)) {
                $devolucionPorDia[$fecha] = 0;
            }
            $devolucionPorDia[$fecha] += $totaldiftn;
        }
    } else {
        $fecha=$row['fecharegistro'];
        $stockdevolucion=0;
        $stockdevolucion= $totaldiftn + $stockdevolucion;
        // Sumar al total de devolucion en este día
        if ($row['motivo'] == "devolucion") {
            if (!array_key_exists($fecha, $devolucionPorDia)) {
                $devolucionPorDia[$fecha] = 0;
            }
            $devolucionPorDia[$fecha] += $totaldiftn;
        }
    }



// solo quiero que me muestre el total por dia de cada resultado de vente 
    if(date('Y-m-d', strtotime($row['fecharegistro'])) == date('Y-m-d', strtotime($fecha)) || $fecha==0){ 
        // Sumar al total de venta en este día
        if ($row['motivo'] == "venta") {
            if (!array_key_exists($fecha, $ventasPorDia)) {
                $ventasPorDia[$fecha] = 0;
            }
            $ventasPorDia[$fecha] += $totaldiftn;
        }
    } else {
        $fecha=$row['fecharegistro'];
        $stockventa=0;
        $stockventa= $totaldiftn + $stockventa;
        // Sumar al total de venta en este día
        if ($row['motivo'] == "venta") {
            if (!array_key_exists($fecha, $ventasPorDia)) {
                $ventasPorDia[$fecha] = 0;
            }
            $ventasPorDia[$fecha] += $totaldiftn;
        }
    }


//----------------------------------------limpieza
//
    if(date('Y-m-d', strtotime($row['fecharegistro'])) == date('Y-m-d', strtotime($fecha)) || $fecha==0){ 

 
        // Sumar al total de venta en este día
        if ($row['motivo'] == "limpieza") {
            if (!array_key_exists($fecha, $limpiezaPorDia)) {
                $limpiezaPorDia[$fecha] = 0;
            }
            $limpiezaPorDia[$fecha] += $totaldiftn;
        }

    } else {
        

        $fecha=$row['fecharegistro'];

        $stocklimpieza=0;
        $stocklimpieza= $totaldiftn + $stocklimpieza;

        // Sumar al total de venta en este día
        if ($row['motivo'] == "limpieza") {
            if (!array_key_exists($fecha, $limpiezaPorDia)) {
                $limpiezaPorDia[$fecha] = 0;
            }
            $limpiezaPorDia[$fecha] += $totaldiftn;
        }
    }



//-----------------------------------------------GVC

    if(date('Y-m-d', strtotime($row['fecharegistro'])) == date('Y-m-d', strtotime($fecha)) || $fecha==0){ 
        // Sumar al total de venta en este día
        if ($row['motivo'] == "gvc") {
            if (!array_key_exists($fecha, $gvcPorDia)) {
                $gvcPorDia[$fecha] = 0;
            }
            $gvcPorDia[$fecha] += $totaldiftn;
        }
    } else {
        $fecha=$row['fecharegistro'];
        $stockgvc=0;
        $stockgvc= $totaldiftn + $stockgvc;
        // Sumar al total de venta en este día
        if ($row['motivo'] == "gvc") {
            if (!array_key_exists($fecha, $gvcPorDia)) {
                $gvcPorDia[$fecha] = 0;
            }
            $gvcPorDia[$fecha] += $totaldiftn;
        }
    }


//--------------------------------------- compras 

    if(date('Y-m-d', strtotime($row['fecharegistro'])) == date('Y-m-d', strtotime($fecha)) || $fecha==0){ 
        // Sumar al total de venta en este día
        if ($row['motivo'] == "compras") {
            if (!array_key_exists($fecha, $comprasPorDia)) {
                $comprasPorDia[$fecha] = 0;
            }
            $comprasPorDia[$fecha] += $totaldiftn;
        }
    } else {
        $fecha=$row['fecharegistro'];
        $stockcompras=0;
        $stockcompras= $totaldiftn + $stockcompras;
        // Sumar al total de venta en este día
        if ($row['motivo'] == "compras") {
            if (!array_key_exists($fecha, $comprasPorDia)) {
                $comprasPorDia[$fecha] = 0;
            }
            $comprasPorDia[$fecha] += $totaldiftn;
        }
    }

//-----------------------------------------produccion

    if(date('Y-m-d', strtotime($row['fecharegistro'])) == date('Y-m-d', strtotime($fecha)) || $fecha==0){ 
        // Sumar al total de venta en este día
        if ($row['motivo'] == "produccion") {
            if (!array_key_exists($fecha, $produccionPorDia)) {
                $produccionPorDia[$fecha] = 0;
            }
            $produccionPorDia[$fecha] += $totaldiftn;
        }
    } else {
        $fecha=$row['fecharegistro'];
        $stockproduccion=0;
        $stockproduccion= $totaldiftn + $stockproduccion;
        // Sumar al total de venta en este día
        if ($row['motivo'] == "produccion") {
            if (!array_key_exists($fecha, $produccionPorDia)) {
                $produccionPorDia[$fecha] = 0;
            }
            $produccionPorDia[$fecha] += $totaldiftn;
        }
    }



//------venta productos-------------------------------------------------------


        if(date('Y-m-d', strtotime($row['fecharegistro'])) == date('Y-m-d', strtotime($fecha)) || $fecha==0){ 
        // Sumar al total de venta en este día
        if ($row['fasonselec'] == "venta unilever") {
            if (!array_key_exists($fecha, $ventaunileverPorDia)) {
                $ventaunileverPorDia[$fecha] = 0;
            }
            $ventaunileverPorDia[$fecha] += $totaldiftn;
        }
    } else {
        $fecha=$row['fecharegistro'];
        $stockventaunilever=0;
        $stockventaunilever= $totaldiftn + $stockventaunilever;
        // Sumar al total de venta en este día
        if ($row['fasonselec'] == "venta unilever") {
            if (!array_key_exists($fecha, $ventaunileverPorDia)) {
                $ventaunileverPorDia[$fecha] = 0;
            }
            $ventaunileverPorDia[$fecha] += $totaldiftn;
        }
    }

//--------------------------
            if(date('Y-m-d', strtotime($row['fecharegistro'])) == date('Y-m-d', strtotime($fecha)) || $fecha==0){ 
        // Sumar al total de venta en este día
        if ($row['fasonselec'] == "venta insugra") {
            if (!array_key_exists($fecha, $ventainsugraPorDia)) {
                $ventainsugraPorDia[$fecha] = 0;
            }
            $ventainsugraPorDia[$fecha] += $totaldiftn;
        }
    } else {
        $fecha=$row['fecharegistro'];
        $stockventainsugra=0;
        $stockventainsugra= $totaldiftn + $stockventainsugra;
        // Sumar al total de venta en este día
        if ($row['fasonselec'] == "venta unilever") {
            if (!array_key_exists($fecha, $ventainsugraPorDia)) {
                $ventainsugraPorDia[$fecha] = 0;
            }
            $ventainsugraPorDia[$fecha] += $totaldiftn;
        }
    }


//-------------------------------
            if(date('Y-m-d', strtotime($row['fecharegistro'])) == date('Y-m-d', strtotime($fecha)) || $fecha==0){ 
        // Sumar al total de venta en este día
        if ($row['fasonselec'] == "venta alicorp") {
            if (!array_key_exists($fecha, $ventaalicorpPorDia)) {
                $ventaalicorpPorDia[$fecha] = 0;
            }
            $ventaalicorpPorDia[$fecha] += $totaldiftn;
        }
    } else {
        $fecha=$row['fecharegistro'];
        $stockventaalicorp=0;
        $stockventaalicorp= $totaldiftn + $stockventaalicorp;
        // Sumar al total de venta en este día
        if ($row['fasonselec'] == "venta alicorp") {
            if (!array_key_exists($fecha, $ventaalicorpPorDia)) {
                $ventaalicorpPorDia[$fecha] = 0;
            }
            $ventaalicorpPorDia[$fecha] += $totaldiftn;
        }
    }

//------------------
//
//
//
//

    //-----------------------------------faso-------------------------------------------------------------
    

if (date('Y-m-d', strtotime($row['fecharegistro'])) == date('Y-m-d', strtotime($fechaAntigua))) {



        if(date('Y-m-d', strtotime($row['fecharegistro'])) == date('Y-m-d', strtotime($fecha)) || $fecha==0){ 
        // Sumar al total de venta en este día
        if ($row['fasonselec'] == "unilever") {
            if (!array_key_exists($fecha, $unileverPorDia)) {
                $unileverPorDia[$fecha] = 0;
            }
            $unileverPorDia[$fecha] += $resultado;
        }
    } else {
        $fecha=$row['fecharegistro'];
        $stockunilever=0;
        $stockunilever= $resultado + $stockunilever;
        // Sumar al total de venta en este día
        if ($row['fasonselec'] == "unilever") {
            if (!array_key_exists($fecha, $unileverPorDia)) {
                $unileverPorDia[$fecha] = 0;
            }
            $unileverPorDia[$fecha] += $resultado;
        }
    }

//------------

    if(date('Y-m-d', strtotime($row['fecharegistro'])) == date('Y-m-d', strtotime($fecha)) || $fecha==0){ 
        // Sumar al total de venta en este día
        if ($row['fasonselec'] == "alicorp") {
            if (!array_key_exists($fecha, $alicorpPorDia)) {
                $alicorpPorDia[$fecha] = 0;
            }
            $alicorpPorDia[$fecha] += $resultado;
        }
    } else {
        $fecha=$row['fecharegistro'];
        $stockalicorp=0;
        $stockalicorp= $resultado + $stockalicorp;
        // Sumar al total de venta en este día
        if ($row['fasonselec'] == "alicorp") {
            if (!array_key_exists($fecha, $alicorpPorDia)) {
                $alicorpPorDia[$fecha] = 0;
            }
            $alicorpPorDia[$fecha] += $resultado;
        }
    }


//------------------



    if(date('Y-m-d', strtotime($row['fecharegistro'])) == date('Y-m-d', strtotime($fecha)) || $fecha==0){ 
        // Sumar al total de venta en este día
        if ($row['fasonselec'] == "insugra") {
            if (!array_key_exists($fecha, $insugraPorDia)) {
                $insugraPorDia[$fecha] = 0;
            }
            $insugraPorDia[$fecha] += $resultado;
        }
    } else {
        $fecha=$row['fecharegistro'];
        $stockinsugra=0;
        $stockinsugra= $resultado + $stockinsugra;
        // Sumar al total de venta en este día
        if ($row['fasonselec'] == "insugra") {
            if (!array_key_exists($fecha, $insugraPorDia)) {
                $insugraPorDia[$fecha] = 0;
            }
            $insugraPorDia[$fecha] += $resultado;
        }
    }





  }else{



if(date('Y-m-d', strtotime($row['fecharegistro'])) == date('Y-m-d', strtotime($fecha)) || $fecha==0){ 
        // Sumar al total de venta en este día
        if ($row['fasonselec'] == "unilever") {
            if (!array_key_exists($fecha, $unileverPorDia)) {
                $unileverPorDia[$fecha] = 0;
            }
            $unileverPorDia[$fecha] += $totaldiftn;
        }
    } else {
        $fecha=$row['fecharegistro'];
        $stockunilever=0;
        $stockunilever= $totaldiftn + $stockunilever;
        // Sumar al total de venta en este día
        if ($row['fasonselec'] == "unilever") {
            if (!array_key_exists($fecha, $unileverPorDia)) {
                $unileverPorDia[$fecha] = 0;
            }
            $unileverPorDia[$fecha] += $totaldiftn;
        }
    }

//------------

    if(date('Y-m-d', strtotime($row['fecharegistro'])) == date('Y-m-d', strtotime($fecha)) || $fecha==0){ 
        // Sumar al total de venta en este día
        if ($row['fasonselec'] == "alicorp") {
            if (!array_key_exists($fecha, $alicorpPorDia)) {
                $alicorpPorDia[$fecha] = 0;
            }
            $alicorpPorDia[$fecha] += $totaldiftn;
        }
    } else {
        $fecha=$row['fecharegistro'];
        $stockalicorp=0;
        $stockalicorp= $totaldiftn + $stockalicorp;
        // Sumar al total de venta en este día
        if ($row['fasonselec'] == "alicorp") {
            if (!array_key_exists($fecha, $alicorpPorDia)) {
                $alicorpPorDia[$fecha] = 0;
            }
            $alicorpPorDia[$fecha] += $totaldiftn;
        }
    }


//------------------



    if(date('Y-m-d', strtotime($row['fecharegistro'])) == date('Y-m-d', strtotime($fecha)) || $fecha==0){ 
        // Sumar al total de venta en este día
        if ($row['fasonselec'] == "insugra") {
            if (!array_key_exists($fecha, $insugraPorDia)) {
                $insugraPorDia[$fecha] = 0;
            }
            $insugraPorDia[$fecha] += $totaldiftn;
        }
    } else {
        $fecha=$row['fecharegistro'];
        $stockinsugra=0;
        $stockinsugra= $totaldiftn + $stockinsugra;
        // Sumar al total de venta en este día
        if ($row['fasonselec'] == "insugra") {
            if (!array_key_exists($fecha, $insugraPorDia)) {
                $insugraPorDia[$fecha] = 0;
            }
            $insugraPorDia[$fecha] += $totaldiftn;
        }
    }





  }




//-------------------------------------total-------------------------------------------------

if (date('Y-m-d', strtotime($row['fecharegistro'])) == date('Y-m-d', strtotime($fechaAntigua))) {
  // Si es el día más antiguo, aplicar el segundo if

//----------------------------------total 

    if(date('Y-m-d', strtotime($row['fecharegistro'])) == date('Y-m-d', strtotime($fecha)) || $fecha==0){ 
        // Sumar al total de venta en este día
    
         if ($row['motivo'] == "produccion" || $row['motivo'] == "gvc" ||  $row['motivo'] == "venta" ||  $row['motivo'] == "limpieza" || $row['motivo'] == "compras" || $row['motivo'] == "devolucion") {
     
            if (!array_key_exists($fecha, $stockPorDia)) {
                $stockPorDia[$fecha] = 0;
            }
            $stockPorDia[$fecha] += $resultado;

     }
        
    } else {
        $fecha=$row['fecharegistro'];
        $stocktotal=0;
        $stocktotal= $resultado + $stocktotal;
        // Sumar al total de venta en este día
 if ($row['motivo'] == "produccion" || $row['motivo'] == "gvc" ||  $row['motivo'] == "venta" ||  $row['motivo'] == "limpieza" || $row['motivo'] == "compras" || $row['motivo'] == "devolucion"){
            if (!array_key_exists($fecha, $stockPorDia)) {
                $stockPorDia[$fecha] = 0;
            }
            $stockPorDia[$fecha] += $resultado; 

            }  
    }


} else {
  // Si no es el día más antiguo, aplicar el primer if

//------------------------------------------------------------------------Resultado stock

    if(date('Y-m-d', strtotime($row['fecharegistro'])) == date('Y-m-d', strtotime($fecha)) || $fecha==0){ 
        // Sumar al total de venta en este día
    
         if ($row['motivo'] == "produccion" || $row['motivo'] == "gvc" ||  $row['motivo'] == "venta" ||  $row['motivo'] == "limpieza" || $row['motivo'] == "compras" || $row['motivo'] == "devolucion") {
     
            if (!array_key_exists($fecha, $stockPorDia)) {
                $stockPorDia[$fecha] = 0;
            }
            $stockPorDia[$fecha] += $totaldiftn;

     }
        
    } else {
        $fecha=$row['fecharegistro'];
        $stocktotal=0;
        $stocktotal= $totaldiftn + $stocktotal;
        // Sumar al total de venta en este día
 if ($row['motivo'] == "produccion" || $row['motivo'] == "gvc" ||  $row['motivo'] == "venta" ||  $row['motivo'] == "limpieza" || $row['motivo'] == "compras" || $row['motivo'] == "devolucion"){
            if (!array_key_exists($fecha, $stockPorDia)) {
                $stockPorDia[$fecha] = 0;
            }
            $stockPorDia[$fecha] += $totaldiftn; 

            }  
    }

}
//--------------------------------------------




 }

         
              
    

//------------------------------ segunda consulta a la base de materiaprima

while($row2 = mysqli_fetch_array($query2)) {



             $totaldiftn = $row2['cantidad'];

                $fecha=$row2['fecharegistro'];


    if(date('Y-m-d', strtotime($row2['fecharegistro'])) == date('Y-m-d', strtotime($fecha)) || $fecha==0){ 
        // Sumar al total de venta en este día
     
            if (!array_key_exists($fecha, $mpPorDia)) {
                $mpPorDia[$fecha] = 0;
            }
            $mpPorDia[$fecha] += $totaldiftn;
        
    } else {
        $fecha=$row2['fecharegistro'];
        $stockmp=0;
        $stockmp= $totaldiftn + $stockmp;
        // Sumar al total de venta en este día
            if (!array_key_exists($fecha, $mpPorDia)) {
                $mpPorDia[$fecha] = 0;
            }
            $mpPorDia[$fecha] += $totaldiftn;   
    }






             $totaldiftn = $row2['hueso'];

    if(date('Y-m-d', strtotime($row2['fecharegistro'])) == date('Y-m-d', strtotime($fecha)) || $fecha==0){ 
        // Sumar al total de venta en este día
     
            if (!array_key_exists($fecha, $huesoPorDia)) {
                $huesoPorDia[$fecha] = 0;
            }
            $huesoPorDia[$fecha] += $totaldiftn;
        
    } else {
        $fecha=$row2['fecharegistro'];
        $stockhueso=0;
        $stockhueso= $totaldiftn + $stockhueso;
        // Sumar al total de venta en este día
            if (!array_key_exists($fecha, $huesoPorDia)) {
                $mpPorDia[$fecha] = 0;
            }
            $mpPorDia[$fecha] += $totaldiftn;   
    }




 

             


     }

//-----------------------------------------------------------

while($row3 = mysqli_fetch_array($query3)) {



             $totaldiftn = $row3['reserva'];

                $fecha=$row3['fecharegistro'];


    if(date('Y-m-d', strtotime($row3['fecharegistro'])) == date('Y-m-d', strtotime($fecha)) || $fecha==0){ 
        // Sumar al total de venta en este día
     
            if (!array_key_exists($fecha, $reservaPorDia)) {
                $reservaPorDia[$fecha] = 0;
            }
            $reservaPorDia[$fecha] += $totaldiftn;
        
    } else {
        $fecha=$row3['fecharegistro'];
        $stockreserva=0;
        $stockreserva= $totaldiftn + $stockreserva;
        // Sumar al total de venta en este día
            if (!array_key_exists($fecha, $reservaPorDia)) {
                $reservaPorDia[$fecha] = 0;
            }
            $reservaPorDia[$fecha] += $totaldiftn;   
    }



     }
 
           


//----------------------------------------------------------------------------------------------------
// Inicializar el array de totales por día devolucion
$totalesPorDia = array();

// Recorrer los totales de devolucion por día y almacenarlos en el array
foreach ($devolucionPorDia as $fecha => $total) {
    $fechaFormateada = date('Y-m-d', strtotime($fecha));
    if (!isset($totalesPorDia[$fechaFormateada])) {
        $totalesPorDia[$fechaFormateada] = array(
            'devolucion' => 0,
            'venta' => 0,
            'limpieza' => 0,
            'gvc' => 0,
            'compras' => 0,
            'reserva' => 0,
            'stock' => 0,
            'produccion' => 0,
            'alicorp' => 0,
            'insugra' => 0,
            'unilever' => 0,
            'ventaunilever' => 0,
            'ventainsugra' => 0,
            'ventaalicorp' => 0,
            'mp' => 0,
            'hueso' => 0
        );
    }
    $totalesPorDia[$fechaFormateada]['devolucion'] += $total;
}



// Recorrer los totales de venta por día y almacenarlos en el array
foreach ($ventasPorDia as $fecha => $total) {
    $fechaFormateada = date('Y-m-d', strtotime($fecha));
    if (!isset($totalesPorDia[$fechaFormateada])) {
        $totalesPorDia[$fechaFormateada] = array(
            'devolucion' => 0,
            'venta' => 0,
            'limpieza' => 0,
            'gvc' => 0,
            'compras' => 0,
            'reserva' => 0,
            'stock' => 0,
            'produccion' => 0,
            'alicorp' => 0,
            'insugra' => 0,
            'unilever' => 0,
            'ventaunilever' => 0,
            'ventainsugra' => 0,
            'ventaalicorp' => 0,
            'mp' => 0,
            'hueso' => 0
        );
    }
    $totalesPorDia[$fechaFormateada]['venta'] += $total;
}

// Recorrer los totales de limpieza por día y almacenarlos en el array
foreach ($limpiezaPorDia as $fecha => $total) {
    $fechaFormateada = date('Y-m-d', strtotime($fecha));
    if (!isset($totalesPorDia[$fechaFormateada])) {
        $totalesPorDia[$fechaFormateada] = array(
            'devolucion' => 0,
            'venta' => 0,
            'limpieza' => 0,
            'gvc' => 0,
            'compras' => 0,
            'reserva' => 0,
            'stock' => 0,
            'produccion' => 0,
            'alicorp' => 0,
            'insugra' => 0,
            'unilever' => 0,
            'ventaunilever' => 0,
            'ventainsugra' => 0,
            'ventaalicorp' => 0,
            'mp' => 0,
            'hueso' => 0
        );
    }
    $totalesPorDia[$fechaFormateada]['limpieza'] += $total;
}

// Recorrer los totales de gvc por día y almacenarlos en el array
foreach ($gvcPorDia as $fecha => $total) {
    $fechaFormateada = date('Y-m-d', strtotime($fecha));
    if (!isset($totalesPorDia[$fechaFormateada])) {
        $totalesPorDia[$fechaFormateada] = array(
            'devolucion' => 0,
            'venta' => 0,
            'limpieza' => 0,
            'gvc' => 0,
            'compras' => 0,
            'reserva' => 0,
            'stock' => 0,
            'produccion' => 0,
            'alicorp' => 0,
            'insugra' => 0,
            'unilever' => 0,
            'ventaunilever' => 0,
            'ventainsugra' => 0,
            'ventaalicorp' => 0,
            'mp' => 0,
            'hueso' => 0
        );
    }
    $totalesPorDia[$fechaFormateada]['gvc'] += $total;
}

// Recorrer los totales de gvc por día y almacenarlos en el array
foreach ($comprasPorDia as $fecha => $total) {
    $fechaFormateada = date('Y-m-d', strtotime($fecha));
    if (!isset($totalesPorDia[$fechaFormateada])) {
        $totalesPorDia[$fechaFormateada] = array(
            'devolucion' => 0,
            'venta' => 0,
            'limpieza' => 0,
            'gvc' => 0,
            'compras' => 0,
            'reserva' => 0,
            'stock' => 0,
            'produccion' => 0,
            'alicorp' => 0,
            'insugra' => 0,
            'unilever' => 0,
            'ventaunilever' => 0,
            'ventainsugra' => 0,
            'ventaalicorp' => 0,
            'mp' => 0,
            'hueso' => 0
        );
    }
    $totalesPorDia[$fechaFormateada]['compras'] += $total;
}


foreach ($reservaPorDia as $fecha => $total) {
    $fechaFormateada = date('Y-m-d', strtotime($fecha));
    if (!isset($totalesPorDia[$fechaFormateada])) {
        $totalesPorDia[$fechaFormateada] = array(
            'devolucion' => 0,
            'venta' => 0,
            'limpieza' => 0,
            'gvc' => 0,
            'compras' => 0,
            'reserva' => 0,
            'stock' => 0,
            'produccion' => 0,
            'alicorp' => 0,
            'insugra' => 0,
            'unilever' => 0,
            'ventaunilever' => 0,
            'ventainsugra' => 0,
            'ventaalicorp' => 0,
            'mp' => 0,
            'hueso' => 0
        );
    }
    $totalesPorDia[$fechaFormateada]['reserva'] += $total;
}





foreach ($produccionPorDia as $fecha => $total) {
    $fechaFormateada = date('Y-m-d', strtotime($fecha));
    if (!isset($totalesPorDia[$fechaFormateada])) {
        $totalesPorDia[$fechaFormateada] = array(
            'devolucion' => 0,
            'venta' => 0,
            'limpieza' => 0,
            'gvc' => 0,
            'compras' => 0,
            'reserva' => 0,
            'stock' => 0,
            'produccion' => 0,
            'alicorp' => 0,
            'insugra' => 0,
            'unilever' => 0,
            'ventaunilever' => 0,
            'ventainsugra' => 0,
            'ventaalicorp' => 0,
            'mp' => 0,
            'hueso' => 0
        );
    }
    $totalesPorDia[$fechaFormateada]['produccion'] += $total;
}


//---------------------------------------------------------------------------------------------fason

foreach ($alicorpPorDia as $fecha => $total) {
    $fechaFormateada = date('Y-m-d', strtotime($fecha));
    if (!isset($totalesPorDia[$fechaFormateada])) {
        $totalesPorDia[$fechaFormateada] = array(
            'devolucion' => 0,
            'venta' => 0,
            'limpieza' => 0,
            'gvc' => 0,
            'compras' => 0,
            'reserva' => 0,
            'stock' => 0,
            'produccion' => 0,
            'alicorp' => 0,
            'insugra' => 0,
            'unilever' => 0,
            'ventaunilever' => 0,
            'ventainsugra' => 0,
            'ventaalicorp' => 0,
            'mp' => 0,
            'hueso' => 0
        );
    }
    $totalesPorDia[$fechaFormateada]['alicorp'] += $total;
}

foreach ($insugraPorDia as $fecha => $total) {
    $fechaFormateada = date('Y-m-d', strtotime($fecha));
    if (!isset($totalesPorDia[$fechaFormateada])) {
        $totalesPorDia[$fechaFormateada] = array(
            'devolucion' => 0,
            'venta' => 0,
            'limpieza' => 0,
            'gvc' => 0,
            'compras' => 0,
            'reserva' => 0,
            'stock' => 0,
            'produccion' => 0,
            'alicorp' => 0,
            'insugra' => 0,
            'unilever' => 0,
            'ventaunilever' => 0,
            'ventainsugra' => 0,
            'ventaalicorp' => 0,
            'mp' => 0,
            'hueso' => 0
        );
    }
    $totalesPorDia[$fechaFormateada]['insugra'] += $total;
}



foreach ($unileverPorDia as $fecha => $total) {
    $fechaFormateada = date('Y-m-d', strtotime($fecha));
    if (!isset($totalesPorDia[$fechaFormateada])) {
        $totalesPorDia[$fechaFormateada] = array(
            'devolucion' => 0,
            'venta' => 0,
            'limpieza' => 0,
            'gvc' => 0,
            'compras' => 0,
            'reserva' => 0,
            'stock' => 0,
            'produccion' => 0,
            'alicorp' => 0,
            'insugra' => 0,
            'unilever' => 0,
            'ventaunilever' => 0,
            'ventainsugra' => 0,
            'ventaalicorp' => 0,
            'mp' => 0,
            'hueso' => 0
        );
    }
    $totalesPorDia[$fechaFormateada]['unilever'] += $total;
}


//-------------------------------------------------------ventas tipos

foreach ($ventaunileverPorDia as $fecha => $total) {
    $fechaFormateada = date('Y-m-d', strtotime($fecha));
    if (!isset($totalesPorDia[$fechaFormateada])) {
        $totalesPorDia[$fechaFormateada] = array(
            'devolucion' => 0,
            'venta' => 0,
            'limpieza' => 0,
            'gvc' => 0,
            'compras' => 0,
            'reserva' => 0,
            'stock' => 0,
            'produccion' => 0,
            'alicorp' => 0,
            'insugra' => 0,
            'unilever' => 0,
            'ventaunilever' => 0,
            'ventainsugra' => 0,
            'ventaalicorp' => 0,
            'mp' => 0,
            'hueso' => 0
        );
    }
    $totalesPorDia[$fechaFormateada]['ventaunilever'] += $total;
}
//---

foreach ($ventainsugraPorDia as $fecha => $total) {
    $fechaFormateada = date('Y-m-d', strtotime($fecha));
    if (!isset($totalesPorDia[$fechaFormateada])) {
        $totalesPorDia[$fechaFormateada] = array(
            'devolucion' => 0,
            'venta' => 0,
            'limpieza' => 0,
            'gvc' => 0,
            'compras' => 0,
            'reserva' => 0,
            'stock' => 0,
            'produccion' => 0,
            'alicorp' => 0,
            'insugra' => 0,
            'unilever' => 0,
            'ventaunilever' => 0,
            'ventainsugra' => 0,
            'ventaalicorp' => 0,
            'mp' => 0,
            'hueso' => 0
        );
    }
    $totalesPorDia[$fechaFormateada]['ventainsugra'] += $total;
}

//----

foreach ($ventaalicorpPorDia as $fecha => $total) {
    $fechaFormateada = date('Y-m-d', strtotime($fecha));
    if (!isset($totalesPorDia[$fechaFormateada])) {
        $totalesPorDia[$fechaFormateada] = array(
            'devolucion' => 0,
            'venta' => 0,
            'limpieza' => 0,
            'gvc' => 0,
            'compras' => 0,
            'reserva' => 0,
            'stock' => 0,
            'produccion' => 0,
            'alicorp' => 0,
            'insugra' => 0,
            'unilever' => 0,
            'ventaunilever' => 0,
            'ventainsugra' => 0,
            'ventaalicorp' => 0,
            'mp' => 0,
            'hueso' => 0
        );
    }
    $totalesPorDia[$fechaFormateada]['ventaalicorp'] += $total;
}



//-------------------------------------------------------------- materiaprima

foreach ($mpPorDia as $fecha => $total) {
    $fechaFormateada = date('Y-m-d', strtotime($fecha));
    if (!isset($totalesPorDia[$fechaFormateada])) {
        $totalesPorDia[$fechaFormateada] = array(
            'devolucion' => 0,
            'venta' => 0,
            'limpieza' => 0,
            'gvc' => 0,
            'compras' => 0,
            'reserva' => 0,
            'stock' => 0,
            'produccion' => 0,
            'alicorp' => 0,
            'insugra' => 0,
            'unilever' => 0,
            'ventaunilever' => 0,
            'ventainsugra' => 0,
            'ventaalicorp' => 0,
            'mp' => 0,
            'hueso' => 0
        );
    }
    $totalesPorDia[$fechaFormateada]['mp'] += $total;
}


//------------------------------------------------- hueso
foreach ($huesoPorDia as $fecha => $total) {
    $fechaFormateada = date('Y-m-d', strtotime($fecha));
    if (!isset($totalesPorDia[$fechaFormateada])) {
        $totalesPorDia[$fechaFormateada] = array(
            'devolucion' => 0,
            'venta' => 0,
            'limpieza' => 0,
            'gvc' => 0,
            'compras' => 0,
            'reserva' => 0,
            'stock' => 0,
            'produccion' => 0,
            'alicorp' => 0,
            'insugra' => 0,
            'unilever' => 0,
            'ventaunilever' => 0,
            'ventainsugra' => 0,
            'ventaalicorp' => 0,
            'mp' => 0,
            'hueso' => 0
        );
    }
    $totalesPorDia[$fechaFormateada]['hueso'] += $total;
}


//-------------------------------mes

$sumadevolucionmes=0;
$sumaventames=0;
$sumalimpiezames=0;
$sumagvcmes=0;
$sumacomprasmes=0;
$sumastockmes=0;
$sumaproduccionmes=0;
$sumaalicorpmes=0;
$sumainsugrames=0;
$sumaunilevermes=0;
$sumaventaunilevermes=0;
$sumaventainsugrames=0;
$sumaventaalicorpmes=0;
$sumampmes=0;
$sumahuesomes=0;

     foreach ($totalesPorDia as $fecha => $totales): 



if (strtotime($fecha) >= strtotime($primer_dia_mes_actual) && strtotime($fecha) <= strtotime($ultimo_dia_mes_actual)){ 

                        
                        $sumaventames=$sumaventames + $totales['venta'];
                        $sumalimpiezames=$sumalimpiezames + $totales['limpieza']; 
                        $sumagvcmes=$sumagvcmes + $totales['gvc'];
                        $sumacomprasmes=$sumacomprasmes + $totales['compras'];
                        $sumastockmes=$sumastockmes;
                        $sumaproduccionmes=$sumaproduccionmes + $totales['produccion'];
                        $sumaalicorpmes=$sumaalicorpmes + $totales['alicorp'];
                        $sumainsugrames=$sumainsugrames + $totales['insugra'];
                        $sumaunilevermes=$sumaunilevermes + $totales['unilever'];
                        $sumaventaunilevermes=$sumaventaunilevermes + $totales['ventaunilever'];
                        $sumaventainsugrames=$sumaventainsugrames + $totales['ventainsugra'];
                        $sumaventaalicorpmes=$sumaventaalicorpmes + $totales['ventaalicorp'];
                        $sumampmes=$sumampmes + $totales['mp'];
                        $sumahuesomes=$sumahuesomes + ($totales['hueso']);
                        $sumadevolucionmes= $sumadevolucionmes + $totales['devolucion'];
}
                   
      endforeach; 



//---------------------------------------------------------------------------------Dia
$sumadevoluciondia=0;
$sumaventadia=0;
$sumalimpiezadia=0;
$sumagvcdia=0;
$sumacomprasdia=0;
$sumastockdia=0;
$sumaproducciondia=0;
$sumaalicorpdia=0;
$sumainsugradia=0;
$sumaunileverdia=0;
$sumaventaunileverdia=0;
$sumaventainsugradia=0;
$sumaventaalicorpdia=0;
$sumampdia=0;
$sumahuesodia=0;

     foreach ($totalesPorDia as $fecha => $totales): 



if (strtotime($fecha) >= strtotime($fecha_actual) && strtotime($fecha) <= strtotime($fecha_actual)){ 

                        
                        $sumaventadia=$sumaventadia + $totales['venta'];
                        $sumalimpiezadia=$sumalimpiezadia + $totales['limpieza']; 
                        $sumagvcdia=$sumagvcdia + $totales['gvc'];
                        $sumacomprasdia=$sumacomprasdia + $totales['compras'];
                        $sumastockdia=$sumastockdia;
                        $sumaproducciondia=$sumaproducciondia + $totales['produccion'];
                        $sumaalicorpdia=$sumaalicorpdia + $totales['alicorp'];
                        $sumainsugradia=$sumainsugradia + $totales['insugra'];
                        $sumaunileverdia=$sumaunileverdia + $totales['unilever'];
                        $sumaventaunileverdia=$sumaventaunileverdia + $totales['ventaunilever'];
                        $sumaventainsugradia=$sumaventainsugradia + $totales['ventainsugra'];
                        $sumaventaalicorpdia=$sumaventaalicorpdia + $totales['ventaalicorp'];
                        $sumampdia=$sumampdia + $totales['mp'];
                        $sumahuesodia=$sumahuesodia + ($totales['hueso']);
                        $sumadevoluciondia= $sumadevoluciondia + $totales['devolucion'];
}
                   
      endforeach; 



//---------------------------------------------------------------------------------año
$sumadevolucionanio=0;
$sumaventaanio=0;
$sumalimpiezaanio=0;
$sumagvcanio=0;
$sumacomprasanio=0;
$sumastockanio=0;
$sumaproduccionanio=0;
$sumaalicorpanio=0;
$sumainsugraanio=0;
$sumaunileveranio=0;
$sumaventaunileveranio=0;
$sumaventainsugraanio=0;
$sumaventaalicorpanio=0;
$sumampanio=0;
$sumahuesoanio=0;

     foreach ($totalesPorDia as $fecha => $totales): 



if (strtotime($fecha) >= strtotime($fecha_inicio_anio) && strtotime($fecha) <= strtotime($fecha_actual)){ 

                        
                        $sumaventaanio=$sumaventaanio + $totales['venta'];
                        $sumalimpiezaanio=$sumalimpiezaanio + $totales['limpieza']; 
                        $sumagvcanio=$sumagvcanio + $totales['gvc'];
                        $sumacomprasanio=$sumacomprasanio + $totales['compras'];
                        $sumastockanio=$sumastockanio;
                        $sumaproduccionanio=$sumaproduccionanio + $totales['produccion'];
                        $sumaalicorpanio=$sumaalicorpanio + $totales['alicorp'];
                        $sumainsugraanio=$sumainsugraanio + $totales['insugra'];
                        $sumaunileveranio=$sumaunileveranio + $totales['unilever'];
                        $sumaventaunileveranio=$sumaventaunileveranio + $totales['ventaunilever'];
                        $sumaventainsugraanio=$sumaventainsugraanio + $totales['ventainsugra'];
                        $sumaventaalicorpanio=$sumaventaalicorpanio + $totales['ventaalicorp'];
                        $sumampanio=$sumampanio + $totales['mp'];
                        $sumahuesoanio=$sumahuesoanio + ($totales['hueso']);
                        $sumadevolucionanio= $sumadevolucionanio + $totales['devolucion'];
}
                   
      endforeach; 


//-------------------por opciones

$sumadevolucionops=0;
$sumaventaops=0;
$sumalimpiezaops=0;
$sumagvcops=0;
$sumacomprasops=0;
$sumastockops=0;
$sumaproduccionops=0;
$sumaalicorpops=0;
$sumainsugraops=0;
$sumaunileverops=0;
$sumaventaunileverops=0;
$sumaventainsugraops=0;
$sumaventaalicorpops=0;
$sumampops=0;
$sumahuesoops=0;

     foreach ($totalesPorDia as $fecha => $totales): 



 if(strtotime($fecha) >= strtotime($fecha_desde) && strtotime($fecha) < strtotime($fecha_hasta) || $fecha_hasta==""){ 

                        
                        $sumaventaops=$sumaventaops + $totales['venta'];
                        $sumalimpiezaops=$sumalimpiezaops + $totales['limpieza']; 
                        $sumagvcops=$sumagvcops + $totales['gvc'];
                        $sumacomprasops=$sumacomprasops + $totales['compras'];
                        $sumastockops=$sumastockops;
                        $sumaproduccionops=$sumaproduccionops + $totales['produccion'];
                        $sumaalicorpops=$sumaalicorpops + $totales['alicorp'];
                        $sumainsugraops=$sumainsugraops + $totales['insugra'];
                        $sumaunileverops=$sumaunileverops + $totales['unilever'];
                        $sumaventaunileverops=$sumaventaunileverops + $totales['ventaunilever'];
                        $sumaventainsugraops=$sumaventainsugraops + $totales['ventainsugra'];
                        $sumaventaalicorpops=$sumaventaalicorpops + $totales['ventaalicorp'];
                        $sumampops=$sumampops + $totales['mp'];
                        $sumahuesoops=$sumahuesoops + ($totales['hueso']);
                        $sumadevolucionops= $sumadevolucionops + $totales['devolucion'];
}
                   
      endforeach; 



// Ordenar el array por fecha
ksort($totalesPorDia);
$totalesPorDia = array_reverse($totalesPorDia, true);

foreach ($stockPorDia as $fecha => $total) {
    $fechaFormateada = date('Y-m-d', strtotime($fecha));
    if (!isset($totalesPorDia[$fechaFormateada])) {
        $totalesPorDia[$fechaFormateada] = array(
            'devolucion' => 0,
            'venta' => 0,
            'limpieza' => 0,
            'gvc' => 0,
            'compras' => 0,
            'reserva' => 0,
            'stock' => 0,
            'produccion' => 0,
            'alicorp' => 0,
            'insugra' => 0,
            'unilever' => 0,
            'ventaunilever' => 0,
            'ventainsugra' => 0,
            'ventaalicorp' => 0,
            'mp' => 0,
            'hueso' => 0
        );
    }
    $totalesPorDia[$fechaFormateada]['stock'] += $total;
}

$stockPosterior = 0; // valor inicial del stock posterior

$stockTotalPorDia = array(); // crear nuevo array para guardar stocktotal

foreach (array_reverse($totalesPorDia, true) as $fecha => $totales) {
   $stockActual = $totales['stock'] + $stockPosterior; // sumar el valor del stock actual al stock anterior
   $stockTotal = $stockActual+($totales['hueso']*0.10)+($totales['reserva']); // inicialmente el stock total es igual al stock actual
   $stockTotalPorDia[$fecha] = $stockTotal; // guardar el stock total en el nuevo array
   $stockPosterior = $stockTotal; // actualizar el valor del stock posterior con el total actual
}



  ///--------------------------------- venta productos


$stockPosterior = 0; // valor inicial del stock posterior

$unileverTotalPorDia = array(); // crear nuevo array para guardar stocktotal

foreach (array_reverse($totalesPorDia, true) as $fecha => $totales) {
   $stockActual = $totales['unilever'] + $stockPosterior; // sumar el valor del stock actual al stock anterior
   $stockTotal = $stockActual+($totales['ventaunilever']*-1); // inicialmente el stock total es igual al stock actual
   $unileverTotalPorDia[$fecha] = $stockTotal; // guardar el stock total en el nuevo array
   $stockPosterior = $stockTotal; // actualizar el valor del stock posterior con el total actual
}

$stockPosterior = 0; // valor inicial del stock posterior

$insugraTotalPorDia = array(); // crear nuevo array para guardar stocktotal

foreach (array_reverse($totalesPorDia, true) as $fecha => $totales) {
   $stockActual = $totales['insugra'] + $stockPosterior; // sumar el valor del stock actual al stock anterior
   $stockTotal = $stockActual+($totales['ventainsugra']*-1); // inicialmente el stock total es igual al stock actual
   $insugraTotalPorDia[$fecha] = $stockTotal; // guardar el stock total en el nuevo array
   $stockPosterior = $stockTotal; // actualizar el valor del stock posterior con el total actual
}

 

$stockPosterior = 0; // valor inicial del stock posterior

$alicorpTotalPorDia = array(); // crear nuevo array para guardar stocktotal

foreach (array_reverse($totalesPorDia, true) as $fecha => $totales) {
   $stockActual = $totales['alicorp'] + $stockPosterior; // sumar el valor del stock actual al stock anterior
   $stockTotal = $stockActual+($totales['ventaalicorp']*-1); // inicialmente el stock total es igual al stock actual
   $alicorpTotalPorDia[$fecha] = $stockTotal; // guardar el stock total en el nuevo array
   $stockPosterior = $stockTotal; // actualizar el valor del stock posterior con el total actual
}





if ($sumampmes != 0) {
     $rendimiento_diario = (($sumahuesomes * 0.10) + $sumaproduccionmes) / ($sumampmes / 100);
} else {
  $rendimiento_diario = 0; // O cualquier otro valor que quieras asignar en este caso
}

$data = array(
    array('Valor', 'Cantidad', array('role' => 'style')),
    array('PROD X HS HDK', $sumahuesomes*0.10, '#3366cc'),
    array('PROD X S/RAMA (Tanques) HDK', $sumaproduccionmes, '#dc3912'),
    array('INGRESO MP',  $sumampmes, '#ff9900'),
    array('Rendimiento Diario (%)', $rendimiento_diario, '#109618'),
    array('Devolucion', $sumadevolucionmes, '#990099'),
    array('VENTA', $sumaventames*(-1), '#0099c6'),
    array('LIMPIEZA',  $sumalimpiezames*(-1), '#dd4477'),
    array('ENTREGADO GVC', $sumagvcmes*(-1), '#66aa00'),
    array('COMPRAS', $sumacomprasmes, '#b82e2e')
);




if ($sumampdia != 0) {
     $rendimiento_diario = (($sumahuesodia * 0.10) + $sumaproducciondia) / ($sumampdia / 100);
} else {
  $rendimiento_diario = 0; // O cualquier otro valor que quieras asignar en este caso
}


// Agregamos el elemento al array

$datadia = array(
    array('Valor', 'Cantidad', array('role' => 'style')),
    array('PROD X HS HDK', $sumahuesodia*0.10, '#3366cc'),
    array('PROD X S/RAMA (Tanques) HDK', $sumaproducciondia, '#dc3912'),
    array('INGRESO MP',  $sumampdia, '#ff9900'),
    array('Rendimiento Diario (%)', $rendimiento_diario,  '#109618'),
    array('Devolucion', $sumadevoluciondia, '#990099'),
    array('VENTA', $sumaventadia*(-1), '#0099c6'),
    array('LIMPIEZA',  $sumalimpiezadia*(-1), '#dd4477'),
    array('ENTREGADO GVC', $sumagvcdia*(-1), '#66aa00'),
    array('COMPRAS', $sumacomprasdia, '#b82e2e')
);




if ($sumampanio != 0) {
     $rendimiento_diario = (($sumahuesoanio * 0.10) + $sumaproduccionanio) / ($sumampanio / 100);
} else {
  $rendimiento_diario = 0; // O cualquier otro valor que quieras asignar en este caso
}


// Agregamos el elemento al array

$dataanio = array(
    array('Valor', 'Cantidad', array('role' => 'style')),
    array('PROD X HS HDK', $sumahuesoanio*0.10, '#3366cc'),
    array('PROD X S/RAMA (Tanques) HDK', $sumaproduccionanio, '#dc3912'),
    array('INGRESO MP',  $sumampanio, '#ff9900'),
    array('Rendimiento Diario (%)', $rendimiento_diario,  '#109618'),
    array('Devolucion', $sumadevolucionanio, '#990099'),
    array('VENTA', $sumaventaanio*(-1), '#0099c6'),
    array('LIMPIEZA',  $sumalimpiezaanio*(-1), '#dd4477'),
    array('ENTREGADO GVC', $sumagvcanio*(-1), '#66aa00'),
    array('COMPRAS', $sumacomprasanio, '#b82e2e')
);



if ($sumampops != 0) {
     $rendimiento_diario = (($sumahuesoops * 0.10) + $sumaproduccionops) / ($sumampops/ 100);
} else {
  $rendimiento_diario = 0; // O cualquier otro valor que quieras asignar en este caso
}


// Agregamos el elemento al array

$dataops = array(
    array('Valor', 'Cantidad', array('role' => 'style')),
    array('PROD X HS HDK', $sumahuesoops*0.10, '#3366cc'),
    array('PROD X S/RAMA (Tanques) HDK', $sumaproduccionops, '#dc3912'),
    array('INGRESO MP',  $sumampops, '#ff9900'),
    array('Rendimiento Diario (%)', $rendimiento_diario,  '#109618'),
    array('Devolucion', $sumadevolucionops, '#990099'),
    array('VENTA', $sumaventaops*(-1), '#0099c6'),
    array('LIMPIEZA',  $sumalimpiezaops*(-1), '#dd4477'),
    array('ENTREGADO GVC', $sumagvcops*(-1), '#66aa00'),
    array('COMPRAS', $sumacomprasops, '#b82e2e')
);



?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet"  type="text/css" href="../css/grafica.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
      <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
  <title>Panel</title>



  <script type="text/javascript">
  var data = <?php echo json_encode($data); ?>;
  var options = {
    title: 'Gráfico datos Mensuales Stock',
    chartArea: {width: '50%'},
    hAxis: {
      title: 'Valor',
      minValue: 0
    },
    vAxis: {
      title: 'Descripcion'
    },
    legend: {position: 'none'}
  };
  google.charts.load('current', {'packages':['corechart']});
  google.charts.setOnLoadCallback(drawChart);
  function drawChart() {
    var chart = new google.visualization.BarChart(document.getElementById('barchart_values'));
    var dataTable = google.visualization.arrayToDataTable(data);
    // Agregar los valores como anotaciones en las barras
    var formatter = new google.visualization.NumberFormat({pattern:'#,###'});
    formatter.format(dataTable, 1);
    var view = new google.visualization.DataView(dataTable);
    view.setColumns([0, 1,
        { 
          calc: "stringify",
          sourceColumn: 1,
          type: "string",
          role: "annotation"
        },
        2]);
    chart.draw(view, options);
  }
</script>
  



<script type="text/javascript">
  var datadia = <?php echo json_encode($datadia); ?>;
  var optionsdia = {
    title: 'Gráfico datos de Hoy Stock',
    chartArea: {width: '50%'},
    hAxis: {
      title: 'Valor',
      minValue: 0
    },
    vAxis: {
      title: 'Descripcion'
    },
    legend: {position: 'none'}
  };
  google.charts.load('current', {'packages':['corechart']});
  google.charts.setOnLoadCallback(drawChart);
  function drawChart() {
    var chart = new google.visualization.BarChart(document.getElementById('graficadia'));
    var dataTable = google.visualization.arrayToDataTable(datadia);
    // Agregar los valores como anotaciones en las barras
    var formatter = new google.visualization.NumberFormat({pattern:'#,###'});
    formatter.format(dataTable, 1);
    var view = new google.visualization.DataView(dataTable);
    view.setColumns([0, 1,
        { 
          calc: "stringify",
          sourceColumn: 1,
          type: "string",
          role: "annotation"
        },
        2]);
    chart.draw(view, optionsdia);
  }
</script>




<script type="text/javascript">
  var dataanio = <?php echo json_encode($dataanio); ?>;
  var optionsanio = {
    title: 'Gráfico datos del año Stock',
    chartArea: {width: '50%'},
    hAxis: {
      title: 'Valor',
      minValue: 0
    },
    vAxis: {
      title: 'Descripcion'
    },
    legend: {position: 'none'}
  };
  google.charts.load('current', {'packages':['corechart']});
  google.charts.setOnLoadCallback(drawChart);
  function drawChart() {
    var chart = new google.visualization.BarChart(document.getElementById('graficaanio'));
    var dataTable = google.visualization.arrayToDataTable(dataanio);
    // Agregar los valores como anotaciones en las barras
    var formatter = new google.visualization.NumberFormat({pattern:'#,###'});
    formatter.format(dataTable, 1);
    var view = new google.visualization.DataView(dataTable);
    view.setColumns([0, 1,
        { 
          calc: "stringify",
          sourceColumn: 1,
          type: "string",
          role: "annotation"
        },
        2]);
    chart.draw(view, optionsanio);
  }
</script>




<script type="text/javascript">
  var dataops = <?php echo json_encode($dataops); ?>;
  var optionsops = {
    title: 'Gráfico datos del opcional Stock',
    chartArea: {width: '50%'},
    hAxis: {
      title: 'Valor',
      minValue: 0
    },
    vAxis: {
      title: 'Descripcion'
    },
    legend: {position: 'none'}
  };
  google.charts.load('current', {'packages':['corechart']});
  google.charts.setOnLoadCallback(drawChart);
  function drawChart() {
    var chart = new google.visualization.BarChart(document.getElementById('graficaops'));
    var dataTable = google.visualization.arrayToDataTable(dataops);
    // Agregar los valores como anotaciones en las barras
    var formatter = new google.visualization.NumberFormat({pattern:'#,###'});
    formatter.format(dataTable, 1);
    var view = new google.visualization.DataView(dataTable);
    view.setColumns([0, 1,
        { 
          calc: "stringify",
          sourceColumn: 1,
          type: "string",
          role: "annotation"
        },
        2]);
    chart.draw(view, optionsops);
  }
</script>
  



</head>
<body>

 <div  class="todo_todo">   

<p>Filtra la informacion  mediante un rango de fecha</p>
  <form action="graficas.php" method="post">
    <br> <br> <br> <br><br> 

    <label>Desde:</label>
   <input type="date" name="fecha_desde" value="<?php echo isset($fecha_desde) ? $fecha_desde : ''; ?>">
    <label>Hasta:</label>
    <input type="date" name="fecha_hasta" value="<?php echo isset($fecha_hasta) ? $fecha_hasta : ''; ?>">



    <input type="submit" value="Filtrar">
  </form>




    <div class="todo">


    <div class="table">
<table>
  <thead>
    <tr>
      <th class="titulos">Tipo</th>

            <th><?php echo "Año"; ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <th>PROD X HS HDK</th>
            <td><?php echo (($sumahuesoops*0.10) != 0) ? number_format(($sumahuesoops*0.10), 0, ',', '.') : ''; ?></td>
        </tr>
        <tr>
            <th>PROD X S/RAMA (Tanques) HDK</th>
            <td><?php echo ($sumaproduccionops != 0) ? number_format($sumaproduccionops, 0, ',', '.') : ''; ?></td>
        </tr>
        <tr>
            <th>INGRESO MP</th>
            <td><?php echo ($sumampops != 0) ? number_format($sumampops, 0, ',', '.') : ''; ?></td>
        </tr>
        <tr>
            <th>Rendimiento Diario (%)</th>
            <td><?php echo ($sumampops != 0) ? number_format((($sumahuesoops*0.10)+$sumaproduccionops)/($sumampops/100), 2, ',', '.')."%" : ''; ?></td>
        </tr>
        <tr>
            <th>Devolucion</th>
            <td><?php echo ($sumadevolucionops != 0) ? number_format($sumadevolucionops, 0, ',', '.') : ''; ?></td>
        </tr>
        <tr>
            <th>VENTA</th>
            <td style="color: red"><?php echo ($sumaventaops!= 0) ? number_format($sumaventaops, 0, ',', '.') : ''; ?></td>
        </tr>
        <tr>
            <th>LIMPIEZA</th>
            <td  style="color: red"><?php echo ($sumalimpiezaops!= 0) ? number_format($sumalimpiezaops, 0, ',', '.') : ''; ?></td>
       </tr>
        <tr>
          <th>ENTREGADO GVC</th>
            <td  style="color: red"><?php echo ($sumagvcops != 0) ? number_format($sumagvcops, 0, ',', '.') : ''; ?></td>

        </tr>
        <tr>
            <th>COMPRAS</th>
            <td><?php echo ($sumacomprasops!= 0) ? number_format($sumacomprasops, 0, ',', '.') : ''; ?></td>
        </tr>

 

<tr>
    <th>Total Incremental</th>
    <?php $contador = 0; 
     foreach (array_reverse($stockTotalPorDia, true) as $fecha => $totales):
         $contador++; if($contador==2){
          ?>
        <td><?php echo ($totales!= 0) ? number_format($totales, 1, ',', '.') : ''; ?></td>
    <?php }else{?>
     <?php }   endforeach; ?>
</tr>



  </tbody>
</table>
</div >
<div class="grafico">
<div id="graficaops" style="width: 1200px; height: 400px;"></div>
</div>
</div>
<br><br>
<h1> MONITOREO POR DIA MES Y AÑO</h1>
<br>
<div class="todo">
    <div class="table">
<table>
  <thead>
    <tr>
      <th class="titulos">Tipo</th>

            <th><?php echo "Dia"; ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <th>PROD X HS HDK</th>
            <td><?php echo (($sumahuesodia*0.10) != 0) ? number_format(($sumahuesodia*0.10), 0, ',', '.') : ''; ?></td>
        </tr>
        <tr>
            <th>PROD X S/RAMA (Tanques) HDK</th>
            <td><?php echo ($sumaproducciondia != 0) ? number_format($sumaproducciondia, 0, ',', '.') : ''; ?></td>
        </tr>
        <tr>
            <th>INGRESO MP</th>
            <td><?php echo ($sumampdia!= 0) ? number_format($sumampdia, 0, ',', '.') : ''; ?></td>
        </tr>
        <tr>
            <th>Rendimiento Diario (%)</th>
            <td><?php echo ($sumampdia != 0) ? number_format((($sumahuesodia*0.10)+$sumaproducciondia)/($sumampdia/100), 2, ',', '.')."%" : ''; ?></td>
        </tr>
        <tr>
            <th>Devolucion</th>
            <td><?php echo ($sumadevoluciondia != 0) ? number_format($sumadevoluciondia, 0, ',', '.') : ''; ?></td>
        </tr>
        <tr>
            <th>VENTA</th>
            <td style="color: red"><?php echo ($sumaventadia != 0) ? number_format($sumaventadia, 0, ',', '.') : ''; ?></td>
        </tr>
        <tr>
            <th>LIMPIEZA</th>
            <td  style="color: red"><?php echo ($sumalimpiezadia!= 0) ? number_format($sumalimpiezadia, 0, ',', '.') : ''; ?></td>
       </tr>
        <tr>
          <th>ENTREGADO GVC</th>
            <td  style="color: red"><?php echo ($sumagvcdia != 0) ? number_format($sumagvcdia, 0, ',', '.') : ''; ?></td>

        </tr>
        <tr>
            <th>COMPRAS</th>
            <td><?php echo ($sumacomprasdia!= 0) ? number_format($sumacomprasdia, 0, ',', '.') : ''; ?></td>
        </tr>

 

<tr>
    <th>Total Incremental</th>
    <?php $contador = 0; 
     foreach (array_reverse($stockTotalPorDia, true) as $fecha => $totales):
         $contador++; if($contador==2){
          ?>
        <td><?php echo ($totales!= 0) ? number_format($totales, 1, ',', '.') : ''; ?></td>
    <?php }else{?>
     <?php }   endforeach; ?>
</tr>



  </tbody>
</table>
</div>
<div  class="grafico">
<div id="graficadia" style="width: 1200px; height: 400px;"></div>
</div>

</div>
<br>
<div class="todo">
    <div class="table">
<table>
  <thead>
    <tr>
      <th class="titulos">Tipo</th>

            <th><?php echo "Mes"; ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <th>PROD X HS HDK</th>
            <td><?php echo (($sumahuesomes*0.10) != 0) ? number_format(($sumahuesomes*0.10), 0, ',', '.') : ''; ?></td>
        </tr>
        <tr>
            <th>PROD X S/RAMA (Tanques) HDK</th>
            <td><?php echo ($sumaproduccionmes != 0) ? number_format($sumaproduccionmes, 0, ',', '.') : ''; ?></td>
        </tr>
        <tr>
            <th>INGRESO MP</th>
            <td><?php echo ($sumampmes != 0) ? number_format($sumampmes, 0, ',', '.') : ''; ?></td>
        </tr>
        <tr>
            <th>Rendimiento Diario (%)</th>
            <td><?php echo ($sumampmes != 0) ? number_format((($sumahuesomes*0.10)+$sumaproduccionmes)/($sumampmes/100), 2, ',', '.')."%" : ''; ?></td>
        </tr>
        <tr>
            <th>Devolucion</th>
            <td><?php echo ($sumadevolucionmes != 0) ? number_format($sumadevolucionmes, 0, ',', '.') : ''; ?></td>
        </tr>
        <tr>
            <th>VENTA</th>
            <td style="color: red"><?php echo ($sumaventames != 0) ? number_format($sumaventames, 0, ',', '.') : ''; ?></td>
        </tr>
        <tr>
            <th>LIMPIEZA</th>
            <td  style="color: red"><?php echo ($sumalimpiezames!= 0) ? number_format($sumalimpiezames, 0, ',', '.') : ''; ?></td>
       </tr>
        <tr>
          <th>ENTREGADO GVC</th>
            <td  style="color: red"><?php echo ($sumagvcmes != 0) ? number_format($sumagvcmes, 0, ',', '.') : ''; ?></td>

        </tr>
        <tr>
            <th>COMPRAS</th>
            <td><?php echo ($sumacomprasmes!= 0) ? number_format($sumacomprasmes, 0, ',', '.') : ''; ?></td>
        </tr>

 

<tr>
    <th>Total Incremental</th>
    <?php $contador = 0; 
     foreach (array_reverse($stockTotalPorDia, true) as $fecha => $totales):
         $contador++; if($contador==2){
          ?>
        <td><?php echo ($totales!= 0) ? number_format($totales, 1, ',', '.') : ''; ?></td>
    <?php }else{?>
     <?php }   endforeach; ?>
</tr>



  </tbody>
</table>
</div >

<div class="grafico">
<div id="barchart_values" style="width: 1200px; height: 400px;"></div>
</div>
</div>



<div class="todo">
    <div class="table">
<table>
  <thead>
    <tr>
      <th class="titulos">Tipo</th>

            <th><?php echo "Año"; ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <th>PROD X HS HDK</th>
            <td><?php echo (($sumahuesoanio*0.10) != 0) ? number_format(($sumahuesoanio*0.10), 0, ',', '.') : ''; ?></td>
        </tr>
        <tr>
            <th>PROD X S/RAMA (Tanques) HDK</th>
            <td><?php echo ($sumaproduccionanio != 0) ? number_format($sumaproduccionanio, 0, ',', '.') : ''; ?></td>
        </tr>
        <tr>
            <th>INGRESO MP</th>
            <td><?php echo ($sumampanio != 0) ? number_format($sumampanio, 0, ',', '.') : ''; ?></td>
        </tr>
        <tr>
            <th>Rendimiento Diario (%)</th>
            <td><?php echo ($sumampanio != 0) ? number_format((($sumahuesoanio*0.10)+$sumaproduccionanio)/($sumampanio/100), 2, ',', '.')."%" : ''; ?></td>
        </tr>
        <tr>
            <th>Devolucion</th>
            <td><?php echo ($sumadevolucionanio != 0) ? number_format($sumadevolucionanio, 0, ',', '.') : ''; ?></td>
        </tr>
        <tr>
            <th>VENTA</th>
            <td style="color: red"><?php echo ($sumaventaanio != 0) ? number_format($sumaventaanio, 0, ',', '.') : ''; ?></td>
        </tr>
        <tr>
            <th>LIMPIEZA</th>
            <td  style="color: red"><?php echo ($sumalimpiezaanio!= 0) ? number_format($sumalimpiezaanio, 0, ',', '.') : ''; ?></td>
       </tr>
        <tr>
          <th>ENTREGADO GVC</th>
            <td  style="color: red"><?php echo ($sumagvcanio != 0) ? number_format($sumagvcanio, 0, ',', '.') : ''; ?></td>

        </tr>
        <tr>
            <th>COMPRAS</th>
            <td><?php echo ($sumacomprasanio!= 0) ? number_format($sumacomprasanio, 0, ',', '.') : ''; ?></td>
        </tr>

 

<tr>
    <th>Total Incremental</th>
    <?php $contador = 0; 
     foreach (array_reverse($stockTotalPorDia, true) as $fecha => $totales):
         $contador++; if($contador==2){
          ?>
        <td><?php echo ($totales!= 0) ? number_format($totales, 1, ',', '.') : ''; ?></td>
    <?php }else{?>
     <?php }   endforeach; ?>
</tr>



  </tbody>
</table>
</div >

<div class="grafico">
<div id="graficaanio" style="width: 1200px; height: 400px;"></div>
</div>
</div>
</div>



</div>


</body>
</html>


