<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="imagen/presentacion.ico" />
  <title>Bienvenido</title>
  <link rel="stylesheet" href="Estilos/bienvenida.css">
</head>
<body>

  <div class="bienvenida-container">
    <img src="imagen/LogoOtceAnimado.gif" alt="Logo">
    <div class="bienvenida-texto">Bienvenido</div>
  </div>

  <script>
    setTimeout(() => {
      window.location.href = "introduccion.php";
    }, 5000); // Redirige a los 5 segundos
  </script>

</body>
</html>
