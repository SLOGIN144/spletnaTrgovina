<?php
// Glava spletne strani. Pred vključitvijo nastavi $pageTitle in $activePage.
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/ikone.php';
$user = current_user();
$activePage = $activePage ?? '';
$cartCount = array_sum($_SESSION['cart'] ?? []);
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Kmetija Skledar') ?> – Kmetija Skledar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <script>document.documentElement.classList.add('js');</script>
</head>
<body>
<header class="site-header">
    <a href="<?= url('index.php') ?>" class="logo"><?= logo_mark() ?><span>Kmetija Skledar</span></a>

    <nav class="main-nav" aria-label="Glavni meni">
        <a href="<?= url('index.php') ?>" <?= $activePage === 'domov' ? 'aria-current="page"' : '' ?>>Domov</a>
        <a href="<?= url('trgovina.php') ?>" <?= $activePage === 'trgovina' ? 'aria-current="page"' : '' ?>>Trgovina</a>
        <a href="<?= url('index.php#o-kmetiji') ?>">O kmetiji</a>
        <a href="<?= url('index.php#kontakt') ?>">Kontakt</a>
    </nav>

    <div class="header-actions">
        <?php if ($user): /* PRIJAVLJEN: ime, moj račun, (admin), odjava */ ?>
            <?php if (is_admin()): ?>
                <a href="<?= url('admin/index.php') ?>" class="link-strong">Administracija</a>
            <?php endif; ?>
            <a href="<?= url('moj-racun.php') ?>" class="user-chip" <?= $activePage === 'racun' ? 'aria-current="page"' : '' ?>>
                <span class="avatar" aria-hidden="true"><?= e(mb_substr($user['first_name'], 0, 1) . mb_substr($user['last_name'], 0, 1)) ?></span>
                <span class="greeting"><?= e($user['first_name']) ?></span>
            </a>
            <?= logout_button() ?>
        <?php else: /* NEPRIJAVLJEN: prijava in registracija */ ?>
            <a href="<?= url('prijava.php') ?>">Prijava</a>
            <a href="<?= url('registracija.php') ?>" class="btn btn-green btn-sm">Registracija</a>
        <?php endif; ?>
        <a href="<?= url('kosarica.php') ?>" class="cart-btn" <?= $activePage === 'kosarica' ? 'aria-current="page"' : '' ?> aria-label="Košarica, izdelkov: <?= $cartCount ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6H21L19.5 15H7.5Z"/><path d="M6 6L5 2H2"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/></svg>
            <?php if ($cartCount > 0): ?><span class="cart-badge"><?= $cartCount ?></span><?php endif; ?>
        </a>
    </div>
</header>
<?php if ($flash = get_flash()): ?>
    <div class="flash flash-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
<?php endif; ?>
