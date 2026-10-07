<?php
require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();

// Seznam in obrazec na isti strani: ?uredi=ID odpre kategorijo v obrazcu, sicer obrazec doda novo
$editId = (int) ($_GET['uredi'] ?? 0);
$cat    = ['name' => '', 'description' => ''];
$errors = [];

if ($editId) {
    $stmt = $pdo->prepare('SELECT name, description FROM categories WHERE id_category = ?');
    $stmt->execute([$editId]);
    $cat = $stmt->fetch();
    if (!$cat) {
        redirect('admin/kategorije.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if (!csrf_valid()) {
        set_flash('error', 'Seja je potekla. Poskusite znova.');
        redirect('admin/kategorije.php');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        try {
            $pdo->prepare('DELETE FROM categories WHERE id_category = ?')->execute([$id]);
            set_flash('success', 'Kategorija je izbrisana.');
        } catch (PDOException $ex) {
            if ($ex->getCode() !== '23000') {
                throw $ex;
            }
            set_flash('error', 'Kategorije ni mogoče izbrisati, ker vsebuje izdelke. Izdelke najprej premaknite ali izbrišite.');
        }
        redirect('admin/kategorije.php');
    }

    $cat = ['name' => trim($_POST['name'] ?? ''), 'description' => trim($_POST['description'] ?? '')];
    if (mb_strlen($cat['name']) < 2 || mb_strlen($cat['name']) > 50) {
        $errors['name'] = 'Ime naj ima 2–50 znakov.';
    }
    if (mb_strlen($cat['description']) > 1000) {
        $errors['description'] = 'Opis je predolg (največ 1000 znakov).';
    }

    if (!$errors) {
        if ($editId) {
            $pdo->prepare('UPDATE categories SET name = ?, description = ? WHERE id_category = ?')
                ->execute([$cat['name'], $cat['description'] ?: null, $editId]);
            set_flash('success', 'Kategorija "' . $cat['name'] . '" je posodobljena.');
        } else {
            $pdo->prepare('INSERT INTO categories (name, description) VALUES (?, ?)')
                ->execute([$cat['name'], $cat['description'] ?: null]);
            set_flash('success', 'Kategorija "' . $cat['name'] . '" je dodana.');
        }
        redirect('admin/kategorije.php');
    }
}

$categories = $pdo->query(
    'SELECT c.id_category, c.name, c.description, COUNT(p.id_product) AS product_count
     FROM categories c LEFT JOIN products p ON p.id_category = c.id_category
     GROUP BY c.id_category ORDER BY c.id_category'
)->fetchAll();

admin_start('Kategorije', 'kategorije');
?>
        <div>
            <h1>Kategorije</h1>
            <div class="admin-sub"><?= count($categories) ?> kategorij</div>
        </div>

        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Ime</th><th>Opis</th><th>Izdelkov</th><th><span class="sr-only">Dejanja</span></th></tr></thead>
                <tbody>
                <?php foreach ($categories as $c): ?>
                    <tr <?= (int) $c['id_category'] === $editId ? 'class="row-selected"' : '' ?>>
                        <td><strong><?= e($c['name']) ?></strong></td>
                        <td class="cell-muted"><?= e($c['description']) ?></td>
                        <td><?= (int) $c['product_count'] ?></td>
                        <td>
                            <div class="row-actions">
                                <a href="<?= url('admin/kategorije.php?uredi=' . $c['id_category']) ?>#obrazec" class="btn btn-black btn-xs">Uredi</a>
                                <?= action_button('delete', (int) $c['id_category'], 'Izbriši', 'btn btn-danger btn-xs',
                                    'Res želite izbrisati kategorijo "' . $c['name'] . '"?') ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <form method="post" action="<?= url('admin/kategorije.php' . ($editId ? '?uredi=' . $editId : '')) ?>" class="form admin-card" id="obrazec" novalidate>
            <h2><?= $editId ? 'Uredi kategorijo' : 'Nova kategorija' ?></h2>
            <?= csrf_field() ?>
            <div class="field">
                <label for="name">Ime kategorije</label>
                <input type="text" id="name" name="name" value="<?= e($cat['name']) ?>" required maxlength="50" <?= field_attrs($errors, 'name') ?>>
                <?= field_error($errors, 'name') ?>
            </div>
            <div class="field">
                <label for="description">Opis</label>
                <textarea id="description" name="description" rows="3" maxlength="1000" <?= field_attrs($errors, 'description') ?>><?= e($cat['description']) ?></textarea>
                <?= field_error($errors, 'description') ?>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-green"><?= $editId ? 'Shrani spremembe' : 'Dodaj kategorijo' ?></button>
                <?php if ($editId): ?><a href="<?= url('admin/kategorije.php') ?>" class="btn btn-outline">Prekliči</a><?php endif; ?>
            </div>
        </form>
<?php admin_end(); ?>
