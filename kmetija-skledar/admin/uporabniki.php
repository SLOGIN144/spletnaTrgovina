<?php
require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();

$me = current_user()['id'];

// Brisanje uporabnika (POST + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);

    if (!csrf_valid()) {
        set_flash('error', 'Seja je potekla. Poskusite znova.');
    } elseif (($_POST['action'] ?? '') === 'delete') {
        if ($id === $me) {
            set_flash('error', 'Svojega računa ne morete izbrisati.'); // sicer bi lahko ostali brez admina
        } else {
            try {
                $pdo->prepare('DELETE FROM users WHERE id_user = ?')->execute([$id]);
                set_flash('success', 'Uporabnik je izbrisan.');
            } catch (PDOException $ex) {
                if ($ex->getCode() !== '23000') {
                    throw $ex;
                }
                set_flash('error', 'Uporabnika ni mogoče izbrisati, ker ima naročila.');
            }
        }
    }
    redirect('admin/uporabniki.php');
}

$users = $pdo->query(
    'SELECT u.id_user, u.first_name, u.last_name, u.email, u.city, u.created_at, r.name AS role,
            (SELECT COUNT(*) FROM orders o WHERE o.id_user = u.id_user) AS order_count
     FROM users u JOIN roles r ON r.id_role = u.id_role
     ORDER BY r.id_role DESC, u.last_name, u.first_name'
)->fetchAll();

admin_start('Uporabniki', 'uporabniki');
?>
        <div class="admin-head">
            <div>
                <h1>Uporabniki</h1>
                <div class="admin-sub"><?= count($users) ?> uporabnikov</div>
            </div>
            <a href="<?= url('admin/uporabnik.php') ?>" class="btn btn-green btn-sm">Dodaj uporabnika</a>
        </div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Ime in priimek</th><th>E-pošta</th><th>Kraj</th><th>Vloga</th><th>Naročil</th><th>Registriran</th><th><span class="sr-only">Dejanja</span></th></tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><strong><?= e($u['first_name'] . ' ' . $u['last_name']) ?></strong><?= (int) $u['id_user'] === $me ? ' <span class="cell-muted">(vi)</span>' : '' ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><?= e($u['city']) ?></td>
                        <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-dark' : 'badge-muted' ?>"><?= $u['role'] === 'admin' ? 'Administrator' : 'Kupec' ?></span></td>
                        <td><?= (int) $u['order_count'] ?></td>
                        <td><?= date('j. n. Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <div class="row-actions">
                                <a href="<?= url('admin/uporabnik.php?id=' . $u['id_user']) ?>" class="btn btn-black btn-xs">Uredi</a>
                                <?php if ((int) $u['id_user'] !== $me): ?>
                                    <?= action_button('delete', (int) $u['id_user'], 'Izbriši', 'btn btn-danger btn-xs',
                                        'Res želite izbrisati uporabnika ' . $u['first_name'] . ' ' . $u['last_name'] . '?') ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
<?php admin_end(); ?>
