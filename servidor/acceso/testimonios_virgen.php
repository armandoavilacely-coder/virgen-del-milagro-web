<?php
// Virgen del Milagro - testimonios de fe.
// POST: una persona envía su testimonio (anónimo o con su nombre). Queda «pendiente» hasta que el administrador lo apruebe.
// GET : devuelve SOLO los testimonios aprobados (los últimos 40). Nunca expone los pendientes.
// Datos en ce_data/virgen_testimonios.jsonl. Dominios permitidos: ce_data/virgen_origins.txt (el mismo del chat).
require __DIR__ . '/lib.php';

const TS_IP_MAX = 3;       // envíos por IP cada 10 min
const TS_DAY_MAX = 150;    // envíos totales por día (freno contra abuso)
const TS_MIN = 40;         // caracteres mínimos
const TS_MAX = 900;        // caracteres máximos

function ts_out($d, $c = 200) { http_response_code($c); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

header('Cache-Control: no-store');
header('Content-Type: application/json; charset=utf-8');
header('Vary: Origin');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = [];
$of = ce_dir() . '/virgen_origins.txt';
if (is_file($of)) {
    foreach (preg_split('/\R/', (string)file_get_contents($of)) as $l) {
        $l = strtolower(rtrim(trim($l), '/'));
        if ($l !== '' && $l[0] !== '#') $allowed[] = $l;
    }
}
if ($origin === '' || !in_array(strtolower(rtrim($origin, '/')), $allowed, true)) ts_out(['ok' => false, 'e' => 'origen'], 403);
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 600');
$method = $_SERVER['REQUEST_METHOD'] ?? '';
if ($method === 'OPTIONS') { http_response_code(204); exit; }

$file = ce_dir() . '/virgen_testimonios.jsonl';

/* ---- Lectura pública: solo aprobados ---- */
if ($method === 'GET') {
    $out = [];
    if (is_file($file)) {
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
            $r = json_decode($l, true);
            if (!is_array($r) || ($r['estado'] ?? '') !== 'aprobado') continue;
            $out[] = ['n' => ($r['anon'] ?? true) ? 'Anónimo' : (string)($r['name'] ?? 'Anónimo'), 't' => (string)$r['text'], 'd' => substr((string)($r['pub'] ?? $r['ts'] ?? ''), 0, 10)];
        }
    }
    $out = array_slice(array_reverse($out), 0, 40);
    header('Cache-Control: public, max-age=120');
    ts_out(['ok' => true, 'items' => $out]);
}
if ($method !== 'POST') ts_out(['ok' => false, 'e' => 'metodo'], 405);

/* ---- Envío ---- */
$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) ts_out(['ok' => false, 'e' => 'datos'], 400);
if (!empty($in['web'])) ts_out(['ok' => true]);   // campo trampa para robots
if (empty($in['consent'])) ts_out(['ok' => false, 'e' => 'consentimiento', 'r' => 'Marca la casilla para autorizar la publicación de tu testimonio.'], 400);

$text = trim(preg_replace('/[ \t]+/', ' ', preg_replace('/\R{3,}/', "\n\n", strip_tags((string)($in['text'] ?? '')))));
$len = mb_strlen($text);
if ($len < TS_MIN) ts_out(['ok' => false, 'e' => 'corto', 'r' => 'Cuéntanos un poco más (al menos ' . TS_MIN . ' letras).'], 400);
if ($len > TS_MAX) ts_out(['ok' => false, 'e' => 'largo', 'r' => 'Tu testimonio es muy largo. Resúmelo a menos de ' . TS_MAX . ' letras.'], 400);
if (preg_match('~https?://|www\.|\.com\b|\.co\b|@[a-z0-9_.-]+\.[a-z]{2,}~i', $text)) ts_out(['ok' => false, 'e' => 'enlaces', 'r' => 'Por seguridad no publicamos enlaces ni correos. Quítalos y vuelve a enviarlo.'], 400);
if (preg_match('/(?:\d[\s.\-]?){9,}/', $text)) ts_out(['ok' => false, 'e' => 'telefono', 'r' => 'Por tu seguridad no publicamos números de teléfono ni de documento. Quítalos y vuelve a enviarlo.'], 400);

$anon = !isset($in['anon']) || (bool)$in['anon'];
$name = trim(mb_substr(strip_tags((string)($in['name'] ?? '')), 0, 40));
if (!$anon && $name === '') ts_out(['ok' => false, 'e' => 'nombre', 'r' => 'Escribe tu nombre o elige publicarlo como anónimo.'], 400);
if ($anon) $name = '';

/* ---- Límites ---- */
$rf = ce_dir() . '/virgen_test_rate.json';
$fh = fopen($rf, 'c+');
if (!$fh) ts_out(['ok' => false, 'e' => 'config'], 503);
flock($fh, LOCK_EX);
$st = json_decode(stream_get_contents($fh) ?: '', true);
if (!is_array($st)) $st = [];
$now = time();
if (($st['d'] ?? '') !== date('Y-m-d')) $st = ['d' => date('Y-m-d'), 'n' => 0, 'ip' => []];
foreach ($st['ip'] as $k => $l) { $st['ip'][$k] = array_values(array_filter($l, fn($x) => $x > $now - 600)); if (!$st['ip'][$k]) unset($st['ip'][$k]); }
$ip = hash('sha256', ce_ip());
$blocked = false;
if ($st['n'] >= TS_DAY_MAX || count($st['ip'][$ip] ?? []) >= TS_IP_MAX) $blocked = true;
else { $st['n']++; $st['ip'][$ip][] = $now; }
ftruncate($fh, 0); rewind($fh); fwrite($fh, json_encode($st)); flock($fh, LOCK_UN); fclose($fh);
if ($blocked) ts_out(['ok' => false, 'e' => 'limite', 'r' => 'Demasiados envíos. Vuelve a intentarlo en unos minutos.'], 429);

/* ---- Guardar como pendiente (sin duplicados exactos) ---- */
$fh = fopen($file, 'c+');
if (!$fh) ts_out(['ok' => false, 'e' => 'config'], 503);
flock($fh, LOCK_EX);
$hash = hash('sha256', mb_strtolower($text));
$dup = false;
while (($line = fgets($fh)) !== false) { $r = json_decode($line, true); if (is_array($r) && ($r['h'] ?? '') === $hash) { $dup = true; break; } }
if (!$dup) {
    fseek($fh, 0, SEEK_END);
    fwrite($fh, json_encode([
        'id' => 't_' . bin2hex(random_bytes(5)), 'ts' => date('c'), 'estado' => 'pendiente',
        'anon' => $anon, 'name' => $name, 'text' => $text, 'h' => $hash, 'src' => 'virgendelmilagro.co',
    ], JSON_UNESCAPED_UNICODE) . "\n");
}
flock($fh, LOCK_UN); fclose($fh);

ts_out(['ok' => true]);
