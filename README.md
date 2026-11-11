# Virgen del Milagro - página web y chat bíblico

Sitio: www.virgendelmilagro.co (HostGator, cPanel de concursosdelestado.com, dominio adicional).

## Dónde va cada archivo en el servidor (/home2/arielar1)
| En este repo | En el servidor |
|---|---|
| `pagina/index.html`, `pagina/virgen.webp` | `/virgendelmilagro.co/` |
| `servidor/acceso/chat_virgen.php` | `/concursosdelestado.com/acceso/` |
| `servidor/ce_data/virgen_origins.txt` | `/ce_data/` |

## Notas
- El chat usa la IA Haiku de Rocky: reutiliza `ce_data/chat.key` y `chat_core.php` del servidor. La llave NO está en este repo.
- `virgen_origins.txt` lista los dominios autorizados a llamar al chat (CORS).
- Git no despliega solo: tras cada cambio hay que subir los archivos al cPanel.
