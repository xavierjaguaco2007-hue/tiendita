<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update'])) {
        foreach ($_POST['cantidad'] as $id => $cantidad) {
            updateCartItem($id, (int)$cantidad);
        }
        header("Location: cart.php?updated=1");
        exit;
    }
    if (isset($_POST['remove'])) {
        $id = (int)$_POST['remove_id'];
        removeFromCart($id);
        header("Location: cart.php?removed=1");
        exit;
    }
    if (isset($_POST['clear'])) {
        clearCart();
        header("Location: cart.php?cleared=1");
        exit;
    }
}

$cartItems = getCartItems($pdo);
$total = getCartTotal($pdo);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Carrito</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Carrito de compras</h1>
        <?php if (isset($_GET['updated'])): ?>
            <div class="success">Cantidades actualizadas.</div>
        <?php elseif (isset($_GET['removed'])): ?>
            <div class="success">Producto eliminado.</div>
        <?php elseif (isset($_GET['cleared'])): ?>
            <div class="success">Carrito vaciado.</div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
            <p>El carrito está vacío. <a href="index.php">Ir a comprar</a></p>
        <?php else: ?>
            <form method="post">
                <table class="cart-table">
                    <thead>
                        <tr><th>Producto</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                            <td>$<?php echo number_format($item['precio'],2); ?></td>
                            <td>
                                <input type="number" name="cantidad[<?php echo $item['id']; ?>]" value="<?php echo $item['cantidad']; ?>" min="0" step="1">
                            </td>
                            <td>$<?php echo number_format($item['subtotal'],2); ?></td>
                            <td>
                                <button type="submit" name="remove" value="1" class="btn-remove" onclick="this.form.remove_id.value=<?php echo $item['id']; ?>">Eliminar</button>
                                <input type="hidden" name="remove_id" value="">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="3"><strong>Total</strong></td><td><strong>$<?php echo number_format($total,2); ?></strong></td><td></td></tr>
                    </tfoot>
                </table>
                <div class="cart-actions">
                    <button type="submit" name="update" class="btn">Actualizar cantidades</button>
                    <button type="submit" name="clear" class="btn-danger">Vaciar carrito</button>
                    <a href="checkout.php" class="btn">Finalizar compra</a>
                </div>
            </form>
        <?php endif; ?>
        <p><a href="index.php">← Seguir comprando</a></p>
    </div>
</body>
</html>