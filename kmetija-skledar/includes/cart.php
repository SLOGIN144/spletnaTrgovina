<?php
// Košarica v seji: $_SESSION['cart'] = [id_product => količina]
// V seji hranimo samo ID-je in količine; ime, cena in zaloga se vedno preberejo iz baze,
// zato uporabnik ne more podtakniti svoje cene in košarica vedno pokaže trenutne cene.
require_once __DIR__ . '/auth.php';

const CART_MAX_QTY = 99; // največja količina enega izdelka v košarici

// Prebere izdelek iz baze, če je v prodaji (aktiven); sicer null
function cart_product(int $id): ?array
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT id_product, name, packaging, stock FROM products WHERE id_product = ? AND active = 1');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

// Doda količino k obstoječi. Vrne dejansko količino v košarici (omejeno z zalogo).
function cart_add(array $product, int $qty): int
{
    $current = $_SESSION['cart'][$product['id_product']] ?? 0;
    return cart_set($product, $current + $qty);
}

// Nastavi točno količino; 0 ali manj izdelek odstrani
function cart_set(array $product, int $qty): int
{
    $id  = (int) $product['id_product'];
    $qty = min($qty, (int) $product['stock'], CART_MAX_QTY);
    if ($qty <= 0) {
        unset($_SESSION['cart'][$id]);
        return 0;
    }
    $_SESSION['cart'][$id] = $qty;
    return $qty;
}

function cart_remove(int $id): void
{
    unset($_SESSION['cart'][$id]);
}

// Vsebina košarice z aktualnimi podatki iz baze in vmesnimi vsotami.
// Izdelke, ki jih ni več (izbrisani/skriti) ali jih je zmanjkalo, uskladi in o tem vrne opozorila.
function cart_contents(): array
{
    global $pdo;
    $cart  = $_SESSION['cart'] ?? [];
    $items = [];
    $notes = [];
    $total = 0.0;

    if ($cart) {
        $ids  = array_map('intval', array_keys($cart));
        $in   = implode(',', array_fill(0, count($ids), '?')); // ?,?,? – en vprašaj za vsak ID
        $stmt = $pdo->prepare(
            "SELECT p.id_product, p.id_category, p.name, p.packaging, p.price, p.stock, p.image, c.name AS category
             FROM products p JOIN categories c ON c.id_category = p.id_category
             WHERE p.active = 1 AND p.id_product IN ($in)"
        );
        $stmt->execute($ids);
        $found = array_column($stmt->fetchAll(), null, 'id_product');

        foreach ($cart as $id => $qty) {
            $p = $found[$id] ?? null;
            if (!$p) {
                unset($_SESSION['cart'][$id]);
                $notes[] = 'Izdelek, ki ste ga imeli v košarici, ni več v prodaji in je bil odstranjen.';
                continue;
            }
            if ($p['stock'] <= 0) {
                unset($_SESSION['cart'][$id]);
                $notes[] = $p['name'] . ' ' . $p['packaging'] . ' je pošel in je bil odstranjen iz košarice.';
                continue;
            }
            if ($qty > $p['stock']) {
                $qty = $_SESSION['cart'][$id] = (int) $p['stock'];
                $notes[] = 'Izdelka ' . $p['name'] . ' ' . $p['packaging'] . ' imamo na zalogi samo ' . $qty . ', zato smo količino zmanjšali.';
            }
            $p['quantity'] = $qty;
            $p['subtotal'] = $qty * (float) $p['price'];
            $total += $p['subtotal'];
            $items[] = $p;
        }
    }

    return ['items' => $items, 'total' => $total, 'count' => array_sum($_SESSION['cart'] ?? []), 'notes' => $notes];
}

// Kam se vrnemo po dodajanju v košarico: samo na stran znotraj projekta (ne na tujo stran)
function safe_back(?string $back, string $default): string
{
    return ($back && str_starts_with($back, BASE) && !str_starts_with($back, '//')) ? $back : url($default);
}

// Obrazec za dodajanje v košarico (kartica izdelka in stran izdelka)
function add_to_cart_form(array $p, bool $withQty = false): string
{
    if ((int) $p['stock'] <= 0) {
        return '<button type="button" class="btn btn-outline btn-sm" disabled>Ni na zalogi</button>';
    }
    // novalidate: količino preveri cart.js (slovensko sporočilo) in nato še strežnik (cart_set omeji na zalogo)
    $html = '<form method="post" action="' . url('kosarica.php') . '" class="add-form" novalidate>'
        . csrf_field()
        . '<input type="hidden" name="action" value="add">'
        . '<input type="hidden" name="id" value="' . (int) $p['id_product'] . '">'
        . '<input type="hidden" name="back" value="' . e($_SERVER['REQUEST_URI']) . '">';
    if ($withQty) {
        $max = min((int) $p['stock'], CART_MAX_QTY);
        $html .= '<label class="sr-only" for="qty-' . (int) $p['id_product'] . '">Količina</label>'
            . '<input type="number" class="qty-input" id="qty-' . (int) $p['id_product'] . '" name="qty" value="1" min="1" max="' . $max . '" required>';
    }
    return $html . '<button type="submit" class="btn btn-green' . ($withQty ? '' : ' btn-sm') . '">V košarico</button></form>';
}
