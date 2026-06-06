<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (isset($_GET['add'])) {
    $id = (int)$_GET['add'];
    addToCart($id);
    header("Location: index.php?added=1");
    exit;
}

$stmt = $pdo->query("SELECT * FROM productos");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tienda Online - Carrito de Compras</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Productos disponibles</h1>
        <?php if (isset($_GET['added'])): ?>
            <div class="success">Producto agregado al carrito.</div>
        <?php endif; ?>
        <div class="product-list">
            <?php foreach ($productos as $prod): ?>
                <div class="product-card">
                    <h3><?php echo htmlspecialchars($prod['nombre']); ?></h3>
                    <p><?php echo htmlspecialchars($prod['descripcion']); ?></p>
                    <p class="price">$<?php echo number_format($prod['precio'], 2); ?></p>
                    <p>Stock: <?php echo $prod['stock']; ?></p>
                    <a href="?add=<?php echo $prod['id']; ?>" class="btn">Agregar al carrito</a>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="cart-link">
            <a href="cart.php" class="btn">Ver carrito</a>
            <a href="import_cart.php" class="btn">Importar carrito (XML)</a>
            <a href="export_cart.php" class="btn">Exportar carrito (XML)</a>
        </div>
    </div>
</body>
</html>