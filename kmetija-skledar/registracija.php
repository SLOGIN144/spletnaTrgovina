<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/password_field.php';

// Prijavljen uporabnik se ne registrira ponovno
if (current_user()) {
    redirect('index.php');
}

$errors = [];
$old = ['first_name' => '', 'last_name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['first_name'] = trim($_POST['first_name'] ?? '');
    $old['last_name']  = trim($_POST['last_name'] ?? '');
    $old['email']      = mb_strtolower(trim($_POST['email'] ?? ''));
    $password          = $_POST['password'] ?? '';
    $password2         = $_POST['password2'] ?? '';

    if (!csrf_valid()) {
        $errors['form'] = 'Seja je potekla. Osvežite stran in poskusite znova.';
    }

    // Ime in priimek: 2–50 znakov, samo črke (tudi č, š, ž), presledek, vezaj, opuščaj
    $namePattern = "/^[\p{L}][\p{L} '\-]{1,49}$/u";
    if ($old['first_name'] === '') {
        $errors['first_name'] = 'Vpišite ime.';
    } elseif (!preg_match($namePattern, $old['first_name'])) {
        $errors['first_name'] = 'Ime naj ima 2–50 črk.';
    }
    if ($old['last_name'] === '') {
        $errors['last_name'] = 'Vpišite priimek.';
    } elseif (!preg_match($namePattern, $old['last_name'])) {
        $errors['last_name'] = 'Priimek naj ima 2–50 črk.';
    }

    if ($old['email'] === '') {
        $errors['email'] = 'Vpišite e-poštni naslov.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($old['email']) > 100) {
        $errors['email'] = 'E-poštni naslov ni veljaven.';
    }

    // Geslo: vsaj 8 znakov, vsaj ena črka in ena številka (bcrypt upošteva največ 72 bajtov)
    if (strlen($password) < 8) {
        $errors['password'] = 'Geslo mora imeti vsaj 8 znakov.';
    } elseif (strlen($password) > 72) {
        $errors['password'] = 'Geslo je predolgo (največ 72 znakov).';
    } elseif (!preg_match('/\p{L}/u', $password) || !preg_match('/\d/', $password)) {
        $errors['password'] = 'Geslo mora vsebovati vsaj eno črko in eno številko.';
    }
    if ($password2 === '') {
        $errors['password2'] = 'Ponovite geslo.';
    } elseif ($password !== $password2) {
        $errors['password2'] = 'Gesli se ne ujemata.';
    }

    // Ali e-poštni naslov že obstaja?
    if (!isset($errors['email'])) {
        $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
        $stmt->execute([$old['email']]);
        if ($stmt->fetchColumn()) {
            $errors['email'] = 'Uporabnik s tem e-poštnim naslovom že obstaja.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT); // bcrypt, vsebuje naključno sol

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (id_role, first_name, last_name, email, password_hash)
                 VALUES ((SELECT id_role FROM roles WHERE name = \'kupec\'), ?, ?, ?, ?)'
            );
            $stmt->execute([$old['first_name'], $old['last_name'], $old['email'], $hash]);
        } catch (PDOException $ex) {
            // 23000 = kršitev UNIQUE (dva hkratna vpisa istega naslova)
            if ($ex->getCode() === '23000') {
                $errors['email'] = 'Uporabnik s tem e-poštnim naslovom že obstaja.';
            } else {
                throw $ex;
            }
        }

        if (!$errors) {
            set_flash('success', 'Račun je ustvarjen. Zdaj se lahko prijavite.');
            $_SESSION['last_email'] = $old['email'];
            redirect('prijava.php');
        }
    }
}

function field_attrs(array $errors, string $key): string
{
    return 'aria-describedby="' . $key . '-error"' . (isset($errors[$key]) ? ' aria-invalid="true"' : '');
}

$pageTitle  = 'Registracija';
$panelTitle = 'Pridružite se kmetiji.';
$panelText  = 'Z računom hitreje oddate naročilo in vidite zgodovino svojih nakupov.';
$activeTab  = 'registracija';
require __DIR__ . '/includes/auth_layout.php';
?>
            <h2>Nov račun</h2>

            <?php if (isset($errors['form'])): ?>
                <div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div>
            <?php elseif ($errors): ?>
                <div class="alert alert-error" role="alert">Preverite označena polja.</div>
            <?php endif; ?>

            <form method="post" class="form" id="register-form" novalidate>
                <?= csrf_field() ?>

                <div class="form-row">
                    <div class="field">
                        <label for="first_name">Ime</label>
                        <input type="text" id="first_name" name="first_name" value="<?= e($old['first_name']) ?>"
                               autocomplete="given-name" required minlength="2" maxlength="50" <?= field_attrs($errors, 'first_name') ?>>
                        <span class="field-error" id="first_name-error"><?= e($errors['first_name'] ?? '') ?></span>
                    </div>
                    <div class="field">
                        <label for="last_name">Priimek</label>
                        <input type="text" id="last_name" name="last_name" value="<?= e($old['last_name']) ?>"
                               autocomplete="family-name" required minlength="2" maxlength="50" <?= field_attrs($errors, 'last_name') ?>>
                        <span class="field-error" id="last_name-error"><?= e($errors['last_name'] ?? '') ?></span>
                    </div>
                </div>

                <div class="field">
                    <label for="email">E-pošta</label>
                    <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" placeholder="ime@primer.si"
                           autocomplete="email" required maxlength="100" <?= field_attrs($errors, 'email') ?>>
                    <span class="field-error" id="email-error"><?= e($errors['email'] ?? '') ?></span>
                </div>

                <div class="field">
                    <label for="password">Geslo</label>
                    <?= password_input('password', 'password', 'new-password', isset($errors['password']), 'password-rules') ?>
                    <ul class="rules" id="password-rules">
                        <li data-rule="length">vsaj 8 znakov</li>
                        <li data-rule="letter">vsaj ena črka</li>
                        <li data-rule="digit">vsaj ena številka</li>
                    </ul>
                    <span class="field-error" id="password-error"><?= e($errors['password'] ?? '') ?></span>
                </div>

                <div class="field">
                    <label for="password2">Ponovi geslo</label>
                    <?= password_input('password2', 'password2', 'new-password', isset($errors['password2'])) ?>
                    <span class="field-error" id="password2-error"><?= e($errors['password2'] ?? '') ?></span>
                </div>

                <button type="submit" class="btn btn-green btn-block">Ustvari račun</button>
                <p class="form-note">Že imate račun? <a href="<?= url('prijava.php') ?>">Prijavite se</a></p>
            </form>
        </div>
    </main>
</div>
</body>
</html>
