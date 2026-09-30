<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ikone.php';
require_admin(); // samo uporabnik z vlogo admin

$products = $pdo->query(
    'SELECT p.name, p.packaging, p.price, p.stock, p.active, c.name AS category
     FROM products p JOIN categories c ON c.id_category = p.id_category
     ORDER BY p.id_category, p.price'
)->fetchAll();
$userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$user  = current_user();
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administracija – Kmetija Skledar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<div class="admin">
    <aside class="admin-side">
        <a href="<?= url('index.php') ?>" class="logo"><?= logo_mark() ?><span>Kmetija Skledar</span></a>
        <div class="admin-label">Administracija</div>
        <nav class="admin-nav" aria-label="Administracija">
            <a href="<?= url('admin/index.php') ?>" aria-current="page">Izdelki</a>
        </nav>
        <div class="admin-bottom">
            <a href="<?= url('index.php') ?>">Nazaj v trgovino</a>
            <a href="<?= url('odjava.php') ?>">Odjava</a>
        </div>
    </aside>

    <main class="admin-main">
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status"><?= e($flash['message']) ?></div>
        <?php endif; ?>
        <div>
            <h1>Izdelki</h1>
            <div class="admin-sub"><?= count($products) ?> izdelkov · <?= $userCount ?> registriranih uporabnikov · prijavljeni kot <?= e($user['first_name'] . ' ' . $user['last_name']) ?></div>
        </div>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr><th>Izdelek</th><th>Kategorija</th><th>Pakiranje</th><th>Cena</th><th>Zaloga</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td><strong><?= e($p['name']) ?></strong></td>
                        <td><?= e($p['category']) ?></td>
                        <td><?= e($p['packaging']) ?></td>
                        <td><strong><?= number_format((float) $p['price'], 2, ',', '.') ?> €</strong></td>
                        <td><?= (int) $p['stock'] ?></td>
                        <td>
                            <?php if (!$p['active']): ?><span class="badge badge-warn">Skrit</span>
                            <?php elseif ($p['stock'] < 20): ?><span class="badge badge-warn">Nizka zaloga</span>
                            <?php else: ?><span class="badge badge-ok">Aktiven</span><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
