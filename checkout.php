<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$mensaje = '';
$error = false;

// Procesar la confirmación del pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartItems = getCartItems($pdo);
    
    if (empty($cartItems)) {
        header("Location: cart.php");
        exit;
    }
    
    try {
        // Iniciar transacción
        $pdo->beginTransaction();
        
        // Verificar stock y calcular totales
        $stockValido = true;
        $erroresStock = [];
        
        foreach ($cartItems as $item) {
            $stmt = $pdo->prepare("SELECT stock FROM productos WHERE id = ? FOR UPDATE");
            $stmt->execute([$item['id']]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$producto) {
                throw new Exception("Producto no encontrado: ID {$item['id']}");
            }
            
            if ($producto['stock'] < $item['cantidad']) {
                $stockValido = false;
                $erroresStock[] = "{$item['nombre']} (stock disponible: {$producto['stock']}, solicitado: {$item['cantidad']})";
            }
        }
        
        if (!$stockValido) {
            $mensaje = "No hay suficiente stock para: " . implode(", ", $erroresStock);
            $error = true;
            $pdo->rollBack();
        } else {
            // Actualizar stock (restar cantidades)
            foreach ($cartItems as $item) {
                $stmt = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$item['cantidad'], $item['id']]);
            }
            
            // Confirmar transacción
            $pdo->commit();
            
            // Vaciar carrito
            clearCart();
            
            // Redirigir con mensaje de éxito
            header("Location: checkout.php?success=1");
            exit;
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = "Error al procesar la compra: " . $e->getMessage();
        $error = true;
    }
}

// Obtener datos del carrito para mostrar el resumen
$cartItems = getCartItems($pdo);
$total = getCartTotal($pdo);

// Si el carrito está vacío y no es una compra exitosa, redirigir
if (empty($cartItems) && !isset($_GET['success'])) {
    header("Location: cart.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Finalizar compra</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Confirmar pedido</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success">¡Compra realizada con éxito! Gracias. El stock ha sido actualizado.</div>
            <p><a href="index.php">Volver a la tienda</a></p>
        <?php else: ?>
            <?php if ($mensaje): ?>
                <div class="error"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>
            
            <h3>Resumen de tu carrito</h3>
            <ul>
                <?php foreach ($cartItems as $item): ?>
                    <li>
                        <?php echo $item['cantidad']; ?> x <?php echo htmlspecialchars($item['nombre']); ?> 
                        - $<?php echo number_format($item['subtotal'],2); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p><strong>Total: $<?php echo number_format($total,2); ?></strong></p>
            
            <form method="post">
                <button type="submit" class="btn">Confirmar pedido</button>
                <a href="cart.php">Volver al carrito</a>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>