<?php
require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();

// Filter po statusu: ?status=poslano (samo vrednosti iz seznama, vse ostalo pomeni "vsa")
$status = in_array($_GET['status'] ?? '', ORDER_STATUSES, true) ? $_GET['status'] : '';

$sql = 'SELECT o.id_order, o.created_at, o.status, o.total, o.payment_method, u.first_name, u.last_name,
               (SELECT SUM(quantity) FROM order_items i WHERE i.id_order = o.id_order) AS item_count
        FROM orders o JOIN users u ON u.id_user = o.id_user';
$stmt = $pdo->prepare($sql . ($status ? ' WHERE o.status = ?' : '') . ' ORDER BY o.created_at DESC');
$stmt->execute($status ? [$status] : []);
$orders = $stmt->fetchAll();

$badge = ['oddano' => 'badge-warn', 'v obdelavi' => 'badge-warn', 'poslano' => 'badge-ok', 'zaključeno' => 'badge-muted'];

admin_start('Naročila', 'narocila');
?>
        <div>
            <h1>Naročila</h1>
            <div class="admin-sub"><?= count($orders) ?> naročil<?= $status ? ' s statusom »' . e($status) . '«' : '' ?></div>
        </div>

        <nav class="filter-chips" aria-label="Filter po statusu">
            <a href="<?= url('admin/narocila.php') ?>" <?= $status === '' ? 'aria-current="page"' : '' ?>>Vsa</a>
            <?php foreach (ORDER_STATUSES as $s): ?>
                <a href="<?= url('admin/narocila.php?status=' . urlencode($s)) ?>" <?= $status === $s ? 'aria-current="page"' : '' ?>><?= e(ucfirst($s)) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Št.</th><th>Datum</th><th>Kupec</th><th>Izdelkov</th><th>Znesek</th><th>Plačilo</th><th>Status</th><th><span class="sr-only">Dejanja</span></th></tr></thead>
                <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><strong>#<?= (int) $o['id_order'] ?></strong></td>
                        <td><?= date('j. n. Y H:i', strtotime($o['created_at'])) ?></td>
                        <td><?= e($o['first_name'] . ' ' . $o['last_name']) ?></td>
                        <td><?= (int) $o['item_count'] ?></td>
                        <td><strong><?= money($o['total']) ?></strong></td>
                        <td><?= e($o['payment_method']) ?></td>
                        <td><span class="badge <?= $badge[$o['status']] ?>"><?= e(ucfirst($o['status'])) ?></span></td>
                        <td><a href="<?= url('admin/narocilo.php?id=' . $o['id_order']) ?>" class="btn btn-black btn-xs">Podrobnosti</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$orders): ?>
                    <tr><td colspan="8" class="empty-row">Ni naročil. Pojavila se bodo, ko bodo kupci oddali naročila prek blagajne.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
<?php admin_end(); ?>
