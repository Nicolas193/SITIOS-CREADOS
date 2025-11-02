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

// Obtener usuario logueado desde sesión
$usuario_actual_id = 0;
if (isset($_SESSION['username'])) {
    $usuario = $_SESSION['username'];
$stmtUser = $conn->prepare("SELECT id_usuario FROM usuarios WHERE usuario = ? LIMIT 1");
$stmtUser->bind_param("s", $usuario);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
if ($rowResponsable = $resultUser->fetch_assoc()) {
    $usuario_actual_id = $rowResponsable['id_usuario'];
}
$stmtUser->close();

}

// Consulta con el estado más reciente
$sql = "
SELECT 
    rt.fecha_solicitud,
    rt.plazo_entrega,
    rt.asunto,
    t.nombre_tarea,
    rt.id_usuario_rest,
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
GROUP BY
  rt.id_registro,
  rt.fecha_solicitud,
  rt.plazo_entrega,
  rt.asunto,
  t.nombre_tarea,
  rt.id_usuario_rest,
  u_res.usuario,
  u_res.cargo,
  u_res.sector,
  e.nombre_estado,
  e.id_estado,
  et.fecha_actualizacion
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

// Verificar si el usuario es responsable
$es_responsable = ($usuario_actual_id == $tarea['id_usuario_rest']);

// Verificar si el usuario es encargado (en lista de usuarios vinculados)
$sql_encargados = "SELECT 1 FROM usuarios_vinculados WHERE id_registro = ? AND id_usuario = ? LIMIT 1";
$stmt_enc = $conn->prepare($sql_encargados);
$stmt_enc->bind_param("ii", $id, $usuario_actual_id);
$stmt_enc->execute();
$stmt_enc->store_result();
$es_encargado = $stmt_enc->num_rows > 0;
$stmt_enc->close();

// Determinar filtro para estados según el rol
if ($es_responsable && !$es_encargado) {
    // Solo responsable
    $filtro_estados = "tipo = 'Evaluador'";
} elseif (!$es_responsable && $es_encargado) {
    // Solo encargado
    $filtro_estados = "tipo = 'Evaluado' OR tipo = 'Ambos'";
} elseif ($es_responsable && $es_encargado) {
    // Ambos roles, no filtro
    $filtro_estados = "1=1";
} else {
    // No es responsable ni encargado: no mostrar formulario
    $filtro_estados = null;
}

// Obtener estados disponibles si corresponde
if ($filtro_estados !== null) {
    $sql_estados = "SELECT id_estado, nombre_estado FROM estados WHERE $filtro_estados ORDER BY nombre_estado";
    $result_estados = $conn->query($sql_estados);
    $estados_disponibles = $result_estados->fetch_all(MYSQLI_ASSOC);
} else {
    $estados_disponibles = [];
}

// ** NUEVA PARTE: obtener historial de cambios **
$sql_historial = "
SELECT 
    et.fecha_actualizacion, 
    st.nombre_estado, 
    st.tipo, 
    et.id_estado,
    u.usuario AS nombre_usuario
FROM estado_tarea et
JOIN estados st ON et.id_estado = st.id_estado
LEFT JOIN usuarios u ON et.id_usuario = u.id_usuario
WHERE et.id_registro = ?
ORDER BY et.fecha_actualizacion ASC

";
$stmt_hist = $conn->prepare($sql_historial);
$stmt_hist->bind_param("i", $id);
$stmt_hist->execute();
$res_hist = $stmt_hist->get_result();
$historial = [];
while ($r = $res_hist->fetch_assoc()) {
    $historial[] = $r;
}
$stmt_hist->close();


// Obtener tipo y sector del usuario logueado
$tipoUsuario = strtolower(trim($_SESSION['tipo']));
$sectorUsuario = trim($_SESSION['sector']);

// Obtener usuarios filtrados según el tipo de usuario
if ($tipoUsuario === 'operador') {
    // Si es operador, solo traer usuarios de su mismo sector
    $stmtUsuarios = $conn->prepare("SELECT id_usuario, usuario, sector FROM usuarios WHERE sector = ? ORDER BY usuario");

    if (!$stmtUsuarios) {
        die("Error en prepare: " . $conn->error);
    }
    $stmtUsuarios->bind_param("s", $sectorUsuario);
    $stmtUsuarios->execute();
    $resultUsuarios = $stmtUsuarios->get_result();
    $usuarios = [];
    while ($row = $resultUsuarios->fetch_assoc()) {
        $usuarios[] = $row;
    }
    $stmtUsuarios->close();
} else {
    // Si no es operador, traer todos los usuarios
    $resultUsuarios = $conn->query("SELECT id_usuario, usuario, sector FROM usuarios ORDER BY usuario");
    $usuarios = [];
    while ($row = $resultUsuarios->fetch_assoc()) {
        $usuarios[] = $row;
    }
}


// Obtener usuarios vinculados ya asignados
$stmtVinc = $conn->prepare("SELECT id_usuario FROM usuarios_vinculados WHERE id_registro = ?");
$stmtVinc->bind_param("i", $id);
$stmtVinc->execute();
$resultVinc = $stmtVinc->get_result();
$usuarios_vinculados_actuales = [];
while ($row = $resultVinc->fetch_assoc()) {
    $usuarios_vinculados_actuales[] = $row['id_usuario'];
}
$stmtVinc->close();

$idResponsable = $tarea['id_usuario_rest'];


$bloquear_comentario = false;

if (count($comentarios) >= 2) {
    $ultimos = array_slice($comentarios, -2);
    if ($ultimos[0]['id_usuario'] == $usuario_actual_id && $ultimos[1]['id_usuario'] == $usuario_actual_id) {
        $bloquear_comentario = true;
    }
}

?>


<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Detalles de la Tarea</title>
  <link rel="stylesheet" href="../css/estilocomentarios.css" />
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body>

<div class="contenedor-padre">

  <div class="contenedor">
    <h2>Detalles de la Tarea</h2>

    <div class="campo"><strong>Fecha de solicitud:</strong> <?= !empty($tarea['fecha_solicitud']) ? date('d/m/Y', strtotime($tarea['fecha_solicitud'])) : '' ?></div>
    <div class="campo"><strong>Plazo de entrega:</strong> <?= !empty($tarea['plazo_entrega']) ? date('d/m/Y', strtotime($tarea['plazo_entrega'])) : '' ?></div>
    <div class="campo"><strong>Responsable de enviar tarea:</strong> <?= htmlspecialchars($tarea['responsable_usuario']) ?></div>
    <div class="campo"><strong>Cargo Responsable:</strong> <?= htmlspecialchars($tarea['responsable_cargo']) ?></div>
    <div class="campo"><strong>Sector Responsable:</strong> <?= htmlspecialchars($tarea['responsable_sector']) ?></div>
    <div class="campo"><strong>Tipo de tarea:</strong> <?= htmlspecialchars($tarea['nombre_tarea']) ?></div>
    <div class="campo"><strong>Asunto:</strong> <?= htmlspecialchars($tarea['asunto']) ?></div>
    <div class="campo">
  <strong>Encargados de realizar tarea:</strong>
  <a ><?= htmlspecialchars($tarea['encargados_detalle']) ?></a>
</div>


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

    <!-- Menú para cambiar estado -->


<?php if ($historial): ?>
  <div class="timeline">
   <?php foreach ($historial as $h): 
  $fecha = date('d/m/Y H:i', strtotime($h['fecha_actualizacion'] ?? ''));
  $id_estado = $h['id_estado'] ?? null;
  $tipo = $h['tipo'] ?? '';
  $nombre_usuario = $h['nombre_usuario'] ?? 'Usuario desconocido';
  $cls = 'estado-default';

  if ((int)trim($id_estado) === 1) {
    $cls = 'estado-verde';
  } elseif ((int)trim($id_estado) === 8) {
    $cls = 'estado-negro';
  } elseif ((int)trim($id_estado) === 6) {
    $cls = 'estado-rojo';
  } else {
    if ($tipo === 'Evaluador') {
      $cls = 'estado-negro';
    } elseif ($tipo === 'Evaluado') {
      $cls = 'estado-amarillo';
    } elseif ($tipo === 'Ambos') {
      $cls = 'estado-azul';
    }
  }
?>
  <div class="timeline-item <?= $cls ?>">
    <div class="dot"></div>
    <div class="content">
      <span class="time"><?= $fecha ?></span>
      <span class="status"><?= htmlspecialchars($h['nombre_estado'] ?? 'Sin Respuesta') ?></span>
      <br />
      <small><em><?= htmlspecialchars($nombre_usuario) ?></em></small>
    </div>
  </div>
<?php endforeach; ?>

  </div>
<?php endif; ?>


<!-- BLOQUE DE COMENTARIOS -->
<div class="comentarios-section">
  <!-- comentarios existentes -->
</div>

  </div>

<div class="comentarios-section">
  <h3>Comentarios</h3>

  <?php if (count($comentarios) === 0): ?>
    <p class="sin-comentarios">No hay comentarios para esta tarea.</p>
  <?php else: ?>
    <ul class="lista-comentarios">
      <?php foreach ($comentarios as $com): ?>
        <?php
          $comentario_texto = htmlspecialchars($com['comentario']);
          $es_estado_cambiado = stripos($comentario_texto, 'cambió el estado') !== false;
          $clase = $es_estado_cambiado ? 'estado-cambiado' : '';
        ?>
        <li>
          <div class="comentario-usuario">
          <strong>
            <a href="#" class="ver-perfil" data-usuario="<?= htmlspecialchars($com['usuario']) ?>">
              <?= htmlspecialchars($com['usuario'] ?? 'Anónimo') ?>
            </a>:
          </strong>
        </div>
          <div class="comentario-texto <?= $clase ?>"><?= nl2br($comentario_texto) ?></div>
          <div class="comentario-fecha">
            <?= date('d/m/Y H:i', strtotime($com['fecha_comentario'])) ?>
            <?php if (!$es_estado_cambiado && $com['usuario'] === $_SESSION['username']): ?>
              <form method="POST" action="eliminar_comentario.php" style="display:inline;">
                <input type="hidden" name="id_registro" value="<?= $id ?>">
                <input type="hidden" name="fecha_comentario" value="<?= $com['fecha_comentario'] ?>">
                <button type="submit" class="btn-eliminar" title="Eliminar comentario">🗑️</button>
              </form>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>


<?php if ($bloquear_comentario): ?>
  <p style="color: red; font-weight: bold;">Solo podés enviar dos comentarios seguidos, incluyendo el automático. Esperá una respuesta antes de continuar. Recordá que la caja de comentarios se utiliza para registrar las tareas realizadas y, si es necesario, agregar un nuevo estado. No es un chat para mantener conversaciones.</p>
<?php endif; ?>

</div>


</div> <!-- Fin contenedor-padre -->
<!-- MODAL PERFIL DE USUARIO -->
<div id="modalPerfil" class="modal-perfil">
  <div class="modal-content">
    <button class="close-modal" aria-label="Cerrar perfil">&times;</button>
    <h3>Perfil de Usuario</h3>
    <div class="perfil-dato"><strong>Usuario:</strong> <span id="perfil-usuario"></span></div>
    <div class="perfil-dato"><strong>Cargo:</strong> <span id="perfil-cargo"></span></div>
    <div class="perfil-dato"><strong>Sector:</strong> <span id="perfil-sector"></span></div>
    <div class="perfil-dato"><strong>Interno:</strong> <span id="perfil-interno"></span></div>
    <div class="perfil-dato"><strong>WhatsApp:</strong> <a id="perfil-wsp" href="#" target="_blank" class="perfil-link"></a></div>
    <div class="perfil-dato"><strong>Email:</strong> <a id="perfil-email" href="#" target="_blank" class="perfil-link"></a></div>
  </div>
</div>

<?php
// Si llega por GET (por URL)
$id_registro = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Si usás POST para cargar este archivo, podría ser
// $id_registro = isset($_POST['id_registro']) ? (int)$_POST['id_registro'] : 0;

// Validar que $id_registro tenga valor válido
if ($id_registro <= 0) {
    die("ID de registro inválido.");
}
?>
<!-- MODAL ENCARGADOS -->
<div id="modalEncargados" class="modal-perfil">
  <div class="modal-content">
    <button class="close-modal-encargados">&times;</button>
    <h3>Agregar encargado</h3>
    <form action="guardar_encargados.php" method="POST">
      <input type="hidden" name="id_registro" value="<?= htmlspecialchars($id_registro) ?>">

      <div class="form-group full-width">
        <label for="usuario_vinculado">Seleccionar usuario para agregar:</label>
      <select id="usuario_vinculado" name="usuario_vinculado" style="width: 100%;">
        <option value="">-- Seleccione un usuario --</option>
        <?php foreach ($usuarios as $u): ?>
          <option value="<?= htmlspecialchars($u['id_usuario']) ?>">
            <?= htmlspecialchars($u['usuario']) ?> (<?= htmlspecialchars($u['sector']) ?>)
          </option>
        <?php endforeach; ?>
      </select>

      </div>

      <button type="submit">Agregar encargado</button>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {
  $('.ver-perfil').click(function (e) {
    e.preventDefault();
    const usuario = $(this).data('usuario');

    $.ajax({
      url: 'obtener_perfil.php',
      method: 'POST',
      data: { usuario: usuario },
      dataType: 'json',
      success: function (data) {
        if (data.error) {
          alert('Error desde servidor: ' + data.error);
          return;
        }
        $('#perfil-usuario').text(data.usuario);
        $('#perfil-cargo').text(data.cargo);
        $('#perfil-sector').text(data.sector);
        $('#perfil-interno').text(data.interno);
        $('#perfil-wsp').attr('href', 'https://wa.me/54' + data.contacto).text(data.contacto);
        $('#perfil-email').attr('href', 'https://outlook.office.com/mail/deeplink/compose?to=' + encodeURIComponent(data.email)).text(data.email);

        // Mostrar modal agregando clase
        $('#modalPerfil').addClass('show');
      },
      error: function (jqXHR, textStatus, errorThrown) {
        alert('Error al cargar perfil: ' + textStatus + ' - ' + errorThrown);
        console.error('Detalles del error:', jqXHR.responseText);
      }
    });
  });

  $('.close-modal').click(function () {
    // Ocultar modal quitando clase
    $('#modalPerfil').removeClass('show');
  });

  $(window).click(function (e) {
    if ($(e.target).is('#modalPerfil')) {
      $('#modalPerfil').removeClass('show');
    }
  });
});
</script>

<script>
$(document).ready(function () {
  function inicializarSelect2() {
    if (!$('#usuarios_vinculados_modal').hasClass("select2-hidden-accessible")) {
      $('#usuarios_vinculados_modal').select2({
        placeholder: "Selecciona usuarios",
        width: '100%',
        dropdownParent: $('#modalEncargados .modal-content')
      });
    }
  }

  // Abrir modal y inicializar Select2
  $('.abrir-modal-encargados').click(function (e) {
    e.preventDefault();
    $('#modalEncargados').addClass('show');
    inicializarSelect2();
  });

  // Cerrar modal
  $('.close-modal-encargados').click(function () {
    $('#modalEncargados').removeClass('show');
  });

  // Cerrar modal si clickeas fuera del contenido
  $(window).on('click', function (e) {
    if ($(e.target).is('#modalEncargados')) {
      $('#modalEncargados').removeClass('show');
    }
  });
  
  // Inicializar Select2 en caso de que el modal ya esté visible (opcional)
  if ($('#modalEncargados').hasClass('show')) {
    inicializarSelect2();
  }
});
</script>

<script>
$(document).ready(function() {
  $('#usuario_vinculado').select2({
    placeholder: "Seleccione un usuario",
    width: '100%',
    allowClear: true
  });
});
</script>

</body>

</html>
