<?php
// Virgen del Milagro - panel de administración (suscriptores, pagos de Wompi y publicidad por día).
// Usa el mismo inicio de sesión de administrador de /admin/ de Concursos del Estado: si no hay sesión, envía a /admin/.
require __DIR__ . '/../../acceso/lib.php';
header('Cache-Control: no-store');
if (function_exists('ce_headers_seguras')) ce_headers_seguras();
header('X-Robots-Tag: noindex, nofollow');

$b = ce_boot();
$S = ($b === true) ? ce_session() : null;
if (!$S || ($S['role'] ?? '') !== 'admin') { header('Location: /admin/'); exit; }

$subsF = ce_dir() . '/virgen_suscriptores.jsonl';
$pagosF = ce_dir() . '/virgen_pagos.jsonl';

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
        vg_json(['ok' => true, 'subs' => vg_read($subsF), 'pagos' => vg_read($pagosF)]);
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
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:20px}
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
@media(max-width:600px){main{margin:0 8px 24px;padding:14px}nav,header{padding-inline:12px}.posts{grid-template-columns:minmax(0,1fr)}}
</style></head>
<body>
<header><h1>Virgen del Milagro · Administración</h1><span><a href="/admin/">Panel de Concursos del Estado</a> · <a href="/admin/?salir=1">Cerrar sesión</a></span></header>
<nav id="tabs"></nav>
<main id="main"><p class="muted">Cargando…</p></main>
<script src="contenido.js"></script>
<script>
(function () {
  "use strict";
  var VG = window.VG, D = { subs: [], pagos: [] }, tab = "resumen";
  function $(id) { return document.getElementById(id); }
  function el(t, c, x) { var n = document.createElement(t); if (c) n.className = c; if (x != null) n.textContent = x; return n; }
  function money(n) { return "$" + Number(n || 0).toLocaleString("es-CO"); }
  function api(a, body) {
    return fetch("?a=" + a, { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/json", "X-VG": "1" }, body: JSON.stringify(body || {}) })
      .then(function (r) { return r.json(); });
  }
  function load() { return api("datos").then(function (d) { if (d.ok) { D.subs = d.subs; D.pagos = d.pagos; } render(); }); }
  var TABS = [["resumen", "Resumen"], ["usuarios", "Suscriptores"], ["pagos", "Pagos"], ["publicidad", "Publicidad"]];
  function drawTabs() {
    var n = $("tabs"); n.textContent = "";
    TABS.forEach(function (t) { var b = el("button", t[0] === tab ? "on" : "", t[1]); b.onclick = function () { tab = t[0]; render(); }; n.appendChild(b); });
  }
  function render() { drawTabs(); var m = $("main"); m.textContent = ""; ({ resumen: vResumen, usuarios: vUsuarios, pagos: vPagos, publicidad: vPub })[tab](m); }

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
    m.appendChild(el("p", "muted", "Los pagos de Wompi llegan solos si en Wompi configuras la URL de eventos y guardas el secreto de eventos en el servidor (ver pestaña Pagos). Mientras tanto puedes registrarlos a mano."));
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
    help.innerHTML = "<p><b>Para que Wompi registre los pagos solo:</b> en Wompi, Desarrolladores → URL de eventos, pega <code>https://www.concursosdelestado.com/acceso/wompi_virgen.php</code>. Luego copia el «Secreto de eventos» y guárdalo tú mismo en un archivo llamado <code>wompi_eventos.key</code> dentro de la carpeta <code>ce_data</code> del servidor (el secreto no debe pasar por el chat ni por git).</p>";
    m.appendChild(help);
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

  /* Imagen 1080x1350 con título del día: cielo, rayos, la Virgen a la derecha y texto dorado a la izquierda */
  var FIG = null;
  function loadFig() { return FIG || (FIG = new Promise(function (ok, no) { var i = new Image(); i.onload = function () { ok(i); }; i.onerror = no; i.src = "virgen.webp"; })); }
  var MOOD = VG.MOODS;
  function drawPost(cv, p, dayIdx) {
    var W = 1080, H = 1350; cv.width = W; cv.height = H; var c = cv.getContext("2d");
    var night = p.id === "n", mood = MOOD[(dayIdx + p.bg * 2) % MOOD.length];
    var g = c.createLinearGradient(0, 0, 0, H);
    if (night) { g.addColorStop(0, "#0E2238"); g.addColorStop(0.6, "#1F4E79"); g.addColorStop(1, "#2F6A9E"); } else { g.addColorStop(0, mood[0]); g.addColorStop(1, mood[1]); }
    c.fillStyle = g; c.fillRect(0, 0, W, H);
    var cx = 760, cy = 520;
    if (c.createConicGradient) {
      var cg = c.createConicGradient(0, cx, cy - 150); for (var k = 0; k < 24; k++) { var a = k / 24; cg.addColorStop(a, "rgba(255,236,170,0)"); cg.addColorStop(a + 0.5 / 24, night ? "rgba(255,236,170,0.20)" : "rgba(255,236,170,0.50)"); cg.addColorStop(Math.min(1, a + 1 / 24 - 0.001), "rgba(255,236,170,0)"); }
      c.save(); c.fillStyle = cg; c.beginPath(); c.arc(cx, cy - 150, 1200, 0, 7); c.fill(); c.restore();
    }
    var rg = c.createRadialGradient(cx, cy, 0, cx, cy, 620);
    rg.addColorStop(0, "rgba(255,252,235,0.98)"); rg.addColorStop(0.3, night ? "rgba(255,226,140,0.55)" : "rgba(255,233,150,0.85)"); rg.addColorStop(1, "rgba(255,243,200,0)");
    c.fillStyle = rg; c.fillRect(0, 0, W, H);
    if (night) { for (var s = 0; s < 70; s++) { var sx = (s * 197) % W, sy = (s * 331) % 700, r = 1 + (s % 3); c.fillStyle = "rgba(255,243,200," + (0.4 + (s % 5) / 10) + ")"; c.beginPath(); c.arc(sx, sy, r, 0, 7); c.fill(); } }
    function fin(img) {
      if (img) { var h = 1230, w = h * img.width / img.height; c.save(); c.shadowColor = "rgba(255,226,140,0.95)"; c.shadowBlur = 40; c.drawImage(img, W - w - 40, H - h - 40, w, h); c.restore(); c.drawImage(img, W - w - 40, H - h - 40, w, h); }
      var x0 = 70, maxW = 490;
      c.textBaseline = "alphabetic"; c.textAlign = "left";
      c.fillStyle = night ? "#F2D68A" : "#7A4E00"; c.font = "700 26px 'Nunito Sans',sans-serif"; if ("letterSpacing" in c) c.letterSpacing = "5px";
      c.fillText(p.eb.toUpperCase(), x0, 130); if ("letterSpacing" in c) c.letterSpacing = "0px";
      // título: se reduce hasta que quepa
      var size = 150, lines;
      for (; size >= 72; size -= 4) { c.font = size + "px 'Great Vibes','Snell Roundhand',cursive"; lines = wrap(c, p.img, maxW); if (lines.length <= 5 && lines.every(function (l) { return c.measureText(l).width <= maxW; })) break; }
      var y = 130 + size * 1.05, tg = c.createLinearGradient(0, y - size, 0, y + size * lines.length);
      tg.addColorStop(0, night ? "#FBEBB5" : "#9A6A10"); tg.addColorStop(0.5, night ? "#F2D68A" : "#C9962E"); tg.addColorStop(1, night ? "#D9A93F" : "#7A4E00");
      c.fillStyle = tg; c.font = size + "px 'Great Vibes','Snell Roundhand',cursive";
      lines.forEach(function (l) { c.fillText(l, x0, y); y += size * 1.02; });
      y += 20;
      c.fillStyle = night ? "#CFE4F7" : "#5A3A12"; c.font = "600 44px 'Cormorant Garamond',Georgia,serif";
      wrap(c, p.sub, maxW).forEach(function (l) { c.fillText(l, x0, y); y += 54; });
      c.fillStyle = night ? "#F2D68A" : "#7A4E00"; c.font = "italic 500 40px 'Cormorant Garamond',Georgia,serif"; c.fillText("Virgen del milagro", x0, H - 70);
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
