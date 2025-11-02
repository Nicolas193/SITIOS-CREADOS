<?php 
include("Backup Cl comunicaciones br-fs informacion.php"); 
include("insertardatos.php"); 

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="../Estilos/EstiloCompartidos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="shortcut icon" href="../imagen/presentacion.ico" />
    <title>Grupos</title>
</head>
<body>
   <?php 
  include("menu.php"); 
  ?>
    <div class="container">
        <h1>Carpeta Compartida Backup Cl comunicaciones br-fs</h1>
    <a href="https://outlook.office365.com/" class="email-link" target="_blank">
        <span>Correo Electrónico</span>
        <i class="far fa-envelope icon"></i> 
    </a>
        <div style="display: flex; padding: 20px;">
            <form method="get">
                <input type="hidden" name="dir" value="<?php echo htmlspecialchars($dir, ENT_QUOTES); ?>">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>" placeholder="Buscar archivos...">
                <input type="submit" value="Buscar">
            </form>
            <form method="post" enctype="multipart/form-data" style="margin-right: 10px; padding-left: 20px;">
                <input type="hidden" name="dir" value="<?php echo htmlspecialchars($dir, ENT_QUOTES); ?>">
                <input type="file" name="fileToUpload" id="fileToUpload">
                <input type="submit" value="Subir archivo" name="submit">
            </form>
        </div>
        <?php if ($dir != '\\\\10.70.150.4\\Backup CI comunicaciones br-fs\\'): ?>
            <button onclick="window.history.back();">Atras</button>
        <?php endif; ?>

        <br><br>
        <table>
            <thead>
                <tr>
                    <th>Nombre del Archivo</th>
                    <th>Última Modificación</th>
                    <th>Tamaño</th>
                </tr>
            </thead>
            <tbody>
                <?php if (is_array($files)): ?>
                    <?php foreach ($files as $file): ?>
                        <?php if ($file != "." && $file != ".."): ?>
                            <?php $fileInfo = getFileSizeAndDate($file); ?>
                            <tr>
                                <td>
                                    <span class="icon"><?php echo getFileIcon($file); ?></span>
                                    <?php if (!is_dir($dir . DIRECTORY_SEPARATOR . $file)): ?>
                                        <a href="?dir=<?php echo urlencode($dir) . '&download=' . urlencode($file); ?>"><?php echo htmlspecialchars($file, ENT_QUOTES); ?></a>
                                    <?php else: ?>
                                        <a href="?dir=<?php echo urlencode($dir . DIRECTORY_SEPARATOR . $file); ?>"><?php echo htmlspecialchars($file, ENT_QUOTES); ?></a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $fileInfo['modified_date']; ?></td>
                                <td>
                                    <?php 
                                    if ($fileInfo['size'] != '0 bytes') {
                                        echo $fileInfo['size'];
                                    } 
                                    ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">No hay archivos disponibles.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
