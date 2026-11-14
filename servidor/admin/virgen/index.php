<?php
// Virgen del Milagro - panel de administración (suscriptores, pagos de Wompi y publicidad por día).
// Usa el mismo inicio de sesión de administrador de /admin/ de Concursos del Estado: si no hay sesión, envía a /admin/.
require __DIR__ . '/../../acceso/lib.php';
header('Cache-Control: no-store');
if (function_exists('ce_headers_seguras')) ce_headers_seguras();
header('X-Robots-Tag: noindex, nofollow');

if (defined('VG_DOMINIO')) {   // en virgendelmilagro.co/admin: inicio de sesión propio (solo HTTPS)
    require __DIR__ . '/../../acceso/vg_auth.php';
    vga_require();
} else {
    $b = ce_boot();
    $S = ($b === true) ? ce_session() : null;
    if (!$S || ($S['role'] ?? '') !== 'admin') { header('Location: /admin/'); exit; }
}

$subsF = ce_dir() . '/virgen_suscriptores.jsonl';
$pagosF = ce_dir() . '/virgen_pagos.jsonl';
$testF = ce_dir() . '/virgen_testimonios.jsonl';
$fbF = ce_dir() . '/virgen_fb.json';   // estado de las publicaciones de Facebook: {id: {done, text}}
function vg_fb($f) { $d = is_file($f) ? json_decode((string)file_get_contents($f), true) : null; return is_array($d) ? $d : []; }

function vg_read($f) {
    $out = [];
    if (!is_file($f)) return $out;
    foreach (file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) { $r = json_decode($l, true); if (is_array($r)) $out[] = $r; }
    return $out;
}
function vg_write($f, $rows) {
    $fh = fopen($f, 'c+'); if (!$fh) return false;
    flock($fh, LOCK_EX); ftruncate($fh, 0); rewind($fh);
    foreach ($rows as $r) fwrite($fh, json_encode($r, JSON_UNESCAPED_UNICODE) . "\n");
    flock($fh, LOCK_UN); fclose($fh); return true;
}
function vg_json($d, $c = 200) { http_response_code($c); header('Content-Type: application/json; charset=utf-8'); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

/* ---- API interna (solo con sesión de administrador, mismo origen) ---- */
$act = $_GET['a'] ?? '';
if ($act !== '') {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $og = parse_url($_SERVER['HTTP_ORIGIN'] ?? '', PHP_URL_HOST);
    if (($_SERVER['HTTP_X_VG'] ?? '') !== '1' || ($og && $og !== preg_replace('/:\d+$/', '', $host))) vg_json(['ok' => false, 'e' => 'csrf'], 403);
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    if ($act === 'datos') {
        vg_json(['ok' => true, 'subs' => vg_read($subsF), 'pagos' => vg_read($pagosF), 'tests' => vg_read($testF), 'fb' => (object)vg_fb($fbF)]);
    }
    if ($act === 'visitas') {   // registros de visitas de los últimos 90 días (los agrega el navegador)
        $vf = ce_dir() . '/virgen_visitas.jsonl'; $out = []; $min = time() - 90 * 86400;
        if (is_file($vf)) foreach (file($vf, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) { $r = json_decode($l, true); if (is_array($r) && ($r['t'] ?? 0) >= $min) $out[] = $r; }
        vg_json(['ok' => true, 'v' => array_slice($out, -60000)]);
    }
    if ($act === 'pago_add') {
        $monto = (int)preg_replace('/\D/', '', (string)($in['monto'] ?? ''));
        $fecha = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($in['fecha'] ?? '')) ? $in['fecha'] : date('Y-m-d');
        if ($monto <= 0) vg_json(['ok' => false, 'r' => 'Escribe un monto válido.'], 400);
        $reg = [
            'id' => 'm_' . bin2hex(random_bytes(5)), 'ts' => date('c'), 'fecha' => $fecha,
            'nombre' => mb_substr(strip_tags((string)($in['nombre'] ?? '')), 0, 80),
            'correo' => mb_substr(strip_tags((string)($in['correo'] ?? '')), 0, 120),
            'tipo' => ($in['tipo'] ?? '') === 'suscripcion' ? 'suscripcion' : 'aporte',
            'monto' => $monto, 'medio' => 'Wompi',
            'ref' => mb_substr(strip_tags((string)($in['ref'] ?? '')), 0, 80),
            'nota' => mb_substr(strip_tags((string)($in['nota'] ?? '')), 0, 200), 'src' => 'manual',
        ];
        $rows = vg_read($pagosF); $rows[] = $reg; vg_write($pagosF, $rows);
        vg_json(['ok' => true, 'pagos' => $rows]);
    }
    if ($act === 'pago_del') {
        $id = (string)($in['id'] ?? '');
        $rows = array_values(array_filter(vg_read($pagosF), fn($r) => ($r['id'] ?? '') !== $id));
        vg_write($pagosF, $rows); vg_json(['ok' => true, 'pagos' => $rows]);
    }
    if ($act === 'sub_del') {
        $k = strtolower((string)($in['k'] ?? ''));
        $rows = array_values(array_filter(vg_read($subsF), fn($r) => ($r['email'] ?? '') . '|' . ($r['wa'] ?? '') !== $k));
        vg_write($subsF, $rows); vg_json(['ok' => true, 'subs' => $rows]);
    }
    if ($act === 'test_set') {   // aprobar / ocultar / editar el texto
        $id = (string)($in['id'] ?? ''); $rows = vg_read($testF);
        foreach ($rows as &$r) {
            if (($r['id'] ?? '') !== $id) continue;
            if (isset($in['estado']) && in_array($in['estado'], ['aprobado', 'pendiente'], true)) { $r['estado'] = $in['estado']; if ($in['estado'] === 'aprobado' && empty($r['pub'])) $r['pub'] = date('c'); }
            if (isset($in['text'])) { $t = trim(mb_substr(strip_tags((string)$in['text']), 0, 900)); if ($t !== '') $r['text'] = $t; }
            if (isset($in['anon'])) { $r['anon'] = (bool)$in['anon']; }
        }
        unset($r);
        vg_write($testF, $rows); vg_json(['ok' => true, 'tests' => $rows]);
    }
    if ($act === 'test_del') {
        $id = (string)($in['id'] ?? '');
        $rows = array_values(array_filter(vg_read($testF), fn($r) => ($r['id'] ?? '') !== $id));
        vg_write($testF, $rows); vg_json(['ok' => true, 'tests' => $rows]);
    }
    if ($act === 'fb_set') {   // marcar publicada / guardar un texto editado
        $id = preg_replace('/[^0-9A-Za-z_\-]/', '', (string)($in['id'] ?? '')); if ($id === '') vg_json(['ok' => false], 400);
        $fh = fopen($fbF, 'c+'); if (!$fh) vg_json(['ok' => false], 503);
        flock($fh, LOCK_EX); $d = json_decode(stream_get_contents($fh) ?: '', true); if (!is_array($d)) $d = [];
        $r = $d[$id] ?? [];
        if (isset($in['done'])) $r['done'] = (bool)$in['done'] ? date('c') : false;
        if (isset($in['text'])) { $t = trim(mb_substr(strip_tags((string)$in['text']), 0, 2000)); if ($t !== '') $r['text'] = $t; }
        $d[$id] = $r; ftruncate($fh, 0); rewind($fh); fwrite($fh, json_encode($d, JSON_UNESCAPED_UNICODE)); flock($fh, LOCK_UN); fclose($fh);
        vg_json(['ok' => true, 'fb' => (object)$d]);
    }
    vg_json(['ok' => false], 404);
}
header('Content-Type: text/html; charset=utf-8');
?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>Administración | Virgen del Milagro</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=Great+Vibes&family=Nunito+Sans:wght@400;600;700&display=swap">
<style>
:root{--bg:#F4F6FB;--card:#fff;--ink:#1A2236;--soft:#5A6478;--line:#D6DCE8;--navy:#1F4E79;--gold:#B8841F;--ok:#1F7A4D;--bad:#A33A2A}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.5 "Nunito Sans",system-ui,Arial,sans-serif}
header{display:flex;flex-wrap:wrap;gap:12px 24px;align-items:center;justify-content:space-between;padding:16px 24px;background:#14304F;color:#fff}
header h1{margin:0;font:600 26px "Cormorant Garamond",Georgia,serif;color:#F2D68A}
header a{color:#CFE4F7;font-size:14px}
nav{display:flex;gap:6px;flex-wrap:wrap;padding:12px 24px 0}
nav button{border:1px solid var(--line);border-bottom:0;background:#E9EEF7;padding:10px 18px;border-radius:12px 12px 0 0;font:inherit;font-weight:700;cursor:pointer;color:var(--soft)}
nav button.on{background:var(--card);color:var(--navy)}
main{background:var(--card);margin:0 24px 40px;border:1px solid var(--line);padding:22px;border-radius:0 14px 14px 14px;min-height:60vh}
h2{margin:0 0 14px;font:600 28px "Cormorant Garamond",Georgia,serif}
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,150px),1fr));gap:12px;margin-bottom:20px}
.kpi{border:1px solid var(--line);border-radius:12px;padding:14px;background:#FAFBFE}.kpi b{display:block;font-size:26px;color:var(--navy)}.kpi span{font-size:13px;color:var(--soft)}
table{width:100%;border-collapse:collapse;font-size:14px}th,td{padding:8px 10px;border-bottom:1px solid var(--line);text-align:left;vertical-align:top}th{background:#F0F3FA;font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:var(--soft)}
.tw{overflow-x:auto}
input,select,textarea{font:inherit;border:1px solid var(--line);border-radius:10px;padding:9px 11px;background:#fff;color:var(--ink);max-width:100%}
textarea{width:100%;min-height:210px;resize:vertical;line-height:1.45}
button.b{background:var(--navy);color:#fff;border:0;border-radius:999px;padding:10px 18px;font:inherit;font-weight:700;cursor:pointer}
button.g{background:#fff;color:var(--navy);border:1px solid var(--line);border-radius:999px;padding:8px 14px;font:inherit;font-weight:600;cursor:pointer}
button.x{background:none;border:0;color:var(--bad);cursor:pointer;font:inherit}
.form{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px;margin:0 0 18px;align-items:end}
.form label{display:grid;gap:3px;font-size:12px;font-weight:700;color:var(--soft)}
.ok{color:var(--ok)}.bad{color:var(--bad)}.muted{color:var(--soft);font-size:13px}
.bar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:16px}
.posts{display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:18px}
.post{border:1px solid var(--line);border-radius:14px;padding:14px;background:#FAFBFE;display:grid;gap:10px;align-content:start}
.post h3{margin:0;font:600 22px "Cormorant Garamond",Georgia,serif}
.post canvas{width:100%;height:auto;border-radius:10px;border:1px solid var(--line);background:#dde}
.tabs{display:flex;gap:6px;flex-wrap:wrap}.tabs button{border:1px solid var(--line);background:#fff;border-radius:999px;padding:5px 12px;font:inherit;font-size:13px;font-weight:700;cursor:pointer;color:var(--soft)}.tabs button.on{background:var(--navy);color:#fff;border-color:var(--navy)}
.row2{display:flex;gap:8px;flex-wrap:wrap}
.vgrid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,420px),1fr));gap:18px;margin-top:10px}
.vcard{border:1px solid var(--line);border-radius:14px;background:#fff;padding:16px;min-width:0;box-shadow:0 1px 2px rgba(20,48,79,.05)}
.vcard h3{margin:0 0 10px;font:600 21px "Cormorant Garamond",Georgia,serif;color:var(--navy)}
.vcard .muted{margin:8px 0 0}
@media(max-width:600px){main{margin:0 8px 24px;padding:14px}nav,header{padding-inline:12px}.posts{grid-template-columns:minmax(0,1fr)}}
</style></head>
<body>
<header><h1>Virgen del Milagro · Administración</h1><span><?php if (defined('VG_DOMINIO')) { ?><a href="?salir=1">Cerrar sesión</a><?php } else { ?><a href="/admin/">Panel de Concursos del Estado</a> · <a href="/admin/?salir=1">Cerrar sesión</a><?php } ?></span></header>
<nav id="tabs"></nav>
<main id="main"><p class="muted">Cargando…</p></main>
<script src="contenido.js"></script>
<script src="facebook/posts.js"></script>
<script>
(function () {
  "use strict";
  var VG = window.VG, D = { subs: [], pagos: [], tests: [], fb: {} }, tab = "resumen";
  function $(id) { return document.getElementById(id); }
  function el(t, c, x) { var n = document.createElement(t); if (c) n.className = c; if (x != null) n.textContent = x; return n; }
  function money(n) { return "$" + Number(n || 0).toLocaleString("es-CO"); }
  function api(a, body) {
    return fetch("?a=" + a, { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/json", "X-VG": "1" }, body: JSON.stringify(body || {}) })
      .then(function (r) { return r.json(); });
  }
  function load() { return api("datos").then(function (d) { if (d.ok) { D.subs = d.subs; D.pagos = d.pagos; D.tests = d.tests || []; D.fb = d.fb || {}; } render(); }); }
  var TABS = [["resumen", "Resumen"], ["usuarios", "Suscriptores"], ["pagos", "Pagos"], ["publicidad", "Publicidad"], ["testimonios", "Testimonios"], ["facebook", "Facebook"], ["visitas", "Visitas"]];
  function drawTabs() {
    var n = $("tabs"); n.textContent = "";
    TABS.forEach(function (t) { var b = el("button", t[0] === tab ? "on" : "", t[1]); b.onclick = function () { tab = t[0]; render(); }; n.appendChild(b); });
  }
  function render() { drawTabs(); var m = $("main"); m.textContent = ""; ({ resumen: vResumen, usuarios: vUsuarios, pagos: vPagos, publicidad: vPub, testimonios: vTest, facebook: vFb, visitas: vVis })[tab](m); }


  /* ---------- Visitas (solo cuentan quienes aceptaron las cookies) ---------- */
  var VIS = null, visRango = 30;
  function bogota(t) { var p = new Intl.DateTimeFormat("en-CA", { timeZone: "America/Bogota", year: "numeric", month: "2-digit", day: "2-digit", hour: "2-digit", hourCycle: "h23" }).formatToParts(new Date(t * 1000)), o = {}; p.forEach(function (x) { o[x.type] = x.value; }); return { d: o.year + "-" + o.month + "-" + o.day, h: +o.hour }; }
  function top(rows, key, n) { var c = {}; rows.forEach(function (r) { var k = typeof key === "function" ? key(r) : r[key]; if (k) c[k] = (c[k] || 0) + 1; }); return Object.keys(c).map(function (k) { return [k, c[k]]; }).sort(function (a, b) { return b[1] - a[1]; }).slice(0, n || 10); }
  function bars(titulo, pares, total) {
    var w = el("div"); w.appendChild(el("h3", null, titulo)); w.firstChild.style.cssText = "margin:0 0 8px;font:600 20px 'Cormorant Garamond',Georgia,serif";
    if (!pares.length) { w.appendChild(el("p", "muted", "Aún sin datos.")); return w; }
    var mx = pares[0][1];
    pares.forEach(function (p) {
      var r = el("div"); r.style.cssText = "display:grid;grid-template-columns:minmax(90px,38%) 1fr auto;gap:8px;align-items:center;margin:3px 0;font-size:14px";
      r.appendChild(el("span", null, p[0]));
      var b = el("div"); b.style.cssText = "background:#E6EBF5;border-radius:6px;height:12px"; var i = el("div"); i.style.cssText = "background:var(--navy);height:12px;border-radius:6px;width:" + Math.max(3, Math.round(p[1] / mx * 100)) + "%"; b.appendChild(i); r.appendChild(b);
      r.appendChild(el("span", "muted", p[1] + (total ? " · " + Math.round(p[1] / total * 100) + "%" : ""))); w.appendChild(r);
    });
    return w;
  }
  function vVis(m) {
    m.appendChild(el("h2", null, "Visitas a la página"));
    if (!VIS) { m.appendChild(el("p", "muted", "Cargando…")); api("visitas").then(function (d) { VIS = d.v || []; if (tab === "visitas") render(); }); return; }
    var corte = Math.floor(Date.now() / 1000) - visRango * 86400;
    var todas = VIS.filter(function (r) { return r.t >= corte; }), vs = todas.filter(function (r) { return (r.ev || "visita") === "visita"; }), evs = todas.filter(function (r) { return r.ev && r.ev !== "visita"; });
    var bar = el("div", "bar"); [[1, "Hoy"], [7, "7 días"], [30, "30 días"], [90, "90 días"]].forEach(function (x) { var b = el("button", "g", x[1]); if (x[0] === visRango) { b.style.background = "var(--navy)"; b.style.color = "#fff"; } b.onclick = function () { visRango = x[0]; render(); }; bar.appendChild(b); });
    m.appendChild(bar);
    var ids = {}; vs.forEach(function (r) { ids[r.vid] = 1; }); var nuevos = vs.filter(function (r) { return r.nuevo; }).length;
    var hoy = bogota(Date.now() / 1000).d, hoyN = VIS.filter(function (r) { return (r.ev || "visita") === "visita" && bogota(r.t).d === hoy; }).length;
var prevN = visRango < 90 ? VIS.filter(function (r) { return (r.ev || "visita") === "visita" && r.t >= corte - visRango * 86400 && r.t < corte; }).length : null;
    function delta(a, b) { if (b === null || b === undefined) return ""; if (!b) return a ? "▲ nuevo" : ""; var p = Math.round((a - b) / b * 100); return (p >= 0 ? "▲ " : "▼ ") + Math.abs(p) + "% vs periodo anterior"; }
        var k = el("div", "kpis");
    [[vs.length, "Visitas (sesiones)"], [Object.keys(ids).length, "Personas distintas"], [nuevos, "Personas nuevas"], [vs.length - nuevos, "Volvieron a visitarnos"], [hoyN, "Visitas hoy"]].forEach(function (x, i) { var d = el("div", "kpi"); d.appendChild(el("b", null, String(x[0]))); d.appendChild(el("span", null, x[1])); if (i === 0 && delta(vs.length, prevN)) { var dd = el("small", null, delta(vs.length, prevN)); dd.style.cssText = "display:block;margin-top:4px;font-size:12px;font-weight:700;color:" + (vs.length >= prevN ? "var(--ok)" : "var(--bad)"); d.appendChild(dd); } k.appendChild(d); });
    m.appendChild(k);
    m.appendChild(el("p", "muted", "Solo se cuentan las personas que aceptaron las cookies; el número real de visitantes es algo mayor. Horas en hora de Colombia. No se guarda la dirección IP."));
    if (!vs.length) { m.appendChild(el("p", null, "Todavía no hay visitas registradas en este periodo. Aparecerán cuando alguien acepte las cookies en virgendelmilagro.co.")); return; }
    var CW = Math.max(290, Math.min(680, ($("main").clientWidth || 700) - 64));
    var PAL = ["#14304F", "#C9962E", "#2F8F83", "#B5475B", "#6B7FD7", "#8C6D3F", "#7A8B99"];
    function card(titulo, nodo, nota) { var c = el("div", "vcard"); c.appendChild(el("h3", null, titulo)); c.appendChild(nodo); if (nota) c.appendChild(el("p", "muted", nota)); return c; }
    function S(tag, at, txt) { var n = document.createElementNS("http://www.w3.org/2000/svg", tag); for (var k in at) n.setAttribute(k, at[k]); if (txt != null) n.textContent = txt; return n; }
    function svgBox(w, h) { var sv = S("svg", { viewBox: "0 0 " + w + " " + h, role: "img" }); sv.style.cssText = "width:100%;max-width:560px;height:auto;display:block"; return sv; }
    function nice(mx) { if (mx <= 4) return 4; var p = Math.pow(10, Math.floor(Math.log10(mx))), f = mx / p; return (f <= 2 ? 2 : f <= 5 ? 5 : 10) * p; }
    /* línea/área por día */
    function lineChart(labels, series) {
      var W = Math.min(CW, 560), H = 150, L = 30, R = 10, T = 10, B = 24, sv = svgBox(W, H), mx = 1;
      series.forEach(function (s) { s.vals.forEach(function (v) { if (v > mx) mx = v; }); }); var top = nice(mx);
      function X(i) { return L + (labels.length < 2 ? 0 : i * (W - L - R) / (labels.length - 1)); } function Y(v) { return T + (H - T - B) * (1 - v / top); }
      for (var g = 0; g <= 4; g++) { var v = top * g / 4, y = Y(v); sv.appendChild(S("line", { x1: L, x2: W - R, y1: y, y2: y, stroke: "#E3E8F2", "stroke-width": 1 })); sv.appendChild(S("text", { x: L - 6, y: y + 4, "text-anchor": "end", "font-size": 11, fill: "#5B6B85" }, String(Math.round(v)))); }
      var paso = Math.max(1, Math.ceil(labels.length / (W < 420 ? 4 : 7)));
      labels.forEach(function (d, i) { if (i % paso === 0 || i === labels.length - 1) sv.appendChild(S("text", { x: X(i), y: H - 9, "text-anchor": i === 0 ? "start" : i === labels.length - 1 ? "end" : "middle", "font-size": 11, fill: "#5B6B85" }, d.slice(8) + "/" + d.slice(5, 7))); });
      series.forEach(function (s, si) {
        var pts = s.vals.map(function (v, i) { return X(i) + "," + Y(v); }).join(" ");
        if (si === 0 && s.vals.length > 1) sv.appendChild(S("polygon", { points: X(0) + "," + Y(0) + " " + pts + " " + X(s.vals.length - 1) + "," + Y(0), fill: s.color, opacity: 0.12 }));
        sv.appendChild(S("polyline", { points: pts, fill: "none", stroke: s.color, "stroke-width": 2, "stroke-linejoin": "round", "stroke-linecap": "round" }));
        s.vals.forEach(function (v, i) { var c = S("circle", { cx: X(i), cy: Y(v), r: labels.length > 20 ? 1.6 : 3, fill: "#fff", stroke: s.color, "stroke-width": 1.6 }); c.appendChild(S("title", {}, labels[i] + " · " + s.name + ": " + v)); sv.appendChild(c); });
      });
      return sv;
    }
    /* columnas */
    function colChart(vals, labs, color, hi) {
      var W = Math.min(CW, 560), H = 140, L = 28, R = 8, T = 10, B = 22, sv = svgBox(W, H), mx = Math.max.apply(null, vals.concat([1])), top = nice(mx), bw = (W - L - R) / vals.length;
      for (var g = 0; g <= 4; g++) { var y = T + (H - T - B) * (1 - g / 4); sv.appendChild(S("line", { x1: L, x2: W - R, y1: y, y2: y, stroke: "#E3E8F2" })); sv.appendChild(S("text", { x: L - 5, y: y + 4, "text-anchor": "end", "font-size": 11, fill: "#5B6B85" }, String(Math.round(top * g / 4)))); }
      var im = vals.indexOf(mx);
      vals.forEach(function (v, i) {
        var h = (H - T - B) * v / top, r = S("rect", { x: L + i * bw + 2, y: T + (H - T - B) - h, width: Math.max(2, bw - 4), height: Math.max(v ? 2 : 0, h), rx: 3, fill: i === im && hi ? "#C9962E" : color }); r.appendChild(S("title", {}, labs[i] + ": " + v)); sv.appendChild(r);
        if (i % (vals.length > 12 ? 3 : 1) === 0) sv.appendChild(S("text", { x: L + i * bw + bw / 2, y: H - 8, "text-anchor": "middle", "font-size": 11, fill: "#5B6B85" }, labs[i]));
      });
      return sv;
    }
    /* anillo + leyenda */
    function donut(pares) {
      var w = el("div"); w.style.cssText = "display:flex;gap:18px;align-items:center;flex-wrap:wrap"; var tot = pares.reduce(function (a, p) { return a + p[1]; }, 0);
      if (!tot) { w.appendChild(el("p", "muted", "Aún sin datos.")); return w; }
      var sv = S("svg", { viewBox: "0 0 120 120", role: "img" }); sv.style.cssText = "width:128px;height:128px;flex:none"; var C = 2 * Math.PI * 46, off = 0;
      pares.forEach(function (p, i) { var len = p[1] / tot * C, c = S("circle", { cx: 60, cy: 60, r: 46, fill: "none", stroke: PAL[i % PAL.length], "stroke-width": 22, "stroke-dasharray": len + " " + (C - len), "stroke-dashoffset": -off, transform: "rotate(-90 60 60)" }); c.appendChild(S("title", {}, p[0] + ": " + p[1])); sv.appendChild(c); off += len; });
      sv.appendChild(S("text", { x: 60, y: 66, "text-anchor": "middle", "font-size": 20, "font-weight": 700, fill: "#14304F" }, String(tot))); w.appendChild(sv);
      var lg = el("div"); lg.style.cssText = "display:grid;gap:5px;font-size:14px;min-width:140px;flex:1";
      pares.forEach(function (p, i) { var r = el("div"); r.style.cssText = "display:flex;gap:8px;align-items:center"; var d = el("span"); d.style.cssText = "width:11px;height:11px;border-radius:3px;flex:none;background:" + PAL[i % PAL.length]; r.appendChild(d); r.appendChild(el("span", null, p[0])); var q = el("span", "muted", p[1] + " · " + Math.round(p[1] / tot * 100) + "%"); q.style.marginLeft = "auto"; r.appendChild(q); lg.appendChild(r); });
      w.appendChild(lg); return w;
    }
    /* mapa de calor día de la semana × hora */
    function heat() {
      var DS = ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"], M = []; for (var d = 0; d < 7; d++) { M.push([]); for (var h = 0; h < 24; h++) M[d].push(0); }
      vs.forEach(function (r) { var b = bogota(r.t); M[new Date(b.d + "T12:00:00Z").getUTCDay()][b.h]++; }); var mx = 1; M.forEach(function (f) { f.forEach(function (v) { if (v > mx) mx = v; }); });
      var W = CW, L = 34, cw = (W - L - 4) / 24, ch = 24, H = 7 * ch + 28, sv = svgBox(W, H);
      [1, 2, 3, 4, 5, 6, 0].forEach(function (d, fi) {
        sv.appendChild(S("text", { x: L - 6, y: fi * ch + 16, "text-anchor": "end", "font-size": 11, fill: "#5B6B85" }, DS[d]));
        for (var h = 0; h < 24; h++) { var v = M[d][h], rc = S("rect", { x: L + h * cw + 1, y: fi * ch + 1, width: cw - 2, height: ch - 2, rx: 4, fill: v ? "#14304F" : "#EEF2F9", opacity: v ? 0.18 + 0.82 * v / mx : 1 }); rc.appendChild(S("title", {}, DS[d] + " " + ("0" + h).slice(-2) + ":00 · " + v + " visitas")); sv.appendChild(rc); }
      });
      for (var h2 = 0; h2 < 24; h2 += 3) sv.appendChild(S("text", { x: L + h2 * cw + cw / 2, y: H - 6, "text-anchor": "middle", "font-size": 11, fill: "#5B6B85" }, ("0" + h2).slice(-2) + "h"));
      return sv;
    }
    /* embudo de acciones */
    function embudo() {
      var nom = { donar: "Tocaron «Aportar»", suscribir: "Tocaron «Suscribirme»", facebook: "Tocaron Facebook", chat: "Usaron el chat" }, w = el("div"), pers = Object.keys(ids).length || 1;
      var por = {}; evs.forEach(function (r) { var k = nom[r.ev] || r.ev; (por[k] = por[k] || {})[r.vid] = 1; });
      var filas = [["Personas que visitaron", Object.keys(ids).length]].concat(Object.keys(por).map(function (k) { return [k, Object.keys(por[k]).length]; }).sort(function (a, b) { return b[1] - a[1]; }));
      filas.forEach(function (f, i) {
        var r = el("div"); r.style.cssText = "display:grid;grid-template-columns:minmax(120px,40%) 1fr auto;gap:8px;align-items:center;margin:5px 0;font-size:14px"; r.appendChild(el("span", null, f[0]));
        var b = el("div"); b.style.cssText = "background:#E6EBF5;border-radius:7px;height:16px"; var q = el("div"); q.style.cssText = "height:16px;border-radius:7px;background:" + PAL[i % PAL.length] + ";width:" + Math.max(f[1] ? 3 : 0, Math.round(f[1] / pers * 100)) + "%"; b.appendChild(q); r.appendChild(b);
        r.appendChild(el("span", "muted", f[1] + (i ? " · " + Math.round(f[1] / pers * 100) + "% de visitantes" : ""))); w.appendChild(r);
      });
      if (filas.length === 1) w.appendChild(el("p", "muted", "Aún nadie ha tocado «Aportar», «Suscribirme» o el chat en este periodo."));
      return w;
    }
    /* barras horizontales de colores */
    function barras(pares, total, color) {
      var w = el("div"); if (!pares.length) { w.appendChild(el("p", "muted", "Aún sin datos.")); return w; } var mx = pares[0][1];
      pares.forEach(function (p, i) {
        var r = el("div"); r.style.cssText = "display:grid;grid-template-columns:minmax(90px,36%) 1fr auto;gap:8px;align-items:center;margin:4px 0;font-size:14px"; var t = el("span", null, p[0]); t.style.cssText = "overflow:hidden;text-overflow:ellipsis;white-space:nowrap"; t.title = p[0]; r.appendChild(t);
        var b = el("div"); b.style.cssText = "background:#E6EBF5;border-radius:7px;height:14px"; var q = el("div"); q.style.cssText = "height:14px;border-radius:7px;background:" + (color || (i === 0 ? "#C9962E" : "#14304F")) + ";width:" + Math.max(3, Math.round(p[1] / mx * 100)) + "%"; b.appendChild(q); r.appendChild(b);
        r.appendChild(el("span", "muted", p[1] + " · " + Math.round(p[1] / total * 100) + "%")); w.appendChild(r);
      });
      return w;
    }
    /* datos por día */
    var dl = [], now0 = Math.floor(Date.now() / 1000); for (var di = visRango - 1; di >= 0; di--) dl.push(bogota(now0 - di * 86400).d);
    var porDia = {}, perDia = {}; vs.forEach(function (r) { var d = bogota(r.t).d; porDia[d] = (porDia[d] || 0) + 1; (perDia[d] = perDia[d] || {})[r.vid] = 1; });
    var gr = el("div", "vgrid");
    if (dl.length > 1) gr.appendChild(card("Visitas por día", lineChart(dl, [{ name: "Visitas", color: "#14304F", vals: dl.map(function (d) { return porDia[d] || 0; }) }, { name: "Personas distintas", color: "#C9962E", vals: dl.map(function (d) { return perDia[d] ? Object.keys(perDia[d]).length : 0; }) }]), "Azul: visitas · Dorado: personas distintas"));
    gr.firstChild && dl.length > 1 && (gr.firstChild.style.gridColumn = "1 / -1");
    var hrs = []; for (var i = 0; i < 24; i++) hrs.push(0); vs.forEach(function (r) { hrs[bogota(r.t).h]++; });
    gr.appendChild(card("¿A qué hora nos visitan? (hora de Colombia)", colChart(hrs, hrs.map(function (_, h) { return ("0" + h).slice(-2) + "h"; }), "#14304F", true), "En dorado, la hora con más visitas."));
    gr.appendChild(card("Día de la semana y hora", heat(), "Más oscuro = más visitas."));
    gr.appendChild(card("¿Qué hacen los visitantes?", embudo()));
    gr.appendChild(card("Nuevos y recurrentes", donut([["Personas nuevas", nuevos], ["Volvieron a visitarnos", vs.length - nuevos]])));
    gr.appendChild(card("Dispositivo", donut(top(vs, "disp", 5))));
    gr.appendChild(card("Países", barras(top(vs, function (r) { return r.pais; }, 8), vs.length)));
    gr.appendChild(card("Ciudades", barras(top(vs, function (r) { return r.ciudad ? r.ciudad + (r.cc ? " (" + r.cc + ")" : "") : ""; }, 12), vs.length)));
    gr.appendChild(card("Departamentos / regiones", barras(top(vs, function (r) { return r.region; }, 8), vs.length)));
    gr.appendChild(card("Vienen de", barras(top(vs.map(function (r) { return { x: r.ref || "Directo / WhatsApp / Facebook app" }; }), "x", 8), vs.length)));
    gr.appendChild(card("Sistema", donut(top(vs, "so", 6))));
    gr.appendChild(card("Navegador", donut(top(vs, "nav", 6))));
    var g = gr;
    m.appendChild(g);
    var ex = el("button", "g", "Descargar CSV"); ex.style.marginTop = "18px";
    ex.onclick = function () {
      var rows = [["fecha_hora_colombia", "evento", "pais", "region", "ciudad", "dispositivo", "sistema", "navegador", "origen", "nuevo"]].concat(todas.map(function (r) { var b = bogota(r.t); return [b.d + " " + ("0" + b.h).slice(-2) + "h", r.ev || "visita", r.pais, r.region, r.ciudad, r.disp, r.so, r.nav, r.ref, r.nuevo ? "si" : "no"]; }));
      var csv = rows.map(function (r) { return r.map(function (c) { return '"' + String(c || "").replace(/"/g, '""') + '"'; }).join(","); }).join("\n");
      var a = document.createElement("a"); a.href = URL.createObjectURL(new Blob(["﻿" + csv], { type: "text/csv" })); a.download = "visitas-virgen.csv"; a.click();
    };
    m.appendChild(ex);
  }

  /* ---------- helpers de pagos ---------- */
  function mesActual() { return new Date().toISOString().slice(0, 7); }
  function suscriptoresActivos() {
    var map = {}, hoy = Date.now();
    D.pagos.filter(function (p) { return p.tipo === "suscripcion"; }).forEach(function (p) {
      var k = (p.correo || p.nombre || p.id).toLowerCase();
      if (!map[k] || p.fecha > map[k].fecha) map[k] = p;
    });
    return Object.keys(map).map(function (k) {
      var p = map[k], vence = new Date(p.fecha + "T12:00:00").getTime() + 30 * 86400000;
      return { p: p, vence: vence, dias: Math.ceil((vence - hoy) / 86400000) };
    }).sort(function (a, b) { return a.vence - b.vence; });
  }

  function vResumen(m) {
    var mes = mesActual(), delMes = D.pagos.filter(function (p) { return (p.fecha || "").slice(0, 7) === mes; });
    var tot = function (a) { return a.reduce(function (s, p) { return s + (p.monto || 0); }, 0); };
    var act = suscriptoresActivos().filter(function (s) { return s.dias >= 0; });
    m.appendChild(el("h2", null, "Resumen"));
    var k = el("div", "kpis");
    [[D.subs.length, "Suscriptores de la Palabra del día"], [money(tot(delMes)), "Recaudado este mes"], [money(tot(D.pagos)), "Recaudado en total"], [act.length, "Suscripciones mensuales al día"], [money(act.length * 20000), "Ingreso mensual esperado"]].forEach(function (x) {
      var c = el("div", "kpi"); c.appendChild(el("b", null, String(x[0]))); c.appendChild(el("span", null, x[1])); k.appendChild(c);
    });
    m.appendChild(k);
    m.appendChild(el("p", "muted", "Los pagos de los dos links de Wompi (aporte y suscripción) se registran solos en la pestaña Pagos. Si alguno no aparece, puedes registrarlo a mano."));
  }

  function vUsuarios(m) {
    m.appendChild(el("h2", null, "Suscriptores (" + D.subs.length + ")"));
    var bar = el("div", "bar");
    var cp = el("button", "g", "Copiar todos los correos"); cp.onclick = function () { copy(D.subs.map(function (s) { return s.email; }).filter(Boolean).join(", "), cp); };
    var ex = el("button", "g", "Descargar CSV"); ex.onclick = function () {
      var rows = [["fecha", "nombre", "correo", "whatsapp"]].concat(D.subs.map(function (s) { return [s.ts, s.name, s.email, s.wa]; }));
      var csv = rows.map(function (r) { return r.map(function (c) { return '"' + String(c || "").replace(/"/g, '""') + '"'; }).join(","); }).join("\n");
      var a = el("a"); a.href = URL.createObjectURL(new Blob(["\ufeff" + csv], { type: "text/csv" })); a.download = "suscriptores-virgen.csv"; a.click();
    };
    bar.appendChild(cp); bar.appendChild(ex); m.appendChild(bar);
    var w = el("div", "tw"), t = el("table"); t.innerHTML = "<tr><th>Fecha</th><th>Nombre</th><th>Correo</th><th>WhatsApp</th><th></th></tr>";
    D.subs.slice().reverse().forEach(function (s) {
      var tr = el("tr");
      [(s.ts || "").slice(0, 10), s.name || "", s.email || "", s.wa || ""].forEach(function (x) { tr.appendChild(el("td", null, x)); });
      var td = el("td"), b = el("button", "x", "Quitar");
      b.onclick = function () { if (b.dataset.s) { api("sub_del", { k: (s.email || "") + "|" + (s.wa || "") }).then(function (d) { if (d.ok) { D.subs = d.subs; render(); } }); } else { b.dataset.s = "1"; b.textContent = "¿Seguro? Toca otra vez"; } };
      td.appendChild(b); tr.appendChild(td); t.appendChild(tr);
    });
    if (!D.subs.length) m.appendChild(el("p", "muted", "Todavía no hay suscriptores."));
    w.appendChild(t); m.appendChild(w);
  }

  function vPagos(m) {
    m.appendChild(el("h2", null, "Pagos por Wompi"));
    var f = el("form", "form"); f.innerHTML =
      '<label>Fecha<input type="date" id="pf" value="' + new Date().toISOString().slice(0, 10) + '"></label>' +
      '<label>Nombre<input id="pn" maxlength="80"></label><label>Correo<input id="pc" type="email" maxlength="120"></label>' +
      '<label>Tipo<select id="pt"><option value="suscripcion">Suscripción mensual</option><option value="aporte">Aporte voluntario</option></select></label>' +
      '<label>Monto (COP)<input id="pm" inputmode="numeric" value="20000"></label><label>Referencia Wompi<input id="pr" maxlength="80"></label>' +
      '<label>Nota<input id="pnota" maxlength="200"></label><button class="b" type="submit">Registrar pago</button>';
    var msg = el("p", "muted", "");
    f.onsubmit = function (e) {
      e.preventDefault();
      api("pago_add", { fecha: $("pf").value, nombre: $("pn").value, correo: $("pc").value, tipo: $("pt").value, monto: $("pm").value, ref: $("pr").value, nota: $("pnota").value })
        .then(function (d) { if (d.ok) { D.pagos = d.pagos; render(); } else msg.textContent = d.r || "No se pudo guardar."; });
    };
    m.appendChild(f); m.appendChild(msg);
    var sa = suscriptoresActivos();
    if (sa.length) {
      m.appendChild(el("h2", null, "Suscripciones mensuales"));
      var w0 = el("div", "tw"), t0 = el("table"); t0.innerHTML = "<tr><th>Persona</th><th>Último pago</th><th>Vence</th><th>Estado</th></tr>";
      sa.forEach(function (s) {
        var tr = el("tr");
        tr.appendChild(el("td", null, (s.p.nombre || "") + " " + (s.p.correo || "")));
        tr.appendChild(el("td", null, s.p.fecha));
        tr.appendChild(el("td", null, new Date(s.vence).toISOString().slice(0, 10)));
        tr.appendChild(el("td", s.dias < 0 ? "bad" : s.dias <= 5 ? "bad" : "ok", s.dias < 0 ? "Vencida hace " + (-s.dias) + " días" : s.dias <= 5 ? "Vence en " + s.dias + " días: recuérdale pagar" : "Al día"));
        t0.appendChild(tr);
      });
      w0.appendChild(t0); m.appendChild(w0);
    }
    m.appendChild(el("h2", null, "Historial (" + D.pagos.length + ")"));
    var w = el("div", "tw"), t = el("table"); t.innerHTML = "<tr><th>Fecha</th><th>Persona</th><th>Tipo</th><th>Monto</th><th>Referencia</th><th>Origen</th><th></th></tr>";
    D.pagos.slice().sort(function (a, b) { return (b.fecha + b.ts).localeCompare(a.fecha + a.ts); }).forEach(function (p) {
      var tr = el("tr");
      [p.fecha, (p.nombre || "") + " " + (p.correo || ""), p.tipo === "suscripcion" ? "Suscripción" : "Aporte", money(p.monto), p.ref || "", p.src === "wompi" ? "Wompi (automático)" : "Manual"].forEach(function (x) { tr.appendChild(el("td", null, x)); });
      var td = el("td"), b = el("button", "x", "Quitar");
      b.onclick = function () { if (b.dataset.s) { api("pago_del", { id: p.id }).then(function (d) { if (d.ok) { D.pagos = d.pagos; render(); } }); } else { b.dataset.s = "1"; b.textContent = "¿Seguro?"; } };
      td.appendChild(b); tr.appendChild(td); t.appendChild(tr);
    });
    w.appendChild(t); m.appendChild(w);
    var help = el("div", "muted");
    help.innerHTML = "<p><b>Pagos automáticos:</b> los pagos de los links de Wompi de la Virgen llegan solos a esta lista (ya probado con un pago real). Si uno no aparece, regístralo a mano arriba. Para quitar un pago de prueba, usa el botón «Quitar» de su fila.</p>";
    m.appendChild(help);
  }

  /* ---------- Testimonios (moderación) ---------- */
  /* ---------- Facebook: imágenes listas con su texto, por día ---------- */
  var fbDia = "";
  function vFb(m) {
    var P = window.FB_POSTS || [];
    var hechas = P.filter(function (p) { return D.fb[p.id] && D.fb[p.id].done; }).length;
    m.appendChild(el("h2", null, "Publicaciones de Facebook"));
    m.appendChild(el("p", "muted", "Imágenes de 1080×1350 con su texto. Copia el texto, descarga la imagen, publícala en Facebook y marca «Publicada». Listas: " + hechas + " de " + P.length + "."));
    var dias = []; P.forEach(function (p) { if (dias.indexOf(p.d) < 0) dias.push(p.d); });
    if (!fbDia || dias.indexOf(fbDia) < 0) fbDia = dias.filter(function (d) { return d >= new Date().toISOString().slice(0, 10); })[0] || dias[0] || "";
    var bar = el("div", "tabs");
    dias.forEach(function (d) { var b = el("button", d === fbDia ? "on" : "", d.slice(8) + "/" + d.slice(5, 7)); b.onclick = function () { fbDia = d; render(); }; bar.appendChild(b); });
    m.appendChild(bar);
    var list = el("div", "posts"); list.style.marginTop = "16px";
    P.filter(function (p) { return p.d === fbDia; }).forEach(function (p) {
      var st = D.fb[p.id] || {}, box = el("div", "post");
      box.appendChild(el("h3", null, p.d + " · " + p.h));
      var a = el("a"); a.href = "facebook/" + p.img; a.target = "_blank"; a.rel = "noopener";
      var im = el("img"); im.src = "facebook/" + p.img; im.loading = "lazy"; im.alt = p.f; im.style.cssText = "width:100%;height:auto;border-radius:10px;border:1px solid var(--line);display:block";
      a.appendChild(im); box.appendChild(a);
      var ta = el("textarea"); ta.style.minHeight = "150px"; ta.value = st.text || p.t; box.appendChild(ta);
      var r = el("div", "row2");
      var b1 = el("button", "b", "Copiar texto"); b1.onclick = function () { copy(ta.value, b1); };
      var d1 = el("a", "g", "Descargar imagen"); d1.href = "facebook/" + p.img; d1.setAttribute("download", p.img); d1.style.cssText = "text-decoration:none;display:inline-block;background:#fff;color:var(--navy);border:1px solid var(--line);border-radius:999px;padding:8px 14px;font-weight:600";
      var b2 = el("button", "g", "Guardar texto"); b2.onclick = function () { api("fb_set", { id: p.id, text: ta.value }).then(function (d) { if (d.ok) { D.fb = d.fb; b2.textContent = "¡Guardado!"; setTimeout(function () { b2.textContent = "Guardar texto"; }, 1200); } }); };
      var b3 = el("button", st.done ? "g" : "b", st.done ? "Publicada ✓ (deshacer)" : "Marcar como publicada");
      b3.onclick = function () { api("fb_set", { id: p.id, done: !st.done }).then(function (d) { if (d.ok) { D.fb = d.fb; render(); } }); };
      r.appendChild(b1); r.appendChild(d1); r.appendChild(b2); r.appendChild(b3); box.appendChild(r);
      list.appendChild(box);
    });
    m.appendChild(list);
  }

  function vTest(m) {
    m.appendChild(el("h2", null, "Testimonios de fe"));
    var pend = D.tests.filter(function (t) { return t.estado !== "aprobado"; }).length;
    m.appendChild(el("p", "muted", "Nada se publica hasta que lo apruebas aquí. Pendientes: " + pend + " · Publicados: " + (D.tests.length - pend) + ". Puedes corregir ortografía antes de aprobar."));
    if (!D.tests.length) { m.appendChild(el("p", "muted", "Todavía no hay testimonios enviados.")); return; }
    var list = el("div", "posts");
    D.tests.slice().reverse().forEach(function (t) {
      var ap = t.estado === "aprobado", box = el("div", "post");
      var quien = t.anon ? "Anónimo" : (t.name || "Anónimo");
      box.appendChild(el("h3", null, quien + " · " + (t.ts || "").slice(0, 10)));
      box.appendChild(el("span", ap ? "ok" : "bad", ap ? "Publicado en la página" : "Pendiente de revisión"));
      var ta = el("textarea"); ta.style.minHeight = "130px"; ta.value = t.text || ""; box.appendChild(ta);
      var r = el("div", "row2");
      var b1 = el("button", "b", ap ? "Dejar de publicar" : "Aprobar y publicar");
      b1.onclick = function () { api("test_set", { id: t.id, estado: ap ? "pendiente" : "aprobado", text: ta.value }).then(function (d) { if (d.ok) { D.tests = d.tests; render(); } }); };
      var b2 = el("button", "g", "Guardar texto"); b2.onclick = function () { api("test_set", { id: t.id, text: ta.value }).then(function (d) { if (d.ok) { D.tests = d.tests; b2.textContent = "¡Guardado!"; setTimeout(function () { b2.textContent = "Guardar texto"; }, 1200); } }); };
      var b3 = el("button", "g", t.anon ? "Mostrar nombre" : "Hacer anónimo");
      if (t.anon && !t.name) b3.disabled = true;
      b3.onclick = function () { api("test_set", { id: t.id, anon: !t.anon }).then(function (d) { if (d.ok) { D.tests = d.tests; render(); } }); };
      var b4 = el("button", "g", "Copiar para Facebook");
      b4.onclick = function () { copy("🙏 Testimonio de fe\n\n“" + ta.value + "”\n\n— " + quien + "\n\n❤️ Virgen del Milagro ❤️\n\n#VirgenDelMilagro #Tunja #TestimonioDeFe #Fe @seguidores", b4); };
      var b5 = el("button", "x", "Borrar");
      b5.onclick = function () { if (b5.dataset.s) { api("test_del", { id: t.id }).then(function (d) { if (d.ok) { D.tests = d.tests; render(); } }); } else { b5.dataset.s = "1"; b5.textContent = "¿Seguro? Toca otra vez"; } };
      [b1, b2, b3, b4, b5].forEach(function (b) { r.appendChild(b); });
      box.appendChild(r); list.appendChild(box);
    });
    m.appendChild(list);
  }

  /* ---------- Publicidad ---------- */
  var PLANF = VG.PLAN.map(function (x, i) {
    var o = typeof x[1] === "number" ? VG.ORACIONES[x[1]] : VG.NUEVAS[x[1]];
    return { key: x[0], o: o, m: VG.MARIAS_DIA[i], tn: VG.TN[i], i: i };
  });
  var MES = VG.MESES, DIAS = VG.DIAS;
  function fechaTxt(key) { var d = new Date(2026, +key.slice(0, 2) - 1, +key.slice(3)); return DIAS[d.getDay()] + " " + d.getDate() + " de " + MES[d.getMonth()]; }
  function tag(t) { return "#" + t.normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/[^A-Za-z0-9 ]/g, "").split(" ").map(function (w) { return w.charAt(0).toUpperCase() + w.slice(1); }).join(""); }
  var AVE = VG.FIJAS[0].p;
  var SITE = "virgendelmilagro.co";
  function posts(d) {
    var base = "#VirgenDelMilagro #Tunja #OraciónDelDía ";
    var maña = { id: "m", nombre: "Mañana", sub: d.o.f, eb: "Oración de la mañana", img: d.o.t, bg: 0 };
    maña.fb = d.o.t + "\n\n" + d.o.p + "\n\nOración a la Virgen del Milagro:\n" + d.m + "\n\nEscribe \"Amén\" si crees en su divina presencia. Comparte esta oración para regalar un respiro de paz a los demás.\n\n❤️ Virgen del Milagro ❤️\n\n" + base + tag(d.o.t) + " @seguidores";
    maña.wa = "🌅 *" + d.o.t + "*\n\n" + d.o.p + "\n\n🙏 *Oración a la Virgen del Milagro:*\n" + d.m + "\n\n✨ Responde \"Amén\" y reenvía este mensaje a quien lo necesite.\n\n_Virgen del Milagro de Tunja · " + SITE + "_";
    maña.gfb = d.o.t + "\n\n" + d.o.p + "\n\n— Oración a la Virgen del Milagro —\n" + d.m + "\n\nEscribe \"Amén\" si quieres unirte a esta oración. 🙏";
    maña.gwa = "🌅 *" + d.o.t + "*\n\n" + d.o.p + "\n\n_" + d.m + "_\n\n🙏 Amén. Si te hizo bien, compártela.";
    var tarde = { id: "t", nombre: "Tarde", sub: d.tn[1], eb: "Promesa de Dios", img: d.tn[0], bg: 1 };
    tarde.fb = d.tn[0] + "\n\n📖 " + d.tn[1] + ": " + d.tn[2] + "\n\n" + d.tn[3] + "\n\nCuéntanos en los comentarios: ¿en qué necesitas hoy esta promesa de Dios?\n\n❤️ Virgen del Milagro ❤️\n\n" + base + "#PromesasDeDios " + tag(d.o.t) + " @seguidores";
    tarde.wa = "📖 *" + d.tn[0] + "*\n\n" + d.tn[1] + ": " + d.tn[2] + "\n\n" + d.tn[3] + "\n\n🙏 Reenvíalo a alguien que hoy necesite esta promesa.\n\n_Virgen del Milagro de Tunja · " + SITE + "_";
    tarde.gfb = d.tn[0] + "\n\n📖 " + d.tn[1] + ": " + d.tn[2] + "\n\n" + d.tn[3] + "\n\n¿Qué promesa de Dios te sostiene hoy? Cuéntanos. 🙏";
    tarde.gwa = "📖 *" + d.tn[0] + "*\n\n" + d.tn[1] + ": " + d.tn[2] + "\n\n_" + d.tn[3] + "_";
    var noche = { id: "n", nombre: "Noche", sub: "Buenas noches", eb: "Oración de la noche", img: d.tn[4], bg: 2 };
    noche.fb = d.tn[4] + "\n\n" + d.tn[5] + "\n\nDios te salve, María:\n" + AVE + "\n\nEscribe \"Amén\" y comparte esta oración con quien necesite descansar en paz esta noche.\n\n❤️ Virgen del Milagro ❤️\n\n" + base + "#BuenasNoches " + tag(d.o.t) + " @seguidores";
    noche.wa = "🌙 *" + d.tn[4] + "*\n\n" + d.tn[5] + "\n\n🙏 *Dios te salve, María:*\n" + AVE + "\n\n✨ Reenvía esta oración a quien necesite descansar en paz.\n\n_Virgen del Milagro de Tunja · " + SITE + "_";
    noche.gfb = d.tn[4] + "\n\n" + d.tn[5] + "\n\nEscribe \"Amén\" para cerrar el día en oración. 🌙";
    noche.gwa = "🌙 *" + d.tn[4] + "*\n\n" + d.tn[5] + "\n\n🙏 Amén. Buenas noches.";
    return [maña, tarde, noche];
  }

  /* Imagen 1080x1350 con título del día: cielo luminoso, rayos, flores pintadas, la Virgen a la derecha y texto dorado a la izquierda */
  var FIG = null;
  function loadFig() { return FIG || (FIG = new Promise(function (ok, no) { var i = new Image(); i.onload = function () { ok(i); }; i.onerror = no; i.src = "virgen.webp"; })); }
  var MOOD = VG.MOODS;
  /* Fondos hechos a mano (Manus, Midjourney…): archivos fondos/manana-1.jpg, tarde-1.jpg, noche-1.jpg… Pon aquí cuántos hay de cada uno; 0 = usa el diseño con código. */
  var FONDOS = { m: 0, t: 0, n: 0 }, FONDO_NOM = { m: "manana", t: "tarde", n: "noche" };
  function loadBg(p, dayIdx) { var n = FONDOS[p.id] || 0; if (!n) return Promise.resolve(null); return new Promise(function (ok) { var i = new Image(); i.onload = function () { ok(i); }; i.onerror = function () { ok(null); }; i.src = "fondos/" + FONDO_NOM[p.id] + "-" + (1 + dayIdx % n) + ".jpg"; }); }
  function rng(seed) { var s = (seed >>> 0) || 1; return function () { s ^= s << 13; s >>>= 0; s ^= s >>> 17; s ^= s << 5; s >>>= 0; return s / 4294967296; }; }
  /* paletas de flor: [claro, medio, oscuro] */
  var FLOR = [
    ["#FFE6EE", "#F6A3C0", "#D2517B"], ["#FFFFFF", "#FCF0E2", "#E3C79E"], ["#FFDCD3", "#F27A6D", "#B8323C"],
    ["#FFF7C9", "#FFD561", "#DFA11E"], ["#F5E9FF", "#CBA9F2", "#8D5EC6"], ["#FFEAD6", "#FFB58C", "#E0725A"]
  ];
  function petal(c, x, y, rx, ry, rot, a, b, d) {
    c.save(); c.translate(x, y); c.rotate(rot);
    var g = c.createRadialGradient(0, -ry * 0.2, 0, 0, 0, Math.max(rx, ry));
    g.addColorStop(0, a); g.addColorStop(0.7, b); g.addColorStop(1, d);
    c.fillStyle = g; c.beginPath(); c.ellipse(0, 0, rx, ry, 0, 0, 7); c.fill();
    c.strokeStyle = "rgba(120,40,60,0.22)"; c.lineWidth = Math.max(1, rx * 0.04); c.stroke();
    c.restore();
  }
  function rosa(c, x, y, r, p, R) {
    c.save(); c.shadowColor = "rgba(90,50,40,0.28)"; c.shadowBlur = r * 0.35; c.shadowOffsetY = r * 0.12;
    c.fillStyle = p[1]; c.beginPath(); c.arc(x, y, r * 0.9, 0, 7); c.fill(); c.restore();
    var o = R() * 6;
    for (var i = 0; i < 6; i++) { var a = o + i * 1.047; petal(c, x + Math.cos(a) * r * 0.42, y + Math.sin(a) * r * 0.42, r * 0.6, r * 0.5, a, p[0], p[1], p[2]); }
    for (i = 0; i < 5; i++) { a = o + 0.5 + i * 1.257; petal(c, x + Math.cos(a) * r * 0.24, y + Math.sin(a) * r * 0.24, r * 0.42, r * 0.34, a + 1.2, p[0], p[1], p[2]); }
    for (i = 0; i < 3; i++) { a = o + i * 2.1; petal(c, x + Math.cos(a) * r * 0.1, y + Math.sin(a) * r * 0.1, r * 0.26, r * 0.2, a, p[1], p[2], p[2]); }
    c.strokeStyle = "rgba(110,30,50,0.45)"; c.lineWidth = Math.max(1.2, r * 0.03);
    c.beginPath(); c.arc(x, y, r * 0.17, o, o + 4.5); c.stroke(); c.beginPath(); c.arc(x + r * 0.03, y, r * 0.3, o + 2, o + 6); c.stroke();
    c.fillStyle = "rgba(255,255,255,0.35)"; c.beginPath(); c.ellipse(x - r * 0.35, y - r * 0.45, r * 0.22, r * 0.1, -0.6, 0, 7); c.fill();
  }
  function azucena(c, x, y, r, R) {
    var rot = R() * 6.28; c.save(); c.shadowColor = "rgba(90,70,50,0.25)"; c.shadowBlur = r * 0.3; c.shadowOffsetY = r * 0.1;
    c.translate(x, y); c.rotate(rot);
    for (var i = 0; i < 6; i++) {
      c.save(); c.rotate(i * 1.047);
      var g = c.createLinearGradient(0, 0, 0, -r); g.addColorStop(0, "#F8E7A8"); g.addColorStop(0.35, "#FFFFFF"); g.addColorStop(1, "#F4ECF4");
      c.fillStyle = g; c.beginPath(); c.moveTo(0, 0); c.bezierCurveTo(r * 0.5, -r * 0.35, r * 0.34, -r * 0.85, 0, -r); c.bezierCurveTo(-r * 0.34, -r * 0.85, -r * 0.5, -r * 0.35, 0, 0); c.fill();
      c.strokeStyle = "rgba(150,130,110,0.35)"; c.lineWidth = 1.4; c.stroke();
      c.strokeStyle = "rgba(210,190,120,0.55)"; c.beginPath(); c.moveTo(0, -r * 0.06); c.lineTo(0, -r * 0.7); c.stroke();
      c.restore();
    }
    c.shadowColor = "transparent"; c.fillStyle = "#D98A1E";
    for (i = 0; i < 6; i++) { var a = i * 1.047 + 0.3; c.strokeStyle = "#E8C57A"; c.lineWidth = 2; c.beginPath(); c.moveTo(0, 0); c.lineTo(Math.cos(a) * r * 0.4, Math.sin(a) * r * 0.4); c.stroke(); c.beginPath(); c.ellipse(Math.cos(a) * r * 0.42, Math.sin(a) * r * 0.42, r * 0.05, r * 0.09, a, 0, 7); c.fill(); }
    c.restore();
  }
  function margarita(c, x, y, r, p, R) {
    var n = 12, o = R() * 6; c.save(); c.shadowColor = "rgba(90,70,50,0.22)"; c.shadowBlur = r * 0.25; c.shadowOffsetY = r * 0.08;
    for (var i = 0; i < n; i++) { var a = o + i * 6.283 / n; petal(c, x + Math.cos(a) * r * 0.62, y + Math.sin(a) * r * 0.62, r * 0.4, r * 0.15, a, "#FFFFFF", "#FFFFFF", p[0]); }
    c.restore();
    var g = c.createRadialGradient(x - r * 0.1, y - r * 0.1, 0, x, y, r * 0.3); g.addColorStop(0, "#FFE680"); g.addColorStop(1, "#E3A21C");
    c.fillStyle = g; c.beginPath(); c.arc(x, y, r * 0.3, 0, 7); c.fill();
  }
  function hoja(c, x, y, len, w, rot, R) {
    c.save(); c.translate(x, y); c.rotate(rot);
    var g = c.createLinearGradient(0, 0, len, 0); g.addColorStop(0, "#4E9A58"); g.addColorStop(0.6, "#86C77F"); g.addColorStop(1, "#B6E0A0");
    c.fillStyle = g; c.shadowColor = "rgba(30,70,40,0.25)"; c.shadowBlur = 8;
    c.beginPath(); c.moveTo(0, 0); c.bezierCurveTo(len * 0.3, -w, len * 0.75, -w * 0.8, len, 0); c.bezierCurveTo(len * 0.75, w * 0.8, len * 0.3, w, 0, 0); c.fill();
    c.shadowColor = "transparent"; c.strokeStyle = "rgba(255,255,255,0.45)"; c.lineWidth = 2; c.beginPath(); c.moveTo(len * 0.04, 0); c.lineTo(len * 0.92, 0); c.stroke();
    c.restore();
  }
  function ramo(c, x, y, len, rot, R) {
    c.save(); c.translate(x, y); c.rotate(rot); c.strokeStyle = "#5FA06A"; c.lineWidth = 3; c.beginPath(); c.moveTo(0, 0); c.quadraticCurveTo(len * 0.5, -len * 0.18, len, 0); c.stroke();
    for (var i = 1; i < 6; i++) { var t = i / 6, px = len * t, py = -len * 0.18 * 2 * t * (1 - t); c.fillStyle = "#FFFFFF"; c.shadowColor = "rgba(0,0,0,0.12)"; c.shadowBlur = 4; c.beginPath(); c.arc(px, py - 8, 7 + R() * 4, 0, 7); c.fill(); c.beginPath(); c.arc(px + 10, py + 9, 5 + R() * 3, 0, 7); c.fill(); }
    c.restore();
  }
  /* ramo de flores: se pinta de atrás hacia adelante dentro de una elipse (cx, cy, ancho, alto) */
  function ramillete(c, cx, cy, w, h, n, pals, R, rotBase) {
    var i, items = [];
    for (i = 0; i < 9; i++) { var a = R() * 6.283; items.push({ k: "h", x: cx + Math.cos(a) * w * 0.5, y: cy + Math.sin(a) * h * 0.5, a: a + (R() - 0.5) * 0.6 + (rotBase || 0), len: 160 + R() * 110, w: 38 + R() * 20, z: -1 }); }
    for (i = 0; i < 3; i++) { a = R() * 6.283; items.push({ k: "r", x: cx + Math.cos(a) * w * 0.45, y: cy + Math.sin(a) * h * 0.45, a: a, len: 150 + R() * 100, z: -0.5 }); }
    for (i = 0; i < n; i++) { var aa = R() * 6.283, rr = Math.sqrt(R()); items.push({ k: "f", x: cx + Math.cos(aa) * w * 0.5 * rr, y: cy + Math.sin(aa) * h * 0.5 * rr, r: 66 + R() * 62 - rr * 14, t: R(), p: pals[Math.floor(R() * pals.length)], z: rr * 0 }); }
    items.sort(function (u, v) { return (u.k === "f" && v.k === "f") ? u.y - v.y : u.z - v.z; });
    items.forEach(function (it) {
      if (it.k === "h") hoja(c, it.x, it.y, it.len, it.w, it.a, R);
      else if (it.k === "r") ramo(c, it.x, it.y, it.len, it.a, R);
      else if (it.t < 0.5) rosa(c, it.x, it.y, it.r, it.p, R); else if (it.t < 0.75) azucena(c, it.x, it.y, it.r * 0.95, R); else margarita(c, it.x, it.y, it.r * 0.8, it.p, R);
    });
  }
  function estrella(c, x, y, r, col) {
    c.save(); c.translate(x, y); c.fillStyle = col; c.beginPath();
    for (var i = 0; i < 8; i++) { var rad = i % 2 ? r * 0.18 : r, a = i * Math.PI / 4; c.lineTo(Math.cos(a) * rad, Math.sin(a) * rad); }
    c.closePath(); c.fill(); c.restore();
  }
  function nube(c, x, y, s, al) {
    for (var i = 0; i < 6; i++) { var px = x + (i - 2.5) * 70 * s, py = y + (i % 2 ? -18 : 10) * s, g = c.createRadialGradient(px, py, 0, px, py, 90 * s); g.addColorStop(0, "rgba(255,255,255," + al + ")"); g.addColorStop(1, "rgba(255,255,255,0)"); c.fillStyle = g; c.fillRect(px - 100 * s, py - 100 * s, 200 * s, 200 * s); }
  }
  function drawPost(cv, p, dayIdx) { return loadBg(p, dayIdx).then(function (bg) { return drawPostBase(cv, p, dayIdx, bg); }); }
  function drawPostBase(cv, p, dayIdx, BG) {
    var W = 1080, H = 1350; cv.width = W; cv.height = H; var c = cv.getContext("2d");
    var night = p.id === "n", mood = MOOD[(dayIdx + p.bg * 2) % MOOD.length], R = rng(dayIdx * 97 + p.bg * 13 + 7);
    var g = c.createLinearGradient(0, 0, 0, H);
    if (night) { g.addColorStop(0, "#B9C8F0"); g.addColorStop(0.5, "#E3DDF6"); g.addColorStop(1, "#FBEFE9"); } else { g.addColorStop(0, mood[0]); g.addColorStop(0.7, mood[1]); g.addColorStop(1, "#FFFAEC"); }
    c.fillStyle = g; c.fillRect(0, 0, W, H);
    if (BG) { var sc = Math.max(W / BG.width, H / BG.height); c.drawImage(BG, (W - BG.width * sc) / 2, (H - BG.height * sc) / 2, BG.width * sc, BG.height * sc); }
    if (!night && !BG) { nube(c, 250, 300, 1.4, 0.55); nube(c, 900, 190, 1.1, 0.5); nube(c, 160, 760, 1.2, 0.35); }
    var cx = 760, cy = 520;
    if (c.createConicGradient) {
      var cg = c.createConicGradient(0, cx, cy - 150); for (var k = 0; k < 24; k++) { var a0 = k / 24; cg.addColorStop(a0, "rgba(255,240,180,0)"); cg.addColorStop(a0 + 0.5 / 24, night ? "rgba(255,244,200,0.32)" : "rgba(255,240,180,0.55)"); cg.addColorStop(Math.min(1, a0 + 1 / 24 - 0.001), "rgba(255,240,180,0)"); }
      c.save(); c.fillStyle = cg; c.beginPath(); c.arc(cx, cy - 150, 1200, 0, 7); c.fill(); c.restore();
    }
    var rg = c.createRadialGradient(cx, cy, 0, cx, cy, 640);
    rg.addColorStop(0, "rgba(255,253,240,1)"); rg.addColorStop(0.3, "rgba(255,236,160,0.85)"); rg.addColorStop(1, "rgba(255,243,200,0)");
    if (BG) { rg = c.createRadialGradient(cx, cy, 0, cx, cy, 560); rg.addColorStop(0, "rgba(255,248,215,0.75)"); rg.addColorStop(1, "rgba(255,243,200,0)"); }
    c.fillStyle = rg; c.fillRect(0, 0, W, H);
    var i, bk;
    if (night && !BG) { for (i = 0; i < 40; i++) estrella(c, R() * W, R() * 650, 5 + R() * 11, "rgba(255,246,206," + (0.55 + R() * 0.4) + ")"); c.save(); c.shadowColor = "rgba(255,240,180,0.95)"; c.shadowBlur = 30; c.fillStyle = "#FFF6D2"; c.beginPath(); c.arc(990, 150, 40, 0, 7); c.arc(1012, 138, 34, 0, 7, true); c.fill("evenodd"); c.restore(); } 
    for (i = 0; i < 22; i++) { bk = c.createRadialGradient(0, 0, 0, 0, 0, 1); var bx = R() * W, by = R() * H, br = 14 + R() * 38; bk = c.createRadialGradient(bx, by, 0, bx, by, br); bk.addColorStop(0, "rgba(255,255,255,0.55)"); bk.addColorStop(1, "rgba(255,240,190,0)"); c.fillStyle = bk; c.fillRect(bx - br, by - br, br * 2, br * 2); }
    var pi = (dayIdx * 2 + p.bg * 3) % FLOR.length, pals = [FLOR[pi], FLOR[(pi + 1) % FLOR.length], FLOR[1], FLOR[(pi + 3) % FLOR.length]];
    // flores del fondo (arriba a la izquierda, detrás de todo)
    if (!BG) ramillete(c, 0, 10, 300, 230, 7, pals, R, 0.7);
    function fin(img) {
      if (img) { var h = 1230, w = h * img.width / img.height; c.save(); c.shadowColor = "rgba(255,226,140,0.95)"; c.shadowBlur = 40; c.drawImage(img, W - w - 40, H - h - 40, w, h); c.restore(); c.drawImage(img, W - w - 40, H - h - 40, w, h); }
      // ramilletes del frente: a los pies de la Virgen y abajo a la izquierda
      if (!BG) { ramillete(c, 830, 1300, 560, 210, 17, pals, R, -1.2);
      ramillete(c, 250, 1290, 600, 230, 17, [pals[1], pals[0], pals[2], pals[3]], R, -1.8); }
      // pétalos y destellos cayendo
      for (var q = 0; q < (BG ? 0 : 26); q++) { var pp = pals[q % pals.length]; c.save(); c.globalAlpha = 0.85; petal(c, R() * W, 120 + R() * 1000, 9 + R() * 9, 5 + R() * 5, R() * 6.28, pp[0], pp[1], pp[2]); c.restore(); }
      for (q = 0; q < 14; q++) estrella(c, R() * W, R() * 1100, 6 + R() * 12, "rgba(255,214,110,0.9)");
      var x0 = 70, maxW = 490;
      c.textBaseline = "alphabetic"; c.textAlign = "left";
      c.fillStyle = "#7A4E00"; c.font = "700 26px 'Nunito Sans',sans-serif"; if ("letterSpacing" in c) c.letterSpacing = "5px";
      c.fillText(p.eb.toUpperCase(), x0, 190); if ("letterSpacing" in c) c.letterSpacing = "0px";
      // título: se reduce hasta que quepa
      var size = 150, lines;
      for (; size >= 72; size -= 4) { c.font = size + "px 'Great Vibes','Snell Roundhand',cursive"; lines = wrap(c, p.img, maxW); if (lines.length <= 5 && lines.every(function (l) { return c.measureText(l).width <= maxW; })) break; }
      var y = 190 + size * 1.05, tg = c.createLinearGradient(0, y - size, 0, y + size * lines.length);
      tg.addColorStop(0, "#9A6A10"); tg.addColorStop(0.5, "#C9962E"); tg.addColorStop(1, "#7A4E00");
      c.save(); c.shadowColor = "rgba(255,255,255,0.9)"; c.shadowBlur = 14; c.fillStyle = tg; c.font = size + "px 'Great Vibes','Snell Roundhand',cursive";
      lines.forEach(function (l) { c.fillText(l, x0, y); y += size * 1.02; }); c.restore();
      y += 20;
      c.save(); c.shadowColor = "rgba(255,255,255,0.9)"; c.shadowBlur = 10; c.fillStyle = "#5A3A12"; c.font = "600 44px 'Cormorant Garamond',Georgia,serif";
      wrap(c, p.sub, maxW).forEach(function (l) { c.fillText(l, x0, y); y += 54; }); c.restore();
      // cinta con la firma
      c.save(); c.shadowColor = "rgba(90,60,20,0.3)"; c.shadowBlur = 16; c.fillStyle = "rgba(255,250,236,0.92)"; c.beginPath();
      if (c.roundRect) c.roundRect(60, 1236, 400, 70, 35); else c.rect(60, 1236, 400, 70); c.fill(); c.restore();
      c.fillStyle = "#7A4E00"; c.font = "italic 600 40px 'Cormorant Garamond',Georgia,serif"; c.textAlign = "center"; c.fillText("Virgen del Milagro", 260, 1284); c.textAlign = "left";
    }
    return loadFig().then(fin, function () { fin(null); });
  }
  function wrap(c, text, maxW) {
    var words = String(text).split(" "), lines = [], cur = "";
    words.forEach(function (w) { var t = cur ? cur + " " + w : w; if (c.measureText(t).width > maxW && cur) { lines.push(cur); cur = w; } else cur = t; });
    if (cur) lines.push(cur); return lines;
  }
  function copy(txt, btn) {
    var ok = function () { var o = btn.textContent; btn.textContent = "¡Copiado!"; setTimeout(function () { btn.textContent = o; }, 1400); };
    var fb = function () { var ta = el("textarea"); ta.value = txt; document.body.appendChild(ta); ta.select(); try { document.execCommand("copy"); ok(); } catch (e) {} ta.remove(); };
    try { navigator.clipboard.writeText(txt).then(ok, fb); } catch (e) { fb(); }
  }
  function hoyIdx() { var k = ("0" + (new Date().getMonth() + 1)).slice(-2) + "-" + ("0" + new Date().getDate()).slice(-2); for (var i = 0; i < PLANF.length; i++) if (PLANF[i].key === k) return i; return 0; }
  var dayI = null;
  function vPub(m) {
    if (dayI === null) dayI = hoyIdx();
    var d = PLANF[dayI];
    m.appendChild(el("h2", null, "Publicidad por día"));
    var bar = el("div", "bar");
    var prev = el("button", "g", "← Anterior"), next = el("button", "g", "Siguiente →"), sel = el("select");
    PLANF.forEach(function (x, i) { var o = el("option", null, fechaTxt(x.key) + " · " + x.o.t); o.value = i; if (i === dayI) o.selected = true; sel.appendChild(o); });
    prev.onclick = function () { if (dayI > 0) { dayI--; render(); } }; next.onclick = function () { if (dayI < PLANF.length - 1) { dayI++; render(); } };
    sel.onchange = function () { dayI = +sel.value; render(); };
    bar.appendChild(prev); bar.appendChild(sel); bar.appendChild(next); m.appendChild(bar);
    m.appendChild(el("p", "muted", "Tres publicaciones por día. Cada una trae su imagen (1080 × 1350, formato de Facebook y WhatsApp) y el texto listo para: página de Facebook, WhatsApp, grupos de Facebook y grupos de WhatsApp. Los hashtags y @seguidores van solo en la página."));
    var grid = el("div", "posts");
    posts(d).forEach(function (p) {
      var card = el("div", "post"); card.appendChild(el("h3", null, p.nombre + " · " + p.eb));
      var cv = el("canvas"); cv.width = 1080; cv.height = 1350; card.appendChild(cv);
      var ready = document.fonts && document.fonts.load ? Promise.all([document.fonts.load("100px 'Great Vibes'"), document.fonts.load("600 40px 'Cormorant Garamond'"), document.fonts.load("700 26px 'Nunito Sans'")]).catch(function () {}) : Promise.resolve();
      ready.then(function () { return drawPost(cv, p, d.i); });
      var ir = el("div", "row2"), dl = el("button", "b", "Descargar imagen"), ci = el("button", "g", "Copiar imagen");
      dl.onclick = function () { cv.toBlob(function (b) { var a = el("a"); a.href = URL.createObjectURL(b); a.download = "virgen-" + d.key + "-" + p.nombre.toLowerCase() + ".png"; a.click(); }, "image/png"); };
      ci.onclick = function () { try { cv.toBlob(function (b) { navigator.clipboard.write([new ClipboardItem({ "image/png": b })]).then(function () { ci.textContent = "¡Copiada!"; setTimeout(function () { ci.textContent = "Copiar imagen"; }, 1400); }, function () { ci.textContent = "Usa Descargar"; }); }, "image/png"); } catch (e) { ci.textContent = "Usa Descargar"; } };
      ir.appendChild(dl); ir.appendChild(ci); card.appendChild(ir);
      var tabs = el("div", "tabs"), ta = el("textarea"), cur = "fb";
      var CH = [["fb", "Facebook (página)"], ["wa", "WhatsApp"], ["gfb", "Grupos Facebook"], ["gwa", "Grupos WhatsApp"]];
      function show() { ta.value = p[cur]; Array.prototype.forEach.call(tabs.children, function (b, i) { b.className = CH[i][0] === cur ? "on" : ""; }); }
      CH.forEach(function (c) { var b = el("button", "", c[1]); b.onclick = function () { cur = c[0]; show(); }; tabs.appendChild(b); });
      var cp = el("button", "b", "Copiar texto"); cp.onclick = function () { copy(ta.value, cp); };
      card.appendChild(tabs); card.appendChild(ta); card.appendChild(cp); show(); grid.appendChild(card);
    });
    m.appendChild(grid);
  }
  load();
})();
</script>
</body></html>
