// views/emails/welcome.php
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
</head>

<body>
    <h1>Hola
        <?= htmlspecialchars($nombre) ?>
    </h1>
    <p>Haz clic para verificar tu cuenta:</p>
    <a href="<?= htmlspecialchars($enlace) ?>">Verificar</a>

    <?php if (!empty($codigo_promocional)): ?>
        <p>Tu código: <strong>
                <?= htmlspecialchars($codigo_promocional) ?>
            </strong></p>
    <?php endif; ?>
</body>

</html>