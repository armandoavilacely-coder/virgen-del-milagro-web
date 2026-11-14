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
    'pagina/historia.html' => $H . '/virgendelmilagro.co/historia.html',
    'pagina/tunja.html' => $H . '/virgendelmilagro.co/tunja.html',
    'pagina/historia/index.html' => $H . '/virgendelmilagro.co/historia/index.html',
    'pagina/tunja/index.html' => $H . '/virgendelmilagro.co/tunja/index.html',
    'pagina/politica-de-datos/index.html' => $H . '/virgendelmilagro.co/politica-de-datos/index.html',
    'pagina/robots.txt' => $H . '/virgendelmilagro.co/robots.txt',
    'pagina/sitemap.xml' => $H . '/virgendelmilagro.co/sitemap.xml',
    'pagina/manifest.webmanifest' => $H . '/virgendelmilagro.co/manifest.webmanifest',
    'pagina/icon-512.png' => $H . '/virgendelmilagro.co/icon-512.png',
    'pagina/icon-192.png' => $H . '/virgendelmilagro.co/icon-192.png',
    'pagina/apple-touch-icon.png' => $H . '/virgendelmilagro.co/apple-touch-icon.png',
    'pagina/favicon-32.png' => $H . '/virgendelmilagro.co/favicon-32.png',
    'pagina/promesas-de-dios/index.html' => $H . '/virgendelmilagro.co/promesas-de-dios/index.html',
    'pagina/oracion-a-la-virgen-del-milagro/index.html' => $H . '/virgendelmilagro.co/oracion-a-la-virgen-del-milagro/index.html',
    'pagina/novena-a-la-virgen-del-milagro/index.html' => $H . '/virgendelmilagro.co/novena-a-la-virgen-del-milagro/index.html',
    'pagina/como-rezar-el-rosario/index.html' => $H . '/virgendelmilagro.co/como-rezar-el-rosario/index.html',
    'pagina/og-virgen.jpg' => $H . '/virgendelmilagro.co/og-virgen.jpg',
    'pagina/cuadro-original.jpg' => $H . '/virgendelmilagro.co/cuadro-original.jpg',
    'pagina/tunja-plaza.webp' => $H . '/virgendelmilagro.co/tunja-plaza.webp',
    'servidor/admin/virgen/index.php' => $H . '/concursosdelestado.com/admin/virgen/index.php',
    'pagina/contenido.js#admin' => $H . '/concursosdelestado.com/admin/virgen/contenido.js',
    'pagina/virgen.webp#admin' => $H . '/concursosdelestado.com/admin/virgen/virgen.webp',
    'servidor/acceso/wompi_virgen.php' => $H . '/concursosdelestado.com/acceso/wompi_virgen.php',
    'servidor/acceso/chat_virgen.php' => $H . '/concursosdelestado.com/acceso/chat_virgen.php',
    'servidor/acceso/virgen_hook.php' => $H . '/concursosdelestado.com/acceso/virgen_hook.php',
    'servidor/acceso/testimonios_virgen.php' => $H . '/concursosdelestado.com/acceso/testimonios_virgen.php',
    'servidor/acceso/suscribir_virgen.php' => $H . '/concursosdelestado.com/acceso/suscribir_virgen.php',
    'servidor/acceso/actualizar_virgen.php' => $H . '/concursosdelestado.com/acceso/actualizar_virgen.php',
    'servidor/acceso/palabra_lib_virgen.php' => $H . '/concursosdelestado.com/acceso/palabra_lib_virgen.php',
    'servidor/acceso/enviar_palabra_virgen.php' => $H . '/concursosdelestado.com/acceso/enviar_palabra_virgen.php',
    'servidor/acceso/baja_virgen.php' => $H . '/concursosdelestado.com/acceso/baja_virgen.php',
    'servidor/ce_data/virgen_palabra.json' => $H . '/ce_data/virgen_palabra.json',
    'servidor/acceso/vg_auth.php' => $H . '/concursosdelestado.com/acceso/vg_auth.php',
    'servidor/dominio_admin/index.php' => $H . '/virgendelmilagro.co/admin/index.php',
    'servidor/dominio_admin/a.php' => $H . '/virgendelmilagro.co/admin/a.php',
    'servidor/dominio_admin/.htaccess' => $H . '/virgendelmilagro.co/admin/.htaccess',
    'pagina/contenido.js#dadmin' => $H . '/virgendelmilagro.co/admin/contenido.js',
    'pagina/virgen.webp#dadmin' => $H . '/virgendelmilagro.co/admin/virgen.webp',
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
    if (!is_dir(dirname($dst)) && (strpos($dst, '/admin/') !== false || preg_match('#/virgendelmilagro\.co/(historia|tunja|politica-de-datos|promesas-de-dios|oracion-a-la-virgen-del-milagro|novena-a-la-virgen-del-milagro|como-rezar-el-rosario)/#', $dst))) @mkdir(dirname($dst), 0755, true);
    if (!is_dir(dirname($dst))) { $res[$src] = 'carpeta destino no existe'; continue; }
    if (is_file($dst) && md5_file($dst) === md5($data)) { $res[$src] = 'igual'; continue; }
    $t = $dst . '.new';
    if (file_put_contents($t, $data) === false || !rename($t, $dst)) { @unlink($t); $res[$src] = 'error al escribir'; continue; }
    $res[$src] = 'actualizado';
}
// Carpeta de promesas (una página y una imagen por promesa): copia solo lo que cumpla el patrón.
$pre = $root . '/pagina/promesa/'; $np = ['actualizado' => 0, 'igual' => 0, 'error' => 0];
for ($zi = 0; $zi < $zip->numFiles; $zi++) {
    $zn = $zip->getNameIndex($zi);
    if (strpos($zn, $pre) !== 0) continue;
    $rel = substr($zn, strlen($pre));
    if (!preg_match('#^(\d{1,3})/(index\\.html|og\\.jpg)$#', $rel)) continue;
    $dst = $H . '/virgendelmilagro.co/promesa/' . $rel;
    if (!is_dir(dirname($dst))) @mkdir(dirname($dst), 0755, true);
    $data = $zip->getFromIndex($zi);
    if ($data === false || $data === '' || !is_dir(dirname($dst))) { $np['error']++; continue; }
    if (is_file($dst) && md5_file($dst) === md5($data)) { $np['igual']++; continue; }
    $t = $dst . '.new';
    if (file_put_contents($t, $data) === false || !rename($t, $dst)) { @unlink($t); $np['error']++; continue; }
    $np['actualizado']++;
}
$res['promesa/*'] = $np;
$zip->close(); @unlink($tmp);
au_out(['ok' => true, 'archivos' => $res, 'hora' => date('c')]);
