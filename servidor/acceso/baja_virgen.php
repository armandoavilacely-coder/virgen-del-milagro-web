<?php
// Virgen del Milagro - darse de baja de la «Palabra del día». GET muestra la confirmación; POST (botón o baja de un clic del correo) borra el correo.
require __DIR__ . '/palabra_lib_virgen.php';
header('Cache-Control: no-store'); header('Content-Type: text/html; charset=utf-8');
$e = strtolower(trim((string)($_GET['e'] ?? ''))); $t = (string)($_GET['t'] ?? '');
$ok = $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL) && hash_equals(pv_token($e), $t);
function pagina($titulo, $texto, $form = '') {
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Virgen del Milagro</title></head>'
       . '<body style="margin:0;background:#FBF4E4;font-family:Georgia,serif;color:#2B2118"><div style="max-width:480px;margin:12vh auto;padding:0 20px;text-align:center">'
       . '<div style="font-size:30px;font-style:italic;color:#7A4E00">Virgen del Milagro</div><h1 style="font-size:22px">' . $titulo . '</h1><p style="font-size:17px;line-height:1.6">' . $texto . '</p>' . $form . '</div></body></html>'; exit;
}
if (!$ok) { http_response_code(400); pagina('Enlace no válido', 'Este enlace no es válido o ya venció. Si quieres dejar de recibir la Palabra del día, escríbenos a info@concursosdelestado.com.'); }
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $sf = ce_dir() . '/virgen_suscriptores.jsonl';
    $fh = fopen($sf, 'c+');
    if ($fh) {
        flock($fh, LOCK_EX); $keep = [];
        while (($l = fgets($fh)) !== false) { $r = json_decode($l, true); if (is_array($r) && strtolower((string)($r['email'] ?? '')) === $e) { if (!empty($r['wa'])) { $r['email'] = ''; $keep[] = json_encode($r, JSON_UNESCAPED_UNICODE); } continue; } $keep[] = rtrim($l); }
        ftruncate($fh, 0); rewind($fh); fwrite($fh, $keep ? implode("\n", $keep) . "\n" : ''); flock($fh, LOCK_UN); fclose($fh);
    }
    pagina('Listo, ya no recibirás la Palabra del día', 'Borramos tu correo de nuestra lista. Que Dios te bendiga siempre; aquí tienes tu casa cuando quieras volver.');
}
pagina('¿Dejar de recibir la Palabra del día?', 'Confirma para borrar <b>' . pv_h($e) . '</b> de la lista.', '<form method="post" style="margin-top:18px"><button style="background:#C9962E;border:0;border-radius:999px;padding:12px 26px;font-size:16px;font-weight:bold;cursor:pointer">Sí, darme de baja</button></form>');
