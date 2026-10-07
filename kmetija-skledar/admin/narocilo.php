<?php
require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);

// Sprememba statusa ali brisanje naročila (POST + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!csrf_valid()) {
        set_flash('error', 'Seja je potekla. Poskusite znova.');
    } elseif ($action === 'status' && in_array($_POST['status'] ?? '', ORDER_STATUSES, true)) {
        $pdo->prepare('UPDATE orders SET status = ? WHERE id_order = ?')->execute([$_POST['status'], $id]);
        set_flash('success', 'Status naročila #' . $id . ' je zdaj »' . $_POST['status'] . '«.');
    } elseif ($action === 'delete') {
        // Postavke se izbrišejo samodejno (ON DELETE CASCADE v order_items)
        $pdo->prepare('DELETE FROM orders WHERE id_order = ?')->execute([$id]);
        set_flash('success', 'Naročilo #' . $id . ' je izbrisano.');
        redirect('admin/narocila.php');
    }
    redirect('admin/narocilo.php?id=' . $id);
}

$stmt = $pdo->prepare(
    'SELECT o.*, u.first_name, u.last_name, u.email, u.phone
     FROM orders o JOIN users u ON u.id_user = o.id_user WHERE o.id_order = ?'
);
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) {
    set_flash('error', 'Naročilo ne obstaja.');
    redirect('admin/narocila.php');
}

$stmt = $pdo->prepare(
    'SELECT p.name, p.packaging, i.quantity, i.price_at_order
     FROM order_items i JOIN products p ON p.id_product = i.id_product WHERE i.id_order = ?'
);
$stmt->execute([$id]);
$items = $stmt->fetchAll();

admin_start('Naročilo #' . $id, 'narocila');
?>
        <div>
            <a href="<?= url('admin/narocila.php') ?>" class="back-link">← Vsa naročila</a>
            <h1>Naročilo #<?= $id ?></h1>
            <div class="admin-sub">Oddano <?= date('j. n. Y \o\b H:i', strtotime($order['created_at'])) ?></div>
        </div>

        <div class="admin-grid">
            <section class="admin-card">
                <h2>Kupec</h2>
                <dl class="details">
                    <dt>Ime in priimek</dt><dd><?= e($order['first_name'] . ' ' . $order['last_name']) ?></dd>
                    <dt>E-pošta</dt><dd><?= e($order['email']) ?></dd>
                    <dt>Telefon</dt><dd><?= e($order['phone'] ?: '–') ?></dd>
                    <dt>Naslov za dostavo</dt><dd><?= e($order['shipping_address']) ?></dd>
                    <dt>Plačilo</dt><dd><?= e($order['payment_method']) ?></dd>
                </dl>
            </section>

            <section class="admin-card">
                <h2>Status</h2>
                <form method="post" class="form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="status">
                    <div class="field">
                        <label for="status">Status naročila</label>
                        <select id="status" name="status">
                            <?php foreach (ORDER_STATUSES as $s): ?>
                                <option value="<?= e($s) ?>" <?= $s === $order['status'] ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-green btn-sm">Shrani status</button>
                    </div>
                </form>
                <?= action_button('delete', $id, 'Izbriši naročilo', 'btn btn-danger btn-xs', 'Res želite izbrisati naročilo #' . $id . '?') ?>
            </section>
        </div>

        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Izdelek</th><th>Pakiranje</th><th>Količina</th><th>Cena ob naročilu</th><th>Skupaj</th></tr></thead>
                <tbody>
                <?php foreach ($items as $i): ?>
                    <tr>
                        <td><strong><?= e($i['name']) ?></strong></td>
                        <td><?= e($i['packaging']) ?></td>
                        <td><?= (int) $i['quantity'] ?></td>
                        <td><?= money($i['price_at_order']) ?></td>
                        <td><strong><?= money($i['quantity'] * $i['price_at_order']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                    <tr class="row-total"><td colspan="4">Skupaj za plačilo</td><td><strong><?= money($order['total']) ?></strong></td></tr>
                </tbody>
            </table>
        </div>
<?php admin_end(); ?>
