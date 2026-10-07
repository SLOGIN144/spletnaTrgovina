<?php
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/slike.php';

// Iskanje in filtri prek GET, da se rezultat da shraniti ali deliti kot povezavo:
// trgovina.php?q=olje&kategorija=1&razvrsti=cena-nar
$q        = is_string($_GET['q'] ?? null) ? trim(mb_substr($_GET['q'], 0, 100)) : '';
$selected = (int) ($_GET['kategorija'] ?? 0);
$onStock  = isset($_GET['na-zalogi']);

// Razvrščanje: uporabnik izbere samo ključ, SQL je vnaprej določen (v ORDER BY ne moremo uporabiti ?)
$sorts = [
    'privzeto'  => ['Privzeto', 'p.id_category, p.price'],
    'cena-nar'  => ['Cena: od najnižje', 'p.price, p.name'],
    'cena-pad'  => ['Cena: od najvišje', 'p.price DESC, p.name'],
    'ime'       => ['Ime: A–Ž', 'p.name, p.price'],
    'najnovejse' => ['Najnovejše', 'p.created_at DESC, p.id_product DESC'],
];
$sort = is_string($_GET['razvrsti'] ?? null) && isset($sorts[$_GET['razvrsti']]) ? $_GET['razvrsti'] : 'privzeto';

$where  = ['p.active = 1'];
$params = [];
if ($q !== '') {
    // % in _ v iskalnem nizu sta v LIKE posebna znaka, zato ju ubežimo
    $like     = '%' . addcslashes($q, '%_\\') . '%';
    $where[]  = '(p.name LIKE ? OR p.description LIKE ? OR p.packaging LIKE ? OR c.name LIKE ?)';
    array_push($params, $like, $like, $like, $like);
}
if ($selected) {
    $where[]  = 'p.id_category = ?';
    $params[] = $selected;
}
if ($onStock) {
    $where[] = 'p.stock > 0';
}

$stmt = $pdo->prepare(
    'SELECT p.id_product, p.id_category, p.name, p.packaging, p.price, p.stock, p.image, c.name AS category
     FROM products p JOIN categories c ON c.id_category = p.id_category
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY ' . $sorts[$sort][1]
);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query('SELECT id_category, name FROM categories ORDER BY id_category')->fetchAll();

// Povezava, ki ohrani trenutno iskanje in spremeni samo en parameter
function shop_url(array $change): string
{
    $params = array_filter(array_merge($_GET, $change), fn($v) => $v !== null && $v !== '' && $v !== 0);
    return url('trgovina.php' . ($params ? '?' . http_build_query($params) : ''));
}

$pageTitle  = $q !== '' ? 'Iskanje: ' . $q : 'Trgovina';
$activePage = 'trgovina';
require __DIR__ . '/includes/header.php';
?>
<main class="section">
    <div class="section-head">
        <h1>Trgovina</h1>
        <span class="product-pack" role="status"><?= plural(count($products), 'izdelek', 'izdelka', 'izdelki', 'izdelkov') ?></span>
    </div>

    <form method="get" action="<?= url('trgovina.php') ?>" class="shop-tools" role="search">
        <?php if ($selected): ?><input type="hidden" name="kategorija" value="<?= $selected ?>"><?php endif; ?>
        <div class="search-box">
            <label for="q" class="sr-only">Iskanje izdelkov</label>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20L16 16"/></svg>
            <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Iščite, npr. olje, čokolada, 250 g" maxlength="100">
        </div>
        <div class="field-inline">
            <label for="razvrsti">Razvrsti</label>
            <select id="razvrsti" name="razvrsti">
                <?php foreach ($sorts as $key => [$label]): ?>
                    <option value="<?= $key ?>" <?= $key === $sort ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <label class="check">
            <input type="checkbox" name="na-zalogi" value="1" <?= $onStock ? 'checked' : '' ?>> Samo na zalogi
        </label>
        <button type="submit" class="btn btn-black btn-sm">Išči</button>
    </form>

    <nav class="filters" aria-label="Kategorije">
        <a class="pill" href="<?= shop_url(['kategorija' => null]) ?>" <?= $selected === 0 ? 'aria-current="true"' : '' ?>>Vse</a>
        <?php foreach ($categories as $c): ?>
            <a class="pill" href="<?= shop_url(['kategorija' => (int) $c['id_category']]) ?>"
               <?= $selected === (int) $c['id_category'] ? 'aria-current="true"' : '' ?>><?= e($c['name']) ?></a>
        <?php endforeach; ?>
    </nav>

    <?php if ($products): ?>
        <div class="product-grid">
            <?php foreach ($products as $p): ?>
                <?php include __DIR__ . '/includes/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-results">
            <h2>Ni zadetkov</h2>
            <p><?= $q !== '' ? 'Za »' . e($q) . '« nismo našli nobenega izdelka.' : 'V tej izbiri ni izdelkov.' ?> Poskusite z drugo besedo ali izberite vse kategorije.</p>
            <a href="<?= url('trgovina.php') ?>" class="btn btn-outline btn-sm">Prikaži vse izdelke</a>
        </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
