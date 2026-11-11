<?php
// Virgen del Milagro - aviso (webhook) de Wompi. Registra solo los pagos APROBADOS en ce_data/virgen_pagos.jsonl.
// Wompi firma cada evento: se valida con el «secreto de eventos», que se guarda SOLO en el servidor: ce_data/wompi_eventos.key
require __DIR__ . '/lib.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function wp_out($d, $c = 200) { http_response_code($c); echo json_encode($d); exit; }

// Esta cuenta de Wompi es compartida con Concursos del Estado: solo se registran los pagos de ESTOS links de pago.
// El código es lo que va después de checkout.wompi.co/l/
const VG_LINKS = ['hyEaOO' => 'aporte', 'smECiy' => 'suscripcion'];

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') wp_out(['ok' => false], 405);
$kf = ce_dir() . '/wompi_eventos.key';
$secret = is_file($kf) ? trim((string)file_get_contents($kf)) : '';
if ($secret === '') wp_out(['ok' => false, 'e' => 'sin_secreto'], 503);

$raw = file_get_contents('php://input');
$ev = json_decode($raw, true);
if (!is_array($ev) || !isset($ev['data'], $ev['signature']['properties'], $ev['signature']['checksum'], $ev['timestamp'])) wp_out(['ok' => false, 'e' => 'datos'], 400);

// Checksum de Wompi: SHA256( valores de las propiedades indicadas + timestamp + secreto )
$cat = '';
foreach ($ev['signature']['properties'] as $path) {
    $v = $ev['data'];
    foreach (explode('.', (string)$path) as $k) { if (!is_array($v) || !array_key_exists($k, $v)) { $v = ''; break; } $v = $v[$k]; }
    $cat .= is_scalar($v) ? (string)$v : '';
}
$calc = hash('sha256', $cat . $ev['timestamp'] . $secret);
if (!hash_equals($calc, strtolower((string)$ev['signature']['checksum']))) wp_out(['ok' => false, 'e' => 'firma'], 401);

$tx = $ev['data']['transaction'] ?? null;
if (($ev['event'] ?? '') !== 'transaction.updated' || !is_array($tx) || ($tx['status'] ?? '') !== 'APPROVED') wp_out(['ok' => true, 'ignorado' => true]);

$link = (string)($tx['payment_link_id'] ?? '');
if (!isset(VG_LINKS[$link])) {
    // Pago de otro negocio de la cuenta (u otro formato de id): se ignora. Se anota solo el id del link para poder verificarlo.
    @file_put_contents(ce_dir() . '/wompi_ignorados.log', date('c') . ' link=' . preg_replace('/[^A-Za-z0-9_-]/', '', $link) . ' monto=' . (int)round(((int)($tx['amount_in_cents'] ?? 0)) / 100) . "\n", FILE_APPEND);
    wp_out(['ok' => true, 'ignorado' => true]);
}
$monto = (int)round(((int)($tx['amount_in_cents'] ?? 0)) / 100);
$reg = [
    'id' => 'w_' . preg_replace('/[^A-Za-z0-9_-]/', '', (string)($tx['id'] ?? '')),
    'ts' => date('c'), 'fecha' => date('Y-m-d'),
    'nombre' => mb_substr((string)($tx['customer_data']['full_name'] ?? ''), 0, 80),
    'correo' => mb_substr((string)($tx['customer_email'] ?? ''), 0, 120),
    'tipo' => VG_LINKS[$link],
    'monto' => $monto, 'medio' => 'Wompi',
    'ref' => mb_substr((string)($tx['reference'] ?? ''), 0, 80), 'nota' => '', 'src' => 'wompi',
];
$pf = ce_dir() . '/virgen_pagos.jsonl';
$fh = fopen($pf, 'c+');
if (!$fh) wp_out(['ok' => false, 'e' => 'config'], 503);
flock($fh, LOCK_EX);
$dup = false;
while (($l = fgets($fh)) !== false) { $r = json_decode($l, true); if (is_array($r) && ($r['id'] ?? '') === $reg['id']) { $dup = true; break; } }
if (!$dup) { fseek($fh, 0, SEEK_END); fwrite($fh, json_encode($reg, JSON_UNESCAPED_UNICODE) . "\n"); }
flock($fh, LOCK_UN); fclose($fh);
wp_out(['ok' => true, 'nuevo' => !$dup]);
