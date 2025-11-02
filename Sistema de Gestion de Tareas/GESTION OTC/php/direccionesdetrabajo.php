<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../../conexion.php");
include("../../menu.php");

// Obtener nombre de usuario desde la sesión
$usuario_nombre = isset($_SESSION['username']) ? $_SESSION['username'] : '';

// Obtener ID del usuario logueado
$usuario_logueado = null;
if ($usuario_nombre) {
    $sql_user = "SELECT id_usuario FROM usuarios WHERE usuario = '$usuario_nombre' LIMIT 1";
    $result_user = mysqli_query($con, $sql_user);
    if ($result_user && mysqli_num_rows($result_user) > 0) {
        $row_user = mysqli_fetch_assoc($result_user);
        $usuario_logueado = $row_user['id_usuario'];
    }
}

// Procesar formulario de agregar URL
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_url'])) {
    $nombre = mysqli_real_escape_string($con, $_POST['nombre']);
    $descripcion = mysqli_real_escape_string($con, $_POST['descripcion']);
    $url = mysqli_real_escape_string($con, $_POST['url']);

    if (!empty($nombre) && !empty($descripcion) && !empty($url) && !empty($usuario_logueado)) {
        $sqlInsert = "INSERT INTO urls (id_usuario, nombre_url, descripcion, url) 
                      VALUES ('$usuario_logueado', '$nombre', '$descripcion', '$url')";
        if (mysqli_query($con, $sqlInsert)) {
            header("Location: direccionesdetrabajo.php");
            exit();
        } else {
            echo "<script>alert('Error al guardar la URL');</script>";
        }
    } else {
        echo "<script>alert('Debe estar logueado y completar todos los campos');</script>";
    }
}

// Procesar eliminación
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar_url'])) {
    $idEliminar = intval($_POST['id_url']);
    $sqlDelete = "DELETE FROM urls WHERE id_url = '$idEliminar' AND id_usuario = '$usuario_logueado'";
    mysqli_query($con, $sqlDelete);
    header("Location: direccionesdetrabajo.php");
    exit();
}

// Consultar URLs
$sql = "SELECT * FROM urls WHERE id_usuario = '$usuario_logueado' ORDER BY id_url DESC";
$query = mysqli_query($con, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Mis URLs Frecuentes</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/direcciones.css">
</head>
<body>
  <div class="content1">

    <!-- Sección de formulario -->
    <div class="form-container">
      <h2>Agregar Nueva URL Frecuente</h2>
      <form method="POST" action="" class="form-wrapper">
        <label for="nombre">Nombre:</label>
        <input type="text" name="nombre" required>

        <label for="descripcion">Descripción:</label>
        <input type="text" name="descripcion" required>

        <label for="url">URL:</label>
        <input type="url" name="url" required>

        <input type="submit" name="agregar_url" value="Guardar URL" class="btn-gradient" />
      </form>
    </div>

    <!-- Sección de tarjetas -->
    <div class="list-section">
      <h2>Mis URLs Frecuentes</h2>

      <input type="text" id="buscador" placeholder="Buscar...">

      <div id="cardsContainer" class="card-container">
        <?php while ($row = mysqli_fetch_assoc($query)): ?>
          <div class="url-card">
            <h3><?php echo htmlspecialchars($row['nombre_url']); ?></h3>
            <p><strong>Descripción:</strong> <?php echo htmlspecialchars($row['descripcion']); ?></p>
            <p><strong>URL:</strong> 
              <a href="<?php echo htmlspecialchars($row['url']); ?>" target="_blank">
                <?php echo htmlspecialchars($row['url']); ?>
              </a>
            </p>
            <form method="POST" action="" onsubmit="return confirm('¿Está seguro que desea eliminar esta URL?');">
              <input type="hidden" name="id_url" value="<?php echo $row['id_url']; ?>">
              <input type="submit" name="eliminar_url" value="Eliminar" class="btn-gradient-danger" />
            </form>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
  </div>

  <script>
    document.getElementById('buscador').addEventListener('keyup', function () {
      const valor = this.value.toLowerCase();
      const cards = document.querySelectorAll('.url-card');

      cards.forEach(card => {
        card.style.display = card.textContent.toLowerCase().includes(valor) ? 'block' : 'none';
      });
    });
  </script>


  
</body>



</html>

<?php ob_end_flush(); ?>
