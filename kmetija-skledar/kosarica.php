<?php
$pageTitle = 'Košarica';
require __DIR__ . '/includes/header.php';
?>
<main class="empty-state">
    <h1>Košarica je prazna</h1>
    <p>Dodajte izdelke iz trgovine.</p>
    <a href="<?= url('trgovina.php') ?>" class="btn btn-green">V trgovino</a>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
