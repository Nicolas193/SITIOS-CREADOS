<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("../../menu.php");
require_once("../../conexion.php");
$conn = conectar();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die("ID inválido");
}

// Consulta con el estado más reciente
$sql = "
SELECT 
    rt.fecha_solicitud,
    rt.plazo_entrega,
    rt.asunto,
    t.nombre_tarea,
    u_res.usuario AS responsable_usuario,
    u_res.cargo AS responsable_cargo,
    u_res.sector AS responsable_sector,
    GROUP_CONCAT(DISTINCT CONCAT(u.usuario, ' (', u.cargo, ', ', u.sector, ')') SEPARATOR ', ') AS encargados_detalle,
    e.nombre_estado,
    e.id_estado,
    et.fecha_actualizacion
FROM registro_de_tareas rt
LEFT JOIN tareas t ON rt.id_tarea = t.id_tarea
LEFT JOIN usuarios u_res ON rt.id_usuario_rest = u_res.id_usuario
LEFT JOIN usuarios_vinculados uv ON rt.id_registro = uv.id_registro
LEFT JOIN usuarios u ON uv.id_usuario = u.id_usuario
LEFT JOIN estado_tarea et ON rt.id_registro = et.id_registro
LEFT JOIN estados e ON et.id_estado = e.id_estado
WHERE rt.id_registro = ?
AND et.fecha_actualizacion = (
    SELECT MAX(fecha_actualizacion)
    FROM estado_tarea
    WHERE id_registro = rt.id_registro
)
GROUP BY rt.id_registro
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$tarea = $result->fetch_assoc();

if (!$tarea) {
    die("No se encontró la tarea.");
}

// Fechas y cálculo días restantes
$hoy = new DateTime();
$plazo = DateTime::createFromFormat('Y-m-d', substr($tarea['plazo_entrega'], 0, 10));

$dias_restantes = null;
$plazo_pasado = false;

if ($plazo) {
    $intervalo = $hoy->diff($plazo);
    $dias_restantes = (int)$intervalo->format('%r%a');
    $plazo_pasado = $dias_restantes < 0;
}

$color_estado = "gray";
if ($tarea['id_estado'] == 1) {
    $color_estado = "green";
} elseif (in_array($tarea['id_estado'], [2, 3, 4, 5, 7])) {
    $color_estado = "orange";
} elseif ($tarea['id_estado'] == 6) {
    $color_estado = "red";
}

$color_dias_restantes = "gray";
if ($tarea['id_estado'] != 1 && $plazo) {
    if ($plazo_pasado || $dias_restantes <= 1) {
        $color_dias_restantes = "red";
    } elseif ($dias_restantes >= 2 && $dias_restantes <= 3) {
        $color_dias_restantes = "orange";
    } elseif ($dias_restantes >= 4) {
        $color_dias_restantes = "green";
    }
} elseif ($tarea['id_estado'] == 1) {
    $color_dias_restantes = "green";
}

// Consulta comentarios con nombre de usuario
$sql_comentarios = "
  SELECT c.comentario, c.fecha_comentario, c.id_usuario, u.usuario
  FROM comentarios c
  LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
  WHERE c.id_registro = ?
  ORDER BY c.fecha_comentario ASC
";

$stmt_com = $conn->prepare($sql_comentarios);
$stmt_com->bind_param("i", $id);
$stmt_com->execute();
$result_com = $stmt_com->get_result();

$comentarios = [];
while ($row = $result_com->fetch_assoc()) {
    $comentarios[] = $row;
}

// Usuario actual (para el comentario)
$usuario_actual_id = $_SESSION['id_usuario'] ?? 0;

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Detalles de la Tarea</title>
  <link rel="stylesheet" href="../css/estilocomentarios.css" />
</head>
<body>

<div class="contenedor-padre">

  <div class="contenedor">
    <h2>Detalles de la Tarea</h2>

    <div class="campo"><strong>Fecha de solicitud:</strong> <?= htmlspecialchars($tarea['fecha_solicitud']) ?></div>
    <div class="campo"><strong>Plazo de entrega:</strong> <?= htmlspecialchars($tarea['plazo_entrega']) ?></div>
    <div class="campo"><strong>Responsable de enviar tarea:</strong> <?= htmlspecialchars($tarea['responsable_usuario']) ?></div>
    <div class="campo"><strong>Cargo Responsable:</strong> <?= htmlspecialchars($tarea['responsable_cargo']) ?></div>
    <div class="campo"><strong>Sector Responsable:</strong> <?= htmlspecialchars($tarea['responsable_sector']) ?></div>
    <div class="campo"><strong>Tipo de tarea:</strong> <?= htmlspecialchars($tarea['nombre_tarea']) ?></div>
    <div class="campo"><strong>Asunto:</strong> <?= htmlspecialchars($tarea['asunto']) ?></div>
    <div class="campo"><strong>Encargados de realizar tarea:</strong> <?= htmlspecialchars($tarea['encargados_detalle']) ?></div>

    <div class="campo">
      <strong>Estado actual:</strong>
      <span class="estado-label <?= $color_estado ?>">
        <?= htmlspecialchars($tarea['nombre_estado']) ?>
      </span>
    </div>

    <div class="campo">
      <strong>Días para entregar:</strong>
      <span class="dias-label <?= $color_dias_restantes ?>">
        <?= $dias_restantes >= 0 ? $dias_restantes : 0 ?> día<?= ($dias_restantes == 1) ? '' : 's' ?>
      </span>
    </div>
  </div>

  <div class="comentarios-section">
    <h3>Comentarios</h3>

    <?php if (count($comentarios) === 0): ?>
      <p class="sin-comentarios">No hay comentarios para esta tarea.</p>
    <?php else: ?>
      <ul class="lista-comentarios">
        <?php foreach ($comentarios as $com): ?>
            <li>
                <div class="comentario-usuario"><strong><?= htmlspecialchars($com['usuario'] ?? 'Anónimo') ?>:</strong></div>
                <div class="comentario-texto"><?= nl2br(htmlspecialchars($com['comentario'])) ?></div>
                <div class="comentario-fecha">
                  <?= date('d/m/Y H:i', strtotime($com['fecha_comentario'])) ?>
                  <?php if ($com['usuario'] === $_SESSION['username']): ?>
                    <form method="POST" action="eliminar_comentario.php" style="display:inline;">
                      <input type="hidden" name="id_registro" value="<?= $id ?>">
                      <input type="hidden" name="fecha_comentario" value="<?= $com['fecha_comentario'] ?>">
                      <button type="submit" class="btn-eliminar">🗑</button>
                    </form>
                  <?php endif; ?>
                </div>
              </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <form action="agregar_comentario.php" method="POST" class="form-comentario">
      <input type="hidden" name="id_registro" value="<?= $id ?>">
      <textarea name="comentario" placeholder="Agregar un comentario..." required></textarea>
      <button type="submit">Enviar</button>
    </form>
  </div>

</div> <!-- Fin contenedor-padre -->

</body>

</html>
