<?php
// Ruta base de la carpeta compartida
$baseDir = '\\\\br-wp-fsmin02.ministerio.seguridadciudad.gob.ar\\sgc$\\';

// Verifica si el parámetro 'file' y 'dir' están presentes en la URL
if (isset($_GET['file']) && isset($_GET['dir'])) {
    $file = basename($_GET['file']); // Sanitiza el nombre del archivo
    $dir = rtrim($_GET['dir'], '\\/'); // Sanitiza la ruta del directorio
    $filePath = $dir . DIRECTORY_SEPARATOR . $file;

    // Debug: muestra la ruta completa del archivo
    echo "Ruta del archivo para descargar: $filePath<br>";

    // Verifica si el archivo existe
    if (file_exists($filePath)) {
        // Define el tipo de contenido
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    } else {
        echo "El archivo no existe.";
    }
} else {
    echo "No se especificó un archivo o directorio.";
}
?>
