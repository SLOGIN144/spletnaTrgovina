<?php
// Preverjanje povezave: odpri http://localhost/kmetija-skledar/test-povezave.php
require 'config/db.php';

$izdelki = $pdo->query(
    'SELECT p.name, p.packaging, p.price, p.stock, c.name AS category
     FROM products p JOIN categories c ON c.id_category = p.id_category
     ORDER BY c.id_category, p.price'
)->fetchAll();

$stUporabnikov = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Test povezave – Kmetija Skledar</title>
    <style>
        body { font-family: sans-serif; background: #fff; color: #111; padding: 24px; }
        .ok { background: #C5D19A; padding: 8px 12px; border-radius: 6px; display: inline-block; }
        table { border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #E5E5E5; padding: 6px 12px; text-align: left; }
        th { background: #111; color: #fff; }
    </style>
</head>
<body>
    <p class="ok">Povezava z bazo <b>kmetija_skledar</b> deluje. Uporabnikov: <?= $stUporabnikov ?></p>
    <table>
        <tr><th>Kategorija</th><th>Izdelek</th><th>Pakiranje</th><th>Cena</th><th>Zaloga</th></tr>
        <?php foreach ($izdelki as $i): ?>
        <tr>
            <td><?= htmlspecialchars($i['category']) ?></td>
            <td><?= htmlspecialchars($i['name']) ?></td>
            <td><?= htmlspecialchars($i['packaging']) ?></td>
            <td><?= number_format($i['price'], 2, ',', '.') ?> €</td>
            <td><?= $i['stock'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
