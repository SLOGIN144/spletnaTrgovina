<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/password_field.php';

if (current_user()) {
    redirect(is_admin() ? 'admin/index.php' : 'index.php');
}

$error = '';
$email = $_SESSION['last_email'] ?? '';
unset($_SESSION['last_email']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = mb_strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!csrf_valid()) {
        $error = 'Seja je potekla. Osvežite stran in poskusite znova.';
    } elseif ($email === '' || $password === '') {
        $error = 'Vpišite e-poštni naslov in geslo.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT u.id_user, u.first_name, u.last_name, u.email, u.password_hash, r.name AS role
             FROM users u JOIN roles r ON r.id_role = u.id_role
             WHERE u.email = ?'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Enako sporočilo ne glede na to, ali je napačen e-naslov ali geslo
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Napačen e-poštni naslov ali geslo.';
        } else {
            // Če PHP kasneje uvede močnejši algoritem, geslo samodejno ponovno zgostimo
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                $pdo->prepare('UPDATE users SET password_hash = ? WHERE id_user = ?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id_user']]);
            }

            session_regenerate_id(true); // zaščita pred prevzemom seje
            $_SESSION['user'] = [
                'id'         => (int) $user['id_user'],
                'first_name' => $user['first_name'],
                'last_name'  => $user['last_name'],
                'email'      => $user['email'],
                'role'       => $user['role'],
            ];

            set_flash('success', 'Pozdravljeni, ' . $user['first_name'] . '! Uspešno ste prijavljeni.');
            redirect($user['role'] === 'admin' ? 'admin/index.php' : 'index.php');
        }
    }
}

$flash      = get_flash();
$pageTitle  = 'Prijava';
$panelTitle = 'Dobrodošli na kmetiji.';
$panelText  = 'Prijavite se in oddajte naročilo ali preglejte svoja pretekla naročila.';
$activeTab  = 'prijava';
require __DIR__ . '/includes/auth_layout.php';
?>
            <h2>Prijava</h2>

            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status"><?= e($flash['message']) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" class="form" id="login-form" novalidate>
                <?= csrf_field() ?>

                <div class="field">
                    <label for="email">E-pošta</label>
                    <input type="email" id="email" name="email" value="<?= e($email) ?>" placeholder="ime@primer.si"
                           autocomplete="email" required aria-describedby="email-error" <?= $error ? 'aria-invalid="true"' : '' ?>
                           <?= $email === '' ? 'autofocus' : '' ?>>
                    <span class="field-error" id="email-error"></span>
                </div>

                <div class="field">
                    <label for="password">Geslo</label>
                    <?= password_input('password', 'password', 'current-password', (bool) $error) ?>
                    <span class="field-error" id="password-error"></span>
                </div>

                <button type="submit" class="btn btn-green btn-block">Prijava</button>
                <p class="form-note">Še nimate računa? <a href="<?= url('registracija.php') ?>">Registrirajte se</a></p>
            </form>
        </div>
    </main>
</div>
</body>
</html>
