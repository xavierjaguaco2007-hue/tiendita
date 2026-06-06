<?php
session_start();

// Inicializar carrito si no existe
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

function addToCart($product_id, $quantity = 1) {
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
}

function updateCartItem($product_id, $quantity) {
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$product_id]);
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
}

function removeFromCart($product_id) {
    unset($_SESSION['cart'][$product_id]);
}

function getCartItems($pdo) {
    $items = [];
    if (empty($_SESSION['cart'])) return $items;

    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $product) {
        $id = $product['id'];
        $quantity = $_SESSION['cart'][$id];
        $items[] = [
            'id' => $id,
            'nombre' => $product['nombre'],
            'precio' => $product['precio'],
            'cantidad' => $quantity,
            'subtotal' => $product['precio'] * $quantity
        ];
    }
    return $items;
}

function getCartTotal($pdo) {
    $total = 0;
    foreach (getCartItems($pdo) as $item) {
        $total += $item['subtotal'];
    }
    return $total;
}

function clearCart() {
    $_SESSION['cart'] = [];
}

function validateXMLWithDTD($xmlFile, $dtdFile) {
    $dom = new DOMDocument();
    $old = libxml_use_internal_errors(true);
    $dom->load($xmlFile);
    libxml_use_internal_errors($old);

    $dom->validateOnParse = true;
    $dom->resolveExternals = true;

    $old = libxml_use_internal_errors(true);
    $dom->load($xmlFile, LIBXML_DTDVALID);
    if (!$dom->validate()) {
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($old);
        $errorMsg = '';
        foreach ($errors as $error) {
            $errorMsg .= "Error línea {$error->line}: {$error->message}\n";
        }
        return $errorMsg;
    }
    libxml_use_internal_errors($old);
    return true;
}

function importCartFromXML($xmlFile, $pdo, $dtdFile) {
    $validation = validateXMLWithDTD($xmlFile, $dtdFile);
    if ($validation !== true) {
        return ['success' => false, 'message' => "XML inválido según DTD: $validation"];
    }

    $dom = new DOMDocument();
    $dom->load($xmlFile);
    $items = $dom->getElementsByTagName('item');

    $newCart = [];
    foreach ($items as $item) {
        $id = $item->getAttribute('id');
        $nombre = $item->getElementsByTagName('nombre')->item(0)->nodeValue;
        $precio = $item->getElementsByTagName('precio')->item(0)->nodeValue;
        $cantidad = (int)$item->getElementsByTagName('cantidad')->item(0)->nodeValue;

        $stmt = $pdo->prepare("SELECT id, nombre, precio FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            return ['success' => false, 'message' => "Producto con ID $id no existe en la base de datos."];
        }
        if ($product['nombre'] !== $nombre || (float)$product['precio'] != (float)$precio) {
            return ['success' => false, 'message' => "Datos del producto $id no coinciden con la base de datos."];
        }

        if ($cantidad > 0) {
            $newCart[$id] = $cantidad;
        }
    }

    $_SESSION['cart'] = $newCart;
    return ['success' => true, 'message' => 'Carrito importado correctamente.'];
}

function exportCartToXML($pdo, $dtdFile = null) {
    $items = getCartItems($pdo);
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = true;

    // Crear elemento raíz
    $carrito = $dom->createElement('carrito');
    $dom->appendChild($carrito);

    foreach ($items as $item) {
        $itemElem = $dom->createElement('item');
        $itemElem->setAttribute('id', $item['id']);

        $nombre = $dom->createElement('nombre', htmlspecialchars($item['nombre']));
        $precio = $dom->createElement('precio', number_format($item['precio'], 2, '.', ''));
        $cantidad = $dom->createElement('cantidad', $item['cantidad']);

        $itemElem->appendChild($nombre);
        $itemElem->appendChild($precio);
        $itemElem->appendChild($cantidad);
        $carrito->appendChild($itemElem);
    }

    // Añadir la declaración DOCTYPE si existe el archivo DTD
    if ($dtdFile && file_exists($dtdFile)) {
        $dtdPublicId = "-//MiCarrito//DTD Carrito 1.0//ES";
        $dtdSystemId = "cart.dtd";
        // Usar DOMImplementation::createDocumentType (más fiable)
        $doctype = $dom->implementation->createDocumentType('carrito', $dtdPublicId, $dtdSystemId);
        // Insertar el DOCTYPE antes del elemento raíz
        $dom->insertBefore($doctype, $carrito);
    }

    return $dom->saveXML();
}
?>