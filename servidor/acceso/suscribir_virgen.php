<?php
// Virgen del Milagro - suscripción a la «Palabra del día».
// Guarda correo (y WhatsApp opcional) con consentimiento en ce_data/virgen_suscriptores.jsonl.
// Los dominios permitidos viven en ce_data/virgen_origins.txt (el mismo archivo del chat).
require __DIR__ . '/lib.php';

const SV_IP_MAX = 5;        // intentos por IP cada 10 min
const SV_DAY_MAX = 500;     // altas totales por día (freno contra abuso)

function sv_out($d, $c = 200) { http_response_code($c); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

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
if ($origin === '' || !in_array(strtolower(rtrim($origin, '/')), $allowed, true)) sv_out(['ok' => false, 'e' => 'origen'], 403);
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 600');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') sv_out(['ok' => false, 'e' => 'metodo'], 405);

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) sv_out(['ok' => false, 'e' => 'datos'], 400);

// Campo trampa: las personas no lo ven; los robots sí lo llenan.
if (!empty($in['web'])) sv_out(['ok' => true]);

if (empty($in['consent'])) sv_out(['ok' => false, 'e' => 'consentimiento', 'r' => 'Debes aceptar el tratamiento de tus datos para suscribirte.'], 400);

$email = strtolower(trim((string)($in['email'] ?? '')));
$wa = preg_replace('/[^0-9]/', '', (string)($in['wa'] ?? ''));
$name = trim(mb_substr(strip_tags((string)($in['name'] ?? '')), 0, 60));
if ($email === '' && $wa === '') sv_out(['ok' => false, 'e' => 'contacto', 'r' => 'Escribe tu correo o tu número de WhatsApp.'], 400);
if ($email !== '' && (strlen($email) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL))) sv_out(['ok' => false, 'e' => 'correo', 'r' => 'Revisa tu correo: parece que le falta algo.'], 400);
if ($wa !== '' && (strlen($wa) < 10 || strlen($wa) > 15)) sv_out(['ok' => false, 'e' => 'wa', 'r' => 'Revisa tu número de WhatsApp (con indicativo, por ejemplo 57 300 123 4567).'], 400);

/* ---- Límites ---- */
$rf = ce_dir() . '/virgen_subs_rate.json';
$fh = fopen($rf, 'c+');
if (!$fh) sv_out(['ok' => false, 'e' => 'config'], 503);
flock($fh, LOCK_EX);
$st = json_decode(stream_get_contents($fh) ?: '', true);
if (!is_array($st)) $st = [];
$now = time();
if (($st['d'] ?? '') !== date('Y-m-d')) $st = ['d' => date('Y-m-d'), 'n' => 0, 'ip' => []];
foreach ($st['ip'] as $k => $l) { $st['ip'][$k] = array_values(array_filter($l, fn($x) => $x > $now - 600)); if (!$st['ip'][$k]) unset($st['ip'][$k]); }
$ip = hash('sha256', ce_ip());
$blocked = false;
if ($st['n'] >= SV_DAY_MAX || count($st['ip'][$ip] ?? []) >= SV_IP_MAX) $blocked = true;
else { $st['n']++; $st['ip'][$ip][] = $now; }
ftruncate($fh, 0); rewind($fh); fwrite($fh, json_encode($st)); flock($fh, LOCK_UN); fclose($fh);
if ($blocked) sv_out(['ok' => false, 'e' => 'limite', 'r' => 'Demasiados intentos. Vuelve a intentarlo en unos minutos.'], 429);

/* ---- Guardar (sin duplicados) ---- */
$sf = ce_dir() . '/virgen_suscriptores.jsonl';
$fh = fopen($sf, 'c+');
if (!$fh) sv_out(['ok' => false, 'e' => 'config'], 503);
flock($fh, LOCK_EX);
$dup = false;
while (($line = fgets($fh)) !== false) {
    $r = json_decode($line, true);
    if (!is_array($r)) continue;
    if (($email !== '' && ($r['email'] ?? '') === $email) || ($wa !== '' && ($r['wa'] ?? '') === $wa)) { $dup = true; break; }
}
if (!$dup) {
    fseek($fh, 0, SEEK_END);
    fwrite($fh, json_encode([
        'ts' => date('c'), 'name' => $name, 'email' => $email, 'wa' => $wa,
        'consent' => true, 'src' => 'virgendelmilagro.co',
    ], JSON_UNESCAPED_UNICODE) . "\n");
}
flock($fh, LOCK_UN); fclose($fh);

sv_out(['ok' => true]);
