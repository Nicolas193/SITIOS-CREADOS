<?php
session_start();

// 🔒 SEGURIDAD: Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require '../db.php';

$usuario_id = $_SESSION['user_id'];

// --- CONSULTA SQL ---
// Traemos solo los comprobantes de ESTE usuario, ordenados por fecha de carga (el más nuevo primero)
$sql = "SELECT * FROM comprobantes WHERE usuario_id = ? ORDER BY fecha_carga DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario_id]);
$comprobantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Cargas</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../css/menu.css"> 
    <link rel="stylesheet" href="../css/usuarios.css"> 

    <style>
        /* AJUSTES ESPECÍFICOS PARA ESTA TABLA */
        
        /* En escritorio, ocultamos el nombre del archivo si es muy largo */
        .archivo-nombre {
            max-width: 150px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
            vertical-align: middle;
        }

        /* --- ETIQUETAS PARA MÓVIL (SOBREESCRIBIMOS LAS DE USUARIOS) --- */
        @media (max-width: 900px) {
            /* Ajustamos las etiquetas (labels) de las tarjetas para que coincidan con Facturas */
            .table-usuarios td:nth-of-type(1)::before { content: "Fecha Carga"; }
            .table-usuarios td:nth-of-type(2)::before { content: "Comprobante"; }
            .table-usuarios td:nth-of-type(3)::before { content: "Emisor"; }
            .table-usuarios td:nth-of-type(4)::before { content: "Total"; }
            .table-usuarios td:nth-of-type(5)::before { content: "Archivo"; }
            
            /* Ajuste para que el total se vea destacado en móvil */
            .col-total {
                font-size: 1.1rem;
                font-weight: bold;
                color: #2563eb;
            }
        }
    </style>
</head>
<body>

<?php include '../menu.php'; ?>

<div class="main-container">
    <div class="header-section">
        <h2><i class="fa-solid fa-list-check"></i> Mis Cargas Realizadas</h2>
        <p>Historial de todos los comprobantes que has subido al sistema.</p>
    </div>

    <div class="card table-card">
        <div class="table-responsive">
            <table class="table-usuarios">
                <thead>
                    <tr>
                        <th>Fecha Carga</th>
                        <th>Tipo / Nro</th>
                        <th>Emisor (Vendedor)</th>
                        <th>Total ($)</th>
                        <th>Archivo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($comprobantes) > 0): ?>
                        <?php foreach ($comprobantes as $c): ?>
                            <tr>
                                <td>
                                    <span style="color: #6b7280; font-weight: 500;">
                                        <?php echo date('d/m/Y H:i', strtotime($c['fecha_carga'])); ?>
                                    </span>
                                </td>

                                <td>
                                    <div style="font-weight: 600; color: #374151;">
                                        <?php echo htmlspecialchars($c['tipo_comprobante'] ?? 'N/A'); ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: #9ca3af;">
                                        <?php 
                                            $pv = $c['punto_venta'] ? str_pad($c['punto_venta'], 4, '0', STR_PAD_LEFT) : '0000';
                                            $num = $c['numero_comprobante'] ? str_pad($c['numero_comprobante'], 8, '0', STR_PAD_LEFT) : '00000000';
                                            echo "$pv-$num";
                                        ?>
                                    </div>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($c['razon_social_emisor'] ?? 'Desconocido'); ?>
                                    <div style="font-size: 0.8rem; color: #9ca3af;">
                                        CUIT: <?php echo htmlspecialchars($c['cuit_emisor'] ?? '-'); ?>
                                    </div>
                                </td>

                                <td class="col-total">
                                    $ <?php echo number_format($c['total'], 2, ',', '.'); ?>
                                </td>

                                <td>
                                    <?php if (!empty($c['archivo_ruta'])): ?>
                                        <a href="<?php echo htmlspecialchars($c['archivo_ruta']); ?>" 
                                           class="btn-icos n edit" 
                                           download 
                                           target="_blank"
                                           title="Descargar <?php echo htmlspecialchars($c['archivo_nombre']); ?>"
                                           style="text-decoration: none; display: inline-flex; align-items: center; gap: 5px; font-size: 0.9rem; padding: 8px 12px;">
                                            <i class="fa-solid fa-download"></i> 
                                            <span class="archivo-nombre"><?php echo htmlspecialchars($c['archivo_nombre']); ?></span>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #9ca3af;">Sin archivo</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px;">
                                <i class="fa-solid fa-folder-open" style="font-size: 3rem; color: #e5e7eb; margin-bottom: 10px;"></i>
                                <p style="color: #6b7280;">Aún no has cargado ningún comprobante.</p>
                                <a href="comprobante.php" class="btn-main" style="display: inline-block; width: auto; margin-top: 10px;">
                                    Cargar Ahora
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>