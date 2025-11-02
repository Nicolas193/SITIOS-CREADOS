<?php
session_start();

$mensaje = '';
$rutaExcel = __DIR__ . '/../archivo/nomina.xlsx';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivoExcel'])) {
    if ($_FILES['archivoExcel']['error'] === UPLOAD_ERR_OK) {
        if (move_uploaded_file($_FILES['archivoExcel']['tmp_name'], $rutaExcel)) {
            $mensaje = "Archivo subido correctamente. Importación en segundo plano iniciada.";
        } else {
            $mensaje = "Error al mover el archivo.";
        }
    } else {
        $mensaje = "Error en la subida del archivo.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<title>Subir archivo Nómina</title>
<style>
    body { font-family: Arial, sans-serif; padding: 20px; max-width: 600px; margin: auto; }
    .mensaje { font-weight: bold; margin-top: 10px; }
    .error { color: red; }
    .exito { color: green; }
</style>
</head>
<body>

<h1>Subir archivo Excel Nómina</h1>

<form method="post" enctype="multipart/form-data" id="formSubir">
    <label for="archivoExcel">Archivo Excel (.xlsx):</label>
    <input type="file" name="archivoExcel" id="archivoExcel" accept=".xlsx" required>
    <button type="submit">Subir Archivo</button>
</form>

<div id="mensaje" class="mensaje <?= (strpos($mensaje, 'Error') === 0) ? 'error' : 'exito' ?>">
    <?= htmlspecialchars($mensaje) ?>
</div>

<div id="progresoImportacion" style="display:none; margin-top: 20px;">
    <strong>Importación en progreso...</strong>
</div>

<p><a href="ver_nomina.php">Ver últimos 100 registros</a></p>

<script>
document.getElementById('formSubir').addEventListener('submit', function(event) {
    event.preventDefault();

    const formData = new FormData(this);

    // Limpiar mensajes previos
    document.getElementById('mensaje').textContent = '';
    document.getElementById('progresoImportacion').style.display = 'none';

    // Subir archivo por fetch
    fetch('', { method: 'POST', body: formData })
        .then(response => response.text())
        .then(html => {
            // Reemplazar el contenido de la página con la respuesta (opcional)
            // O solo mostrar mensaje y lanzar importación
            // Aquí vamos a suponer que la respuesta incluye el mensaje de subida
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            const nuevoMensaje = tempDiv.querySelector('#mensaje');
            if (nuevoMensaje) {
                document.getElementById('mensaje').textContent = nuevoMensaje.textContent;
                document.getElementById('mensaje').className = nuevoMensaje.className;
            }

            if (nuevoMensaje && nuevoMensaje.textContent.includes('Archivo subido correctamente')) {
                // Mostrar mensaje de progreso importación
                document.getElementById('progresoImportacion').style.display = 'block';

                // Lanzar importación en segundo plano (sin bloquear la UI)
                fetch('importar_nomina.php')
                    .then(res => res.text())
                    .then(text => {
                        document.getElementById('progresoImportacion').textContent = 'Importación completada.';
                    })
                    .catch(err => {
                        document.getElementById('progresoImportacion').textContent = 'Error en la importación: ' + err;
                    });
            }
        })
        .catch(error => {
            document.getElementById('mensaje').textContent = 'Error en la subida: ' + error;
            document.getElementById('mensaje').className = 'error';
        });
});
</script>

</body>
</html>
