<?php
require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/slike.php';
require_admin();

// Isti obrazec za dodajanje (brez ?id) in urejanje (?id=5)
$id = (int) ($_GET['id'] ?? 0);
$categories = $pdo->query('SELECT id_category, name FROM categories ORDER BY id_category')->fetchAll();

$p = ['id_category' => $categories[0]['id_category'] ?? 0, 'name' => '', 'packaging' => '', 'price' => '',
      'stock' => '0', 'description' => '', 'image' => '', 'active' => 1, 'featured' => 0];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id_product = ?');
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if (!$p) {
        set_flash('error', 'Izdelek ne obstaja.');
        redirect('admin/index.php');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldImage = $p['image']; // slika, ki je že v bazi (pri novem izdelku prazno)
    $p = [
        'id_category' => (int) ($_POST['id_category'] ?? 0),
        'name'        => trim($_POST['name'] ?? ''),
        'packaging'   => trim($_POST['packaging'] ?? ''),
        'price'       => str_replace(',', '.', trim($_POST['price'] ?? '')), // dovoli tudi 4,50
        'stock'       => trim($_POST['stock'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'image'       => $oldImage,
        'active'      => isset($_POST['active']) ? 1 : 0,
        'featured'    => isset($_POST['featured']) ? 1 : 0,
    ];
    $removeImage = isset($_POST['remove_image']);

    // Prazen POST pomeni, da je bila datoteka večja od post_max_size v php.ini (PHP zavrže vse podatke)
    if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $errors['image'] = 'Slika je prevelika (največ 2 MB).';
    }

    $upload = check_upload($_FILES['image'] ?? null, $uploadError);
    if ($uploadError) {
        $errors['image'] = $uploadError;
    } elseif (!$upload && !$id) {
        $errors['image'] = 'Izberite sliko izdelka.';
    }

    if (!csrf_valid()) {
        $errors['form'] = 'Seja je potekla. Osvežite stran in poskusite znova.';
    }
    if (!in_array($p['id_category'], array_map('intval', array_column($categories, 'id_category')), true)) {
        $errors['id_category'] = 'Izberite kategorijo.';
    }
    if (mb_strlen($p['name']) < 2 || mb_strlen($p['name']) > 100) {
        $errors['name'] = 'Ime naj ima 2–100 znakov.';
    }
    if ($p['packaging'] === '' || mb_strlen($p['packaging']) > 30) {
        $errors['packaging'] = 'Vpišite pakiranje (npr. 0,5 l), največ 30 znakov.';
    }
    if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $p['price'])) {
        $errors['price'] = 'Cena naj bo število z največ dvema decimalkama, npr. 12,50.';
    }
    if (!preg_match('/^\d{1,6}$/', $p['stock'])) {
        $errors['stock'] = 'Zaloga naj bo celo število 0 ali več.';
    }
    if (mb_strlen($p['description']) < 10 || mb_strlen($p['description']) > 2000) {
        $errors['description'] = 'Opis naj ima 10–2000 znakov.';
    }

    if (!$errors) {
        // Sliko shranimo šele, ko je vse ostalo pravilno, da na disku ne ostajajo odvečne datoteke
        if ($upload) {
            $p['image'] = save_upload($upload);
        } elseif ($removeImage) {
            $p['image'] = null;
        }

        $values = [$p['id_category'], $p['name'], $p['description'] ?: null, $p['price'],
                   $p['packaging'], (int) $p['stock'], $p['image'] ?: null, $p['active'], $p['featured']];
        if ($id) {
            $pdo->prepare(
                'UPDATE products SET id_category = ?, name = ?, description = ?, price = ?,
                 packaging = ?, stock = ?, image = ?, active = ?, featured = ? WHERE id_product = ?'
            )->execute([...$values, $id]);
            if ($oldImage !== $p['image']) {
                delete_upload($oldImage); // stara slika ni več v uporabi
            }
            set_flash('success', 'Izdelek "' . $p['name'] . '" je posodobljen.');
        } else {
            $pdo->prepare(
                'INSERT INTO products (id_category, name, description, price, packaging, stock, image, active, featured)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute($values);
            set_flash('success', 'Izdelek "' . $p['name'] . '" je dodan.');
        }
        redirect('admin/index.php');
    }
}

$title = $id ? 'Uredi izdelek' : 'Nov izdelek';
admin_start($title, 'izdelki');
?>
        <div>
            <a href="<?= url('admin/index.php') ?>" class="back-link">← Vsi izdelki</a>
            <h1><?= $title ?></h1>
        </div>

        <?php if (isset($errors['form'])): ?>
            <div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div>
        <?php endif; ?>
        <?php if (!$categories): ?>
            <div class="alert alert-error" role="alert">Najprej dodajte vsaj eno <a href="<?= url('admin/kategorije.php') ?>">kategorijo</a>.</div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="form admin-card" novalidate>
            <?= csrf_field() ?>

            <div class="form-row">
                <div class="field">
                    <label for="name">Ime izdelka</label>
                    <input type="text" id="name" name="name" value="<?= e($p['name']) ?>" required maxlength="100" <?= field_attrs($errors, 'name') ?>>
                    <?= field_error($errors, 'name') ?>
                </div>
                <div class="field">
                    <label for="id_category">Kategorija</label>
                    <select id="id_category" name="id_category" <?= field_attrs($errors, 'id_category') ?>>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int) $c['id_category'] ?>" <?= (int) $c['id_category'] === (int) $p['id_category'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= field_error($errors, 'id_category') ?>
                </div>
            </div>

            <div class="form-row form-row-3">
                <div class="field">
                    <label for="packaging">Pakiranje</label>
                    <input type="text" id="packaging" name="packaging" value="<?= e($p['packaging']) ?>" placeholder="0,5 l" required maxlength="30" <?= field_attrs($errors, 'packaging') ?>>
                    <?= field_error($errors, 'packaging') ?>
                </div>
                <div class="field">
                    <label for="price">Cena (€)</label>
                    <input type="text" id="price" name="price" inputmode="decimal" value="<?= e(str_replace('.', ',', $p['price'])) ?>" placeholder="12,00" required <?= field_attrs($errors, 'price') ?>>
                    <?= field_error($errors, 'price') ?>
                </div>
                <div class="field">
                    <label for="stock">Zaloga (kos)</label>
                    <input type="number" id="stock" name="stock" min="0" step="1" value="<?= e($p['stock']) ?>" required <?= field_attrs($errors, 'stock') ?>>
                    <?= field_error($errors, 'stock') ?>
                </div>
            </div>

            <div class="field">
                <label for="description">Opis</label>
                <textarea id="description" name="description" rows="4" minlength="10" maxlength="2000" <?= field_attrs($errors, 'description') ?>><?= e($p['description']) ?></textarea>
                <?= field_error($errors, 'description') ?>
            </div>

            <div class="field">
                <label for="image">Slika izdelka <span class="label-hint">(JPG, PNG ali WEBP, največ 2 MB)</span></label>
                <div class="image-field">
                    <div class="image-preview" id="image-preview"><?= product_image($p) ?></div>
                    <div class="image-controls">
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" <?= $id ? '' : 'required' ?> <?= field_attrs($errors, 'image') ?>>
                        <?php if ($id && $p['image']): ?>
                            <label class="check"><input type="checkbox" name="remove_image" value="1"> Odstrani sliko (prikaže se ilustracija)</label>
                        <?php elseif ($id): ?>
                            <span class="label-hint">Izdelek še nima fotografije, zato trgovina prikazuje ilustracijo.</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?= field_error($errors, 'image') ?>
            </div>

            <label class="check">
                <input type="checkbox" name="active" value="1" <?= $p['active'] ? 'checked' : '' ?>>
                Prikazan v trgovini
            </label>
            <label class="check">
                <input type="checkbox" name="featured" value="1" <?= $p['featured'] ? 'checked' : '' ?>>
                Izpostavljen na domači strani <span class="label-hint">(prikažejo se prvi 4)</span>
            </label>

            <div class="form-actions">
                <button type="submit" class="btn btn-green"><?= $id ? 'Shrani spremembe' : 'Dodaj izdelek' ?></button>
                <a href="<?= url('admin/index.php') ?>" class="btn btn-outline">Prekliči</a>
            </div>
        </form>
<?php admin_end(); ?>
