<?php // Vsebina strani 403 – vključi jo require_admin() med glavo in nogo ?>
<main class="empty-state">
    <h1>Dostop zavrnjen</h1>
    <p>Ta del strani je namenjen samo administratorju kmetije.</p>
    <a href="<?= url('index.php') ?>" class="btn btn-green">Nazaj na domačo stran</a>
</main>
