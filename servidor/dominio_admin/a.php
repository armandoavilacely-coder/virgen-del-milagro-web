<?php
// Entrega las imágenes y archivos del panel (facebook/, fondos/) solo con sesión iniciada.
require dirname(__DIR__, 2) . '/concursosdelestado.com/acceso/vg_auth.php';
if (!vga_https() || !vga_session_ok()) { http_response_code(403); exit; }
$d = $_GET['d'] ?? ''; $f = (string)($_GET['f'] ?? '');
if (!in_array($d, ['facebook', 'fondos'], true) || !preg_match('#^[A-Za-z0-9_.-]{1,120}$#', $f) || $f[0] === '.') { http_response_code(404); exit; }
$p = dirname(__DIR__, 2) . '/concursosdelestado.com/admin/virgen/' . $d . '/' . $f;
if (!is_file($p)) { http_response_code(404); exit; }
$tipos = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'js' => 'text/javascript; charset=utf-8', 'json' => 'application/json'];
$e = strtolower(pathinfo($f, PATHINFO_EXTENSION));
if (!isset($tipos[$e])) { http_response_code(404); exit; }
header('Content-Type: ' . $tipos[$e]); header('X-Content-Type-Options: nosniff'); header('Cache-Control: private, max-age=300'); header('X-Robots-Tag: noindex');
readfile($p);
