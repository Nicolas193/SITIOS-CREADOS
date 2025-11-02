<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("../../conexion.php");
require_once("../../menu.php");
$conn = conectar();

if (!isset($_SESSION['username'])) {
    die("Acceso denegado. Por favor, inicia sesión.");
}

// === Tablas y columnas ===
$tablas = [
    'accionddjj' => ['id'=>'id_accion', 'campo'=>'descripcion', 'label'=>'Acción DDJJ'],
    'anioestadoddjj' => ['id'=>'id_anioestado', 'campo'=>'anio', 'label'=>'Año Estado DDJJ'],
    'clasificacionddjj' => ['id'=>'id_clasificacion', 'campo'=>'descripcion', 'label'=>'Clasificación DDJJ'],
    'clasificacionesconsultaddjj' => ['id'=>'id_clasificacionconsulta', 'campo'=>'descripcion', 'label'=>'Clasificación Consulta DDJJ'],
    'estadoddjj' => ['id'=>'id_estado', 'campo'=>'descripcion', 'label'=>'Estado DDJJ'],
    'observacionesddjj' => ['id'=>'id_observaciones', 'campo'=>'observacion', 'label'=>'Observaciones DDJJ'],
    'origenddjj' => ['id'=>'id_origen', 'campo'=>'descripcion', 'label'=>'Origen DDJJ'],
];

$tabla = $_GET['tabla'] ?? 'clasificacionddjj';
if (!array_key_exists($tabla, $tablas)) die("Tabla inválida.");

$id_col = $tablas[$tabla]['id'];
$campo = $tablas[$tabla]['campo'];
$label = $tablas[$tabla]['label'];
$accion = $_GET['accion'] ?? null;
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

function limpiar($str) {
    return htmlspecialchars(trim($str));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valor = limpiar($_POST['valor'] ?? '');
    $id_validar = isset($_POST['id_validar']) ? 1 : 0;

    if ($valor === '') {
        $error = "El campo no puede estar vacío.";
    } else {
        if (!empty($_POST['id'])) {
            // === EDITAR ===
            $id_editar = intval($_POST['id']);
            $stmt = $conn->prepare("UPDATE $tabla SET $campo = ?, id_validar = ? WHERE $id_col = ?");
            if ($tabla === 'anioestadoddjj') {
                if (!ctype_digit($valor)) {
                    $error = "El año debe ser un número válido.";
                } else {
                    $stmt->bind_param("iii", $valor, $id_validar, $id_editar);
                }
            } else {
                $stmt->bind_param("sii", $valor, $id_validar, $id_editar);
            }

            if (!isset($error) && $stmt->execute()) {
                header("Location: editarmenu_ddjj.php?tabla=$tabla");
                exit;
            } else {
                $error = "Error al editar: " . $stmt->error;
            }
            $stmt->close();

        } else {
            // === AGREGAR ===
            $stmt = $conn->prepare("INSERT INTO $tabla ($campo, id_validar) VALUES (?, ?)");
            if ($tabla === 'anioestadoddjj') {
                if (!ctype_digit($valor)) {
                    $error = "El año debe ser un número válido.";
                } else {
                    $stmt->bind_param("ii", $valor, $id_validar);
                }
            } else {
                $stmt->bind_param("si", $valor, $id_validar);
            }

            if (!isset($error) && $stmt->execute()) {
                header("Location: editarmenu_ddjj.php?tabla=$tabla");
                exit;
            } else {
                $error = "Error al insertar: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// === Obtener datos ===
$result = $conn->query("SELECT * FROM $tabla ORDER BY $id_col ASC");
$datos = $result->fetch_all(MYSQLI_ASSOC);

// === Obtener fila a editar ===
if ($accion === 'editar' && $id) {
    $stmt = $conn->prepare("SELECT * FROM $tabla WHERE $id_col = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $editarFila = $resultado->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<link rel="stylesheet" type="text/css" href="../css/ddjjconsulta.css">
<title>Administrar <?= $label ?></title>
</head>
<body>

<div class="wrapper-mistareas">
  <div class="container-mistareas form-container">
    <h1 class="title">Administrar <?= $label ?></h1>

    <nav>
      <?php
        $total = count($tablas); $i = 0;
        foreach ($tablas as $key => $val): $i++;
      ?>
        <a href="editarmenu_ddjj.php?tabla=<?= $key ?>"<?= $key === $tabla ? ' style="font-weight:bold;"' : '' ?> class="tabla-badge">
          <?= htmlspecialchars($val['label']) ?>
        </a><?= $i < $total ? ',' : '' ?>
      <?php endforeach; ?>
    </nav>

    <?php if (!empty($error)): ?>
      <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <!-- === FORMULARIO === -->
    <form method="post" action="editarmenu_ddjj.php?tabla=<?= $tabla ?>">
      <div class="form-row">
        <div class="form-group">
          <input
            type="<?= $tabla === 'anioestadoddjj' ? 'number' : 'text' ?>"
            name="valor"
            placeholder="<?= $label ?>"
            value="<?= htmlspecialchars($editarFila[$campo] ?? '') ?>"
            required
          >
        </div>

        <div class="form-group">
          <label>
<input type="checkbox" name="id_validar" value="1"
  <?= !isset($editarFila) || (isset($editarFila['id_validar']) && $editarFila['id_validar'] == 1) ? 'checked' : '' ?>>

            Validar
          </label>
        </div>
      </div>

      <?php if (isset($editarFila[$id_col])): ?>
        <input type="hidden" name="id" value="<?= $editarFila[$id_col] ?>">
        <button type="submit">Guardar cambios</button>
        <a href="editarmenu_ddjj.php?tabla=<?= $tabla ?>">Cancelar</a>
      <?php else: ?>
        <button type="submit">Agregar</button>
      <?php endif; ?>
    </form>

    <!-- === TABLA === -->
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th><?= $label ?></th>
          <th>Validar</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($datos as $fila): ?>
          <tr>
            <td><?= $fila[$id_col] ?></td>
            <td><?= htmlspecialchars($fila[$campo]) ?></td>
            <td><?= $fila['id_validar'] == 1 ? '✅' : '❌' ?></td>
            <td>
              <a href="editarmenu_ddjj.php?tabla=<?= $tabla ?>&accion=editar&id=<?= $fila[$id_col] ?>">Editar</a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($datos)): ?>
          <tr><td colspan="4">No hay registros.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

  </div>
</div>

</body>
</html>

<?php
$conn->close();
?>
