<?php
$pageTitle  = 'Domov';
$activePage = 'domov';
require __DIR__ . '/includes/header.php';

// Izpostavljeni izdelki iz baze
$featured = $pdo->query(
    'SELECT p.id_product, p.id_category, p.name, p.packaging, p.price, c.name AS category
     FROM products p JOIN categories c ON c.id_category = p.id_category
     WHERE p.active = 1 AND p.id_product IN (2, 3, 5, 7)
     ORDER BY p.id_category, p.price'
)->fetchAll();
?>
<main>
    <section class="hero">
        <div class="hero-text reveal">
            <div class="eyebrow">Družinska kmetija Stanka Skledarja</div>
            <h1>Olje, kot ga delamo doma.</h1>
            <p>Hladno stiskano bučno olje in bučnice z naše kmetije – naročite na spletu, dostavimo po vsej Sloveniji.</p>
            <div class="hero-actions">
                <a href="<?= url('trgovina.php') ?>" class="btn btn-green">V trgovino
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M5 12H19M13 6L19 12L13 18"/></svg>
                </a>
                <a href="#o-kmetiji" class="btn btn-outline">O kmetiji</a>
            </div>
        </div>
        <div class="hero-visual reveal reveal-2">
            <svg width="150" height="320" viewBox="0 0 84 180" aria-hidden="true"><rect x="33" y="2" width="18" height="16" rx="3" fill="#C5D19A"/><path d="M35 18V40C35 48 10 54 10 78V168C10 173 13 176 18 176H66C71 176 74 173 74 168V78C74 54 49 48 49 40V18Z" fill="#fff"/><rect x="18" y="100" width="48" height="48" rx="4" fill="#C5D19A"/><path d="M42 112C42 112 32 124 32 130C32 135.5 36.5 139 42 139C47.5 139 52 135.5 52 130C52 124 42 112 42 112Z" fill="#111"/></svg>
            <span class="caption">[fotografija: bučno olje ali bučno polje]</span>
        </div>
    </section>

    <section class="features" aria-label="Zakaj pri nas">
        <div class="feature">
            <span class="feature-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3C12 3 5 11 5 15.5C5 19.1 8.1 21 12 21C15.9 21 19 19.1 19 15.5C19 11 12 3 12 3Z"/></svg></span>
            <div><strong>Hladno stiskano</strong><span>Ohrani okus in barvo buče</span></div>
        </div>
        <div class="feature">
            <span class="feature-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11L12 4L21 11"/><path d="M5 10V20H19V10"/><path d="M10 20V14H14V20"/></svg></span>
            <div><strong>Z domače kmetije</strong><span>Neposredno od pridelovalca</span></div>
        </div>
        <div class="feature">
            <span class="feature-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 6H15V17H2Z"/><path d="M15 10H19L22 13V17H15"/><circle cx="6" cy="19" r="2"/><circle cx="18" cy="19" r="2"/></svg></span>
            <div><strong>Dostava po Sloveniji</strong><span>Pošljemo na vaš naslov</span></div>
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <h2>Iz naše ponudbe</h2>
            <a href="<?= url('trgovina.php') ?>">Vsi izdelki</a>
        </div>
        <div class="product-grid">
            <?php foreach ($featured as $p): ?>
                <?php include __DIR__ . '/includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="o-kmetiji" class="about">
        <div class="about-img">[fotografija kmetije ali babice in dedka pri stiskanju olja]</div>
        <div class="about-text">
            <div class="eyebrow">O kmetiji</div>
            <h2>Kmetija Skledar</h2>
            <p>[Kratka zgodba kmetije – od kdaj pridelujete buče, kako nastane olje, kdo dela na kmetiji. Dve do tri povedi.]</p>
            <a href="<?= url('trgovina.php') ?>" class="btn btn-black">Poglej izdelke</a>
        </div>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
