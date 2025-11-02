<?php
require_once 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

if(isset($_POST['html'])) {
  // Obtenemos el contenido HTML sin enlaces
  $html = $_POST['html'];
  $html = preg_replace('/<a[^>]*>(.*?)<\/a>/', '$1', $html);

  // Cargamos el contenido HTML en Dompdf
  $dompdf = new Dompdf();
  $dompdf->loadHtml($html);

  // Configuramos el tamaño y orientación de página
  $dompdf->setPaper('A4', 'landscape');

  // Renderizamos el PDF
  $dompdf->render();

  // Descargamos el archivo PDF
  $dompdf->stream('Tabla Fecha: ' . date('Y-m-d') . '.pdf', array('Attachment' => false));
}