<?php
// Skupna postavitev administracije: admin_start() izpiše glavo s stranskim menijem, admin_end() zaključek.
// Vsaka admin stran še vedno sama pokliče require_admin() takoj na vrhu.
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/ikone.php';

const ORDER_STATUSES  = ['oddano', 'v obdelavi', 'poslano', 'zaključeno'];
const PAYMENT_METHODS = ['po povzetju', 'predračun'];

function admin_start(string $title, string $active): void
{
    $menu = [
        'izdelki'    => ['admin/index.php', 'Izdelki'],
        'kategorije' => ['admin/kategorije.php', 'Kategorije'],
        'uporabniki' => ['admin/uporabniki.php', 'Uporabniki'],
        'narocila'   => ['admin/narocila.php', 'Naročila'],
    ];
    $flash = get_flash();
    ?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> – Administracija – Kmetija Skledar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<div class="admin">
    <aside class="admin-side">
        <a href="<?= url('index.php') ?>" class="logo"><?= logo_mark() ?><span>Kmetija Skledar</span></a>
        <div class="admin-label">Administracija</div>
        <nav class="admin-nav" aria-label="Administracija">
            <?php foreach ($menu as $key => [$path, $label]): ?>
                <a href="<?= url($path) ?>" <?= $key === $active ? 'aria-current="page"' : '' ?>><?= $label ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="admin-bottom">
            <a href="<?= url('index.php') ?>">Nazaj v trgovino</a>
            <?= logout_button() ?>
        </div>
    </aside>

    <main class="admin-main">
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status"><?= e($flash['message']) ?></div>
        <?php endif; ?>
    <?php
}

function admin_end(): void
{
    ?>
    </main>
</div>
<script src="<?= url('assets/js/forms.js') ?>"></script>
</body>
</html>
    <?php
}

// Pomožne funkcije za obrazce: atributi polja z napako in prostor za sporočilo pod poljem
function field_attrs(array $errors, string $key): string
{
    return 'aria-describedby="' . $key . '-error"' . (isset($errors[$key]) ? ' aria-invalid="true"' : '');
}

function field_error(array $errors, string $key): string
{
    return '<span class="field-error" id="' . $key . '-error">' . e($errors[$key] ?? '') . '</span>';
}

// Gumb, ki pošlje POST z dejanjem (brisanje, skrij/prikaži …); data-confirm vpraša za potrditev
function action_button(string $action, int $id, string $label, string $class = 'btn btn-outline btn-xs', string $confirm = ''): string
{
    return '<form method="post"' . ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') . '>'
        . csrf_field()
        . '<input type="hidden" name="action" value="' . e($action) . '">'
        . '<input type="hidden" name="id" value="' . $id . '">'
        . '<button type="submit" class="' . $class . '">' . e($label) . '</button></form>';
}
