<?php
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/slike.php';

// Stran posameznega izdelka: izdelek.php?id=2 (skriti izdelki niso dostopni)
$stmt = $pdo->prepare(
    'SELECT p.id_product, p.id_category, p.name, p.description, p.packaging, p.price, p.stock, p.image, c.name AS category
     FROM products p JOIN categories c ON c.id_category = p.id_category
     WHERE p.id_product = ? AND p.active = 1'
);
$stmt->execute([(int) ($_GET['id'] ?? 0)]);
$p = $stmt->fetch();

if (!$p) {
    http_response_code(404);
    $pageTitle = 'Izdelek ne obstaja';
    require __DIR__ . '/includes/header.php';
    ?>
    <main class="empty-state">
        <h1>Izdelka ni</h1>
        <p>Izdelek ne obstaja ali trenutno ni na voljo.</p>
        <a href="<?= url('trgovina.php') ?>" class="btn btn-green">V trgovino</a>
    </main>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle  = $p['name'] . ' ' . $p['packaging'];
$activePage = 'trgovina';
require __DIR__ . '/includes/header.php';
?>
<main class="section">
    <a href="<?= url('trgovina.php?kategorija=' . $p['id_category']) ?>" class="back-link">← <?= e($p['category']) ?></a>
    <article class="product-detail">
        <div class="product-detail-img reveal"><?= product_image($p) ?></div>
        <div class="product-detail-info reveal reveal-2">
            <div class="product-cat"><?= e($p['category']) ?></div>
            <h1><?= e($p['name']) ?></h1>
            <div class="product-pack"><?= e($p['packaging']) ?></div>
            <div class="price price-lg"><?= money($p['price']) ?></div>
            <?php if ($p['description']): ?>
                <p class="product-desc"><?= nl2br(e($p['description'])) ?></p>
            <?php endif; ?>
            <div>
                <?php if ($p['stock'] > 0): ?>
                    <span class="badge badge-ok">Na zalogi</span>
                <?php else: ?>
                    <span class="badge badge-warn">Trenutno ni na zalogi</span>
                <?php endif; ?>
                <?php if ($inCart = $_SESSION['cart'][$p['id_product']] ?? 0): ?>
                    <a href="<?= url('kosarica.php') ?>" class="in-cart">V košarici: <?= $inCart ?> kos</a>
                <?php endif; ?>
            </div>
            <?= add_to_cart_form($p, true) ?>
        </div>
    </article>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
