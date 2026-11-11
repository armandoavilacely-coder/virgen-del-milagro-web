<?php
// Virgen del Milagro - actualizador. Baja la última versión del repo público de GitHub y copia cada archivo a su lugar.
// No necesita clave: solo puede publicar lo que ya está en el repo, y un freno evita llamadas seguidas.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const REPO_ZIP = 'https://codeload.github.com/armandoavilacely-coder/virgen-del-milagro-web/zip/refs/heads/main';
const MIN_GAP = 20; // segundos entre actualizaciones

function au_out($d, $c = 200) { http_response_code($c); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

$H = getenv('VG_HOME') ?: dirname(__DIR__, 2);   // /home2/arielar1
$MAP = [   // archivo en el repo => destino en el servidor
    'pagina/index.html' => $H . '/virgendelmilagro.co/index.html',
    'pagina/virgen.webp' => $H . '/virgendelmilagro.co/virgen.webp',
    'pagina/contenido.js' => $H . '/virgendelmilagro.co/contenido.js',
    'servidor/admin/virgen/index.php' => $H . '/concursosdelestado.com/admin/virgen/index.php',
    'pagina/contenido.js#admin' => $H . '/concursosdelestado.com/admin/virgen/contenido.js',
    'pagina/virgen.webp#admin' => $H . '/concursosdelestado.com/admin/virgen/virgen.webp',
    'servidor/acceso/wompi_virgen.php' => $H . '/concursosdelestado.com/acceso/wompi_virgen.php',
    'servidor/acceso/chat_virgen.php' => $H . '/concursosdelestado.com/acceso/chat_virgen.php',
    'servidor/acceso/virgen_hook.php' => $H . '/concursosdelestado.com/acceso/virgen_hook.php',
    'servidor/acceso/testimonios_virgen.php' => $H . '/concursosdelestado.com/acceso/testimonios_virgen.php',
    'servidor/acceso/suscribir_virgen.php' => $H . '/concursosdelestado.com/acceso/suscribir_virgen.php',
    'servidor/acceso/actualizar_virgen.php' => $H . '/concursosdelestado.com/acceso/actualizar_virgen.php',
    'servidor/ce_data/virgen_origins.txt' => $H . '/ce_data/virgen_origins.txt',
];

$lock = $H . '/ce_data/virgen_update.lock';
$last = is_file($lock) ? (int)file_get_contents($lock) : 0;
if (time() - $last < MIN_GAP) au_out(['ok' => false, 'e' => 'espera', 'r' => 'Actualizado hace un momento; espera unos segundos.'], 429);
file_put_contents($lock, (string)time());

if (!class_exists('ZipArchive') || !function_exists('curl_init')) au_out(['ok' => false, 'e' => 'servidor', 'r' => 'Falta ZipArchive o curl en el servidor.'], 500);

$tmp = tempnam(sys_get_temp_dir(), 'vg');
$fp = fopen($tmp, 'w');
$ch = curl_init(REPO_ZIP);
curl_setopt_array($ch, [CURLOPT_FILE => $fp, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 40, CURLOPT_USERAGENT => 'virgen-actualizar']);
$okc = curl_exec($ch); $http = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); fclose($fp);
if (!$okc || $http !== 200) { @unlink($tmp); au_out(['ok' => false, 'e' => 'descarga', 'r' => 'No se pudo bajar el repo (HTTP ' . $http . ').'], 502); }

$zip = new ZipArchive();
if ($zip->open($tmp) !== true) { @unlink($tmp); au_out(['ok' => false, 'e' => 'zip'], 502); }
$root = rtrim((string)$zip->getNameIndex(0), '/');   // virgen-del-milagro-web-main
$res = [];
foreach ($MAP as $src => $dst) {
    $data = $zip->getFromName($root . '/' . explode('#', $src)[0]);
    if ($data === false || $data === '') { $res[$src] = 'no encontrado'; continue; }
    if (!is_dir(dirname($dst)) && strpos($dst, '/admin/virgen/') !== false) @mkdir(dirname($dst), 0755, true);
    if (!is_dir(dirname($dst))) { $res[$src] = 'carpeta destino no existe'; continue; }
    if (is_file($dst) && md5_file($dst) === md5($data)) { $res[$src] = 'igual'; continue; }
    $t = $dst . '.new';
    if (file_put_contents($t, $data) === false || !rename($t, $dst)) { @unlink($t); $res[$src] = 'error al escribir'; continue; }
    $res[$src] = 'actualizado';
}
$zip->close(); @unlink($tmp);
au_out(['ok' => true, 'archivos' => $res, 'hora' => date('c')]);
