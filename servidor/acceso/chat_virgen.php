<?php
// Virgen del Milagro - chat bíblico (Claude Haiku).
// Usa la misma llave de Rocky (ce_data/chat.key) y las mismas funciones (chat_call), pero con instrucciones propias
// y límites propios. No toca el chat de Rocky. Los dominios permitidos viven en ce_data/virgen_origins.txt.
require __DIR__ . '/lib.php';
require __DIR__ . '/chat_core.php';

const VG_MAX_USER = 600;     // caracteres por mensaje de la persona
const VG_MAX_ASSIST = 1600;  // caracteres por respuesta anterior (contexto)
const VG_MAX_HIST = 8;       // mensajes de contexto
const VG_IP_MAX = 12;        // mensajes por IP cada 10 min
const VG_DAY_MAX = 300;      // mensajes totales por día (tope de gasto de esta página)

function vg_out($d, $c = 200) { http_response_code($c); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

header('Cache-Control: no-store');
header('Content-Type: application/json; charset=utf-8');
header('Vary: Origin');

/* ---- Origen permitido (CORS): un dominio por línea en ce_data/virgen_origins.txt, con https:// y sin barra final ---- */
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = [];
$of = ce_dir() . '/virgen_origins.txt';
if (is_file($of)) {
    foreach (preg_split('/\R/', (string)file_get_contents($of)) as $l) {
        $l = strtolower(rtrim(trim($l), '/'));
        if ($l !== '' && $l[0] !== '#') $allowed[] = $l;
    }
}
if ($origin === '' || !in_array(strtolower(rtrim($origin, '/')), $allowed, true)) vg_out(['ok' => false, 'e' => 'origen'], 403);
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 600');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') vg_out(['ok' => false, 'e' => 'metodo'], 405);

$keyf = ce_dir() . '/chat.key';
$key = is_file($keyf) ? trim((string)file_get_contents($keyf)) : '';
if ($key === '') vg_out(['ok' => false, 'e' => 'config'], 503);

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in) || !isset($in['m']) || !is_array($in['m'])) vg_out(['ok' => false, 'e' => 'datos'], 400);

/* ---- Versión de la Biblia (lista cerrada) ---- */
$VERS = [
    'BLA' => 'Biblia Latinoamericana',
    'BJ' => 'Biblia de Jerusalén',
    'DHH' => 'Dios Habla Hoy (DHH)',
    'NVI' => 'Nueva Versión Internacional (NVI)',
    'RVR1960' => 'Reina-Valera 1960',
];
$vid = (string)($in['v'] ?? 'BLA');
$vname = $VERS[$vid] ?? $VERS['BLA'];

/* ---- Mensajes: solo texto, roles alternados, empieza y termina en la persona ---- */
$msgs = [];
foreach (array_slice($in['m'], -VG_MAX_HIST) as $m) {
    if (!is_array($m) || !isset($m['r'], $m['t']) || !is_string($m['t'])) continue;
    $asst = $m['r'] === 'a';
    $t = trim(mb_substr($m['t'], 0, $asst ? VG_MAX_ASSIST : VG_MAX_USER));
    if ($t === '') continue;
    $msgs[] = ['role' => $asst ? 'assistant' : 'user', 'content' => $t];
}
while ($msgs && $msgs[0]['role'] !== 'user') array_shift($msgs);
$clean = [];
foreach ($msgs as $m) { if ($clean && end($clean)['role'] === $m['role']) array_pop($clean); $clean[] = $m; }
if (!$clean || end($clean)['role'] !== 'user') vg_out(['ok' => false, 'e' => 'datos'], 400);

/* ---- Límites de esta página (archivo propio, distinto al de Rocky) ---- */
$rf = ce_dir() . '/virgen_rate.json';
$fh = fopen($rf, 'c+');
if (!$fh) vg_out(['ok' => false, 'e' => 'config'], 503);
flock($fh, LOCK_EX);
$st = json_decode(stream_get_contents($fh) ?: '', true);
if (!is_array($st)) $st = [];
$now = time();
if (($st['d'] ?? '') !== date('Y-m-d')) $st = ['d' => date('Y-m-d'), 'n' => 0, 'ip' => []];
foreach ($st['ip'] as $k => $l) { $st['ip'][$k] = array_values(array_filter($l, fn($x) => $x > $now - 600)); if (!$st['ip'][$k]) unset($st['ip'][$k]); }
$ip = hash('sha256', ce_ip());
$blocked = null;
if ($st['n'] >= VG_DAY_MAX) $blocked = 'dia';
elseif (count($st['ip'][$ip] ?? []) >= VG_IP_MAX) $blocked = 'ip';
else { $st['n']++; $st['ip'][$ip][] = $now; }
ftruncate($fh, 0); rewind($fh); fwrite($fh, json_encode($st)); flock($fh, LOCK_UN); fclose($fh);
if ($blocked) vg_out(['ok' => true, 'lim' => true, 'r' => $blocked === 'ip'
    ? 'Hiciste muchas preguntas seguidas. Espera unos minutos y vuelve a intentarlo.'
    : 'Hoy el chat alcanzó su límite de mensajes. Vuelve mañana, te esperamos con la Palabra.']);

/* ---- Instrucciones para Claude ---- */
$system = implode("\n", [
    'Eres «Palabra para hoy», el acompañante de lectura bíblica de la página de la Virgen del Milagro de Tunja (Boyacá, Colombia). Hablas con personas cristianas, en su mayoría católicas. Responde siempre en español.',
    '',
    'TONO: cercano y cálido, de tú, con palabras sencillas. No sermonees ni juzgues. Respeta a católicos y evangélicos y no entres en polémicas entre denominaciones; si la pregunta es doctrinal y discutida, presenta las posturas con respeto y sugiere hablar con su párroco, pastor o líder de confianza.',
    'CONTEXTO: piensa en la vida cotidiana en Colombia (familia, empleo informal y el rebusque, deudas, duelo y violencia, desplazamiento, campo, migración) sin politizar y sin forzar regionalismos.',
    'VERSIÓN: ' . $vname . '. Da la cita exacta (libro capítulo:versículo) y no inventes citas. Transcribe el texto en esa versión solo si lo recuerdas con seguridad; si no, resume el versículo en una frase y escribe «(paráfrasis)» al final. Máximo 3 versículos, cortos, que respondan de verdad a lo que la persona vive.',
    'MARÍA: si encaja de forma natural con lo que la persona cuenta, puedes incluir un pasaje sobre María (por ejemplo Lucas 1,46-55; Juan 2,1-11; Juan 19,25-27) y cerrar la oración con «Santa María, Virgen del Milagro, ruega por nosotros». No lo fuerces en cada respuesta.',
    'LÍMITES: este chat es solo de acompañamiento bíblico; si piden otra cosa (tareas, ventas, política, consejos legales o médicos), responde con amabilidad que aquí solo acompañas con la Palabra y devuelve la conversación a lo que la persona vive. No prometas sanidades, prosperidad ni resultados, y no uses la Biblia para culpar. No sustituyas la atención médica o psicológica: si el tema lo pide, recomienda buscarla con calma. No pidas ni repitas datos personales. Si la persona expresa deseos de hacerse daño o de morir, responde solo con INTRO (y un VERSO de consuelo si encaja): con calidez, anímala a hablar ahora mismo con alguien de confianza y a llamar a la Línea 192, opción 4 (salud mental, Colombia), o al 123 si hay peligro inmediato.',
    '',
    'FORMATO EXACTO (texto plano, sin markdown ni asteriscos; cada etiqueta empieza en una línea nueva):',
    'INTRO: una o dos frases que reconozcan lo que la persona vive.',
    'VERSO: Cita | texto del versículo   (una línea por versículo)',
    'REFLEXION: dos a cuatro frases que conecten los versículos con su situación.',
    'ORACION: dos o tres frases, en primera persona, para que la persona la haga suya.',
    'Si el mensaje es un saludo, un agradecimiento o una aclaración, responde solo con INTRO (1 a 3 frases) y, si ayuda, una pregunta corta.',
]);

list($txt, $sr, $code) = chat_call($key, $clean, $system);
if ($txt === '') vg_out(['ok' => true, 'lim' => true, 'r' => 'En este momento no puedo responder. Intenta de nuevo en un minuto.']);
vg_out(['ok' => true, 'r' => $txt, 'sr' => $sr]);
