<?php

include("conexion.php");
$con=conectar();

$ID=$_GET['id'];

$sql="SELECT * FROM formulario  WHERE ID='$ID'";
$query=mysqli_query($con,$sql);

$row=mysqli_fetch_array($query);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title></title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="css/style.css" rel="stylesheet">
        <title>Actualizar</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
        
    </head>
    <body>
                <div class="container mt-5">
                    <form method="POST" action="actualizar.php" class="formulario">
                        <div class="textomail">ESCRIBENOS Y EN BREVE NOS PONDREMOS EN CONTACTO CONTIGO </div>
                         <input type="hidden" name="ID" value="<?php echo $row['ID']  ?>">
                            <label for="nombre">Nombre:</label>
                                <input id="nombre" name="Nombre" placeholder="Nombre completo" value="<?php echo $row['Nombre']  ?>">>
                                <label for="email">Email:</label>
                                <input id="email" name="Email" type="email" placeholder="ejemplo@email.com" value="<?php echo $row['Nombre']  ?>">
                                 <label for="mensaje">Mensaje:</label>
                                <textarea id="mensaje" name="Comentario" placeholder="Danos tu mensaje" value="<?php echo $row['Comentario']  ?>"></textarea>
                                <input id="submit" name="submit" type="submit" value="Enviar">
                    </form>
    </div>
                    
                </div>
    </body>
</html>