<?php
require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/password_field.php';
require_admin();

// Isti obrazec za dodajanje (brez ?id) in urejanje (?id=5) uporabnika
$id    = (int) ($_GET['id'] ?? 0);
$isMe  = $id === current_user()['id'];
$roles = $pdo->query('SELECT id_role, name FROM roles ORDER BY id_role')->fetchAll();

$u = ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '', 'address' => '',
      'postal_code' => '', 'city' => '', 'id_role' => $roles[0]['id_role']];

if ($id) {
    $stmt = $pdo->prepare(
        'SELECT first_name, last_name, email, phone, address, postal_code, city, id_role FROM users WHERE id_user = ?'
    );
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if (!$u) {
        set_flash('error', 'Uporabnik ne obstaja.');
        redirect('admin/uporabniki.php');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $u;
    foreach (['first_name', 'last_name', 'phone', 'address', 'postal_code', 'city'] as $key) {
        $u[$key] = trim($_POST[$key] ?? '');
    }
    $u['email']   = mb_strtolower(trim($_POST['email'] ?? ''));
    $u['id_role'] = (int) ($_POST['id_role'] ?? 0);
    $password     = $_POST['new_password'] ?? '';

    // Svoje vloge admin ne more spremeniti, da se ne zaklene iz administracije
    if ($isMe) {
        $u['id_role'] = (int) $old['id_role'];
    }

    if (!csrf_valid()) {
        $errors['form'] = 'Seja je potekla. Osvežite stran in poskusite znova.';
    }

    $namePattern = "/^[\p{L}][\p{L} '\-]{1,49}$/u";
    if (!preg_match($namePattern, $u['first_name'])) {
        $errors['first_name'] = 'Ime naj ima 2–50 črk.';
    }
    if (!preg_match($namePattern, $u['last_name'])) {
        $errors['last_name'] = 'Priimek naj ima 2–50 črk.';
    }
    if (!filter_var($u['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($u['email']) > 100) {
        $errors['email'] = 'E-poštni naslov ni veljaven.';
    } else {
        $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ? AND id_user <> ?');
        $stmt->execute([$u['email'], $id]);
        if ($stmt->fetchColumn()) {
            $errors['email'] = 'Uporabnik s tem e-poštnim naslovom že obstaja.';
        }
    }
    if ($u['phone'] !== '' && !preg_match('/^\+?[0-9 \/\-]{6,20}$/', $u['phone'])) {
        $errors['phone'] = 'Telefon naj vsebuje samo številke (npr. 041 123 456).';
    }
    if (mb_strlen($u['address']) > 150) {
        $errors['address'] = 'Naslov je predolg (največ 150 znakov).';
    }
    if ($u['postal_code'] !== '' && !preg_match('/^\d{4}$/', $u['postal_code'])) {
        $errors['postal_code'] = 'Poštna številka naj ima 4 števke.';
    }
    if (mb_strlen($u['city']) > 50) {
        $errors['city'] = 'Kraj je predolg (največ 50 znakov).';
    }
    if (!in_array($u['id_role'], array_map('intval', array_column($roles, 'id_role')), true)) {
        $errors['id_role'] = 'Izberite vlogo.';
    }

    // Geslo je obvezno za novega uporabnika; pri urejanju prazno polje pomeni "ne spremeni"
    if ($password !== '' || !$id) {
        if (strlen($password) < 8) {
            $errors['new_password'] = 'Geslo mora imeti vsaj 8 znakov.';
        } elseif (strlen($password) > 72) {
            $errors['new_password'] = 'Geslo je predolgo (največ 72 znakov).';
        } elseif (!preg_match('/\p{L}/u', $password) || !preg_match('/\d/', $password)) {
            $errors['new_password'] = 'Geslo mora vsebovati vsaj eno črko in eno številko.';
        }
    }

    if (!$errors) {
        $values = [$u['id_role'], $u['first_name'], $u['last_name'], $u['email'],
                   $u['phone'] ?: null, $u['address'] ?: null, $u['postal_code'] ?: null, $u['city'] ?: null];
        if ($id) {
            $pdo->prepare(
                'UPDATE users SET id_role = ?, first_name = ?, last_name = ?, email = ?,
                 phone = ?, address = ?, postal_code = ?, city = ? WHERE id_user = ?'
            )->execute([...$values, $id]);
            if ($password !== '') {
                $pdo->prepare('UPDATE users SET password_hash = ? WHERE id_user = ?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
            }
            if ($isMe) { // osveži ime v seji, da se glava takoj posodobi
                $_SESSION['user']['first_name'] = $u['first_name'];
                $_SESSION['user']['last_name']  = $u['last_name'];
                $_SESSION['user']['email']      = $u['email'];
            }
            set_flash('success', 'Podatki uporabnika ' . $u['first_name'] . ' ' . $u['last_name'] . ' so posodobljeni.');
        } else {
            $pdo->prepare(
                'INSERT INTO users (id_role, first_name, last_name, email, phone, address, postal_code, city, password_hash)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([...$values, password_hash($password, PASSWORD_DEFAULT)]);
            set_flash('success', 'Uporabnik ' . $u['first_name'] . ' ' . $u['last_name'] . ' je dodan.');
        }
        redirect('admin/uporabniki.php');
    }
}

$title = $id ? 'Uredi uporabnika' : 'Nov uporabnik';
admin_start($title, 'uporabniki');
?>
        <div>
            <a href="<?= url('admin/uporabniki.php') ?>" class="back-link">← Vsi uporabniki</a>
            <h1><?= $title ?></h1>
        </div>

        <?php if (isset($errors['form'])): ?>
            <div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div>
        <?php endif; ?>

        <form method="post" class="form admin-card" id="user-form" <?= $id ? 'data-edit="1"' : '' ?> novalidate>
            <?= csrf_field() ?>

            <div class="form-row">
                <div class="field">
                    <label for="first_name">Ime</label>
                    <input type="text" id="first_name" name="first_name" value="<?= e($u['first_name']) ?>" required maxlength="50" <?= field_attrs($errors, 'first_name') ?>>
                    <?= field_error($errors, 'first_name') ?>
                </div>
                <div class="field">
                    <label for="last_name">Priimek</label>
                    <input type="text" id="last_name" name="last_name" value="<?= e($u['last_name']) ?>" required maxlength="50" <?= field_attrs($errors, 'last_name') ?>>
                    <?= field_error($errors, 'last_name') ?>
                </div>
            </div>

            <div class="form-row">
                <div class="field">
                    <label for="email">E-pošta</label>
                    <input type="email" id="email" name="email" value="<?= e($u['email']) ?>" required maxlength="100" autocomplete="off" <?= field_attrs($errors, 'email') ?>>
                    <?= field_error($errors, 'email') ?>
                </div>
                <div class="field">
                    <label for="phone">Telefon <span class="label-hint">(neobvezno)</span></label>
                    <input type="tel" id="phone" name="phone" value="<?= e($u['phone']) ?>" maxlength="20" <?= field_attrs($errors, 'phone') ?>>
                    <?= field_error($errors, 'phone') ?>
                </div>
            </div>

            <div class="field">
                <label for="address">Naslov <span class="label-hint">(neobvezno)</span></label>
                <input type="text" id="address" name="address" value="<?= e($u['address']) ?>" maxlength="150" <?= field_attrs($errors, 'address') ?>>
                <?= field_error($errors, 'address') ?>
            </div>

            <div class="form-row">
                <div class="field">
                    <label for="postal_code">Poštna številka</label>
                    <input type="text" id="postal_code" name="postal_code" inputmode="numeric" value="<?= e($u['postal_code']) ?>" maxlength="4" <?= field_attrs($errors, 'postal_code') ?>>
                    <?= field_error($errors, 'postal_code') ?>
                </div>
                <div class="field">
                    <label for="city">Kraj</label>
                    <input type="text" id="city" name="city" value="<?= e($u['city']) ?>" maxlength="50" <?= field_attrs($errors, 'city') ?>>
                    <?= field_error($errors, 'city') ?>
                </div>
            </div>

            <div class="form-row">
                <div class="field">
                    <label for="id_role">Vloga</label>
                    <select id="id_role" name="id_role" <?= $isMe ? 'disabled aria-describedby="role-note"' : field_attrs($errors, 'id_role') ?>>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= (int) $r['id_role'] ?>" <?= (int) $r['id_role'] === (int) $u['id_role'] ? 'selected' : '' ?>><?= $r['name'] === 'admin' ? 'Administrator' : 'Kupec' ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($isMe): ?>
                        <span class="label-hint" id="role-note">Svoje vloge ne morete spremeniti.</span>
                    <?php endif; ?>
                    <?= field_error($errors, 'id_role') ?>
                </div>
                <div class="field">
                    <label for="new_password"><?= $id ? 'Novo geslo' : 'Geslo' ?></label>
                    <?= password_input('new_password', 'new_password', 'new-password', isset($errors['new_password']), $id ? 'password-note' : '') ?>
                    <?php if ($id): ?><span class="label-hint" id="password-note">Pustite prazno, če gesla ne spreminjate.</span><?php endif; ?>
                    <?= field_error($errors, 'new_password') ?>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-green"><?= $id ? 'Shrani spremembe' : 'Dodaj uporabnika' ?></button>
                <a href="<?= url('admin/uporabniki.php') ?>" class="btn btn-outline">Prekliči</a>
            </div>
        </form>
<?php admin_end(); ?>
