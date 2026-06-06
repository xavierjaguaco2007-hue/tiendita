<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$mensaje = '';
$error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['xml_file'])) {
    $archivo = $_FILES['xml_file']['tmp_name'];
    $dtdPath = realpath('dtd/cart.dtd');

    if ($_FILES['xml_file']['error'] !== UPLOAD_ERR_OK) {
        $mensaje = "Error al subir el archivo.";
        $error = true;
    } else {
        $result = importCartFromXML($archivo, $pdo, $dtdPath);
        if ($result['success']) {
            $mensaje = $result['message'];
            $error = false;
        } else {
            $mensaje = $result['message'];
            $error = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Importar carrito desde XML</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Importar carrito (XML + DTD)</h1>
        <?php if ($mensaje): ?>
            <div class="<?php echo $error ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <label for="xml_file">Selecciona un archivo XML que cumpla con el DTD:</label>
            <input type="file" name="xml_file" id="xml_file" accept=".xml" required>
            <button type="submit" class="btn">Importar</button>
        </form>
        <p><strong>Formato esperado:</strong></p>
        <pre>&lt;?xml version="1.0"?&gt;
&lt;!DOCTYPE carrito SYSTEM "cart.dtd"&gt;
&lt;carrito&gt;
  &lt;item id="1"&gt;
    &lt;nombre&gt;Camiseta Deportiva&lt;/nombre&gt;
    &lt;precio&gt;19.99&lt;/precio&gt;
    &lt;cantidad&gt;2&lt;/cantidad&gt;
  &lt;/item&gt;
&lt;/carrito&gt;</pre>
        <p><a href="cart.php">Ver carrito actual</a> | <a href="index.php">Tienda</a></p>
    </div>
</body>
</html>