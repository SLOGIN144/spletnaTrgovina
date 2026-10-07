<?php
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/slike.php';

// Zahtevo je poslal JavaScript (fetch v cart.js)? Takrat odgovorimo z JSON namesto s preusmeritvijo.
$isFetch = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';

// Zaključi dejanje: JS dobi JSON s trenutnim stanjem košarice, navaden obrazec pa sporočilo in preusmeritev
function cart_respond(bool $ok, string $message, string $location): void
{
    global $isFetch;
    if ($isFetch) {
        $cart = cart_contents();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok'      => $ok,
            'message' => $message,
            'count'   => $cart['count'],
            'total'   => money($cart['total']),
            'items'   => array_map(fn($p) => [
                'id'       => (int) $p['id_product'],
                'quantity' => $p['quantity'],
                'subtotal' => money($p['subtotal']),
            ], $cart['items']),
            'notes'   => $cart['notes'],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    set_flash($ok ? 'success' : 'error', $message);
    header('Location: ' . $location);
    exit;
}

// Dejanja nad košarico (vedno POST + CSRF), nato preusmeritev, da osvežitev strani ne ponovi dejanja
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);
    $qty    = (int) ($_POST['qty'] ?? 1);

    if (!csrf_valid()) {
        cart_respond(false, 'Seja je potekla. Osvežite stran in poskusite znova.', url('kosarica.php'));
    }

    if ($action === 'add') {
        $back    = safe_back($_POST['back'] ?? null, 'trgovina.php');
        $product = cart_product($id);
        if (!$product || $product['stock'] <= 0) {
            cart_respond(false, 'Izdelek trenutno ni na voljo.', $back);
        }
        $before = $_SESSION['cart'][$id] ?? 0;
        $now    = cart_add($product, max(1, $qty));
        if ($now === $before) {
            cart_respond(false, 'V košarici že imate vse, kar imamo na zalogi (' . $now . ' kos).', $back);
        }
        if ($now < $before + max(1, $qty)) {
            cart_respond(true, 'Dodano v košarico. Na zalogi je samo ' . $now . ' kos, zato imate v košarici ' . $now . '.', $back);
        }
        cart_respond(true, $product['name'] . ' ' . $product['packaging'] . ' je v košarici.', $back);
    }

    if ($action === 'update') {
        // Več vrstic naenkrat: qty[ID] = količina (JS pošlje samo vrstico, ki se je spremenila)
        foreach ((array) ($_POST['qty'] ?? []) as $pid => $q) {
            $product = cart_product((int) $pid);
            $product ? cart_set($product, (int) $q) : cart_remove((int) $pid);
        }
        cart_respond(true, 'Košarica je posodobljena.', url('kosarica.php'));
    }
    if ($action === 'remove') {
        cart_remove($id);
        cart_respond(true, 'Izdelek je odstranjen iz košarice.', url('kosarica.php'));
    }
    if ($action === 'clear') {
        unset($_SESSION['cart']);
        cart_respond(true, 'Košarica je izpraznjena.', url('kosarica.php'));
    }
    redirect('kosarica.php');
}

$cart       = cart_contents(); // pred glavo, da števec v glavi upošteva usklajeno košarico
$pageTitle  = 'Košarica';
$activePage = 'kosarica';
require __DIR__ . '/includes/header.php';
?>
<?php if (!$cart['items']): ?>
<main class="empty-state">
    <?php foreach ($cart['notes'] as $note): ?>
        <div class="alert alert-error" role="status"><?= e($note) ?></div>
    <?php endforeach; ?>
    <h1>Košarica je prazna</h1>
    <p>Dodajte izdelke iz trgovine.</p>
    <a href="<?= url('trgovina.php') ?>" class="btn btn-green">V trgovino</a>
</main>
<?php else: ?>
<main class="section cart">
    <div class="section-head">
        <h1>Košarica</h1>
        <span class="product-pack" id="cart-count"><?= plural($cart['count'], 'izdelek', 'izdelka', 'izdelki', 'izdelkov') ?></span>
    </div>

    <?php foreach ($cart['notes'] as $note): ?>
        <div class="alert alert-error" role="status"><?= e($note) ?></div>
    <?php endforeach; ?>

    <div class="cart-layout">
        <form method="post" class="cart-items" id="cart-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update">
            <div class="table-wrap">
                <table class="data cart-table">
                    <thead>
                        <tr><th colspan="2">Izdelek</th><th>Cena</th><th>Količina</th><th>Skupaj</th><th><span class="sr-only">Odstrani</span></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cart['items'] as $p): ?>
                        <tr data-id="<?= (int) $p['id_product'] ?>">
                            <td class="cell-thumb"><a href="<?= url('izdelek.php?id=' . $p['id_product']) ?>" class="thumb" tabindex="-1" aria-hidden="true"><?= product_image($p) ?></a></td>
                            <td>
                                <a href="<?= url('izdelek.php?id=' . $p['id_product']) ?>" class="product-name"><?= e($p['name']) ?></a>
                                <div class="product-pack"><?= e($p['packaging']) ?></div>
                            </td>
                            <td data-label="Cena"><?= money($p['price']) ?></td>
                            <td data-label="Količina">
                                <label class="sr-only" for="qty-<?= (int) $p['id_product'] ?>">Količina za <?= e($p['name'] . ' ' . $p['packaging']) ?></label>
                                <div class="qty-stepper">
                                    <button type="button" class="step js-only" data-step="-1" aria-label="Manj">−</button>
                                    <input type="number" class="qty-input" id="qty-<?= (int) $p['id_product'] ?>"
                                       name="qty[<?= (int) $p['id_product'] ?>]" value="<?= (int) $p['quantity'] ?>"
                                       min="0" max="<?= min((int) $p['stock'], CART_MAX_QTY) ?>">
                                    <button type="button" class="step js-only" data-step="1" aria-label="Več">+</button>
                                </div>
                            </td>
                            <td data-label="Skupaj"><strong data-subtotal><?= money($p['subtotal']) ?></strong></td>
                            <td>
                                <button type="submit" form="remove-<?= (int) $p['id_product'] ?>" class="icon-btn" aria-label="Odstrani <?= e($p['name'] . ' ' . $p['packaging']) ?>">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6L18 18M18 6L6 18"/></svg>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="cart-actions">
                <button type="submit" class="btn btn-outline btn-sm no-js-only">Posodobi košarico</button>
                <button type="submit" form="clear-cart" class="link-btn">Izprazni košarico</button>
            </div>
        </form>

        <aside class="cart-summary" aria-label="Povzetek">
            <h2>Povzetek</h2>
            <dl>
                <?php foreach ($cart['items'] as $p): ?>
                    <dt data-id="<?= (int) $p['id_product'] ?>"><span data-qty><?= (int) $p['quantity'] ?></span> × <?= e($p['name'] . ' ' . $p['packaging']) ?></dt><dd data-id="<?= (int) $p['id_product'] ?>"><?= money($p['subtotal']) ?></dd>
                <?php endforeach; ?>
            </dl>
            <div class="cart-total"><span>Skupaj</span><strong id="cart-total" aria-live="polite"><?= money($cart['total']) ?></strong></div>
            <p class="card-note">Naslov za dostavo in način plačila izberete na blagajni.</p>
            <button type="button" class="btn btn-green btn-block" disabled title="Blagajna pride v naslednji nalogi">Na blagajno</button>
            <a href="<?= url('trgovina.php') ?>" class="btn btn-outline btn-block">Nadaljuj z nakupovanjem</a>
        </aside>
    </div>

    <?php // Ločena obrazca (gnezdenje obrazcev v HTML ni dovoljeno), gumbi jih sprožijo z atributom form="…" ?>
    <?php foreach ($cart['items'] as $p): ?>
        <form method="post" id="remove-<?= (int) $p['id_product'] ?>" hidden>
            <?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="id" value="<?= (int) $p['id_product'] ?>">
        </form>
    <?php endforeach; ?>
    <form method="post" id="clear-cart" data-confirm="Res želite izprazniti košarico?" hidden>
        <?= csrf_field() ?><input type="hidden" name="action" value="clear">
    </form>
</main>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
