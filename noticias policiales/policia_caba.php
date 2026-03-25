<?php
ob_start();
// ══════════════════════════════════════════════════════════════
//  MONITOR · POLICÍA DE LA CIUDAD · CABA — v14
//  Redes: X (f=live), TikTok (keyword+hashtag), YouTube (shorts+videos),
//         Facebook (publicaciones recientes), Google News
//  Perfiles @arroba: X, IG, FB, YT, TikTok
// ══════════════════════════════════════════════════════════════
error_reporting(0);
ini_set('display_errors','0');
ini_set('display_startup_errors','0');
set_time_limit(120);
date_default_timezone_set('America/Argentina/Buenos_Aires');

define('CACHE_TTL',      300);
define('TV_CACHE_TTL',   180);
define('MAX_POR_FUENTE', 15);
define('FETCH_TIMEOUT',  5);
define('NEWS_DAYS',      4);

foreach (['monitor_v14.json','tv_ids_v14.json'] as $k=>$fn) {
    $var = ($k===0) ? 'CACHE_FILE' : 'TV_CACHE_FILE';
    $tmp = sys_get_temp_dir().'/'.$fn;
    $dir = __DIR__.'/cache';
    if (!is_dir($dir)) @mkdir($dir,0755,true);
    $$var = (is_writable($dir)) ? "$dir/$fn" : $tmp;
}

$TV_CHANNELS = [
    ['nombre'=>'TN',         'id'=>'UCDOEbL_bHVnRhA92MnWvCew','handle'=>'@todonoticias',       'color'=>'#b71c1c'],
    ['nombre'=>'C5N',        'id'=>'UCkzDFHFSfE3SXxlgFumJUwA','handle'=>'@C5N',                'color'=>'#1565c0'],
    ['nombre'=>'Telefe',     'id'=>'UCdioHcKb2SsYNKJXJo_6wog','handle'=>'@telefe',             'color'=>'#c62828'],
    ['nombre'=>'Crónica HD', 'id'=>'UCvfETj0dBJFnkdDJ0SXIq3A','handle'=>'@cronicatv',          'color'=>'#6a1b9a'],
    ['nombre'=>'TV Pública', 'id'=>'UCIdKnUf_giFgaiwPBYmfNsQ','handle'=>'@TVPublicaArgentina', 'color'=>'#0277bd'],
    ['nombre'=>'Infobae TV', 'id'=>'UCP7sAsP7DdPGXPdS3c9wsyg','handle'=>'@infobae',            'color'=>'#455a64'],
    ['nombre'=>'A24',        'id'=>'',                        'handle'=>'',                    'color'=>'#ff6f00', 'videoUrl'=>'https://www.youtube.com/watch?v=ArKbAx1K-2U', 'forceFallback'=>true],
    ['nombre'=>'Canal 22',   'id'=>'',                        'handle'=>'',                    'color'=>'#1e88e5', 'videoUrl'=>'https://www.youtube.com/live/jViaM8OeZfo'],
    ['nombre'=>'Canal 26',   'id'=>'',                        'handle'=>'',                    'color'=>'#43a047', 'videoUrl'=>'https://www.youtube.com/live/WxdijQ-LFMw'],
    ['nombre'=>'La Nación+', 'id'=>'',                        'handle'=>'',                    'color'=>'#5e35b1', 'videoUrl'=>'https://www.youtube.com/watch?v=DVZ2rJQb_0g'],
    ['nombre'=>'Canal 9',    'id'=>'',                        'handle'=>'',                    'color'=>'#e53935', 'videoUrl'=>'https://www.youtube.com/watch?v=63FF72LFxAw'],
    ['nombre'=>'El Trece',   'id'=>'',                        'handle'=>'',                    'color'=>'#f4511e', 'videoUrl'=>'https://www.youtube.com/watch?v=cb12KmMMDJA'],
    ['nombre'=>'AR 12',      'id'=>'',                        'handle'=>'',                    'color'=>'#00897b', 'videoUrl'=>'https://www.youtube.com/watch?v=JrmCaXL5GZs'],
];

$FUENTES = [
    // --- BÚSQUEDAS GOOGLE NEWS (Específicas Policía de la Ciudad) ---
    'GN·PDC General'      => 'https://news.google.com/rss/search?q=%22Polic%C3%ADa+de+la+Ciudad%22&hl=es-419&gl=AR&ceid=AR:es-419',
    'GN·PDC Hoy'          => 'https://news.google.com/rss/search?q=%22Polic%C3%ADa+de+la+Ciudad%22+when:1d&hl=es-419&gl=AR&ceid=AR:es-419',
    'GN·PDC Detenidos'    => 'https://news.google.com/rss/search?q=%22Polic%C3%ADa+de+la+Ciudad%22+(Detenido+OR+Arrestado+OR+Aprehendido)&hl=es-419&gl=AR&ceid=AR:es-419',
    'GN·PDC Allanamientos'=> 'https://news.google.com/rss/search?q=%22Polic%C3%ADa+de+la+Ciudad%22+(Allanamiento+OR+Allanaron)&hl=es-419&gl=AR&ceid=AR:es-419',
    'GN·PDC Narco'        => 'https://news.google.com/rss/search?q=%22Polic%C3%ADa+de+la+Ciudad%22+(Narco+OR+Droga+OR+Estupefacientes)&hl=es-419&gl=AR&ceid=AR:es-419',
    'GN·PDC Homicidios'   => 'https://news.google.com/rss/search?q=%22Polic%C3%ADa+de+la+Ciudad%22+(Homicidio+OR+Femicidio+OR+Asesinato)&hl=es-419&gl=AR&ceid=AR:es-419',
    'GN·PDC Robos'        => 'https://news.google.com/rss/search?q=%22Polic%C3%ADa+de+la+Ciudad%22+(Robo+OR+Asalto+OR+Motochorro)&hl=es-419&gl=AR&ceid=AR:es-419',
    'GN·PDC Tiroteos'     => 'https://news.google.com/rss/search?q=%22Polic%C3%ADa+de+la+Ciudad%22+(Tiroteo+OR+Balacera)&hl=es-419&gl=AR&ceid=AR:es-419',
    'GN·PDC Operativos'   => 'https://news.google.com/rss/search?q=%22Polic%C3%ADa+de+la+Ciudad%22+(Operativo+OR+Procedimiento)&hl=es-419&gl=AR&ceid=AR:es-419',
    'GN·Policía Porteña'  => 'https://news.google.com/rss/search?q=%22Polic%C3%ADa+Porte%C3%B1a%22+when:3d&hl=es-419&gl=AR&ceid=AR:es-419',
    'GN·Min.Seguridad'    => 'https://news.google.com/rss/search?q=%22Ministerio+de+Seguridad%22+%22Ciudad+de+Buenos+Aires%22+when:3d&hl=es-419&gl=AR&ceid=AR:es-419',
    'GN·PDC Judicial'     => 'https://news.google.com/rss/search?q=%22Polic%C3%ADa+de+la+Ciudad%22+(Imputado+OR+Condenado+OR+Procesado)+when:7d&hl=es-419&gl=AR&ceid=AR:es-419',
    'GN·PDC Institucional'=> 'https://news.google.com/rss/search?q=%22Polic%C3%ADa+de+la+Ciudad%22+(Jura+OR+Ascenso+OR+Comisario)&hl=es-419&gl=AR&ceid=AR:es-419',

    // --- TIER 1: Medios Digitales de Mayor Tráfico Nacional ---
    'Infobae'       => 'https://www.infobae.com/arc/outboundfeeds/rss/?outputType=xml',
    'Infobae·Seg'   => 'https://www.infobae.com/arc/outboundfeeds/rss/category/sociedad/?outputType=xml',
    'Clarín'        => 'https://www.clarin.com/rss/lo-ultimo/',
    'Clarín·Pol'    => 'https://www.clarin.com/rss/policiales/',
    'La Nación'     => 'https://www.lanacion.com.ar/arc/outboundfeeds/rss/?outputType=xml',
    'LaNación·Seg'  => 'https://www.lanacion.com.ar/arc/outboundfeeds/rss/category/seguridad/?outputType=xml',
    'TN'            => 'https://tn.com.ar/rss.xml',
    
    // --- TIER 2: Canales de Noticias y Diarios de Policiales ---
    'C5N'           => 'https://www.c5n.com/rss/pages/home.xml',
    'A24'           => 'https://www.a24.com/rss/pages/home.xml',
    'Crónica'       => 'https://www.cronica.com.ar/rss/',
    'MinutoUno'     => 'https://www.minutouno.com/rss/pages/home.xml',
    'Diario Popular'=> 'https://www.diariopopular.com.ar/rss/policiales.xml', 

    // --- TIER 3: Medios Políticos e Institucionales ---
    'Perfil'        => 'https://www.perfil.com/feed',
    'Página 12'     => 'https://www.pagina12.com.ar/rss/portada',
    'Ámbito'        => 'https://www.ambito.com/rss/pages/home.xml',
    'El Destape'    => 'https://www.eldestapeweb.com/feed',
    'LPO'           => 'https://www.lapoliticaonline.com/rss/', 
    'Urgente24'     => 'https://urgente24.com/feed',
    'BigBang'       => 'https://www.bigbangnews.com/rss/pages/home.xml',

    // --- TIER 4: YouTube Channels ---
    'YT·TN'         => 'https://www.youtube.com/feeds/videos.xml?channel_id=UCDOEbL_bHVnRhA92MnWvCew',
    'YT·C5N'        => 'https://www.youtube.com/feeds/videos.xml?channel_id=UCkzDFHFSfE3SXxlgFumJUwA',
    'YT·Infobae'    => 'https://www.youtube.com/feeds/videos.xml?channel_id=UCP7sAsP7DdPGXPdS3c9wsyg',
    'YT·Crónica'    => 'https://www.youtube.com/feeds/videos.xml?channel_id=UCvfETj0dBJFnkdDJ0SXIq3A',
    'Charly TV'     => 'https://www.youtube.com/feeds/videos.xml?channel_id=UClJzK_8I9rD-vC2A1WfKqvw',
];

$KW_INST=['policía de la ciudad','policia de la ciudad','policía porteña','policia porteña',
    'comisaría vecinal','comisaria vecinal','anillo digital','ministerio de seguridad de la ciudad',
    'ministerio de seguridad porteño','waldo wolff','comisario mayor','comisario general',
    'jefe de la policía de la ciudad','policía de caba','policia de caba','OTCEPCDAD'];
$CATEGORIAS=[
    'Femicidio'    =>['femicidio','feminicidio','crimen de género'],
    'Homicidio'    =>['homicidio','asesinato','mataron','muerte violenta','hallaron el cuerpo'],
    'Tiroteo'      =>['tiroteo','balacera','baleado','disparos'],
    'Secuestro'    =>['secuestro','secuestraron','privación ilegal','extorsión'],
    'Narco'        =>['narcotráfico','narco','cocaína','paco','marihuana','estupefacientes'],
    'Motochorro'   =>['motochorro','entradera','motoasaltante','arrebato','rapiña'],
    'Robo/Asalto'  =>['robo','robaron','asalto','asaltaron','hurto','sustrajeron'],
    'Allanamiento' =>['allanamiento','allanaron','allanó'],
    'Detenido'     =>['detenido','detuvieron','arrestado','aprehendido','capturado'],
    'Operativo'    =>['operativo','patrullaje','rastrillaje','zona liberada','anillo digital'],
    'Judicial'     =>['imputado','condenado','procesado','prisión preventiva','sentencia'],
    'Institucional'=>['jura','ascenso','comisario','comisaría','designación','retiro policial'],
    'Policía CABA' =>['policía de la ciudad','policía porteña','comisaría vecinal','ministerio de seguridad'],
];
$CSS_CAT=['Femicidio'=>'cat-bordo','Homicidio'=>'cat-rojo','Tiroteo'=>'cat-naranja-osc',
    'Secuestro'=>'cat-violeta','Narco'=>'cat-purpura','Motochorro'=>'cat-naranja',
    'Robo/Asalto'=>'cat-amarillo','Allanamiento'=>'cat-ocre','Detenido'=>'cat-azul',
    'Operativo'=>'cat-celeste','Judicial'=>'cat-indigo','Institucional'=>'cat-verde','Policía CABA'=>'cat-marino'];

function fetchUrl(string $u,int $t=FETCH_TIMEOUT):string|false{
    return @file_get_contents($u,false,stream_context_create(['http'=>['timeout'=>$t,'follow_location'=>1,
        'header'=>"User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\nAccept: */*\r\n"],
        'ssl'=>['verify_peer'=>false,'verify_peer_name'=>false]]));
}
function fetchChrome(string $u,int $t=9):string|false{
    return @file_get_contents($u,false,stream_context_create(['http'=>['timeout'=>$t,'follow_location'=>1,
        'header'=>implode("\r\n",['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0.0.0 Safari/537.36',
        'Accept-Language: es-AR,es;q=0.9','Accept: text/html,*/*;q=0.8','Cache-Control: no-cache'])],
        'ssl'=>['verify_peer'=>false,'verify_peer_name'=>false]]));
}
function limpiar(string $s):string{return trim(preg_replace('/\s+/',' ',strip_tags(html_entity_decode($s,ENT_HTML5|ENT_QUOTES,'UTF-8'))));}
function esRelevante(string $texto, bool $directo): bool {
    global $KW_INST;
    $low = mb_strtolower($texto);
    
    // Si contiene alguna palabra institucional (Policía de la Ciudad, etc), es relevante SI O SI.
    foreach($KW_INST as $kw){
        if(str_contains($low, $kw)) return true;
    }
    
    // Si no es una fuente directa (como el feed general de Clarín), 
    // solo aceptamos si menciona un delito + una zona de CABA.
    if($directo) return false;
    $delitos=['homicidio','femicidio','asesinato','mataron','tiroteo','balacera','baleado','allanamiento','allanaron','narcotráfico','narco','estupefacientes','motochorro','entradera','secuestro','detenido','detuvieron','arrestado','aprehendido'];
    $caba=[' caba','(caba)','ciudad de buenos aires','porteño','porteña','villa 31','villa 1-11-14','villa lugano','villa soldati','bajo flores'];
    $d=false;foreach($delitos as $k){if(str_contains($low,$k)){$d=true;break;}}
    if(!$d)return false;foreach($caba as $u){if(str_contains($low,$u))return true;}return false;
}
function normalizar(SimpleXMLElement $item,string $medio,string $uf):array{
    $titulo=limpiar((string)($item->title??''));
    $link=trim((string)($item->link??''));
    if(!$link)$link=trim((string)($item->link['href']??''));
    if(!$link&&isset($item->id)&&str_starts_with(trim((string)$item->id),'http'))$link=trim((string)$item->id);
    $desc=limpiar(strip_tags((string)($item->description??$item->summary??'')));
    $rd=(string)($item->pubDate??$item->published??$item->updated??'');
    $ts=$rd?(strtotime($rd)?:time()):time();
    $fuente=$medio;
    if(str_contains($uf,'news.google.com')){$src=trim((string)($item->source??''));if($src){$fuente=$src;$titulo=trim(preg_replace('/ - '.preg_quote($src,'/').'$/','',$titulo));}}
    $tipo=str_starts_with($medio,'YT')?'youtube':'rss';
    $img=null;
    if(!empty((string)($item->enclosure['url']??'')))$img=(string)$item->enclosure['url'];
    if(!$img){$m=$item->children('media',true);foreach(['content','thumbnail'] as $t){if(isset($m->$t)){$u=(string)($m->$t->attributes()->url??'');if($u){$img=$u;break;}}}if(!$img&&isset($m->group)){$u=(string)($m->group->thumbnail->attributes()->url??'');if($u)$img=$u;}}
    if(!$img&&$tipo==='youtube'&&$link){if(preg_match('/[?&]v=([a-zA-Z0-9_-]{11})/',$link,$mv)||preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/',$link,$mv))$img="https://i.ytimg.com/vi/{$mv[1]}/mqdefault.jpg";}
    if($img&&!str_starts_with($img,'http'))$img=null;
    return ['id'=>md5($link?:$titulo),'medio'=>$medio,'fuente'=>$fuente,'tipo'=>$tipo,'titulo'=>$titulo,'desc'=>mb_substr($desc,0,220),'link'=>$link,'imagen'=>$img,'timestamp'=>$ts,'fecha'=>date('d/m/Y H:i',$ts),'categoria'=>null,'cat_css'=>null];
}
function clasificar(array $item):array{
    global $CATEGORIAS,$CSS_CAT;$txt=mb_strtolower($item['titulo'].' '.$item['desc']);
    foreach($CATEGORIAS as $cat=>$kws){foreach($kws as $kw){if(str_contains($txt,$kw)){$item['categoria']=$cat;$item['cat_css']=$CSS_CAT[$cat]??'cat-marino';return $item;}}}
    $item['categoria']='Policía CABA';$item['cat_css']='cat-marino';return $item;
}
function pipeline():array{
    global $CACHE_FILE,$FUENTES;
    if(file_exists($CACHE_FILE)&&(time()-filemtime($CACHE_FILE))<CACHE_TTL){$c=json_decode(file_get_contents($CACHE_FILE),true);if($c&&!empty($c['items']))return array_merge($c,['cache'=>true]);}
    $vistos=$pool=[];$ok=$des=0;libxml_use_internal_errors(true);
    foreach($FUENTES as $medio=>$url){$raw=fetchUrl($url);if(!$raw)continue;$ok++;
        $feed=@simplexml_load_string($raw,'SimpleXMLElement',LIBXML_NOCDATA|LIBXML_NOERROR|LIBXML_NOWARNING);if(!$feed)continue;
        $items=null;if(isset($feed->channel->item))$items=$feed->channel->item;elseif(isset($feed->entry))$items=$feed->entry;if(!$items)continue;
        $dir=!str_contains($url,'news.google.com')&&!str_contains($url,'youtube.com/feeds');$n=0;
        foreach($items as $ri){if($n>=MAX_POR_FUENTE)break;$t=trim((string)($ri->title??''));if(!$t){$des++;continue;}if(!esRelevante($t,$dir)){$des++;continue;}
            $it=normalizar($ri,$medio,$url);if(!$it['titulo']){$des++;continue;}if(isset($vistos[$it['id']])){$des++;continue;}$vistos[$it['id']]=true;$pool[]=$it;$n++;}}
    $items=array_map('clasificar',$pool);$cutoff=time()-(NEWS_DAYS*86400);
    $items=array_values(array_filter($items,fn($it)=>$it['timestamp']>=$cutoff));usort($items,fn($a,$b)=>$b['timestamp']<=>$a['timestamp']);
    $pc=[];foreach($items as $it){$c=$it['categoria']??'Policía CABA';$pc[$c]=($pc[$c]??0)+1;}arsort($pc);
    $p=['items'=>$items,'total'=>count($items),'por_cat'=>$pc,'fuentes_ok'=>$ok,'descartados'=>$des,'updated_at'=>time(),'updated_fmt'=>date('d/m/Y H:i:s'),'cache'=>false];
    file_put_contents($CACHE_FILE,json_encode($p,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE),LOCK_EX);return $p;
}
function extraerVideoId(string $html):?string{
    foreach(['/"videoId"\s*:\s*"([a-zA-Z0-9_-]{11})"/','/watch\?v=([a-zA-Z0-9_-]{11})/','/"video_id"\s*:\s*"([a-zA-Z0-9_-]{11})"/','/embed\/([a-zA-Z0-9_-]{11})\?/'] as $p){if(preg_match($p,$html,$m))return $m[1];}return null;
}
function getLiveVideoId(string $handle,string $channelId):?string{
    if($h=fetchChrome("https://www.youtube.com/{$handle}/live"))if($v=extraerVideoId($h))return $v;
    if($h=fetchChrome("https://www.youtube.com/{$handle}"))if($v=extraerVideoId($h))return $v;
    if($r=fetchUrl("https://www.youtube.com/feeds/videos.xml?channel_id={$channelId}",6)){if(preg_match('/<yt:videoId>([a-zA-Z0-9_-]{11})<\/yt:videoId>/',$r,$m))return $m[1];if(preg_match('/watch\?v=([a-zA-Z0-9_-]{11})/',$r,$m))return $m[1];}
    return null;
}
function getTVVideoIds():array{
    global $TV_CACHE_FILE,$TV_CHANNELS;
    if(file_exists($TV_CACHE_FILE)&&(time()-filemtime($TV_CACHE_FILE))<TV_CACHE_TTL){$c=json_decode(file_get_contents($TV_CACHE_FILE),true);if($c)return $c;}
    $ids=[];foreach($TV_CHANNELS as $ch){$ids[$ch['nombre']]=['videoId'=>getLiveVideoId($ch['handle'],$ch['id']),'handle'=>$ch['handle'],'channelId'=>$ch['id']];}
    file_put_contents($TV_CACHE_FILE,json_encode($ids),LOCK_EX);return $ids;
}

if(isset($_GET['api'])){while(ob_get_level())ob_end_clean();header('Content-Type: application/json; charset=utf-8');header('Access-Control-Allow-Origin: *');header('Cache-Control: no-store');try{$j=json_encode(pipeline(),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);}catch(Throwable $e){$j=json_encode(['items'=>[],'total'=>0,'error'=>$e->getMessage()]);}die($j);}
if(isset($_GET['tv'])){while(ob_get_level())ob_end_clean();header('Content-Type: application/json; charset=utf-8');header('Access-Control-Allow-Origin: *');header('Cache-Control: max-age=60');die(json_encode(getTVVideoIds()));}
if(isset($_GET['refresh'])){@unlink($CACHE_FILE);header('Location: ?');exit;}

$KW_SOCIAL=[
    '"Policía de la Ciudad"',
    '"Policía de la Ciudad de Buenos Aires"',
    '"PDC Buenos Aires"',
    '"Policía CABA"',
    'operativos "Policía de la Ciudad"',
    'detenciones "Policía de la Ciudad"',
    'allanamientos "Policía de la Ciudad"',
    'narcotráfico CABA policía',
    'robos CABA policía',
    'patrullaje "Policía de la Ciudad"',
    'tiroteo CABA policía',
    'femicidio "Policía de la Ciudad"',
];
?>
<!DOCTYPE html>
<html lang="es-AR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Monitor · Policía de la Ciudad · CABA</title>
   <link rel="icon" href="images/LogoBA-accesos.png" type="image/x-icon">
<meta http-equiv="Content-Security-Policy" content="default-src * 'unsafe-inline' 'unsafe-eval' data: blob:;">
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<!-- SheetJS para exportación Excel client-side -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;overflow:hidden}
body{font-family:'Segoe UI',system-ui,-apple-system,sans-serif;background:#0f0f0f;color:#1a1a2e;font-size:13px}

header{background:linear-gradient(135deg,#003087 0%,#0046a8 55%,#005ec7 100%);display:flex;align-items:center;gap:12px;padding:0 14px;height:44px;flex-shrink:0;box-shadow:0 2px 8px rgba(0,48,135,.5);z-index:300}
.btn-portal{color:rgba(255,255,255,.8);text-decoration:none;font-size:.65rem;border:1px solid rgba(255,255,255,.3);padding:3px 9px;border-radius:20px;white-space:nowrap;flex-shrink:0;transition:all .2s}
.btn-portal:hover{background:rgba(255,255,255,.15);color:#fff}
header h1{flex:1;color:#fff;font-size:.88rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.info-header{display:flex;flex-direction:column;align-items:flex-end;flex-shrink:0}
.reloj{font-size:.95rem;font-weight:800;color:#fff;font-variant-numeric:tabular-nums;letter-spacing:.5px}
#contador{font-size:.58rem;color:rgba(255,255,255,.6);margin-top:1px}

#alerta-policia{padding:4px 14px;font-size:.75rem;font-weight:600;display:flex;align-items:center;gap:8px;flex-wrap:wrap;flex-shrink:0;min-height:26px}
.estado-alerta{background:#fff0f0;border-bottom:2px solid #e53935;color:#b71c1c;animation:pulso .9s ease-in-out infinite alternate}
.estado-sin-novedad{background:#f0faf1;border-bottom:1px solid #a5d6a7;color:#1b5e20}
@keyframes pulso{from{box-shadow:inset 0 -2px 0 rgba(229,57,53,0)}to{box-shadow:inset 0 -2px 0 rgba(229,57,53,.4)}}
.btn-alerta{background:#e53935;color:#fff;border:none;padding:2px 10px;border-radius:20px;font-size:.7rem;font-weight:700;cursor:pointer}
.btn-alerta:hover{background:#b71c1c}

.app-shell{display:flex;flex-direction:column;height:100vh}
.app-body{display:flex;flex:1;overflow:hidden;min-height:0}
.resizer{width:4px;background:#c7d0de;cursor:col-resize;flex-shrink:0;transition:background .2s;z-index:10}
.resizer:hover,.resizer:active{background:#0046a8}

/* NOTICIAS */
.col-noticias{display:flex;flex-direction:column;overflow:hidden;border-right:1px solid #2a2a2a;background:#f5f6fa}
.filtros{display:flex;gap:4px;flex-wrap:wrap;align-items:center;padding:6px 8px;background:#fff;border-bottom:1px solid #dde3ed;flex-shrink:0}
.filtros select,.filtros input[type=search]{border:1.5px solid #dde3ed;border-radius:5px;padding:4px 7px;font-size:.72rem;color:#1a1a2e;background:#f7f9fc;outline:none;transition:border-color .2s}
.filtros select:focus,.filtros input[type=search]:focus{border-color:#0046a8;background:#fff}
.filtros input[type=search]{flex:1;min-width:80px}
#btn-actualizar{background:#003087;color:#fff;border:none;border-radius:5px;padding:4px 10px;font-size:.72rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:3px;flex-shrink:0;transition:background .2s}
#btn-actualizar:hover{background:#00205f}

/* ── BOTÓN EXCEL ── */
#btn-excel{background:#1d6f42;color:#fff;border:none;border-radius:5px;padding:4px 11px;font-size:.72rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:4px;flex-shrink:0;transition:background .2s}
#btn-excel:hover{background:#145232}
#btn-excel:disabled{background:#aaa;cursor:not-allowed}

#noticias{flex:1;overflow-y:auto;padding:7px 8px;display:flex;flex-direction:column;gap:5px}
#noticias::-webkit-scrollbar{width:3px}
#noticias::-webkit-scrollbar-thumb{background:#c7d0de;border-radius:3px}

/* CARDS */
.noticia{background:#fff;border-radius:7px;display:flex;overflow:hidden;border-left:3px solid #c7d0de;box-shadow:0 1px 3px rgba(0,0,0,.07);flex-shrink:0}
.noticia.cat-bordo{border-left-color:#7f0000}.noticia.cat-rojo{border-left-color:#c62828}.noticia.cat-naranja-osc{border-left-color:#bf360c}
.noticia.cat-violeta{border-left-color:#6a1b9a}.noticia.cat-purpura{border-left-color:#4a148c}.noticia.cat-naranja{border-left-color:#e65100}
.noticia.cat-amarillo{border-left-color:#f57f17}.noticia.cat-ocre{border-left-color:#e65100}.noticia.cat-azul{border-left-color:#1565c0}
.noticia.cat-celeste{border-left-color:#0277bd}.noticia.cat-indigo{border-left-color:#283593}.noticia.cat-verde{border-left-color:#1b5e20}
.noticia.cat-marino{border-left-color:#003087}
.imagen-wrapper{width:75px;min-height:62px;flex-shrink:0;overflow:hidden;background:#e8edf5;position:relative}
.imagen-wrapper img{width:100%;height:100%;object-fit:cover;display:block}
.badge-yt{position:absolute;bottom:2px;left:2px;background:rgba(0,0,0,.75);color:#fff;font-size:.5rem;font-weight:700;padding:1px 3px;border-radius:2px;display:flex;align-items:center;gap:1px}
.contenido{padding:6px 8px;flex:1;display:flex;flex-direction:column;gap:3px;min-width:0}
.etiquetas{display:flex;gap:3px;flex-wrap:wrap;align-items:center}
.etiqueta-medio{font-size:.57rem;font-weight:700;text-transform:uppercase;background:#eef2fa;color:#3d5a99;padding:1px 4px;border-radius:2px;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.etiqueta-cat{font-size:.57rem;font-weight:700;padding:1px 4px;border-radius:2px;white-space:nowrap}
.etiqueta-cat.cat-bordo{background:#fce4e4;color:#7f0000}.etiqueta-cat.cat-rojo{background:#ffebee;color:#b71c1c}
.etiqueta-cat.cat-naranja-osc{background:#fbe9e7;color:#bf360c}.etiqueta-cat.cat-violeta{background:#f3e5f5;color:#6a1b9a}
.etiqueta-cat.cat-purpura{background:#ede7f6;color:#4a148c}.etiqueta-cat.cat-naranja{background:#fff3e0;color:#e65100}
.etiqueta-cat.cat-amarillo{background:#fffde7;color:#b45309}.etiqueta-cat.cat-ocre{background:#fffbeb;color:#92400e}
.etiqueta-cat.cat-azul{background:#e3f2fd;color:#0d47a1}.etiqueta-cat.cat-celeste{background:#e1f5fe;color:#01579b}
.etiqueta-cat.cat-indigo{background:#e8eaf6;color:#1a237e}.etiqueta-cat.cat-verde{background:#e8f5e9;color:#1b5e20}
.etiqueta-cat.cat-marino{background:#e3eaf8;color:#003087}
.contenido h2{font-size:.78rem;font-weight:700;line-height:1.3}
.contenido h2 a{color:#1a1a2e;text-decoration:none}.contenido h2 a:hover{color:#0046a8}
.desc-noticia{font-size:.67rem;color:#607d8b;line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.meta{font-size:.61rem;color:#90a4ae;display:flex;align-items:center;gap:3px;margin-top:auto}
.empty-state{text-align:center;padding:28px 12px;color:#90a4ae;background:#fff;border-radius:7px}
.empty-state i{font-size:1.5rem;display:block;margin-bottom:5px}

/* PANEL DERECHO */
.col-derecha{display:flex;flex-direction:column;overflow:hidden;background:#111;flex:1;min-width:300px}
.panel-tabs{display:flex;flex-shrink:0;background:#0a0a0a;border-bottom:1px solid #222}
.panel-tab{flex:1;padding:7px 4px;font-size:.69rem;font-weight:700;color:#777;cursor:pointer;text-align:center;border:none;background:none;transition:all .15s;display:flex;align-items:center;justify-content:center;gap:3px;white-space:nowrap;border-bottom:2px solid transparent}
.panel-tab:hover{color:#bbb;background:#151515}
.panel-tab.active{color:#fff;border-bottom-color:#0046a8;background:#111}
.tab-content{display:none;flex:1;overflow:hidden;flex-direction:column;min-height:0}
.tab-content.active{display:flex}

/* TV */
.tv-panel{display:flex;flex-direction:column;flex:1;min-height:0;overflow:hidden}
.tv-ch-bar{display:flex;gap:3px;overflow-x:auto;flex-shrink:0;padding:5px 6px;background:#0a0a0a;border-bottom:1px solid #222}
.tv-ch-bar::-webkit-scrollbar{height:2px}.tv-ch-bar::-webkit-scrollbar-thumb{background:#333;border-radius:1px}
.tv-ch-btn{padding:3px 8px;font-size:.62rem;font-weight:700;border:1px solid #2a2a2a;border-radius:4px;cursor:pointer;background:#1a1a1a;color:#777;white-space:nowrap;transition:all .12s;flex-shrink:0}
.tv-ch-btn:hover{border-color:#555;color:#ccc}
.tv-grid{display:grid;grid-template-columns:1fr 1fr;grid-template-rows:1fr 1fr;flex:1;gap:2px;background:#000;min-height:0;transition:all 0.3s ease}
.tv-grid.grid-6{grid-template-columns:1fr 1fr 1fr;grid-template-rows:1fr 1fr;}
.tv-grid.grid-9{grid-template-columns:1fr 1fr 1fr;grid-template-rows:1fr 1fr 1fr;}
.tv-cell{position:relative;background:#0d0d0d;overflow:hidden;cursor:pointer;min-height:0}
.tv-cell.selected{outline:2px solid #0046a8;outline-offset:-2px;z-index:1}
.tv-cell iframe{position:absolute;top:0;left:0;width:100%;height:calc(100% - 22px);border:none}
.tv-overlay{position:absolute;top:0;left:0;right:0;bottom:22px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;background:#0d0d0d;z-index:2;padding:8px;text-align:center}
.tv-overlay.off{display:none}
.tv-overlay-canal{font-size:.68rem;font-weight:700;color:#ccc}
.tv-overlay-estado{font-size:.58rem;color:#555}
.tv-overlay-actions{display:flex;gap:5px;flex-wrap:wrap;justify-content:center}
.tv-btn-retry{background:#0046a8;color:#fff;border:none;padding:3px 10px;border-radius:4px;font-size:.6rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:3px}
.tv-btn-retry:hover{background:#003087}
.tv-btn-yt{display:inline-flex;align-items:center;gap:3px;color:#fff;background:#c62828;padding:3px 9px;border-radius:4px;text-decoration:none;font-size:.6rem;font-weight:700}
.tv-btn-yt:hover{background:#b71c1c}
.tv-cell-bar{position:absolute;bottom:0;left:0;right:0;height:22px;background:rgba(0,0,0,.9);display:flex;align-items:center;gap:3px;padding:0 5px;z-index:3}
.tv-blink{width:5px;height:5px;background:#f44336;border-radius:50%;animation:blink 1s infinite;flex-shrink:0}
.tv-blink.off{display:none}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.15}}
.tv-cell-name{font-size:.6rem;font-weight:700;color:#ccc;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tv-cell-sel{font-size:.58rem;background:rgba(255,255,255,.08);color:#aaa;border:none;border-radius:3px;padding:1px 2px;cursor:pointer;outline:none;max-width:90px}
.tv-cell-sel option{background:#1a1a1a;color:#fff}
.tv-cell-yt{color:rgba(255,255,255,.5);text-decoration:none;font-size:.62rem;display:flex;align-items:center;flex-shrink:0}
.tv-cell-yt:hover{color:#fff}

/* ══ REDES ══ */
.redes-panel{display:flex;flex-direction:column;flex:1;min-height:0;overflow:hidden;background:#f4f6fb}
.kw-bar{display:flex;flex-wrap:wrap;gap:4px;padding:7px 8px;flex-shrink:0;border-bottom:1px solid #e4e9f2;background:#fff;align-items:center}
.kw-label{font-size:.58rem;font-weight:800;color:#003087;text-transform:uppercase;letter-spacing:.5px;flex-shrink:0;white-space:nowrap}
.kw-btn{font-size:.6rem;font-weight:700;padding:3px 8px;border-radius:10px;border:1.5px solid #dde3ed;background:#f4f6fb;color:#455a64;cursor:pointer;transition:all .12s;white-space:nowrap}
.kw-btn:hover,.kw-btn.active{border-color:#003087;background:#003087;color:#fff}
.redes-scroll{flex:1;overflow-y:auto;padding:8px;display:flex;flex-direction:column;gap:7px}
.redes-scroll::-webkit-scrollbar{width:3px}
.redes-scroll::-webkit-scrollbar-thumb{background:#c7d0de;border-radius:3px}

/* Sección */
.sec{background:#fff;border-radius:9px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.07);flex-shrink:0}
.sec-hdr{display:flex;align-items:center;gap:7px;padding:8px 11px;border-bottom:1px solid #eef1f7;background:#f7f9fc}
.sec-icon{font-size:1.05rem;flex-shrink:0}
.sec-title{font-size:.71rem;font-weight:800;color:#1a1a2e;flex:1}
.sec-badge{font-size:.56rem;background:#e3eaf8;color:#003087;border-radius:8px;padding:2px 8px;font-weight:700;white-space:nowrap}
.sec-body{padding:5px;display:flex;flex-direction:column;gap:4px}

/* Card link búsqueda */
.lk{display:flex;align-items:center;gap:8px;text-decoration:none;color:#1a1a2e;border:1.5px solid #eef1f7;border-radius:7px;padding:8px 10px;transition:all .13s;background:#fff}
.lk:hover{background:#f0f4ff;border-color:#0046a8;transform:translateX(2px)}
.lk-icon{font-size:1rem;flex-shrink:0;width:22px;text-align:center}
.lk-body{display:flex;flex-direction:column;flex:1;min-width:0}
.lk-title{font-size:.71rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.lk-sub{font-size:.58rem;color:#78909c;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.lk-pill{font-size:.57rem;color:#fff;font-weight:800;padding:3px 9px;border-radius:6px;white-space:nowrap;flex-shrink:0}
.p-tw{background:#000}.p-yt{background:#ff0000}.p-tk{background:#010101}
.p-gn{background:#e65100}.p-ig{background:linear-gradient(135deg,#f09433,#dc2743,#bc1888)}.p-fb{background:#1877f2}

/* Grid de perfiles @arroba */
.perfiles-grid{display:grid;grid-template-columns:1fr 1fr;gap:4px;padding:5px}
.perfil-card{display:flex;align-items:center;gap:6px;text-decoration:none;color:#1a1a2e;border:1.5px solid #eef1f7;border-radius:7px;padding:7px 8px;transition:all .13s;background:#fff}
.perfil-card:hover{background:#f0f4ff;border-color:#0046a8}
.perfil-red{font-size:.95rem;flex-shrink:0;width:20px;text-align:center}
.perfil-info{display:flex;flex-direction:column;flex:1;min-width:0}
.perfil-handle{font-size:.68rem;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.perfil-desc{font-size:.56rem;color:#78909c;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.perfil-tag{font-size:.52rem;font-weight:800;padding:2px 5px;border-radius:4px;color:#fff;flex-shrink:0}
.tag-tw{background:#000}.tag-ig{background:linear-gradient(135deg,#f09433,#dc2743,#bc1888)}
.tag-fb{background:#1877f2}.tag-yt{background:#ff0000}.tag-tk{background:#010101}

/* Íconos */
.ic-tw{color:#000}.ic-yt{color:#ff0000}.ic-tk{color:#000}.ic-gn{color:#e65100}.ic-ig{color:#c13584}.ic-fb{color:#1877f2}.ic-pdc{color:#003087}

/* Nota */
.nota{font-size:.61rem;color:#546e7a;line-height:1.5;padding:6px 9px;background:#e8f4fd;border-radius:6px;border-left:3px solid #0046a8;flex-shrink:0}
.nota strong{color:#003087}.nota code{background:#dce9f7;border-radius:3px;padding:0 3px;font-size:.57rem}

/* STATS */
.stats-panel{flex:1;overflow-y:auto;padding:9px;display:flex;flex-direction:column;gap:9px;background:#fff}
.stats-panel::-webkit-scrollbar{width:3px}.stats-panel::-webkit-scrollbar-thumb{background:#c7d0de;border-radius:3px}
.widget-title{font-size:.72rem;font-weight:700;color:#37474f;margin-bottom:6px;padding-bottom:5px;border-bottom:1.5px solid #eef1f6;display:flex;align-items:center;gap:4px}
.widget-title i{color:#003087}
.stat-row{display:flex;justify-content:space-between;align-items:center;padding:3px 4px;border-radius:4px;font-size:.72rem;cursor:pointer;transition:background .12s;margin:0 -4px}
.stat-row:hover{background:#f3f6fb}
.stat-row .label{color:#455a64;font-weight:500}.stat-row .count{background:#eef2fa;color:#3d5a99;border-radius:9px;padding:1px 6px;font-size:.64rem;font-weight:700}
.pip-row{display:flex;justify-content:space-between;padding:3px 0;font-size:.7rem;border-bottom:1px solid #f0f4f8;color:#607d8b}
.pip-row:last-child{border-bottom:none}.pip-row strong{color:#37474f}
.clima-content{display:flex;align-items:center;gap:9px;padding:3px 0}
.temp{font-size:1.6rem;font-weight:800;color:#003087;line-height:1}
.clima-info{display:flex;flex-direction:column}.clima-info .desc{font-size:.72rem;color:#546e7a}.clima-info .sensacion{font-size:.64rem;color:#90a4ae}

/* MODAL */
#tv-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.95);z-index:9999;flex-direction:column;align-items:center;justify-content:center}
#tv-modal.open{display:flex}
#tv-modal-inner{width:min(1400px,96vw);aspect-ratio:16/9;position:relative;background:#000}
#tv-modal-inner iframe{width:100%;height:100%;border:none}
#tv-modal-bar{width:min(1400px,96vw);background:#003087;display:flex;align-items:center;justify-content:space-between;padding:7px 14px}
#tv-modal-title{color:#fff;font-size:.78rem;font-weight:700;display:flex;align-items:center;gap:6px}
#tv-modal-close{background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);padding:4px 12px;border-radius:6px;cursor:pointer;font-size:.72rem;font-weight:700;display:flex;align-items:center;gap:4px}
#tv-modal-close:hover{background:rgba(255,255,255,.25)}

@media(max-width:900px){
  html,body{height:auto;overflow:auto}
  .app-body{grid-template-columns:1fr;height:auto;overflow:visible}
  .col-noticias,.col-derecha{overflow:visible;height:auto}
  #noticias{max-height:55vh}
  .tab-content.active{min-height:380px}
  .tv-grid{min-height:300px}
  .perfiles-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>
<div class="app-shell">

<header>
  <a href="https://otcepcdad.seguridadciudad.gob.ar/" class="btn-portal">← Portal</a>
  <h1>Monitoreo de Noticias de la Policia de la Ciudad - OTCEPCDAD</h1>
  <div class="info-header">
    <div class="reloj" id="reloj-header">--:--:--</div>
    <div id="contador">Actualizando en: 300s</div>
  </div>
</header>

<div id="alerta-policia" class="estado-sin-novedad">⏳ Cargando...</div>

<div class="app-body" id="app-body">

  <!-- NOTICIAS -->
  <div class="col-noticias" id="col-noticias" style="width:35%; flex-shrink:0; min-width:300px;">
    <div class="filtros">
      <select id="filtro-medio"><option value="">Todos</option></select>
      <select id="filtro-categoria"><option value="">Categorías</option></select>
      <input type="search" id="filtro-titulo" placeholder="Buscar..." autocomplete="off">
      <button id="btn-actualizar" onclick="cargarNoticias()" title="Actualizar noticias"><i class="ph ph-arrows-clockwise"></i></button>
      <!-- ══ BOTÓN DESCARGA EXCEL ══ -->
      <button id="btn-excel" onclick="descargarExcel()" title="Descargar noticias visibles como Excel">
        <i class="ph ph-microsoft-excel-logo"></i> Excel
      </button>
    </div>
    <main id="noticias"><div class="empty-state"><i class="ph ph-spinner"></i>Iniciando...</div></main>
  </div>

  <div class="resizer" id="resizer"></div>

  <!-- PANEL DERECHO -->
  <div class="col-derecha" id="col-derecha">
    <div class="panel-tabs">
      <button class="panel-tab active" onclick="switchTab(this,'tab-tv')"><i class="ph ph-television"></i> TV en Vivo</button>
      <button class="panel-tab" onclick="switchTab(this,'tab-redes')"><i class="ph ph-share-network"></i> Redes Sociales</button>
      <button class="panel-tab" onclick="switchTab(this,'tab-stats')"><i class="ph ph-chart-bar"></i> Stats</button>
    </div>

    <!-- TV -->
    <div id="tab-tv" class="tab-content active">
      <div class="tv-panel">
        <div class="tv-ch-bar" id="tv-ch-bar">
          <div style="display:flex; gap:3px; margin-right: 15px; border-right: 1px solid #333; padding-right: 10px;">
            <button class="tv-ch-btn active" id="btn-grid-4" onclick="cambiarGrid(4)" title="4 Pantallas">4 <i class="ph ph-squares-four"></i></button>
            <button class="tv-ch-btn" id="btn-grid-6" onclick="cambiarGrid(6)" title="6 Pantallas">6 <i class="ph ph-squares-four"></i></button>
            <button class="tv-ch-btn" id="btn-grid-9" onclick="cambiarGrid(9)" title="9 Pantallas">9 <i class="ph ph-squares-four"></i></button>
          </div>
          <?php foreach($TV_CHANNELS as $i=>$ch): ?>
          <button class="tv-ch-btn ch-sel-btn" data-idx="<?=$i?>" data-color="<?=$ch['color']?>" onclick="asignarCelda(<?=$i?>)"><?=htmlspecialchars($ch['nombre'])?></button>
          <?php endforeach; ?>
          <button class="tv-ch-btn" style="background:#c62828;color:#fff;border-color:#c62828;margin-left:auto" onclick="abrirModal()"><i class="ph ph-arrows-out"></i> Ampliar</button>
        </div>
        <div class="tv-grid" id="tv-grid">
          <!-- Celdas generadas dinámicamente en JS -->
        </div>
      </div>
    </div>

    <!-- REDES SOCIALES -->
    <div id="tab-redes" class="tab-content">
      <div class="redes-panel">
        <div class="kw-bar">
          <span class="kw-label">🔑 Tema:</span>
          <div id="kw-bar-cont" style="display:flex;flex-wrap:wrap;gap:4px;flex:1;"></div>
        </div>
        <div class="redes-scroll" id="redes-scroll"></div>
      </div>
    </div>

    <!-- STATS -->
    <div id="tab-stats" class="tab-content">
      <div class="stats-panel">
        <div>
          <div class="widget-title"><i class="ph ph-cloud-sun"></i> Buenos Aires</div>
          <div id="clima-data" class="clima-content"><span class="temp">--°</span><div class="clima-info"><span class="desc">Cargando...</span></div></div>
        </div>
        <div>
          <div class="widget-title"><i class="ph ph-chart-bar"></i> Incidentes (últimos 4 días)</div>
          <div id="stats-tipos"><div style="color:#90a4ae;font-size:.72rem">Cargando...</div></div>
        </div>
        <div>
          <div class="widget-title"><i class="ph ph-activity"></i> Pipeline RSS</div>
          <div id="pipeline-info"><div style="color:#90a4ae;font-size:.72rem">Cargando...</div></div>
        </div>
        <div style="font-size:.64rem;color:#90a4ae;text-align:center">
          <a href="?refresh=1" style="color:#607d8b;text-decoration:none">↺ Forzar actualización del caché</a>
        </div>
      </div>
    </div>

  </div>
</div>
</div>

<div id="tv-modal">
  <div id="tv-modal-inner">
    <iframe id="tv-modal-iframe" allow="accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture" allowfullscreen></iframe>
  </div>
  <div id="tv-modal-bar">
    <div id="tv-modal-title"><div class="tv-blink"></div><span id="tv-modal-nombre">—</span></div>
    <button id="tv-modal-close" onclick="cerrarModal()"><i class="ph ph-x"></i> Cerrar</button>
  </div>
</div>

<script>
'use strict';

const enc = s => encodeURIComponent(s);
function esc(s){
  if(s==null)return'';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
const $ = id => document.getElementById(id);

const KW      = <?=json_encode($KW_SOCIAL,  JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)?>;
const TV_META = <?=json_encode($TV_CHANNELS,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)?>;

/* ══ Estado ══ */
const INTERVALO = 300;
let restante          = INTERVALO;
let listaNoticias     = [];
let noticiasFiltradas = [];   // siempre refleja lo visible en pantalla
let tvVideoIds        = {};
let kwActivo          = KW[0]||'';
let celdaActiva       = 0;
let cantidadCeldas    = 4;

document.addEventListener('DOMContentLoaded', () => {
  generarGrid(cantidadCeldas);
  iniciarTV();
  iniciarRedes();
  cargarNoticias();
  fetchClima();
  iniciarTimers();
  ['filtro-medio','filtro-categoria'].forEach(id=>{
    const el=$(id); if(el) el.addEventListener('change',aplicarFiltros);
  });
  const ft=$('filtro-titulo'); if(ft) ft.addEventListener('input',aplicarFiltros);
  document.addEventListener('keydown',e=>{ if(e.key==='Escape') cerrarModal(); });
  const modal=$('tv-modal');
  if(modal) modal.addEventListener('click',e=>{ if(e.target===modal) cerrarModal(); });

  // Lógica de resizer
  const resizer = $('resizer');
  const panelIzq = $('col-noticias');
  let isResizing = false;

  // Cargar preferencia guardada
  const anchoGuardado = localStorage.getItem('panelNoticiasWidth');
  if (anchoGuardado && panelIzq) {
    panelIzq.style.width = anchoGuardado;
  }

  if (resizer && panelIzq) {
    resizer.addEventListener('mousedown', (e) => {
      isResizing = true;
      document.body.style.cursor = 'col-resize';
      document.body.style.userSelect = 'none';
      e.preventDefault();
    });

    document.addEventListener('mousemove', (e) => {
      if (!isResizing) return;
      const containerWidth = document.body.clientWidth;
      let newWidth = (e.clientX / containerWidth) * 100;
      
      // Limitar entre 15% y 85% para no romper el layout
      if (newWidth < 15) newWidth = 15;
      if (newWidth > 85) newWidth = 85;

      const widthStr = newWidth + '%';
      panelIzq.style.width = widthStr;
    });

    document.addEventListener('mouseup', () => {
      if (isResizing) {
        isResizing = false;
        document.body.style.cursor = '';
        document.body.style.userSelect = '';
        localStorage.setItem('panelNoticiasWidth', panelIzq.style.width);
      }
    });
  }
});

/* ══════════════════════════════════════════════════════════════
   DESCARGA EXCEL — exporta las noticias actualmente visibles
   Columnas: Fecha · Categoría · Fuente · Título (Detalle del Hecho) · Descripción · Link
══════════════════════════════════════════════════════════════ */
function descargarExcel() {
  const btn = $('btn-excel');
  if (!noticiasFiltradas.length) {
    alert('No hay noticias para exportar.\nActualizá el monitor o quitá los filtros.');
    return;
  }
  btn.disabled = true;
  btn.innerHTML = '<i class="ph ph-spinner"></i> Generando...';

  try {
    const ahora   = new Date();
    const fmtDate = ahora.toLocaleDateString('es-AR');
    const fmtTime = ahora.toLocaleTimeString('es-AR');

    /* ── Hoja principal: Noticias ────────────────────────────── */
    const filas = noticiasFiltradas.map(n => ({
      'Fecha'                     : n.fecha      || '',
      'Categoría'                 : n.categoria  || 'Policía CABA',
      'Fuente / Medio'            : n.fuente     || n.medio || '',
      'Título / Detalle del Hecho': n.titulo     || '',
      'Descripción'               : n.desc       || '',
      'Link'                      : n.link       || '',
    }));

    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.json_to_sheet(filas, { cellStyles: true });

    /* anchos de columna */
    ws['!cols'] = [
      { wch: 16 },  // Fecha
      { wch: 15 },  // Categoría
      { wch: 24 },  // Fuente
      { wch: 68 },  // Título
      { wch: 58 },  // Descripción
      { wch: 55 },  // Link
    ];

    /* alto de filas */
    ws['!rows'] = [{ hpt: 24 }];
    for (let r = 0; r < filas.length; r++) ws['!rows'].push({ hpt: 42 });

    /* estilo encabezado (fila 1) */
    const hdrStyle = {
      font     : { bold: true, color: { rgb: 'FFFFFF' }, name: 'Arial', sz: 10 },
      fill     : { fgColor: { rgb: '003087' }, patternType: 'solid' },
      alignment: { horizontal: 'center', vertical: 'center' },
      border   : { bottom: { style: 'medium', color: { rgb: 'C7D0DE' } } },
    };
    ['A','B','C','D','E','F'].forEach(col => {
      const cell = ws[col + '1'];
      if (cell) cell.s = hdrStyle;
    });

    /* estilos alternados para filas de datos */
    for (let r = 2; r <= filas.length + 1; r++) {
      const par = r % 2 === 0;
      const bg  = par ? 'EEF2FA' : 'FFFFFF';
      ['A','B','C','D','E'].forEach(col => {
        const addr = col + r;
        if (!ws[addr]) return;
        ws[addr].s = {
          font     : { name: 'Arial', sz: 9 },
          fill     : { fgColor: { rgb: bg }, patternType: 'solid' },
          alignment: { vertical: 'top', wrapText: true },
        };
      });
      /* Link en azul subrayado */
      const lAddr = 'F' + r;
      if (ws[lAddr]) {
        ws[lAddr].s = {
          font     : { name: 'Arial', sz: 9, color: { rgb: '0046A8' }, underline: true },
          fill     : { fgColor: { rgb: bg }, patternType: 'solid' },
          alignment: { vertical: 'top', wrapText: false },
        };
      }
    }

    /* ── Hoja secundaria: Info de exportación ────────────────── */
    const metaData = [
      ['Monitor Policía de la Ciudad · CABA', ''],
      ['Generado el'              , `${fmtDate} ${fmtTime}`],
      ['Noticias exportadas'      , filas.length],
      ['Filtro Medio'             , $('filtro-medio')?.value      || '(todos)'],
      ['Filtro Categoría'         , $('filtro-categoria')?.value  || '(todas)'],
      ['Filtro Búsqueda'          , $('filtro-titulo')?.value     || '(sin filtro)'],
      ['', ''],
      ['Columnas incluidas', 'Fecha · Categoría · Fuente/Medio · Título/Detalle · Descripción · Link'],
    ];
    const wsMeta = XLSX.utils.aoa_to_sheet(metaData);
    wsMeta['!cols'] = [{ wch: 26 }, { wch: 55 }];

    /* estilo título hoja meta */
    if (wsMeta['A1']) {
      wsMeta['A1'].s = {
        font : { bold: true, sz: 12, color: { rgb: '003087' }, name: 'Arial' },
      };
    }

    XLSX.utils.book_append_sheet(wb, ws,     'Noticias PDC');
    XLSX.utils.book_append_sheet(wb, wsMeta, 'Exportación');

    /* nombre de archivo con timestamp */
    const ts = `${ahora.getFullYear()}${String(ahora.getMonth()+1).padStart(2,'0')}${String(ahora.getDate()).padStart(2,'0')}_${String(ahora.getHours()).padStart(2,'0')}${String(ahora.getMinutes()).padStart(2,'0')}`;
    XLSX.writeFile(wb, `Monitor_PDC_CABA_${ts}.xlsx`);

  } catch (err) {
    console.error('Excel error:', err);
    alert('Error al generar el Excel: ' + err.message);
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="ph ph-microsoft-excel-logo"></i> Excel';
  }
}

/* ══ TV ══ */
function cambiarGrid(n){
  cantidadCeldas = n;
  const grid = $('tv-grid');
  grid.className = 'tv-grid' + (n===6?' grid-6':n===9?' grid-9':'');
  
  ['btn-grid-4', 'btn-grid-6', 'btn-grid-9'].forEach(id => {
    $(id).classList.remove('active');
    $(id).style.background = '';
    $(id).style.color = '';
  });
  const btn = $(`btn-grid-${n}`);
  btn.classList.add('active');
  btn.style.background = '#333';
  btn.style.color = '#fff';

  generarGrid(n);
  iniciarTV();
}

function generarGrid(n){
  const grid = $('tv-grid');
  grid.innerHTML = '';
  
  let opcionesCanales = '<option value="-1">— Canal —</option>';
  TV_META.forEach((ch, ci) => {
    opcionesCanales += `<option value="${ci}">${esc(ch.nombre)}</option>`;
  });

  for(let c=0; c<n; c++){
    grid.innerHTML += `
      <div class="tv-cell" id="tv-cell-${c}" onclick="selCelda(${c})">
        <div class="tv-overlay" id="tv-ov-${c}">
          <span class="tv-overlay-canal" id="tv-ov-canal-${c}">— Sin canal —</span>
          <span class="tv-overlay-estado" id="tv-ov-txt-${c}">Seleccioná un canal</span>
          <div class="tv-overlay-actions" id="tv-ov-acts-${c}"></div>
        </div>
        <iframe id="tv-iframe-${c}" allow="accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture;web-share" allowfullscreen style="display:none"></iframe>
        <div class="tv-cell-bar">
          <div class="tv-blink off" id="tv-dot-${c}"></div>
          <span class="tv-cell-name" id="tv-name-${c}">— Sin canal —</span>
          <select class="tv-cell-sel" id="tv-sel-${c}" onchange="asignarCeldaDirecta(${c},parseInt(this.value))" onclick="event.stopPropagation()">
            ${opcionesCanales}
          </select>
          <a class="tv-cell-yt" id="tv-yt-${c}" href="#" target="_blank" rel="noopener" onclick="event.stopPropagation()" title="Abrir en YouTube"><i class="ph ph-arrow-square-out"></i></a>
        </div>
      </div>
    `;
  }
}

function iniciarTV(){
  // Asignamos a cada celda canales por defecto
  for(let i=0; i<cantidadCeldas; i++){
    const s = $(`tv-sel-${i}`); 
    const chanIdx = i % TV_META.length; // Asigna secuencialmente, volviendo al principio si hay más celdas que canales
    if(s) s.value = chanIdx; 
    cargarCelda(i, chanIdx); 
  }
  selCelda(0); 
  cargarTVIds();
}
async function cargarTVIds(){
  try{
    const res=await fetch('?tv=1&t='+Date.now());
    if(!res.ok)return;
    tvVideoIds=await res.json();
    document.querySelectorAll('.tv-cell-sel').forEach((sel,i)=>{
      const v=parseInt(sel.value);
      if(!isNaN(v)&&v>=0) {
        // Only reload if it relies on TV IDs (no direct URL)
        const meta = TV_META[v] || {};
        if(!meta.videoUrl) cargarCelda(i,v,true);
      }
    });
  }catch(e){ console.warn('TV IDs: fallback live_stream'); }
}
function selCelda(idx){
  celdaActiva=idx;
  document.querySelectorAll('.tv-cell').forEach((el,i)=>el.classList.toggle('selected',i===idx));
}
function asignarCelda(chanIdx){
  const sel=$(`tv-sel-${celdaActiva}`); if(sel) sel.value=chanIdx;
  cargarCelda(celdaActiva,chanIdx);
  document.querySelectorAll('.ch-sel-btn').forEach((b,i)=>{
    const on=(i===chanIdx);
    b.classList.toggle('active',on);
    const col=TV_META[chanIdx]?.color||'#333';
    b.style.background=on?col:''; b.style.color=on?'#fff':''; b.style.borderColor=on?col:'';
  });
}
function asignarCeldaDirecta(ci,ch){ if(isNaN(ch)||ch<0)return; selCelda(ci); cargarCelda(ci,ch); }

function extractVideoIdFromUrl(url) {
  let match = url.match(/[?&]v=([a-zA-Z0-9_-]{11})/);
  if (match) return match[1];
  match = url.match(/youtu\.be\/([a-zA-Z0-9_-]{11})/);
  if (match) return match[1];
  match = url.match(/youtube\.com\/live\/([a-zA-Z0-9_-]{11})/);
  if (match) return match[1];
  return null;
}

function cargarCelda(cellIdx,chanIdx,silent=false){
  chanIdx=parseInt(chanIdx); if(isNaN(chanIdx)||chanIdx<0)return;
  const iframe=$(`tv-iframe-${cellIdx}`),overlay=$(`tv-ov-${cellIdx}`);
  const ovCanal=$(`tv-ov-canal-${cellIdx}`),ovTxt=$(`tv-ov-txt-${cellIdx}`);
  const ovActs=$(`tv-ov-acts-${cellIdx}`),dot=$(`tv-dot-${cellIdx}`);
  const name=$(`tv-name-${cellIdx}`),ytLink=$(`tv-yt-${cellIdx}`);
  if(!iframe||!overlay)return;
  
  const meta=TV_META[chanIdx]||{};
  const nombre=meta.nombre||'?',handle=meta.handle||'';
  
  let videoId = null;
  let ytLiveUrl = handle ? `https://www.youtube.com/${handle}/live` : 'https://www.youtube.com/';
  
  // Logic for direct URL vs automatic lookup
  if (meta.videoUrl) {
    videoId = extractVideoIdFromUrl(meta.videoUrl);
    ytLiveUrl = meta.videoUrl;
  } else {
    const entry=tvVideoIds[nombre];
    videoId=entry?.videoId||null;
  }

  const embedUrl=videoId
    ?`https://www.youtube.com/embed/${videoId}?autoplay=1&mute=1&rel=0&modestbranding=1`
    :`https://www.youtube.com/embed/live_stream?channel=${meta.id||''}&autoplay=1&mute=1&rel=0&modestbranding=1`;
    
  if(name) name.textContent=nombre;
  if(ovCanal) ovCanal.textContent=nombre;
  if(dot) dot.classList.remove('off');
  if(ytLink) ytLink.href=ytLiveUrl;
  if(ovActs) ovActs.innerHTML='';
  if(!silent){ if(ovTxt) ovTxt.textContent=`Cargando ${nombre}…`; overlay.classList.remove('off'); iframe.style.display='none'; }
  let timer=setTimeout(()=>errCelda(cellIdx,chanIdx,nombre,ytLiveUrl),18000);
  iframe.onload=()=>{ clearTimeout(timer); overlay.classList.add('off'); iframe.style.display='block'; };
  iframe.onerror=()=>{ clearTimeout(timer); errCelda(cellIdx,chanIdx,nombre,ytLiveUrl); };
  iframe.src=''; iframe.src=embedUrl;
}

// Variables globales para timers de auto-reconexión
const autoRetryTimers = {};

function errCelda(ci,chanIdx,nombre,ytLiveUrl){
  const iframe=$(`tv-iframe-${ci}`),overlay=$(`tv-ov-${ci}`);
  const ovTxt=$(`tv-ov-txt-${ci}`),ovActs=$(`tv-ov-acts-${ci}`);
  if(iframe) iframe.style.display='none';
  if(ovTxt) ovTxt.innerHTML = 'No se pudo cargar la señal.<br><small style="color:#e53935">Auto-reintentando en 10s...</small>';
  if(ovActs) ovActs.innerHTML=`
    <button class="tv-btn-retry" onclick="event.stopPropagation();reintentarCelda(${ci},${chanIdx})">
      <i class="ph ph-arrows-clockwise"></i> Reintentar ahora
    </button>
    <a class="tv-btn-yt" href="${esc(ytLiveUrl)}" target="_blank" rel="noopener" onclick="event.stopPropagation()">
      <i class="ph ph-youtube-logo"></i> Ver en YouTube
    </a>`;
  if(overlay) overlay.classList.remove('off');

  // Limpiar timer anterior si existe
  if(autoRetryTimers[ci]) {
    clearTimeout(autoRetryTimers[ci]);
  }
  
  // Programar auto-reintento
  autoRetryTimers[ci] = setTimeout(() => {
    reintentarCelda(ci, chanIdx);
  }, 10000);
}
async function reintentarCelda(ci,chanIdx){
  if(autoRetryTimers[ci]) {
    clearTimeout(autoRetryTimers[ci]);
    delete autoRetryTimers[ci];
  }

  const meta=TV_META[chanIdx]||{};
  const ovTxt=$(`tv-ov-txt-${ci}`),ovActs=$(`tv-ov-acts-${ci}`);
  if(ovTxt) ovTxt.textContent='Buscando señal…'; if(ovActs) ovActs.innerHTML='';
  
  if (!meta.videoUrl) {
    try{
      const r=await fetch(`https://www.youtube.com/feeds/videos.xml?channel_id=${meta.id}`);
      const txt=await r.text();
      const m=txt.match(/<yt:videoId>([a-zA-Z0-9_-]{11})<\/yt:videoId>/);
      if(m) tvVideoIds[meta.nombre]={videoId:m[1],handle:meta.handle,channelId:meta.id};
    }catch(_){}
  }
  cargarCelda(ci,chanIdx);
}
function abrirModal(){
  const iframe=$(`tv-iframe-${celdaActiva}`);
  if(!iframe||!iframe.src||iframe.src===location.href)return;
  const mi=$('tv-modal-iframe'); if(mi) mi.src=iframe.src;
  const mn=$('tv-modal-nombre'); if(mn) mn.textContent=$(`tv-name-${celdaActiva}`)?.textContent||'—';
  const modal=$('tv-modal'); if(modal) modal.classList.add('open');
}
function cerrarModal(){
  const mi=$('tv-modal-iframe'); if(mi) mi.src='';
  const modal=$('tv-modal'); if(modal) modal.classList.remove('open');
}

/* ══ HELPERS REDES ══ */
function card(href,icon,icCls,pill,title,sub){
  return `<a class="lk" href="${esc(href)}" target="_blank" rel="noopener noreferrer">
    <i class="ph ${icon} lk-icon ${icCls}"></i>
    <div class="lk-body"><span class="lk-title">${esc(title)}</span><span class="lk-sub">${esc(sub)}</span></div>
    <span class="lk-pill ${pill}">Abrir →</span>
  </a>`;
}
function sec(icon,icCls,title,badge,body){
  return `<div class="sec">
    <div class="sec-hdr"><i class="ph ${icon} sec-icon ${icCls}"></i><span class="sec-title">${title}</span>${badge?`<span class="sec-badge">${badge}</span>`:''}</div>
    <div class="sec-body">${body}</div>
  </div>`;
}

function toHashtag(kw){
  return kw.replace(/"/g,'').replace(/\s+/g,'')
    .replace(/á/g,'a').replace(/é/g,'e').replace(/í/g,'i').replace(/ó/g,'o').replace(/ú/g,'u')
    .replace(/ñ/g,'n').replace(/Á/g,'A').replace(/É/g,'E').replace(/Í/g,'I').replace(/Ó/g,'O').replace(/Ú/g,'U')
    .replace(/[^a-zA-Z0-9_]/g,'').toLowerCase();
}

/* ══ REDES ══ */
function iniciarRedes(){
  const bar=$('kw-bar-cont'); if(!bar) return;
  KW.forEach((kw,i)=>{
    const b=document.createElement('button');
    b.className='kw-btn'+(i===0?' active':'');
    b.textContent=kw.replace(/"/g,'').substring(0,22);
    b.title=kw;
    b.onclick=()=>{
      bar.querySelectorAll('.kw-btn').forEach(x=>x.classList.remove('active'));
      b.classList.add('active');
      kwActivo=kw;
      renderRedes();
    };
    bar.appendChild(b);
  });
  renderRedes();
}
function renderRedes(){
  const panel=$('redes-scroll'); if(!panel) return;
  const kw=kwActivo,kwClean=kw.replace(/"/g,''),kwHash=toHashtag(kw);
  let html='';
  html+=`<div class="nota">🔍 Tema activo: <strong>${esc(kwClean)}</strong><br>
    <span style="font-size:.57rem">Todas las búsquedas aplican el tema seleccionado arriba, ordenadas de <strong>más reciente a más antiguo</strong>.
    Facebook requiere sesión activa. Instagram no soporta filtros de fecha por URL.</span></div>`;
  const twBody=
    card(`https://twitter.com/search?q=${enc(kw)}&f=live&src=typed_query`,'ph-x-logo','ic-tw','p-tw',`X — "${kwClean}"`,'Tiempo real · más recientes primero · f=live')+
    card(`https://twitter.com/search?q=${enc('"Policía de la Ciudad" '+kw)}&f=live`,'ph-x-logo','ic-tw','p-tw',`X — "Policía de la Ciudad" + ${kwClean.substring(0,25)}`,'PDC + tema · tiempo real · f=live');
  html+=sec('ph-x-logo','ic-tw','X / Twitter — Tiempo real','f=live · recientes primero',twBody);
  const tkBody=
    card(`https://www.tiktok.com/search?q=${enc(kw)}&sort_type=2&publish_time=7`,'ph-tiktok-logo','ic-tk','p-tk',`TikTok búsqueda — "${kwClean}"`,'Últimos 7 días · recientes primero · sort_type=2 + publish_time=7')+
    (kwHash?card(`https://www.tiktok.com/tag/${enc(kwHash)}`,'ph-hash','ic-tk','p-tk',`TikTok hashtag — #${kwHash}`,'Etiqueta generada del tema · recientes al top'):'');
  html+=sec('ph-tiktok-logo','ic-tk','TikTok — Recientes + Hashtag','sort_type=2 · publish_time=7 · #hashtag dinámico',tkBody);
  const ytBody=card(`https://www.youtube.com/results?search_query=${enc(kw)}&sp=EgQQARgC`,'ph-youtube-logo','ic-yt','p-yt',`YouTube Shorts — "${kwClean}"`,'Solo Shorts (vertical/corto) · sp=EgQQARgC');
  html+=sec('ph-youtube-logo','ic-yt','YouTube Shorts','sp=EgQQARgC · solo contenido corto vertical',ytBody);
  const gnBody=
    card(`https://news.google.com/search?q=%22Polic%C3%ADa+de+la+Ciudad%22+${enc(kw)}&hl=es-419&gl=AR&ceid=AR%3Aes-419`,'ph-newspaper','ic-gn','p-gn','Google News — PDC + tema',`"Policía de la Ciudad" + ${kwClean.substring(0,28)} · noticias recientes`)+
    card(`https://www.google.com/search?q=${enc(kw)}+%22polic%C3%ADa+de+la+ciudad%22&tbm=nws&tbs=qdr:w`,'ph-newspaper','ic-gn','p-gn','Google Noticias — última semana',`${kwClean.substring(0,30)} · "Policía de la Ciudad" · últimos 7 días`)+
    card(`https://www.google.com/search?q=${enc(kw)}+%22polic%C3%ADa+de+la+ciudad%22&tbm=nws&tbs=qdr:d`,'ph-newspaper','ic-gn','p-gn','Google Noticias — últimas 24 horas',`${kwClean.substring(0,30)} · "Policía de la Ciudad" · hoy`);
  html+=sec('ph-newspaper','ic-gn','Google News — PDC · Recientes','tbs=qdr:d (24h) · tbs=qdr:w (7d)',gnBody);
  panel.innerHTML=html;
}

/* ══ TABS ══ */
function switchTab(btn,tabId){
  document.querySelectorAll('.panel-tab').forEach(b=>b.classList.remove('active'));
  document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
  btn.classList.add('active');
  const el=$(tabId); if(el) el.classList.add('active');
}

/* ══ NOTICIAS ══ */
async function cargarNoticias(){
  const notEl=$('noticias'); if(notEl) notEl.style.opacity='.4';
  try{
    const res=await fetch('?api=1&t='+Date.now());
    const text=await res.text();
    let data;
    try{
      const i=text.indexOf('{'),j=text.lastIndexOf('}');
      if(i<0||j<=i)throw new Error('Sin JSON');
      data=JSON.parse(text.substring(i,j+1));
    }catch(pe){
      if(notEl) notEl.innerHTML=`<div class="empty-state"><i class="ph ph-warning"></i><strong style="color:#c62828">Error al parsear</strong><br><code style="font-size:.6rem;color:#607d8b;word-break:break-all">${text.substring(0,250).replace(/</g,'&lt;')}</code></div>`;
      return;
    }
    listaNoticias=Array.isArray(data.items)?data.items:[];
    actualizarSelector('filtro-medio',[...new Set(listaNoticias.map(n=>n.medio))].sort(),'Todos');
    actualizarSelector('filtro-categoria',[...new Set(listaNoticias.map(n=>n.categoria).filter(Boolean))].sort(),'Categorías');
    actualizarAlerta();
    renderStats(data.por_cat||{});
    renderPipeline(data);
    aplicarFiltros();
  }catch(err){
    if(notEl) notEl.innerHTML=`<div class="empty-state"><i class="ph ph-wifi-slash"></i><strong style="color:#c62828">Error de conexión</strong><br><small style="color:#607d8b">${esc(err.message)}</small></div>`;
  }finally{
    if(notEl) notEl.style.opacity='1';
  }
}
function actualizarAlerta(){
  const GRAVES=['Femicidio','Homicidio','Tiroteo','Secuestro'];
  const graves=listaNoticias.filter(n=>GRAVES.includes(n.categoria));
  const al=$('alerta-policia'); if(!al)return;
  if(graves.length){
    al.className='estado-alerta';
    al.innerHTML=`⚠️ <strong>ALERTA:</strong> ${graves.length} noticia(s) grave(s). <button class="btn-alerta" onclick="filtrarCat('${esc(graves[0].categoria)}')">Ver ahora</button>`;
  }else if(listaNoticias.length>0){
    al.className='estado-sin-novedad';
    al.innerHTML=`✅ Sin novedades críticas · <strong>${listaNoticias.length}</strong> noticias monitoreadas`;
  }else{
    al.className='estado-sin-novedad';
    al.innerHTML='⏳ Cargando fuentes...';
  }
}
function renderStats(porCat){
  const el=$('stats-tipos'); if(!el)return;
  if(!Object.keys(porCat).length){el.innerHTML='<div style="color:#90a4ae;font-size:.72rem">Sin datos</div>';return;}
  el.innerHTML=Object.entries(porCat).slice(0,13).map(([cat,n])=>
    `<div class="stat-row" onclick="filtrarCat('${esc(cat)}')"><span class="label">${esc(cat)}</span><span class="count">${n}</span></div>`).join('');
}
function renderPipeline(data){
  const el=$('pipeline-info'); if(!el)return;
  const c=data.cache?'<strong style="color:#f57f17">Caché</strong>':'<strong style="color:#2e7d32">En vivo</strong>';
  el.innerHTML=`<div class="pip-row"><span>Estado</span>${c}</div><div class="pip-row"><span>Total noticias</span><strong>${data.total||0}</strong></div><div class="pip-row"><span>Fuentes OK</span><strong>${data.fuentes_ok||'—'}</strong></div><div class="pip-row"><span>Descartadas</span><strong>${data.descartados||0}</strong></div><div class="pip-row"><span>Actualizado</span><strong>${data.updated_fmt||'—'}</strong></div>`;
}
function actualizarSelector(id,opciones,ph){
  const sel=$(id); if(!sel)return;
  const val=sel.value;
  sel.innerHTML=`<option value="">${ph}</option>`;
  opciones.forEach(o=>{const opt=document.createElement('option');opt.value=o;opt.textContent=o;if(o===val)opt.selected=true;sel.appendChild(opt);});
}
function filtrarCat(cat){
  const fc=$('filtro-categoria'); if(fc) fc.value=cat;
  const fm=$('filtro-medio');     if(fm) fm.value='';
  const ft=$('filtro-titulo');    if(ft) ft.value='';
  aplicarFiltros();
  const n=$('noticias'); if(n) n.scrollTop=0;
}
function aplicarFiltros(){
  const medio=($('filtro-medio')?.value||'');
  const cat=($('filtro-categoria')?.value||'');
  const texto=($('filtro-titulo')?.value||'').toLowerCase();
  noticiasFiltradas=listaNoticias.filter(n=>
    (!medio||n.medio===medio)&&(!cat||n.categoria===cat)&&
    (!texto||(n.titulo||'').toLowerCase().includes(texto)));
  renderNoticias(noticiasFiltradas);
  // Actualizar tooltip del botón Excel
  const btn=$('btn-excel');
  if(btn) btn.title=`Descargar ${noticiasFiltradas.length} noticia(s) como Excel`;
}
function renderNoticias(lista){
  const el=$('noticias'); if(!el)return;
  if(!lista.length){el.innerHTML=`<div class="empty-state"><i class="ph ph-magnifying-glass"></i>Sin resultados.</div>`;return;}
  el.innerHTML=lista.map(n=>{
    const css=esc(n.cat_css||'cat-marino'),esYT=n.tipo==='youtube';
    return `<article class="noticia ${css}">
      ${n.imagen?`<div class="imagen-wrapper"><img src="${esc(n.imagen)}" loading="lazy" alt="" onerror="this.closest('.imagen-wrapper').remove()">${esYT?`<div class="badge-yt"><i class="ph ph-youtube-logo"></i></div>`:''}</div>`:''}
      <div class="contenido">
        <div class="etiquetas"><span class="etiqueta-medio" title="${esc(n.fuente)}">${esc(n.fuente)}</span><span class="etiqueta-cat ${css}">${esc(n.categoria||'Policía CABA')}</span></div>
        <h2><a href="${esc(n.link)}" target="_blank" rel="noopener noreferrer">${esc(n.titulo)}</a></h2>
        ${n.desc?`<p class="desc-noticia">${esc(n.desc)}</p>`:''}
        <div class="meta"><i class="ph ph-clock"></i><span>${esc(n.fecha)}</span>${n.medio!==n.fuente?`<span style="color:#b0bec5">·</span><span>${esc(n.medio)}</span>`:''}</div>
      </div></article>`;
  }).join('');
}

/* ══ CLIMA ══ */
async function fetchClima(){
  try{
    const r=await fetch('https://api.open-meteo.com/v1/forecast?latitude=-34.6037&longitude=-58.3816&current=temperature_2m,apparent_temperature,weather_code&timezone=America%2FArgentina%2FBuenos_Aires');
    const d=await r.json();
    const wmap={0:'☀️ Despejado',1:'🌤 Poco nublado',2:'⛅ Parcial',3:'☁️ Nublado',45:'🌫 Neblina',51:'🌦 Llovizna',61:'🌧 Lluvia',63:'🌧 Lluvia mod.',80:'🌦 Chubascos',95:'⛈ Tormenta'};
    const el=$('clima-data');
    if(el) el.innerHTML=`<span class="temp">${Math.round(d.current.temperature_2m)}°</span><div class="clima-info"><span class="desc">${wmap[d.current.weather_code]||'—'}</span><span class="sensacion">Sensación ${Math.round(d.current.apparent_temperature)}°</span></div>`;
  }catch{ const el=$('clima-data'); if(el) el.innerHTML='<span class="temp" style="color:#c62828">—</span>'; }
}

/* ══ TIMERS ══ */
function iniciarTimers(){
  setInterval(()=>{ const r=$('reloj-header'); if(r) r.textContent=new Date().toLocaleTimeString('es-AR'); },1000);
  setInterval(()=>{
    restante--;
    const c=$('contador'); if(c) c.textContent='Actualizando en: '+restante+'s';
    if(restante<=0){ restante=INTERVALO; cargarNoticias(); fetchClima(); }
  },1000);
}
</script>
</body>
</html>