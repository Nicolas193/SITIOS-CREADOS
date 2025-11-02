<?php
// MANEJO DE LA SUBIDA DE ARCHIVOS
if (isset($_FILES["fileToUpload"])) {
    $target_dir = $dir . DIRECTORY_SEPARATOR;
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    // Comprobar si el archivo ya existe
    if (file_exists($target_file)) {
        echo "El archivo ya existe.";
        $uploadOk = 0;
    }

    // Limitar el tipo de archivo si es necesario
    // Aquí puedes agregar tus propias restricciones de tipos de archivo si lo deseas
    if($imageFileType != "txt" && $imageFileType != "pdf" && $imageFileType != "docx" && $imageFileType != "xlsx" && $imageFileType != "pptx" && $imageFileType != "csv") {
        echo "Solo se permiten archivos TXT, PDF, DOCX, XLSX, PPTX., CSV";
        $uploadOk = 0;
    }

    // Verificar si $uploadOk está configurado en 0 por algún error
    if ($uploadOk == 0) {
        echo "El archivo no se pudo subir.";
    } else {
        // Intentar subir el archivo
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            echo "El archivo ". htmlspecialchars( basename( $_FILES["fileToUpload"]["name"])). " ha sido subido.";
        } else {
            echo "Hubo un error al subir el archivo.";
        }
    }
}
?>