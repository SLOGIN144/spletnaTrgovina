<?php
require_once __DIR__ . '/includes/auth.php';
require_login(); // neprijavljenega preusmeri na prijavo

// Sveži podatki iz baze (seja hrani samo osnovno, da se ne pokvari, če admin kaj spremeni)
$stmt = $pdo->prepare(
    'SELECT u.first_name, u.last_name, u.email, u.created_at, r.name AS role
     FROM users u JOIN roles r ON r.id_role = u.id_role WHERE u.id_user = ?'
);
$stmt->execute([current_user()['id']]);
$account = $stmt->fetch();

$pageTitle  = 'Moj račun';
$activePage = 'racun';
require __DIR__ . '/includes/header.php';

$loginTime   = $_SESSION['login_time'] ?? time();
$minutesLeft = (int) ceil((SESSION_TIMEOUT - (time() - ($_SESSION['last_activity'] ?? time()))) / 60);
?>
<main class="account">
    <div class="account-head reveal">
        <span class="avatar" aria-hidden="true"><?= e(mb_substr($account['first_name'], 0, 1) . mb_substr($account['last_name'], 0, 1)) ?></span>
        <div>
            <h1><?= e($account['first_name'] . ' ' . $account['last_name']) ?></h1>
            <p><?= $account['role'] === 'admin' ? 'Administrator' : 'Kupec' ?> · član od <?= date('j. n. Y', strtotime($account['created_at'])) ?></p>
        </div>
    </div>

    <section class="card reveal reveal-2">
        <h2>Podatki računa</h2>
        <dl class="details">
            <dt>Ime in priimek</dt><dd><?= e($account['first_name'] . ' ' . $account['last_name']) ?></dd>
            <dt>E-pošta</dt><dd><?= e($account['email']) ?></dd>
            <dt>Vloga</dt><dd><?= e($account['role']) ?></dd>
        </dl>
    </section>

    <section class="card card-tint reveal reveal-3">
        <h2>Trenutna seja</h2>
        <dl class="details">
            <dt>Prijavljeni od</dt><dd><?= date('j. n. Y \o\b H:i', $loginTime) ?></dd>
            <dt>Samodejna odjava</dt><dd>čez <?= $minutesLeft ?> min neaktivnosti</dd>
            <dt>ID uporabnika v seji</dt><dd><?= (int) $_SESSION['user']['id'] ?></dd>
        </dl>
        <p class="card-note">Ob odjavi se seja na strežniku uniči in piškotek izbriše.</p>
        <?= logout_button('btn btn-black btn-sm') ?>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
