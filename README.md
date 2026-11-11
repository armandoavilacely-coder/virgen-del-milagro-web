# Virgen del Milagro - página web y chat bíblico

Sitio: www.virgendelmilagro.co (HostGator, cPanel de concursosdelestado.com, dominio adicional).

## Dónde va cada archivo en el servidor (/home2/arielar1)
| En este repo | En el servidor |
|---|---|
| `pagina/index.html`, `pagina/virgen.webp` | `/virgendelmilagro.co/` |
| `servidor/acceso/chat_virgen.php`, `servidor/acceso/suscribir_virgen.php` | `/concursosdelestado.com/acceso/` |
| `servidor/ce_data/virgen_origins.txt` | `/ce_data/` |

## Notas
- El chat usa la IA Haiku de Rocky: reutiliza `ce_data/chat.key` y `chat_core.php` del servidor. La llave NO está en este repo.
- `virgen_origins.txt` lista los dominios autorizados a llamar al chat (CORS).
- Git no despliega solo: tras cada cambio hay que subir los archivos al cPanel.
- `suscribir_virgen.php` guarda suscriptores en `ce_data/virgen_suscriptores.jsonl` (fuera de la web pública).
- Monetización: en el `<script>` de `pagina/index.html` están WHATSAPP, PRODUCTS, SPONSORS, PAY y TOUR; lo que está vacío no se muestra.
- Panel: `servidor/admin/virgen/index.php` (en `/admin/virgen/` de concursosdelestado.com; usa el mismo inicio de sesión de `/admin/`). Pestañas: resumen, suscriptores, pagos (solo Wompi) y publicidad por día (imagen + 4 textos por publicación, 3 al día).
- `wompi_virgen.php` recibe los eventos de Wompi y registra los pagos aprobados; necesita el secreto de eventos en `ce_data/wompi_eventos.key` (lo guarda el dueño en el servidor, nunca en git).
- `pagina/contenido.js` tiene las oraciones, tarde y noche hasta el 31 de diciembre; lo usan la página y el panel.

## Testimonios y Rosario
- `servidor/acceso/testimonios_virgen.php`: recibe testimonios (anónimos o con nombre) como *pendientes*; el GET público solo devuelve los aprobados. Se aprueban en el panel, pestaña Testimonios.
- Sección `#rosario` en la página: misterios del día (lun/sáb gozosos, mar/vie dolorosos, mié/dom gloriosos, jue luminosos) con guía paso a paso.
- `pagina/index.html` es un documento completo (doctype, charset, viewport). Para el artifact se quita ese esqueleto.
