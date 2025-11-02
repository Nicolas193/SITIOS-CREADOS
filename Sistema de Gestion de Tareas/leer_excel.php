<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$archivo = 'ejemplo.xlsx'; // archivo de prueba
$spreadsheet = IOFactory::load($archivo);
$hoja = $spreadsheet->getActiveSheet();
echo $hoja->getCell('A1')->getValue();
