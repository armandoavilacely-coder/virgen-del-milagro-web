<?php
// Virgen del Milagro - inicio de sesión propio del panel en virgendelmilagro.co/admin (independiente del panel de Concursos del Estado).
// - Solo funciona por HTTPS (con el candado). Contraseña guardada con hash en ce_data/virgen_admin.json.
// - La primera vez pide un código de instalación (se entrega aparte) y deja que la persona escriba su contraseña.
// - Frenos: 5 intentos fallidos por IP cada 15 min; sesión de 8 horas, con cierre a los 60 min sin uso; cookie Secure, HttpOnly y SameSite=Strict.
require_once __DIR__ . '/lib.php';
const VGA_SETUP_HASH = 'a4c5005727e30b7d502b2195f176036e264b8e4cf640a9aecebd3c6a23ba0b1b';
const VGA_COOKIE = 'vgadm';
const VGA_GOOGLE_CLIENT = '';   // ID de cliente de Google (público), p. ej. 123-abc.apps.googleusercontent.com
function vga_gclient() { $f = ce_dir() . '/virgen_google_client.txt'; $c = VGA_GOOGLE_CLIENT ?: (is_file($f) ? trim((string)file_get_contents($f)) : ''); return preg_match('/^[0-9A-Za-z._-]+\.apps\.googleusercontent\.com$/', $c) ? $c : ''; }
function vga_gverify($cred, $cid) {   // valida el token de Google con Google y devuelve el correo verificado
    $ch = curl_init('https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode($cred)); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8]);
    $r = curl_exec($ch); curl_close($ch); $j = json_decode((string)$r, true);
    if (!is_array($j) || ($j['aud'] ?? '') !== $cid || !in_array((string)($j['iss'] ?? ''), ['accounts.google.com', 'https://accounts.google.com'], true) || ($j['exp'] ?? 0) < time() || !in_array((string)($j['email_verified'] ?? ''), ['true', '1'], true)) return '';
    return strtolower((string)($j['email'] ?? ''));
}

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
    return '<form method="post">' . $extra . '<button style="width:100%;min-height:48px;margin-top:14px;background:#14304F;color:#fff;border:0;border-radius:999px;font-size:16px;font-weight:bold;cursor:pointer">' . $btn . '</button></form>';
}
function vga_input($name, $label, $type = 'password') {
    return '<label style="display:block;margin-top:10px;font-size:14px;font-weight:bold">' . $label . '<input name="' . $name . '" type="' . $type . '"' . ($name === 'correo' ? ' autocomplete="username"' : ($name === 'pw' ? ' autocomplete="current-password"' : ($name === 'p1' || $name === 'p2' ? ' autocomplete="new-password"' : ''))) . ' required style="display:block;width:100%;box-sizing:border-box;min-height:48px;margin-top:4px;padding:0 12px;border:1px solid #C9B27A;border-radius:10px;font:inherit;font-size:16px"></label>';
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
    if (!$cfg) {   // primera vez: código de instalación + contraseña
        $msg = '';
        if ($post) {
            if (!vga_limit(false)) vga_page('Demasiados intentos', 'Espera 15 minutos e inténtalo de nuevo.', 429);
            $code = strtoupper(trim((string)($_POST['codigo'] ?? ''))); $p1 = (string)($_POST['p1'] ?? ''); $p2 = (string)($_POST['p2'] ?? '');
            if (!hash_equals(VGA_SETUP_HASH, hash('sha256', $code))) { vga_fail(); $msg = 'El código de instalación no es correcto.'; }
            elseif ($p1 !== $p2) $msg = 'Las dos contraseñas no coinciden.';
            elseif (mb_strlen($p1) < 12) $msg = 'Usa al menos 12 caracteres (puede ser una frase).';
            else { vga_wr('virgen_admin.json', ['hash' => password_hash($p1, PASSWORD_DEFAULT), 'ts' => date('c')]); header('Location: ./'); exit; }
        }
        vga_page('Crear contraseña del panel', ($msg ? '<p style="color:#9B2C2C;margin:0 0 6px"><b>' . vga_h($msg) . '</b></p>' : '<p style="margin:0 0 4px">Escribe el código de instalación y elige tu contraseña. Solo tú debes conocerla. Después activarás la verificación en dos pasos.</p>')
            . vga_form(vga_input('codigo', 'Código de instalación', 'text') . vga_input('p1', 'Nueva contraseña (mínimo 12 caracteres)') . vga_input('p2', 'Repite la contraseña'), 'Crear contraseña'));
    }
    // Paso 1: correo + contraseña -> "pre-sesión" de 10 minutos
    if (!vga_pre()) {
        $msg = '';
        if ($post && isset($_POST['gcred']) && vga_gclient()) {
            if (!vga_limit(false)) vga_page('Demasiados intentos', 'Espera 15 minutos e inténtalo de nuevo.', 429);
            $ge = vga_gverify((string)$_POST['gcred'], vga_gclient());
            if ($ge && !empty($cfg['email']) && hash_equals((string)$cfg['email'], $ge)) { vga_pre_new(); header('Location: ./'); exit; }
            vga_fail(); $msg = $cfg['email'] ?? '' ? 'Esa cuenta de Google no está autorizada.' : 'Primero ingresa una vez con correo y contraseña para ligar tu correo.';
        }
        if ($post && isset($_POST['pw'])) {
            if (!vga_limit(false)) vga_page('Demasiados intentos', 'Espera 15 minutos e inténtalo de nuevo.', 429);
            $em = strtolower(trim((string)($_POST['correo'] ?? '')));
            $okPw = password_verify((string)$_POST['pw'], (string)($cfg['hash'] ?? ''));
            if (!filter_var($em, FILTER_VALIDATE_EMAIL)) { $msg = 'Escribe un correo válido.'; }
            elseif ($okPw && empty($cfg['email'])) {   // primer ingreso con correo: queda ligado a esta cuenta
                $cfg['email'] = $em; vga_wr('virgen_admin.json', $cfg); vga_pre_new(); header('Location: ./'); exit;
            }
            elseif ($okPw && hash_equals((string)$cfg['email'], $em)) { vga_pre_new(); header('Location: ./'); exit; }
            else { vga_fail(); usleep(600000); $msg = 'Correo o contraseña incorrectos.'; }
        }
        $primera = empty($cfg['email']);
        vga_page('Administración', ($msg ? '<p style="color:#9B2C2C;margin:0 0 6px"><b>' . vga_h($msg) . '</b></p>' : '')
            . ($primera ? '<p style="margin:0 0 4px;font-size:14px">Primer ingreso con correo: el que escribas ahora quedará como el correo de acceso del panel.</p>' : '')
            . vga_form(vga_input('correo', 'Correo electrónico', 'email') . vga_input('pw', 'Contraseña'), 'Continuar')
            . (vga_gclient() ? '<div style="text-align:center;margin:16px 0 4px;font-size:14px;color:#666">o</div><div id="g_id_onload" data-client_id="' . vga_h(vga_gclient()) . '" data-callback="vgaG" data-auto_prompt="false"></div><div class="g_id_signin" data-type="standard" data-size="large" data-text="signin_with" data-locale="es" style="display:flex;justify-content:center"></div><form id="gf" method="post" style="display:none"><input name="gcred" id="gcred"></form><script src="https://accounts.google.com/gsi/client" async defer></script><script>function vgaG(r){document.getElementById("gcred").value=r.credential;document.getElementById("gf").submit();}</script>' : ''));
    }
    // Paso 2: verificación en dos pasos
    $msg = '';
    if (empty($cfg['totp'])) {   // aún no activada: mostrar clave y confirmar un código
        $sec = $cfg['pend'] ?? null;
        if (!$sec) { $sec = vga_b32(random_bytes(20)); $cfg['pend'] = $sec; vga_wr('virgen_admin.json', $cfg); }
        if ($post && isset($_POST['c'])) {
            if (!vga_limit(false)) vga_page('Demasiados intentos', 'Espera 15 minutos e inténtalo de nuevo.', 429);
            if (vga_totp_check($sec, (string)$_POST['c'], $cfg)) {
                $rec = []; $plain = [];
                for ($k = 0; $k < 8; $k++) { $c = strtoupper(bin2hex(random_bytes(4))); $plain[] = substr($c, 0, 4) . '-' . substr($c, 4); $rec[] = hash('sha256', $c); }
                $cfg['totp'] = $sec; unset($cfg['pend']); $cfg['rec'] = $rec; vga_wr('virgen_admin.json', $cfg);
                vga_login();
                vga_page('Guarda tus códigos de respaldo', '<p style="margin:0 0 8px">Si pierdes tu celular, cada uno de estos códigos sirve <b>una sola vez</b> para entrar. Anótalos en un lugar seguro: <b>no se volverán a mostrar</b>.</p><pre style="background:#FBF4E4;border-radius:10px;padding:12px;font-size:18px;line-height:1.7;margin:0 0 6px">' . implode("\n", $plain) . '</pre><a href="./" style="display:block;text-align:center;min-height:48px;line-height:48px;margin-top:10px;background:#14304F;color:#fff;border-radius:999px;font-weight:bold;text-decoration:none">Ya los guardé, entrar al panel</a>');
            }
            vga_fail(); $msg = 'Ese código no es correcto. Revisa que la hora del celular sea automática.';
        }
        $uri = 'otpauth://totp/Virgen%20del%20Milagro:admin?secret=' . $sec . '&issuer=Virgen%20del%20Milagro&digits=6&period=30';
        vga_page('Activa la verificación en dos pasos', ($msg ? '<p style="color:#9B2C2C;margin:0 0 6px"><b>' . vga_h($msg) . '</b></p>' : '')
            . '<p style="margin:0 0 8px">1. Abre <b>Google Authenticator</b> (o Authy, Microsoft Authenticator) en tu celular.<br>2. Toca <b>+ → Ingresar clave de configuración</b>.<br>3. Nombre: <i>Virgen del Milagro</i>. Clave:</p>'
            . '<p style="font-family:monospace;font-size:18px;word-break:break-all;background:#FBF4E4;border-radius:10px;padding:10px;margin:0 0 8px;user-select:all">' . implode(' ', str_split($sec, 4)) . '</p>'
            . '<p style="margin:0 0 4px;font-size:14px">En este mismo celular, también puedes <a href="' . vga_h($uri) . '">tocar aquí para abrirla en tu app</a>.</p>'
            . '<p style="margin:10px 0 0">4. Escribe el código de 6 dígitos que muestra la app:</p>' . vga_form(vga_input('c', 'Código de 6 dígitos', 'text'), 'Activar y entrar'));
    }
    if ($post && isset($_POST['c'])) {
        if (!vga_limit(false)) vga_page('Demasiados intentos', 'Espera 15 minutos e inténtalo de nuevo.', 429);
        $in = (string)$_POST['c'];
        if (vga_totp_check($cfg['totp'], $in, $cfg)) { vga_login(); header('Location: ./'); exit; }
        $norm = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $in)); $h = hash('sha256', $norm);
        if (strlen($norm) === 8 && !empty($cfg['rec']) && ($k = array_search($h, $cfg['rec'], true)) !== false) { unset($cfg['rec'][$k]); $cfg['rec'] = array_values($cfg['rec']); vga_wr('virgen_admin.json', $cfg); vga_login(); header('Location: ./'); exit; }
        vga_fail(); usleep(600000); $msg = 'Código incorrecto.';
    }
    vga_page('Verificación en dos pasos', ($msg ? '<p style="color:#9B2C2C;margin:0 0 6px"><b>' . vga_h($msg) . '</b></p>' : '<p style="margin:0 0 4px">Escribe el código de 6 dígitos de tu app de autenticación (o un código de respaldo).</p>') . vga_form(vga_input('c', 'Código', 'text'), 'Entrar'));
}

/* ---- ayudas de la verificación en dos pasos ---- */
function vga_b32($bin) { $a = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $b = ''; foreach (str_split($bin) as $c) $b .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT); $o = ''; foreach (str_split($b, 5) as $x) $o .= $a[bindec(str_pad($x, 5, '0'))]; return $o; }
function vga_unb32($s) { $a = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $b = ''; foreach (str_split(strtoupper($s)) as $c) { $p = strpos($a, $c); if ($p !== false) $b .= str_pad(decbin($p), 5, '0', STR_PAD_LEFT); } $o = ''; foreach (str_split($b, 8) as $x) if (strlen($x) === 8) $o .= chr(bindec($x)); return $o; }
function vga_totp_at($sec, $ctr) { $h = hash_hmac('sha1', pack('N*', 0, $ctr), vga_unb32($sec), true); $o = ord($h[19]) & 15; $v = ((ord($h[$o]) & 127) << 24) | (ord($h[$o + 1]) << 16) | (ord($h[$o + 2]) << 8) | ord($h[$o + 3]); return str_pad((string)($v % 1000000), 6, '0', STR_PAD_LEFT); }
function vga_totp_check($sec, $code, &$cfg) {
    $code = preg_replace('/\D/', '', $code); if (strlen($code) !== 6) return false;
    $now = (int)floor(time() / 30);
    for ($d = -1; $d <= 1; $d++) {
        $c = $now + $d;
        if (hash_equals(vga_totp_at($sec, $c), $code)) {
            if ((int)($cfg['last'] ?? 0) >= $c) return false;   // no se puede reutilizar el mismo código
            $cfg['last'] = $c; vga_wr('virgen_admin.json', $cfg); return true;
        }
    }
    return false;
}
function vga_pre() {   // contraseña ya verificada (10 min)
    $t = $_COOKIE['vgpre'] ?? ''; if (!preg_match('/^[0-9a-f]{64}$/', $t)) return false;
    $P = vga_rd('virgen_admin_pre.json'); $h = hash('sha256', $t); $now = time();
    foreach ($P as $k => $e) if ($e < $now) unset($P[$k]);
    vga_wr('virgen_admin_pre.json', $P); return isset($P[$h]);
}
function vga_pre_new() {
    $t = bin2hex(random_bytes(32)); $P = vga_rd('virgen_admin_pre.json'); $P[hash('sha256', $t)] = time() + 600; vga_wr('virgen_admin_pre.json', $P);
    setcookie('vgpre', $t, ['expires' => time() + 600, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Strict']);
}
function vga_login() {
    $tok = bin2hex(random_bytes(32)); $S = vga_rd('virgen_admin_sessions.json'); $now = time();
    $S[hash('sha256', $tok)] = ['exp' => $now + 8 * 3600, 'last' => $now]; vga_wr('virgen_admin_sessions.json', $S); vga_cookie($tok, $now + 8 * 3600);
    $t = $_COOKIE['vgpre'] ?? ''; if ($t) { $P = vga_rd('virgen_admin_pre.json'); unset($P[hash('sha256', $t)]); vga_wr('virgen_admin_pre.json', $P); setcookie('vgpre', '', ['expires' => time() - 3600, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Strict']); }
}
