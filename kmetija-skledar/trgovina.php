<?php
$pageTitle  = 'Trgovina';
$activePage = 'trgovina';
require __DIR__ . '/includes/header.php';

$categories = $pdo->query('SELECT id_category, name FROM categories ORDER BY id_category')->fetchAll();
$selected   = (int) ($_GET['kategorija'] ?? 0);

$sql = 'SELECT p.id_product, p.id_category, p.name, p.packaging, p.price, c.name AS category
        FROM products p JOIN categories c ON c.id_category = p.id_category
        WHERE p.active = 1' . ($selected ? ' AND p.id_category = ?' : '') . '
        ORDER BY p.id_category, p.price';
$stmt = $pdo->prepare($sql);
$stmt->execute($selected ? [$selected] : []);
$products = $stmt->fetchAll();
?>
<main class="section">
    <div class="section-head">
        <h1>Trgovina</h1>
        <span class="product-pack"><?= count($products) ?> izdelkov</span>
    </div>

    <nav class="filters" aria-label="Kategorije">
        <a class="pill" href="<?= url('trgovina.php') ?>" <?= $selected === 0 ? 'aria-current="true"' : '' ?>>Vse</a>
        <?php foreach ($categories as $c): ?>
            <a class="pill" href="<?= url('trgovina.php?kategorija=' . $c['id_category']) ?>"
               <?= $selected === (int) $c['id_category'] ? 'aria-current="true"' : '' ?>><?= e($c['name']) ?></a>
        <?php endforeach; ?>
    </nav>

    <div class="product-grid">
        <?php foreach ($products as $p): ?>
            <?php include __DIR__ . '/includes/product_card.php'; ?>
        <?php endforeach; ?>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
