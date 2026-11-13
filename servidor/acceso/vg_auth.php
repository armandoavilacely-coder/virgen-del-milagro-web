<?php
// Virgen del Milagro - inicio de sesión propio del panel en virgendelmilagro.co/admin (independiente del panel de Concursos del Estado).
// - Solo funciona por HTTPS (con el candado). Contraseña guardada con hash en ce_data/virgen_admin.json.
// - La primera vez pide un código de instalación (se entrega aparte) y deja que la persona escriba su contraseña.
// - Frenos: 5 intentos fallidos por IP cada 15 min; sesión de 8 horas, con cierre a los 60 min sin uso; cookie Secure, HttpOnly y SameSite=Strict.
require_once __DIR__ . '/lib.php';
const VGA_SETUP_HASH = 'e2151d52f7905d7ab2aa9b94361fb2e7ec15c7c57d02583b8ca9d424700bae8e';
const VGA_COOKIE = 'vgadm';

function vga_https() { return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'; }
function vga_f($n) { return ce_dir() . '/' . $n; }
function vga_rd($n) { $d = is_file(vga_f($n)) ? json_decode((string)file_get_contents(vga_f($n)), true) : null; return is_array($d) ? $d : []; }
function vga_wr($n, $d) { file_put_contents(vga_f($n), json_encode($d), LOCK_EX); @chmod(vga_f($n), 0600); }
function vga_h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function vga_page($titulo, $cuerpo, $code = 200) {
    http_response_code($code); header('Content-Type: text/html; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex, nofollow');
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Administración · Virgen del Milagro</title></head>'
       . '<body style="margin:0;background:#FBF4E4;font-family:Georgia,serif;color:#2B2118"><div style="max-width:420px;margin:10vh auto;padding:0 20px"><div style="text-align:center;font-size:28px;font-style:italic;color:#7A4E00">Virgen del Milagro</div>'
       . '<h1 style="font-size:20px;text-align:center;margin:8px 0 18px">' . $titulo . '</h1><div style="background:#fff;border:1px solid #E8D3A0;border-radius:16px;padding:20px;font-size:16px;line-height:1.5">' . $cuerpo . '</div></div></body></html>';
    exit;
}
function vga_form($extra, $btn) {
    return '<form method="post" autocomplete="off">' . $extra . '<button style="width:100%;min-height:48px;margin-top:14px;background:#14304F;color:#fff;border:0;border-radius:999px;font-size:16px;font-weight:bold;cursor:pointer">' . $btn . '</button></form>';
}
function vga_input($name, $label, $type = 'password') {
    return '<label style="display:block;margin-top:10px;font-size:14px;font-weight:bold">' . $label . '<input name="' . $name . '" type="' . $type . '" required style="display:block;width:100%;box-sizing:border-box;min-height:48px;margin-top:4px;padding:0 12px;border:1px solid #C9B27A;border-radius:10px;font:inherit;font-size:16px"></label>';
}
function vga_limit($ok) {   // devuelve false si hay demasiados fallos recientes de esta IP
    $st = vga_rd('virgen_admin_rate.json'); $now = time(); $ip = hash('sha256', ce_ip());
    foreach ($st as $k => $l) { $st[$k] = array_values(array_filter($l, fn($x) => $x > $now - 900)); if (!$st[$k]) unset($st[$k]); }
    if (!$ok && count($st[$ip] ?? []) >= 5) return false;
    return true;
}
function vga_fail() { $st = vga_rd('virgen_admin_rate.json'); $ip = hash('sha256', ce_ip()); $st[$ip][] = time(); vga_wr('virgen_admin_rate.json', $st); }

function vga_session_ok() {
    $t = $_COOKIE[VGA_COOKIE] ?? ''; if (!preg_match('/^[0-9a-f]{64}$/', $t)) return false;
    $S = vga_rd('virgen_admin_sessions.json'); $h = hash('sha256', $t); $now = time();
    foreach ($S as $k => $s) if (($s['exp'] ?? 0) < $now || ($s['last'] ?? 0) < $now - 3600) unset($S[$k]);
    if (!isset($S[$h])) { vga_wr('virgen_admin_sessions.json', $S); return false; }
    $S[$h]['last'] = $now; vga_wr('virgen_admin_sessions.json', $S); return true;
}
function vga_cookie($val, $exp) { setcookie(VGA_COOKIE, $val, ['expires' => $exp, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Strict']); }

/* Llamar al inicio del panel. Si no hay sesión válida, muestra instalación o acceso y termina. */
function vga_require() {
    if (!vga_https()) vga_page('Falta el candado (HTTPS)', 'Por seguridad, el acceso al panel solo funciona cuando la página abre con <b>https://</b>. Entra por <b>https://virgendelmilagro.co/admin/</b>. Si el navegador dice «No seguro», el certificado aún no está activo.', 403);
    if (($_GET['salir'] ?? '') === '1') {
        $t = $_COOKIE[VGA_COOKIE] ?? ''; $S = vga_rd('virgen_admin_sessions.json'); unset($S[hash('sha256', $t)]); vga_wr('virgen_admin_sessions.json', $S);
        vga_cookie('', time() - 3600); header('Location: ./'); exit;
    }
    if (vga_session_ok()) return;
    $cfg = vga_rd('virgen_admin.json'); $post = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    if (!$cfg) {   // primera vez
        $msg = '';
        if ($post) {
            if (!vga_limit(false)) vga_page('Demasiados intentos', 'Espera 15 minutos e inténtalo de nuevo.', 429);
            $code = strtoupper(trim((string)($_POST['codigo'] ?? ''))); $p1 = (string)($_POST['p1'] ?? ''); $p2 = (string)($_POST['p2'] ?? '');
            if (!hash_equals(VGA_SETUP_HASH, hash('sha256', $code))) { vga_fail(); $msg = 'El código de instalación no es correcto.'; }
            elseif ($p1 !== $p2) $msg = 'Las dos contraseñas no coinciden.';
            elseif (mb_strlen($p1) < 12) $msg = 'Usa al menos 12 caracteres (puede ser una frase).';
            else { vga_wr('virgen_admin.json', ['hash' => password_hash($p1, PASSWORD_DEFAULT), 'ts' => date('c')]); header('Location: ./'); exit; }
        }
        vga_page('Crear contraseña del panel', ($msg ? '<p style="color:#9B2C2C;margin:0 0 6px"><b>' . vga_h($msg) . '</b></p>' : '<p style="margin:0 0 4px">Escribe el código de instalación y elige tu contraseña. Solo tú debes conocerla.</p>')
            . vga_form(vga_input('codigo', 'Código de instalación', 'text') . vga_input('p1', 'Nueva contraseña (mínimo 12 caracteres)') . vga_input('p2', 'Repite la contraseña'), 'Crear contraseña'));
    }
    $msg = '';
    if ($post) {
        if (!vga_limit(false)) vga_page('Demasiados intentos', 'Espera 15 minutos e inténtalo de nuevo.', 429);
        if (password_verify((string)($_POST['pw'] ?? ''), (string)($cfg['hash'] ?? ''))) {
            $tok = bin2hex(random_bytes(32)); $S = vga_rd('virgen_admin_sessions.json'); $now = time();
            $S[hash('sha256', $tok)] = ['exp' => $now + 8 * 3600, 'last' => $now];
            vga_wr('virgen_admin_sessions.json', $S); vga_cookie($tok, $now + 8 * 3600); header('Location: ./'); exit;
        }
        vga_fail(); usleep(600000); $msg = 'Contraseña incorrecta.';
    }
    vga_page('Administración', ($msg ? '<p style="color:#9B2C2C;margin:0 0 6px"><b>' . vga_h($msg) . '</b></p>' : '') . vga_form(vga_input('pw', 'Contraseña'), 'Entrar'));
}
