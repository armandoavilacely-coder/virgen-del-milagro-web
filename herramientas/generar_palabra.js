// Genera servidor/ce_data/virgen_palabra.json (contenido de los correos diarios) desde pagina/contenido.js y las promesas de pagina/index.html.
// Uso: node herramientas/generar_palabra.js
const fs = require("fs"), path = require("path");
const root = path.join(__dirname, "..");
global.window = {}; eval(fs.readFileSync(path.join(root, "pagina/contenido.js"), "utf8"));
const VG = window.VG;
const html = fs.readFileSync(path.join(root, "pagina/index.html"), "utf8");
const a = html.indexOf("var PROMESAS = ["), b = html.indexOf("];", a) + 1;
const PROMESAS = eval(html.slice(a + "var PROMESAS = ".length, b));
const plan = {};
VG.PLAN.forEach((x, i) => {
  const o = typeof x[1] === "number" ? VG.ORACIONES[x[1]] : VG.NUEVAS[x[1]];
  plan[x[0]] = { tema: o.t, frase: o.f, oracion: o.p, maria: VG.MARIAS_DIA[i] };
});
const out = {
  plan,
  ciclo: VG.ORACIONES.map(o => ({ tema: o.t, frase: o.f, oracion: o.p })),
  marias: VG.MARIAS.map(m => (typeof m === "string" ? m : m.p)),
  promesas: PROMESAS.map(p => ({ k: p.k, v: p.t, c: p.c, r: p.r })),
};
fs.writeFileSync(path.join(root, "servidor/ce_data/virgen_palabra.json"), JSON.stringify(out));
console.log(Object.keys(plan).length + " días en el plan, " + out.ciclo.length + " temas, " + out.marias.length + " marianas, " + out.promesas.length + " promesas");
