<?php
// Začetek strani za prijavo/registracijo (levi črni panel). Nastavi $pageTitle, $panelTitle, $panelText, $activeTab.
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/ikone.php';
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> – Kmetija Skledar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <script src="<?= url('assets/js/forms.js') ?>" defer></script>
</head>
<body>
<div class="auth">
    <aside class="auth-panel">
        <a href="<?= url('index.php') ?>" class="logo"><?= logo_mark() ?><span>Kmetija Skledar</span></a>
        <div class="reveal">
            <h1><?= e($panelTitle) ?></h1>
            <p><?= e($panelText) ?></p>
        </div>
        <a href="<?= url('index.php') ?>" class="auth-back">← Nazaj na domačo stran</a>
    </aside>

    <main class="auth-main">
        <div class="auth-box reveal reveal-2">
            <nav class="tabs" aria-label="Prijava ali registracija">
                <a href="<?= url('prijava.php') ?>" <?= $activeTab === 'prijava' ? 'aria-current="page"' : '' ?>>Prijava</a>
                <a href="<?= url('registracija.php') ?>" <?= $activeTab === 'registracija' ? 'aria-current="page"' : '' ?>>Registracija</a>
            </nav>
