<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$xml = exportCartToXML($pdo, 'dtd/cart.dtd');

header('Content-Type: application/xml');
header('Content-Disposition: attachment; filename="carrito.xml"');
echo $xml;
exit;