<?php
// Virgen del Milagro - envío diario de la «Palabra del día» (promesa + reflexión + oración) a quienes se suscribieron con su correo.
// Lo llama GitHub Actions cada pocos minutos de la mañana. Es seguro llamarlo varias veces: manda como máximo un correo por persona al día,
// solo desde las 6:00 (hora de Bogotá) y en tandas pequeñas. ?dry=1 solo muestra cuántos faltan, sin enviar nada.
require __DIR__ . '/palabra_lib_virgen.php';
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
const PV_HORA = 6;      // hora de Bogotá desde la que se envía
const PV_TANDA = 30;    // correos por llamada
function out($d, $c = 200) { http_response_code($c); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

$hoy = date('Y-m-d');
$o = pv_contenido();
if (!$o) out(['ok' => false, 'e' => 'contenido'], 500);
$sf = ce_dir() . '/virgen_suscriptores.jsonl';
$subs = [];
if (is_file($sf)) foreach (preg_split('/\R/', (string)file_get_contents($sf)) as $l) {
    $r = json_decode($l, true);
    if (is_array($r) && !empty($r['email']) && filter_var($r['email'], FILTER_VALIDATE_EMAIL)) $subs[strtolower($r['email'])] = (string)($r['name'] ?? '');
}
$ef = ce_dir() . '/virgen_envio.json';
$fh = fopen($ef, 'c+'); if (!$fh) out(['ok' => false, 'e' => 'config'], 503);
flock($fh, LOCK_EX);
$st = json_decode(stream_get_contents($fh) ?: '', true);
if (!is_array($st) || ($st['d'] ?? '') !== $hoy) $st = ['d' => $hoy, 'ok' => [], 'fail' => []];
$pend = array_values(array_filter(array_keys($subs), fn($e) => !in_array($e, $st['ok'], true) && (($st['fail'][$e] ?? 0) < 3)));
if (!empty($_GET['dry'])) { flock($fh, LOCK_UN); fclose($fh); out(['ok' => true, 'dry' => true, 'suscriptores' => count($subs), 'enviados_hoy' => count($st['ok']), 'pendientes' => count($pend), 'promesa' => $o['promesa']['c'], 'tema' => $o['tema']]); }
if ((int)date('G') < PV_HORA) { flock($fh, LOCK_UN); fclose($fh); out(['ok' => true, 'e' => 'temprano', 'r' => 'Aún no es la hora de envío.', 'pendientes' => count($pend)]); }
$n = 0;
foreach (array_slice($pend, 0, PV_TANDA) as $e) {
    if (pv_enviar($e, $subs[$e], $o)) { $st['ok'][] = $e; $n++; } else { $st['fail'][$e] = ($st['fail'][$e] ?? 0) + 1; }
    usleep(300000);
    ftruncate($fh, 0); rewind($fh); fwrite($fh, json_encode($st)); fflush($fh);
}
ftruncate($fh, 0); rewind($fh); fwrite($fh, json_encode($st)); flock($fh, LOCK_UN); fclose($fh);
out(['ok' => true, 'enviados' => $n, 'enviados_hoy' => count($st['ok']), 'pendientes' => max(0, count($pend) - $n)]);
