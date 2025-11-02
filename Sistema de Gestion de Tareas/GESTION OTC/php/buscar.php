<?php
// ----------------- CONFIG INICIAL -----------------
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();
session_start();

include("../../conexion.php");
$con = conectar();
include("../../AutenticadorUser.php");

header('Content-Type: application/json; charset=utf-8');

// ----------------- STOPWORDS -----------------
$stopwords = [
    'la','los','las','el','de','y','en','a','que','con','por','para','un','una','unos','unas',
    'al','del','se','es','su','como','más','pero','o','sus'
];

// ----------------- ENTRADA -----------------
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);

if (!is_array($input) || !isset($input['palabra'])) {
    echo json_encode(['error' => 'No se recibió la clave "palabra".']);
    exit;
}

$tabla_directa = $input['tabla'] ?? null;
$frase = trim(mb_strtolower($input['palabra']));

if ($frase === '') {
    echo json_encode(['error' => 'La palabra de búsqueda está vacía.']);
    exit;
}

// ----------------- FUNCIONES -----------------
function limpiar_palabras_clave($frase, $stopwords) {
    $palabras = preg_split('/\s+/', $frase);
    $palabras_clave = array_filter($palabras, fn($p) => $p !== '' && !in_array($p, $stopwords));
    return array_values($palabras_clave);
}

function buscar($con, $sql, $types, $params) {
    $st = $con->prepare($sql);
    if (!$st) return [];
    if (!call_user_func_array([$st, 'bind_param'], array_merge([$types], $params))) return [];
    if (!$st->execute()) return [];
    $res = $st->get_result();
    $out = [];
    while ($r = $res->fetch_assoc()) $out[] = $r;
    $st->close();
    return $out;
}

function similitud($a, $b) {
    similar_text(mb_strtolower($a), mb_strtolower($b), $perc);
    return $perc;
}

// ----------------- TABLAS -----------------
$tablas = [
    'pregunta' => [
        'sql' => "SELECT respuesta, pregunta FROM preguntas WHERE LOWER(pregunta) LIKE ? OR LOWER(respuesta) LIKE ? LIMIT 50",
        'params_count' => 2,
        'columns' => ['respuesta'],
        'similitud_cols' => ['pregunta'],
        'fmt' => "%s"
    ],
    'usuario' => [
        'sql' => "SELECT COALESCE(usuario, '') AS usuario, COALESCE(contacto, '') AS contacto, COALESCE(cargo, '') AS cargo, COALESCE(CAST(interno AS CHAR), '') AS interno, COALESCE(email, '') AS email, COALESCE(sector, '') AS sector
                  FROM usuarios
                  WHERE LOWER(COALESCE(usuario, '')) LIKE ? OR LOWER(COALESCE(sector, '')) LIKE ? OR LOWER(COALESCE(cargo, '')) LIKE ? OR LOWER(COALESCE(contacto, '')) LIKE ? OR LOWER(CAST(COALESCE(interno, '') AS CHAR)) LIKE ? OR LOWER(COALESCE(email, '')) LIKE ?
                  LIMIT 50",
        'params_count' => 6,
        'columns' => ['usuario', 'contacto', 'cargo', 'interno', 'email', 'sector'],
        'fmt' => "Usuario: %s, Contacto: %s, Cargo: %s, Interno: %s, Email: %s, Sector: %s"
    ],
    'url' => [
        'sql' => "SELECT nombre_url, url, descripcion FROM urls WHERE LOWER(nombre_url) LIKE ? OR LOWER(descripcion) LIKE ? LIMIT 50",
        'params_count' => 2,
        'columns' => ['nombre_url', 'url', 'descripcion'],
        'fmt' => "Nombre: %s, URL: %s, Descripción: %s"
    ],
    'tareas' => [
        'sql' => "SELECT DATE_FORMAT(rdt.fecha_solicitud, '%d/%m/%Y') AS fecha_solicitud, DATE_FORMAT(rdt.plazo_entrega, '%d/%m/%Y') AS plazo_entrega, COALESCE(u_responsable.usuario, '') AS responsable, COALESCE(rdt.asunto, '') AS descripcion, COALESCE(t.nombre_tarea, '') AS tipo_tarea, COALESCE(u_dirigido.usuario, '') AS dirigido_a, COALESCE(CAST(uv.id_persona_asignada AS CHAR), '') AS id_persona_asignada, COALESCE(e.nombre_estado, '') AS estado_texto
                  FROM registro_de_tareas AS rdt
                  LEFT JOIN usuarios AS u_dirigido ON u_dirigido.id_usuario = rdt.id_usuario_rest
                  LEFT JOIN usuarios AS u_responsable ON u_responsable.id_usuario = rdt.id_usuario_rest
                  LEFT JOIN tareas AS t ON t.id_tarea = rdt.id_tarea
                  LEFT JOIN usuarios_vinculados AS uv ON uv.id_registro = rdt.id_registro
                  LEFT JOIN estado_tarea AS et ON et.id_registro = rdt.id_registro
                  LEFT JOIN estados AS e ON e.id_estado = et.id_estado
                  WHERE LOWER(COALESCE(u_dirigido.usuario, '')) LIKE ? OR LOWER(COALESCE(e.nombre_estado, '')) LIKE ? OR LOWER(COALESCE(rdt.asunto, '')) LIKE ? OR LOWER(COALESCE(t.nombre_tarea, '')) LIKE ? OR CAST(COALESCE(uv.id_persona_asignada, '') AS CHAR) LIKE ?
                  GROUP BY rdt.id_registro
                  ORDER BY MAX(et.fecha_actualizacion) DESC
                  LIMIT 50",
        'params_count' => 5,
        'columns' => ['responsable','dirigido_a','descripcion','tipo_tarea','estado_texto','fecha_solicitud','plazo_entrega','id_persona_asignada'],
        'fmt' => "Responsable: %s | Dirigido a: %s | Descripción: %s | Tipo de tarea: %s | Estado: %s | Fecha solicitud: %s | Plazo entrega: %s | ID Persona Asignada: %s",
        'similitud_cols' => ['responsable','dirigido_a','descripcion','estado_texto','tipo_tarea','id_persona_asignada']
    ]
];

// ----------------- LIMPIEZA DE FRASE -----------------
$palabras_clave = limpiar_palabras_clave($frase, $stopwords);
if (count($palabras_clave) == 0) {
    echo json_encode(['error' => 'No hay palabras clave para buscar después de filtrar conectores.']);
    exit;
}

// ----------------- FUNCIONALIDAD TABLA DIRECTA -----------------
if ($tabla_directa !== null) {
    if (!array_key_exists($tabla_directa, $tablas)) {
        echo json_encode(['error' => 'La tabla seleccionada no es válida.']);
        exit;
    }

    $info = $tablas[$tabla_directa];
    $resultados = [];

    foreach ($palabras_clave as $palabra) {
        $like = "%$palabra%";
        $params = array_fill(0, $info['params_count'], $like);
        $params_ref = [];
        foreach ($params as &$p) $params_ref[] = &$p;

        $rows = buscar($con, $info['sql'], str_repeat('s', $info['params_count']), $params_ref);

        foreach ($rows as $row) {
            $sim_max_fila = 0;
            $cols_similitud = $info['similitud_cols'] ?? $info['columns'];
            foreach ($cols_similitud as $col) {
                if (isset($row[$col])) $sim_max_fila = max($sim_max_fila, similitud($palabra,$row[$col]));
            }
            if ($sim_max_fila >= 40) {
                $resultados[] = ['similitud'=>$sim_max_fila, 'contenido'=>vsprintf($info['fmt'], array_map(fn($c)=>$row[$c]??'', $info['columns']))];
            }
        }
    }

    echo json_encode(['tipo'=>$tabla_directa,'resultados'=>$resultados], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
    exit;
}

// ----------------- BÚSQUEDA GENERAL -----------------
$resultados_tablas = [];
$max_similitudes = [];

foreach ($tablas as $clave_tabla=>$info) {
    $max_sim_tabla = 0;
    $resultados_tabla = [];
    foreach ($palabras_clave as $palabra) {
        $like = "%$palabra%";
        $params = array_fill(0, $info['params_count'], $like);
        $params_ref = [];
        foreach ($params as &$p) $params_ref[] = &$p;

        $rows = buscar($con, $info['sql'], str_repeat('s', $info['params_count']), $params_ref);

        foreach ($rows as $row) {
            $sim_max_fila = 0;
            $cols_similitud = $info['similitud_cols'] ?? $info['columns'];
            foreach ($cols_similitud as $col) {
                if (isset($row[$col])) $sim_max_fila = max($sim_max_fila, similitud($palabra,$row[$col]));
            }
            if ($sim_max_fila >= 40) {
                $resultados_tabla[] = ['similitud'=>$sim_max_fila,'contenido'=>vsprintf($info['fmt'], array_map(fn($c)=>$row[$c]??'', $info['columns']))];
                $max_sim_tabla = max($max_sim_tabla, $sim_max_fila);
            }
        }
    }
    if (count($resultados_tabla)>0) {
        $resultados_tablas[$clave_tabla]=$resultados_tabla;
        $max_similitudes[$clave_tabla]=$max_sim_tabla;
    }
}

// ----------------- RESPUESTA FINAL -----------------
if (count($resultados_tablas)==0) {
    echo json_encode(['mensaje'=>'No se encontraron resultados con al menos 40% de similitud.'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
    exit;
}

arsort($max_similitudes);
$max_val = reset($max_similitudes);
$tablas_maximas = array_keys(array_filter($max_similitudes, fn($v)=>$v===$max_val));

if (count($tablas_maximas)>1) {
    $labels = ['pregunta'=>'preguntas frecuentes','usuario'=>'usuarios','url'=>'enlaces','tareas'=>'registro de tareas'];
    $opts = array_map(fn($k)=>$labels[$k]??$k, $tablas_maximas);
    echo json_encode(['pregunta'=>"¿Querés ver información de:",'opciones'=>$opts,'claves'=>$tablas_maximas], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
    exit;
}

$tabla_seleccionada = $tablas_maximas[0];
echo json_encode(['tipo'=>$tabla_seleccionada,'resultados'=>$resultados_tablas[$tabla_seleccionada]], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
exit;
