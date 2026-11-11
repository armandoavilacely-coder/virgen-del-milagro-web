<?php
// Gancho para wompi.php (Concursos del Estado). Wompi permite una sola URL de eventos por cuenta;
// si el pago viene de uno de los links de la Virgen del Milagro, se lo pasamos a wompi_virgen.php y se termina aquí.
// Cualquier otro evento sigue su camino normal en wompi.php. Para desactivarlo basta con quitar la línea que lo incluye.
$vgRaw = file_get_contents('php://input');
if ($vgRaw !== false && preg_match('/"payment_link_id"\s*:\s*"(hyEaOO|smECiy)"/', $vgRaw)) {
    require __DIR__ . '/wompi_virgen.php';
    exit;
}
