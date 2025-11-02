    <?php
    $archivos = explode(',', $row['archivos']);
    if (!empty($archivos)) {
        echo "<ul>";
        foreach ($archivos as $archivo) {
            $nombre_archivo = basename($archivo);
            $directorio_escritorio = "C:/Users/TuUsuario/Desktop/{$_SESSION['username']}/";
            $ruta_archivo = $directorio_escritorio . $nombre_archivo;

            // Verificar si el archivo ya existe en el escritorio
            if (file_exists($ruta_archivo)) {
                // Si el archivo ya existe, mostrar un mensaje indicándolo
                echo "<li>El archivo <strong>$nombre_archivo</strong> ya existe en tu escritorio.</li>";
            } else {
                // Si el archivo no existe, copiarlo al escritorio
                if (copy($archivo, $ruta_archivo)) {
                    // Mostrar un mensaje indicando que se copió exitosamente
                    echo "<li>El archivo <strong>$nombre_archivo</strong> se ha guardado en tu escritorio.</li>";
                } else {
                    // Mostrar un mensaje de error si no se pudo copiar el archivo
                    echo "<li>Error al guardar el archivo <strong>$nombre_archivo</strong> en tu escritorio.</li>";
                }
            }
        }
        echo "</ul>";
    }
    ?>