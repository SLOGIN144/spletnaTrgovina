<?php
require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/slike.php';
require_admin(); // samo uporabnik z vlogo admin

// Dejanja nad izdelkom (POST + CSRF): skrij/prikaži ali izbriši
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);

    if (!csrf_valid()) {
        set_flash('error', 'Seja je potekla. Poskusite znova.');
    } elseif (($_POST['action'] ?? '') === 'toggle') {
        $pdo->prepare('UPDATE products SET active = 1 - active WHERE id_product = ?')->execute([$id]);
        set_flash('success', 'Vidnost izdelka v trgovini je spremenjena.');
    } elseif (($_POST['action'] ?? '') === 'delete') {
        try {
            $stmt = $pdo->prepare('SELECT image FROM products WHERE id_product = ?');
            $stmt->execute([$id]);
            $image = $stmt->fetchColumn();

            $pdo->prepare('DELETE FROM products WHERE id_product = ?')->execute([$id]);
            delete_upload($image ?: null); // datoteko izbrišemo šele, ko je vrstica res izbrisana
            set_flash('success', 'Izdelek je izbrisan.');
        } catch (PDOException $ex) {
            // Tuji ključ: izdelek je v starih naročilih, zato ga ne smemo izbrisati
            if ($ex->getCode() !== '23000') {
                throw $ex;
            }
            set_flash('error', 'Izdelka ni mogoče izbrisati, ker je v naročilih. Namesto tega ga skrijte.');
        }
    }
    redirect('admin/index.php');
}

$products = $pdo->query(
    'SELECT p.id_product, p.id_category, p.name, p.image, p.packaging, p.price, p.stock, p.active, p.featured, c.name AS category
     FROM products p JOIN categories c ON c.id_category = p.id_category
     ORDER BY p.id_category, p.price'
)->fetchAll();
$userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$user = current_user();

admin_start('Izdelki', 'izdelki');
?>
        <div class="admin-head">
            <div>
                <h1>Izdelki</h1>
                <div class="admin-sub"><?= count($products) ?> izdelkov · <?= $userCount ?> registriranih uporabnikov · prijavljeni kot <?= e($user['first_name'] . ' ' . $user['last_name']) ?></div>
            </div>
            <a href="<?= url('admin/izdelek.php') ?>" class="btn btn-green btn-sm">Dodaj izdelek</a>
        </div>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr><th><span class="sr-only">Slika</span></th><th>Izdelek</th><th>Kategorija</th><th>Pakiranje</th><th>Cena</th><th>Zaloga</th><th>Status</th><th><span class="sr-only">Dejanja</span></th></tr>
                </thead>
                <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td class="cell-thumb"><div class="thumb"><?= product_image($p) ?></div></td>
                        <td><strong><?= e($p['name']) ?></strong><?php if ($p['featured']): ?> <span class="badge badge-dark">Domača stran</span><?php endif; ?></td>
                        <td><?= e($p['category']) ?></td>
                        <td><?= e($p['packaging']) ?></td>
                        <td><strong><?= money($p['price']) ?></strong></td>
                        <td><?= (int) $p['stock'] ?></td>
                        <td>
                            <?php if (!$p['active']): ?><span class="badge badge-muted">Skrit</span>
                            <?php elseif ($p['stock'] < 20): ?><span class="badge badge-warn">Nizka zaloga</span>
                            <?php else: ?><span class="badge badge-ok">Aktiven</span><?php endif; ?>
                        </td>
                        <td>
                            <div class="row-actions">
                                <a href="<?= url('admin/izdelek.php?id=' . $p['id_product']) ?>" class="btn btn-black btn-xs">Uredi</a>
                                <?= action_button('toggle', (int) $p['id_product'], $p['active'] ? 'Skrij' : 'Prikaži') ?>
                                <?= action_button('delete', (int) $p['id_product'], 'Izbriši', 'btn btn-danger btn-xs',
                                    'Res želite izbrisati izdelek "' . $p['name'] . ' ' . $p['packaging'] . '"?') ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$products): ?>
                    <tr><td colspan="8" class="empty-row">Ni še nobenega izdelka.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
<?php admin_end(); ?>
