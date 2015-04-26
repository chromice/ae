<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?></title>
<?php if (!empty($alert)): ?>
    <script>alert(document.querySelector('<?= $alert ?>').textContent);</script>
<?php endif ?>
</head>
<body>
    <?= $content ?>
</body>
</html>