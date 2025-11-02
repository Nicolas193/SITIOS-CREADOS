<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
     <link rel="stylesheet" href="../../Estilos/EstiloCompartidos.css">
    <title>Navegador de Carpeta Compartida</title>

</head>
<body>
    <h1>Navegador de Carpeta Compartida</h1>

    <?php
    // Ruta base de la carpeta compartida
    $baseDir = '\\\\br-wp-fsmin02.ministerio.seguridadciudad.gob.ar\\tablerostableau$\\';

    // Obtiene la ruta actual
    $currentDir = isset($_GET['dir']) ? $_GET['dir'] : $baseDir;

    // Verifica si la ruta actual es válida
    if (!is_dir($currentDir)) {
        echo "<p>El directorio no es válido.</p>";
        exit;
    }

    // Muestra el enlace para volver al directorio superior
    if ($currentDir != $baseDir) {
        $parentDir = dirname($currentDir);
        echo "<p><a href='?dir=" . urlencode($parentDir) . "'>Volver al directorio superior</a></p>";
    }

    // Carga de archivos
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fileToUpload'])) {
        $uploadFile = $currentDir . DIRECTORY_SEPARATOR . basename($_FILES['fileToUpload']['name']);
        if (move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $uploadFile)) {
            echo "<p>Archivo cargado exitosamente.</p>";
        } else {
            echo "<p>Error al cargar el archivo.</p>";
        }
    }

    // Eliminación de archivos
    if (isset($_GET['delete'])) {
        $deleteFile = $currentDir . DIRECTORY_SEPARATOR . $_GET['delete'];

        // Debug: muestra la ruta completa del archivo
        echo "Ruta del archivo para eliminar: $deleteFile<br>";

        // Verifica si el archivo existe
        if (file_exists($deleteFile)) {
            if (unlink($deleteFile)) {
                echo "<p>Archivo eliminado exitosamente.</p>";
            } else {
                echo "<p>Error al eliminar el archivo.</p>";
            }
        } else {
            echo "<p>El archivo no existe.</p>";
        }
    }

    // Muestra el formulario para cargar archivos
    echo '<div class="form-container">
            <h2>Cargar un nuevo archivo</h2>
            <form action="" method="post" enctype="multipart/form-data">
                <input type="file" name="fileToUpload" required>
                <input type="submit" value="Cargar Archivo">
            </form>
          </div>';

    // Muestra el contenido del directorio
    if ($dh = opendir($currentDir)) {
        echo "<table><thead><tr><th>Nombre</th><th>Tamaño</th><th>Última Modificación</th><th>Acciones</th></tr></thead><tbody>";

        // Lee los archivos y carpetas
        while (($file = readdir($dh)) !== false) {
            // Ignora los directorios "." y ".."
            if ($file != "." && $file != "..") {
                $filePath = $currentDir . DIRECTORY_SEPARATOR . $file;
                $isDir = is_dir($filePath);
                echo "<tr>";
                echo "<td class='" . ($isDir ? "folder" : "") . "'>";
                echo $isDir ? "<a href='?dir=" . urlencode($filePath) . "'>$file</a>" : $file;
                echo "</td>";
                echo "<td>";
                echo $isDir ? "-" : number_format(filesize($filePath) / 1048576, 2) . " MB";
                echo "</td>";
                echo "<td>";
                echo $isDir ? "-" : date("d/m/Y H:i", filemtime($filePath));
                echo "</td>";
                echo "<td>";
                if (!$isDir) {
                    echo "<a href='download.php?file=" . urlencode($file) . "&dir=" . urlencode($currentDir) . "'>Descargar</a> | ";
                    echo "<a href='?dir=" . urlencode($currentDir) . "&delete=" . urlencode($file) . "' onclick='return confirm(\"¿Estás seguro de que deseas eliminar este archivo?\")'>Eliminar</a>";
                }
                echo "</td>";
                echo "</tr>";
            }
        }
        closedir($dh);

        echo "</tbody></table>";
    } else {
        echo "<p>No se puede abrir el directorio.</p>";
    }
    ?>
</body>
</html>
