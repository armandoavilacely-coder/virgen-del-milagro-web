<?php
// Virgen del Milagro - utilidades compartidas de la «Palabra del día» por correo.
require_once __DIR__ . '/lib.php';
date_default_timezone_set('America/Bogota');

const PV_SITE = 'https://virgendelmilagro.co';
const PV_BAJA = 'https://www.concursosdelestado.com/acceso/baja_virgen.php';
const PV_FROM = 'palabra@concursosdelestado.com';

function pv_salt() {
    $f = ce_dir() . '/virgen_salt.txt';
    if (!is_file($f) || strlen(trim((string)file_get_contents($f))) < 32) file_put_contents($f, bin2hex(random_bytes(24)));
    @chmod($f, 0600);
    return trim((string)file_get_contents($f));
}
function pv_token($email) { return substr(hash_hmac('sha256', strtolower($email), pv_salt()), 0, 32); }
function pv_baja_url($email) { return PV_BAJA . '?e=' . rawurlencode($email) . '&t=' . pv_token($email); }
function pv_h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* Contenido del día: misma lógica de la página (plan por fecha; fuera del plan, el ciclo de temas). */
function pv_contenido($ts = null) {
    $ts = $ts ?: time();
    $d = json_decode((string)file_get_contents(ce_dir() . '/virgen_palabra.json'), true);
    if (!is_array($d)) return null;
    $key = date('m-d', $ts);
    $n = (int)floor((gmmktime(0, 0, 0, (int)date('n', $ts), (int)date('j', $ts), (int)date('Y', $ts)) - gmmktime(0, 0, 0, 1, 0, (int)date('Y', $ts))) / 86400);
    if (isset($d['plan'][$key])) { $o = $d['plan'][$key]; }
    else { $c = $d['ciclo'][$n % count($d['ciclo'])]; $o = ['tema' => $c['tema'], 'frase' => $c['frase'], 'oracion' => $c['oracion'], 'maria' => $d['marias'][$n % count($d['marias'])]]; }
    $o['promesa'] = $d['promesas'][$n % count($d['promesas'])];
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $dias = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    $o['fecha'] = ucfirst($dias[(int)date('w', $ts)]) . ' ' . date('j', $ts) . ' de ' . $meses[(int)date('n', $ts) - 1];
    return $o;
}

function pv_html($o, $name, $baja) {
    $p = $o['promesa']; $hola = $name !== '' ? 'Hola, ' . pv_h($name) . '.' : 'Hola.';
    $oracion = nl2br(pv_h($o['oracion'])); $maria = nl2br(pv_h($o['maria']));
    return '<!doctype html><html lang="es"><body style="margin:0;background:#FBF4E4;font-family:Georgia,\'Times New Roman\',serif;color:#2B2118">'
    . '<div style="max-width:560px;margin:0 auto;padding:24px 18px">'
    . '<div style="text-align:center;padding:8px 0 18px"><div style="font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:#8A5E10;font-family:Arial,sans-serif">' . pv_h($o['fecha']) . ' · Tunja</div>'
    . '<div style="font-size:30px;font-style:italic;color:#7A4E00;margin-top:6px">Virgen del Milagro</div></div>'
    . '<div style="background:#fff;border:1px solid #E8D3A0;border-radius:18px;padding:24px 22px">'
    . '<p style="margin:0 0 16px;font-size:16px">' . $hola . ' Que Dios te regale un día lleno de paz.</p>'
    . '<div style="font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:#8A5E10;font-family:Arial,sans-serif;margin-bottom:8px">Promesa de Dios · ' . pv_h($p['k']) . '</div>'
    . '<p style="margin:0 0 6px;font-size:21px;line-height:1.4;font-style:italic">«' . pv_h($p['v']) . '»</p>'
    . '<p style="margin:0 0 14px;font-weight:bold;color:#8A5E10;font-family:Arial,sans-serif;font-size:14px">' . pv_h($p['c']) . ' · Reina-Valera 1909</p>'
    . '<p style="margin:0 0 4px;font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:#8A5E10;font-family:Arial,sans-serif">Reflexión del día</p>'
    . '<p style="margin:0 0 22px;font-size:17px;line-height:1.65">' . pv_h($p['r']) . '</p>'
    . '<hr style="border:0;border-top:1px solid #E8D3A0;margin:0 0 20px">'
    . '<div style="font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:#8A5E10;font-family:Arial,sans-serif;margin-bottom:8px">Oración del día · ' . pv_h($o['tema']) . '</div>'
    . '<p style="margin:0 0 16px;font-size:17px;line-height:1.65">' . $oracion . '</p>'
    . '<div style="background:#FBF4E4;border-radius:12px;padding:14px 16px"><div style="font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:#8A5E10;font-family:Arial,sans-serif;margin-bottom:6px">A la Virgen del Milagro</div>'
    . '<p style="margin:0;font-size:16px;line-height:1.6;font-style:italic">' . $maria . '</p></div>'
    . '<p style="text-align:center;margin:24px 0 4px"><a href="' . PV_SITE . '/#chat" style="background:#C9962E;color:#2B2118;text-decoration:none;font-family:Arial,sans-serif;font-weight:bold;padding:12px 24px;border-radius:999px;display:inline-block">Hablar con la Palabra</a></p>'
    . '<p style="text-align:center;margin:10px 0 0;font-family:Arial,sans-serif;font-size:13px"><a href="' . PV_SITE . '/#oracion-dia" style="color:#8A5E10">Rezar el Ave María y la Salve</a></p>'
    . '</div>'
    . '<p style="text-align:center;font-family:Arial,sans-serif;font-size:12px;line-height:1.6;color:#6B5A44;margin:18px 0 0">Recibes este correo porque te suscribiste a la Palabra del día en virgendelmilagro.co.<br>'
    . '<a href="' . pv_h($baja) . '" style="color:#6B5A44">Dejar de recibirlo</a></p></div></body></html>';
}
function pv_texto($o, $name, $baja) {
    $p = $o['promesa'];
    return ($name !== '' ? "Hola, $name.\n" : "Hola.\n") . "Que Dios te regale un día lleno de paz.\n\n"
    . strtoupper($o['fecha']) . " · VIRGEN DEL MILAGRO\n\nPROMESA DE DIOS · {$p['k']}\n«{$p['v']}»\n{$p['c']} · Reina-Valera 1909\n\nREFLEXIÓN DEL DÍA\n{$p['r']}\n\n"
    . "ORACIÓN DEL DÍA · {$o['tema']}\n{$o['oracion']}\n\nA LA VIRGEN DEL MILAGRO\n{$o['maria']}\n\n"
    . "Habla con la Palabra: " . PV_SITE . "/#chat\n\nRecibes este correo porque te suscribiste en virgendelmilagro.co.\nDejar de recibirlo: $baja\n";
}
function pv_enviar($email, $name, $o) {
    $baja = pv_baja_url($email);
    $b = 'vg' . bin2hex(random_bytes(8));
    $asunto = '=?UTF-8?B?' . base64_encode('Palabra del día · ' . $o['promesa']['c']) . '?=';
    $cab = "From: =?UTF-8?B?" . base64_encode('Virgen del Milagro') . "?= <" . PV_FROM . ">\r\nReply-To: info@concursosdelestado.com\r\nMIME-Version: 1.0\r\n"
         . "List-Unsubscribe: <$baja>\r\nList-Unsubscribe-Post: List-Unsubscribe=One-Click\r\nContent-Type: multipart/alternative; boundary=\"$b\"";
    $cuerpo = "--$b\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode(pv_texto($o, $name, $baja)))
            . "--$b\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode(pv_html($o, $name, $baja))) . "--$b--";
    return @mail($email, $asunto, $cuerpo, $cab, '-f' . PV_FROM);
}
