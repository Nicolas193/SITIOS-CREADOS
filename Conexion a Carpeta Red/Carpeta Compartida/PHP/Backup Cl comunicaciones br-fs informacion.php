<?php

// MANEJO DE LA EXTRACCION DE INFORMACION
$dir = isset($_GET['dir']) ? $_GET['dir'] : '\\\\10.70.150.4\\Backup CI comunicaciones br-fs\\';
$search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    $files = @scandir($dir); // Suprimir el warning de directorio no válido
    if ($files === false) {
        throw new Exception("El directorio '$dir' no es válido.");
    }
    $files = array_filter($files, function($file) use ($search) {
        return stripos($file, $search) !== false;
    });

} catch (Exception $e) {
    echo "Error al escanear el directorio: " . $e->getMessage();
}


function getFileIcon($file) {
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    switch($extension) {
        case 'csv':
        case 'xlsx':
        case 'xls':
        case 'xlsm':
            return '&#x1F4CA;'; // Icono de Excel
        case 'txt':
            return '&#128462;'; // Icono de texto
        case 'pptx':
            return '&#128470;'; // Icono de presentación (PowerPoint)
        case 'docx':
            return '&#128221;'; // Icono de Word
        case 'png':
        case 'jpg':
        case 'jpeg':
        case 'gif':
            return '&#128443;'; // Icono de imagen
        case 'ini':
            return '&#128199;'; // Icono de archivo de configuración
        case 'pdf':
            return '&#128194;'; // Icono de PDF
        default:
            return '&#128193;'; // Icono predeterminado para otros tipos de archivo
    }
}

// Verificar si se solicitó la descarga de un archivo
if (isset($_GET['download'])) {
    $file = $_GET['download'];
    $filepath = $dir . DIRECTORY_SEPARATOR . $file;
    if (file_exists($filepath) && !is_dir($filepath)) { // Asegurarse de que no sea un directorio
        // Encabezado para forzar la descarga del archivo
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        ob_clean(); // Limpiar el búfer de salida
        flush(); // Vaciar el búfer de salida
        readfile($filepath);
        exit;
    } else {
        echo "El archivo solicitado no existe o es un directorio.";
    }
}


function formatSizeUnits($bytes){
    if ($bytes >= 1073741824){
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576){
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024){
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1){
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1){
        $bytes = $bytes . ' byte';
    } else{
        $bytes = '0 bytes';
    }
    return $bytes;
}

function getFileSizeAndDate($file) {
    $filePath = $GLOBALS['dir'] . DIRECTORY_SEPARATOR . $file;
    $size = filesize($filePath);
    $modifiedDate = date("Y-m-d H:i:s", filemtime($filePath));
    return array('size' => formatSizeUnits($size), 'modified_date' => $modifiedDate);
}
?>