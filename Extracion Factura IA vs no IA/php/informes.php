<?php
ob_start();
session_start();

// 🔒 SEGURIDAD
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

require '../db.php';

// --- CONSULTA SQL COMPLETA ---
// Seleccionamos TODOS los campos necesarios de ambas tablas
$sql = "SELECT 
            -- Datos Usuario
            u.id as user_id, u.username, u.nombre_completo, u.rol, 
            u.numerotel, u.dni, u.mail, u.ultimo_acceso, u.creado_en as usuario_creado,
            
            -- Datos Comprobante
            c.id as comp_id, c.archivo_nombre, c.archivo_ruta, c.fecha_carga,
            c.tipo_comprobante, c.punto_venta, c.numero_comprobante,
            c.fecha_emision, c.fecha_vencimiento,
            c.razon_social_emisor, c.cuit_emisor,
            c.razon_social_cliente, c.cuit_cliente,
            c.iva, c.otros_impuestos, c.total,
            c.texto_ocr
        FROM usuarios u
        INNER JOIN comprobantes c ON u.id = c.usuario_id
        ORDER BY u.nombre_completo ASC, c.fecha_carga DESC";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- PROCESAMIENTO ---
$reporte = [];
$datosParaExportar = [];

foreach ($rows as $row) {
    $uid = $row['user_id'];
    
    // 1. Agrupado para la VISTA (Acordeón)
    if (!isset($reporte[$uid])) {
        $reporte[$uid] = [
            'datos_usuario' => [
                'nombre' => $row['nombre_completo'],
                'username' => $row['username'],
                'dni' => $row['dni'] ?? '',
                'tel' => $row['numerotel'] ?? '',
                'mail' => $row['mail'] ?? ''
            ],
            'comprobantes' => []
        ];
    }
    $reporte[$uid]['comprobantes'][] = $row;

    // 2. Datos Planos para EXCEL (TODOS LOS CAMPOS)
    // Limpiamos el texto OCR de saltos de línea para que no rompa el CSV
    $ocrLimpio = str_replace(["\r", "\n", ";"], " ", $row['texto_ocr'] ?? '');
    
    $datosParaExportar[] = [
        'ID Usuario' => $row['user_id'],
        'Usuario' => $row['username'],
        'Nombre Completo' => $row['nombre_completo'],
        'Rol' => $row['rol'],
        'DNI' => $row['dni'] ?? '',
        'Mail' => $row['mail'] ?? '',
        'Teléfono' => $row['numerotel'] ?? '',
        'Último Acceso' => $row['ultimo_acceso'],
        'Usuario Creado' => $row['usuario_creado'],
        
        'ID Factura' => $row['comp_id'],
        'Fecha Carga' => $row['fecha_carga'],
        'Archivo Original' => $row['archivo_nombre'],
        'Ruta Archivo' => $row['archivo_ruta'],
        'Tipo Comp.' => $row['tipo_comprobante'],
        'Punto Venta' => $row['punto_venta'],
        'Número' => $row['numero_comprobante'],
        'Emisión' => $row['fecha_emision'],
        'Vencimiento' => $row['fecha_vencimiento'],
        
        'Emisor' => $row['razon_social_emisor'],
        'CUIT Emisor' => $row['cuit_emisor'],
        'Cliente' => $row['razon_social_cliente'],
        'CUIT Cliente' => $row['cuit_cliente'],
        
        'IVA' => str_replace('.', ',', $row['iva']), // Formato Excel
        'Otros Imp.' => str_replace('.', ',', $row['otros_impuestos']),
        'Total' => str_replace('.', ',', $row['total']),
        
        'Texto OCR' => substr($ocrLimpio, 0, 1000) // Limitamos largo por seguridad
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informes de Facturación</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/menu.css"> 
    
    <style>
        body { background-color: #f3f4f6; font-family: 'Inter', sans-serif; }
        .main-container { max-width: 1400px; margin: 90px auto 40px auto; padding: 0 15px; }

        .header-section { margin-bottom: 25px; }
        .header-section h2 { color: #1f2937; margin-bottom: 5px; }
        .header-section p { color: #6b7280; margin-bottom: 20px; }

        /* BARRA DE HERRAMIENTAS */
        .toolbar {
            display: flex; gap: 15px; margin-bottom: 25px; background: white;
            padding: 15px; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb; align-items: center; flex-wrap: wrap;
        }
        .search-box { flex: 1; position: relative; min-width: 250px; }
        .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
        .search-box input {
            width: 100%; padding: 12px 15px 12px 40px; border: 1px solid #d1d5db;
            border-radius: 8px; font-size: 1rem; outline: none; transition: border-color 0.2s;
        }
        .search-box input:focus { border-color: #2563eb; }

        .btn-export {
            background-color: #10b981; color: white; border: none; padding: 12px 20px;
            border-radius: 8px; font-weight: 600; cursor: pointer; display: flex;
            align-items: center; gap: 8px; transition: background 0.2s;
        }
        .btn-export:hover { background-color: #059669; }

        /* ACORDEÓN */
        .accordion-item {
            background: white; border-radius: 12px; margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); overflow: hidden;
            border: 1px solid #e5e7eb; transition: all 0.3s ease;
        }
        .accordion-header {
            padding: 20px; background: #fff; cursor: pointer; display: flex;
            align-items: center; justify-content: space-between; transition: background 0.2s;
        }
        .accordion-header:hover { background-color: #f9fafb; }
        .accordion-item.active { border-color: #2563eb; }
        .accordion-item.active .accordion-header { background-color: #eff6ff; border-bottom: 1px solid #dbeafe; }
        .accordion-item.active .chevron { transform: rotate(180deg); color: #2563eb; }

        /* DATOS USUARIO */
        .user-info { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; width: 100%; }
        .user-avatar {
            width: 45px; height: 45px; background: #dbeafe; color: #2563eb;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; font-weight: bold;
        }
        .user-details { flex: 1; display: flex; flex-direction: column; }
        .user-name { font-weight: 700; color: #1f2937; font-size: 1.1rem; }
        
        .user-meta { display: flex; gap: 10px; font-size: 0.85rem; color: #6b7280; margin-top: 4px; flex-wrap: wrap; }
        
        .meta-link {
            text-decoration: none; color: #6b7280; display: inline-flex;
            align-items: center; gap: 5px; padding: 4px 10px; border-radius: 6px;
            background-color: #f3f4f6; transition: 0.2s;
        }
        .meta-link:hover { background-color: #e5e7eb; color: #111827; }
        .meta-link.whatsapp:hover { background-color: #dcfce7; color: #166534; }
        .meta-link.email:hover { background-color: #dbeafe; color: #1e40af; }
        
        .chevron { transition: transform 0.3s; color: #9ca3af; font-size: 1.2rem; }
        .accordion-content { max-height: 0; overflow: hidden; transition: max-height 0.4s ease-out; background-color: #ffffff; }

        /* TABLA INTERNA */
        .table-wrapper { padding: 20px; overflow-x: auto; }
        .table-informe { width: 100%; border-collapse: collapse; font-size: 0.85rem; white-space: nowrap; }
        .table-informe th { background: #f8fafc; color: #475569; font-weight: 600; text-transform: uppercase; padding: 12px 15px; border-bottom: 2px solid #e2e8f0; text-align: left; }
        .table-informe td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
        .col-total { font-weight: 700; color: #059669; text-align: right; }
        .col-archivo a { display: inline-flex; align-items: center; gap: 5px; background: #eff6ff; color: #2563eb; padding: 5px 10px; border-radius: 6px; text-decoration: none; font-weight: 500; transition: 0.2s; }
        .col-archivo a:hover { background: #2563eb; color: white; }
        .badge-tipo { padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; background: #e2e8f0; color: #475569; }

        @media (max-width: 768px) {
            .toolbar { flex-direction: column; align-items: stretch; }
            .user-meta { flex-direction: column; gap: 8px; align-items: flex-start; }
            .accordion-header { align-items: flex-start; }
            .chevron { margin-top: 10px; }
        }
    </style>
</head>
<body>

<?php include '../menu.php'; ?>

<div class="main-container">
    <div class="header-section">
        <h2><i class="fa-solid fa-chart-pie"></i> Informes Completos</h2>
        <p>Visualiza y descarga la información detallada de todas las facturaciones.</p>
    </div>

    <div class="toolbar">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchInput" placeholder="Buscar usuario, DNI o mail...">
        </div>
        <button onclick="descargarExcel()" class="btn-export">
            <i class="fa-solid fa-file-csv"></i> Descargar Todo (Excel)
        </button>
    </div>

    <div class="accordion-container" id="accordionContainer">
        
        <?php foreach ($reporte as $uid => $data): ?>
            <?php 
                $u = $data['datos_usuario']; 
                $cant_facts = count($data['comprobantes']);
                $iniciales = strtoupper(substr($u['nombre'], 0, 1) . substr(strrchr($u['nombre'], ' '), 1, 1)); 
                if(!$iniciales) $iniciales = strtoupper(substr($u['username'], 0, 2));
                
                $wsp_clean = preg_replace('/[^0-9]/', '', $u['tel']); 
                $wsp_link = "https://wa.me/" . $wsp_clean;
            ?>
            
            <div class="accordion-item user-card" data-search="<?php echo strtolower($u['nombre'] . ' ' . $u['username'] . ' ' . $u['dni'] . ' ' . $u['mail']); ?>">
                <div class="accordion-header" onclick="toggleAccordion(this)">
                    <div class="user-info">
                        <div class="user-avatar"><?php echo $iniciales; ?></div>
                        <div class="user-details">
                            <div class="user-name">
                                <?php echo htmlspecialchars($u['nombre']); ?> 
                                <span style="font-weight: 400; color: #6b7280; font-size: 0.9rem;">(@<?php echo htmlspecialchars($u['username']); ?>)</span>
                            </div>
                            
                            <div class="user-meta">
                                <span class="meta-link" style="cursor: default;">
                                    <i class="fa-solid fa-file-invoice-dollar"></i> <strong><?php echo $cant_facts; ?></strong>
                                </span>
                                <?php if(!empty($u['dni'])): ?>
                                    <span class="meta-link" style="cursor: default;"><i class="fa-solid fa-id-card"></i> <?php echo htmlspecialchars($u['dni']); ?></span>
                                <?php endif; ?>
                                <?php if(!empty($u['tel'])): ?>
                                    <a href="<?php echo $wsp_link; ?>" target="_blank" class="meta-link whatsapp" title="WhatsApp" onclick="event.stopPropagation()"><i class="fa-brands fa-whatsapp"></i> <?php echo htmlspecialchars($u['tel']); ?></a>
                                <?php endif; ?>
                                <?php if(!empty($u['mail'])): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($u['mail']); ?>" class="meta-link email" title="Email" onclick="event.stopPropagation()"><i class="fa-regular fa-envelope"></i> <?php echo htmlspecialchars($u['mail']); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-down chevron"></i>
                </div>

                <div class="accordion-content">
                    <div class="table-wrapper">
                        <table class="table-informe">
                            <thead>
                                <tr>
                                    <th>Archivo</th>
                                    <th>Fecha Carga</th>
                                    <th>Tipo / Nro</th>
                                    <th>Fechas</th>
                                    <th>Emisor</th>
                                    <th>Cliente</th>
                                    <th>Impuestos</th>
                                    <th style="text-align: right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['comprobantes'] as $c): ?>
                                <tr>
                                    <td class="col-archivo">
                                        <?php if($c['archivo_ruta']): ?>
                                            <a href="<?php echo htmlspecialchars($c['archivo_ruta']); ?>" download target="_blank">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                        <?php else: ?> - <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($c['fecha_carga'])); ?></td>
                                    <td>
                                        <span class="badge-tipo"><?php echo htmlspecialchars($c['tipo_comprobante']); ?></span><br>
                                        <small style="color:#64748b"><?php echo $c['punto_venta'] . '-' . $c['numero_comprobante']; ?></small>
                                    </td>
                                    <td>
                                        <small>Emi: <?php echo $c['fecha_emision'] ? date('d/m/y', strtotime($c['fecha_emision'])) : '-'; ?></small><br>
                                        <small style="color:#dc2626">Vto: <?php echo $c['fecha_vencimiento'] ? date('d/m/y', strtotime($c['fecha_vencimiento'])) : '-'; ?></small>
                                    </td>
                                    <td>
                                        <div style="max-width: 150px; white-space: normal; font-weight:600;"><?php echo htmlspecialchars($c['razon_social_emisor'] ?? ''); ?></div>
                                        <small>CUIT: <?php echo htmlspecialchars($c['cuit_emisor'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <div style="max-width: 150px; white-space: normal;"><?php echo htmlspecialchars($c['razon_social_cliente'] ?? ''); ?></div>
                                        <small>CUIT: <?php echo htmlspecialchars($c['cuit_cliente'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <small>IVA: $<?php echo number_format($c['iva'], 2, ',', '.'); ?></small><br>
                                        <small>Otros: $<?php echo number_format($c['otros_impuestos'], 2, ',', '.'); ?></small>
                                    </td>
                                    <td class="col-total">$<?php echo number_format($c['total'], 2, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($reporte)): ?>
            <div style="text-align: center; padding: 40px; color: #6b7280;">No hay usuarios con datos cargados.</div>
        <?php endif; ?>

    </div>
</div>

<script>
    // --- 1. DATOS PARA EXPORTAR (JSON SEGURO) ---
    const datosReporte = <?php echo json_encode($datosParaExportar); ?>;

    // --- 2. BUSCADOR EN TIEMPO REAL ---
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const query = this.value.toLowerCase();
        const items = document.querySelectorAll('.accordion-item');

        items.forEach(item => {
            const text = item.getAttribute('data-search');
            if (text.includes(query)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });

    // --- 3. ACORDEÓN ---
    function toggleAccordion(header) {
        const item = header.parentElement;
        const content = item.querySelector('.accordion-content');
        item.classList.toggle('active');
        if (item.classList.contains('active')) {
            content.style.maxHeight = content.scrollHeight + "px";
        } else {
            content.style.maxHeight = null;
        }
    }

    // --- 4. EXPORTADOR CSV (Compatible Excel) ---
    function descargarExcel() {
        if (!datosReporte || datosReporte.length === 0) {
            alert("No hay datos para exportar.");
            return;
        }

        const headers = Object.keys(datosReporte[0]);
        const csvRows = [];
        
        // Cabecera con ; para Excel
        csvRows.push(headers.join(';')); 

        datosReporte.forEach(row => {
            const values = headers.map(header => {
                let val = row[header] === null || row[header] === undefined ? '' : String(row[header]);
                // Escapar comillas dobles y saltos de linea
                val = val.replace(/"/g, '""'); 
                return `"${val}"`;
            });
            csvRows.push(values.join(';'));
        });

        // BOM para UTF-8 (Vital para que Excel reconozca tildes y ñ)
        const csvString = "\uFEFF" + csvRows.join('\n'); 
        
        const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        
        const fecha = new Date().toISOString().slice(0,10);
        link.href = url;
        link.setAttribute('download', `Reporte_Total_Facturas_${fecha}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>

<?php ob_end_flush(); ?>
</body>
</html>