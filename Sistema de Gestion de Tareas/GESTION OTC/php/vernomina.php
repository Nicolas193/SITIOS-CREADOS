<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("../../conexion.php");

ini_set('max_execution_time', 300);  // 5 min maximo
ini_set('memory_limit', '256M');     // limt de memoria
set_time_limit(300);                 // coincidir con max_execution_time



$directorioDestino  = __DIR__ . '/../archivo';
$nombreArchivoFinal = 'nomina.csv';
$rutaCSV = $directorioDestino . "/{$nombreArchivoFinal}";

// ----------- FUNCI -----------
function getUploadErrorMessage(int $errorCode): string {
    return match ($errorCode) {
        UPLOAD_ERR_INI_SIZE   => 'El archivo excede el tamaño máximo permitido por el servidor.',
        UPLOAD_ERR_FORM_SIZE  => 'El archivo excede el tamaño máximo permitido por el formulario.',
        UPLOAD_ERR_PARTIAL    => 'El archivo se subió parcialmente.',
        UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo para subir.',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal en el servidor.',
        UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en disco.',
        UPLOAD_ERR_EXTENSION  => 'Una extensión de PHP detuvo la subida.',
        default               => 'Error desconocido (código: ' . $errorCode . ').',
    };
}

function detectarDelimitador(string $archivo): string {
    $f = fopen($archivo, 'r');
    $linea = fgets($f);
    fclose($f);

    $c = [
        ','  => substr_count($linea, ','),
        ';'  => substr_count($linea, ';'),
        "\t" => substr_count($linea, "\t")
    ];
    return array_search(max($c), $c);
}

function importarCSV(string $rutaArchivo): int {
    $raw = file_get_contents($rutaArchivo);
    $encoding = mb_detect_encoding($raw, ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1', 'Windows-1252'], true);
    if ($encoding !== 'UTF-8') {
        $raw = mb_convert_encoding($raw, 'UTF-8', $encoding);
    }
    $tmp = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($tmp, $raw);

    $delim = detectarDelimitador($tmp);

    $mysqli = conectar();
    $mysqli->set_charset('utf8mb4');

    if (!$mysqli->query('TRUNCATE TABLE nominaddjj')) {
        throw new RuntimeException('No se pudo truncar la tabla: ' . $mysqli->error);
    }

    $stmt = $mysqli->prepare('
        INSERT INTO nominaddjj (lp, grado, apellido, nombre, dni, correo, telasignado, dependencia)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');
    if (!$stmt) {
        throw new RuntimeException('Error al preparar la consulta: ' . $mysqli->error);
    }

    $handle = fopen($tmp, 'r');
    if (!$handle) {
        throw new RuntimeException('No se pudo abrir el archivo CSV.');
    }

    $fila = 0;
    $insertadas = 0;
    $batchSize = 1000;
    $mysqli->begin_transaction();

    while (($data = fgetcsv($handle, 0, $delim)) !== false) {
        $fila++;
        if ($fila === 1) continue; // Saltar encabezado

        $data += array_fill(0, 15, '');

        [$lp, $grado, $apellido, $nombre] = array_map('trim', array_slice($data, 0, 4));
        $dni         = trim($data[10]);
        $correo      = trim($data[13]);
        $telasignado = trim($data[14]);
        $dependencia = trim($data[8]);

        if ($lp === '' && $apellido === '' && $nombre === '') continue;

        $stmt->bind_param('ssssssss', $lp, $grado, $apellido, $nombre, $dni, $correo, $telasignado, $dependencia);
        if (!$stmt->execute()) {
            throw new RuntimeException("Error al insertar fila {$fila}: " . $stmt->error);
        }

        $insertadas++;

        if ($insertadas % $batchSize === 0) {
            $mysqli->commit();
            $mysqli->begin_transaction();
        }
    }

    $mysqli->commit();

    fclose($handle);
    unlink($tmp);
    $stmt->close();
    $mysqli->close();

    return $insertadas;
}



function cargarDatos(): array {
    $mysqli = conectar();
    $datos = [];
    $query = 'SELECT lp, grado, apellido, nombre, dni, correo, telasignado, dependencia
              FROM nominaddjj
              ORDER BY lp DESC
              LIMIT 100';
    if ($result = $mysqli->query($query)) {
        while ($row = $result->fetch_assoc()) {
            $datos[] = $row;
        }
        $result->free();
    }
    $mysqli->close();
    return $datos;
}

// ------ MANEJO POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivoCSV'])) {
    header('Content-Type: application/json; charset=utf-8');

    if (!is_dir($directorioDestino) && !mkdir($directorioDestino, 0775, true)) {
        echo json_encode(['error' => true, 'mensaje' => 'No se pudo crear el directorio.']);
        exit;
    }

    if (!is_writable($directorioDestino)) {
        echo json_encode(['error' => true, 'mensaje' => 'No hay permisos de escritura en el directorio.']);
        exit;
    }

    $file = $_FILES['archivoCSV'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => true, 'mensaje' => getUploadErrorMessage($file['error'])]);
        exit;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($extension !== 'csv') {
        echo json_encode(['error' => true, 'mensaje' => 'Solo se aceptan archivos CSV.']);
        exit;
    }

    if (!move_uploaded_file($file['tmp_name'], $rutaCSV)) {
        echo json_encode(['error' => true, 'mensaje' => 'Error al mover el archivo.']);
        exit;
    }

try {
    $insertadas = importarCSV($rutaCSV);
    echo json_encode([
        'error' => false,
        'mensaje' => "✅ Archivo importado correctamente. Filas insertadas: {$insertadas}"
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'error' => true,
        'mensaje' => '❌ Error durante la importación: ' . $e->getMessage()
    ]);
}


    exit;
}

// ------------- MANEJO GET ---------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_SESSION['username']) || !isset($_SESSION['sector']) || strtolower($_SESSION['sector']) !== 'goci') {
        header("Location: cartelaccesodenegado.php");
        exit();
    }

    require_once("../../menu.php");

    $fechaModificacion = file_exists($rutaCSV) ? date("d/m/Y H:i:s", filemtime($rutaCSV)) : 'Nunca';
    $datos = cargarDatos();
    ?>

    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8" />
        <link rel="shortcut icon" href="../../imagen/presentacion.ico" />
        <title>Ver Nómina y Subir CSV</title>
        <link rel="stylesheet" href="../css/ddjjconsulta.css" />
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    </head>
    <body>
    <div class="contenedor">
        <div class="importancion">
            <h1>Ver Nómina - Últimos 100 Registros</h1>

            <form id="formSubir" enctype="multipart/form-data" method="post">
                <label for="archivoCSV">Seleccionar archivo CSV:</label>
                <input type="file" name="archivoCSV" id="archivoCSV" accept=".csv" required />
                <button type="submit">Subir e Importar</button>
            </form>

            <p>Última actualización del archivo CSV: <strong><?= htmlspecialchars($fechaModificacion, ENT_QUOTES) ?></strong></p>

            <div id="mensaje"></div>
            <div id="progresoImportacion" style="display:none;"></div>

            <div class="importanciontablero" style="padding-left: 220px;">
                <table id="tablaNomina">
                    <thead>
                        <tr>
                            <th>LP</th><th>Grado</th><th>Apellido</th><th>Nombre</th><th>DNI</th><th>Correo</th><th>Tel. Asignado</th><th>Dependencia</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($datos as $fila): ?>
                        <tr>
                            <td><?= htmlspecialchars($fila['lp']) ?></td>
                            <td><?= htmlspecialchars($fila['grado']) ?></td>
                            <td><?= htmlspecialchars($fila['apellido']) ?></td>
                            <td><?= htmlspecialchars($fila['nombre']) ?></td>
                            <td><?= htmlspecialchars($fila['dni']) ?></td>
                            <td><?= htmlspecialchars($fila['correo']) ?></td>
                            <td><?= htmlspecialchars($fila['telasignado']) ?></td>
                            <td><?= htmlspecialchars($fila['dependencia']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<script>
/* ---------- utilidades ---------- */
function escapeHTML (text) {
    return text
        .replace(/&/g,  "&amp;")
        .replace(/</g,  "&lt;")
        .replace(/>/g,  "&gt;")
        .replace(/"/g,  "&quot;")
        .replace(/'/g,  "&#039;");
}


async function fetchJsonSafe (url, opts = {}) {
    const res  = await fetch(url, opts);
    const ctype = res.headers.get("content-type") || "";

    // obtenemos la respuesta como texto siempre
    const raw = await res.text();

    if (ctype.includes("application/json")) {
        try {
            return { ok: true, data: JSON.parse(raw), raw };
        } catch (e) {
            // JSON mal formado
            return { ok: false, data: null, raw };
        }
    }
    // no era JSON
    return { ok: false, data: null, raw };
}

/* ---------- refrescar tabla ---------- */
async function refrescarTabla () {
    const tablaBody  = document.querySelector("#tablaNomina tbody");
    const mensajeDiv = document.getElementById("mensaje");

    try {
        const { ok, data, raw } = await fetchJsonSafe("cargar_nomina.php");

        if (!ok) {
            throw new Error("La respuesta del servidor no es JSON válido.\n" + raw.slice(0, 300));
        }


        tablaBody.innerHTML = "";

        for (const fila of data) {
            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td>${escapeHTML(fila.lp)}</td>
                <td>${escapeHTML(fila.grado)}</td>
                <td>${escapeHTML(fila.apellido)}</td>
                <td>${escapeHTML(fila.nombre)}</td>
                <td>${escapeHTML(fila.dni)}</td>
                <td>${escapeHTML(fila.correo)}</td>
                <td>${escapeHTML(fila.telasignado)}</td>
                <td>${escapeHTML(fila.dependencia)}</td>
            `;
            tablaBody.appendChild(tr);
        }
    } catch (err) {
        mensajeDiv.textContent = "Error al actualizar la tabla: " + err.message;
        mensajeDiv.className   = "error";
    }
}

/* ---------- subida del CSV ---------- */
document.getElementById("formSubir").addEventListener("submit", async function (e) {
    e.preventDefault();

    const boton       = this.querySelector('button[type="submit"]');
    const mensajeDiv  = document.getElementById("mensaje");
    const progresoDiv = document.getElementById("progresoImportacion");

    /* guardar y modificar estado del botón */
    const textoOriginal = boton.textContent;
    boton.disabled = true;
    boton.textContent = "Subiendo…";

    /* feedback inicial */
    mensajeDiv.textContent = "";
    mensajeDiv.className   = "";
    progresoDiv.style.display = "block";
    progresoDiv.textContent   = "Subiendo archivo e importando…";

    try {
        const formData = new FormData(this);
        const { ok, data, raw } = await fetchJsonSafe("", {
            method: "POST",
            body  : formData
        });

        progresoDiv.style.display = "none";

        if (!ok) {
            throw new Error("Respuesta inesperada del servidor.\n" + raw.slice(0, 300));
        }

        mensajeDiv.textContent = data.mensaje || "Proceso finalizado.";
        mensajeDiv.className   = data.error ? "error" : "exito";

        if (!data.error) {
            await refrescarTabla();
        }
    } catch (err) {
        mensajeDiv.textContent = "Error inesperado: " + err.message;
        mensajeDiv.className   = "error";
    } finally {
        /* restauramos botón siempre */
        boton.disabled  = false;
        boton.textContent = textoOriginal;
        progresoDiv.style.display = "none";
    }
});
</script>

    </body>
    </html>

<?php } ?>
