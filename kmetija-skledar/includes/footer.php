<footer id="kontakt" class="site-footer">
    <div class="footer-brand">
        <div class="footer-logo">Kmetija Skledar</div>
        <p>Bučno olje in bučnice z domače kmetije.</p>
    </div>
    <div class="footer-col">
        <div class="footer-title">Kontakt</div>
        <div>Stanko Skledar</div>
        <div>[naslov kmetije]</div>
        <div>[telefon]</div>
        <div>[e-pošta]</div>
    </div>
    <div class="footer-col">
        <div class="footer-title">Povezave</div>
        <a href="<?= url('trgovina.php') ?>">Trgovina</a>
        <a href="<?= url('index.php#o-kmetiji') ?>">O kmetiji</a>
        <?php if (current_user()): ?>
            <a href="<?= url('moj-racun.php') ?>">Moj račun</a>
        <?php else: ?>
            <a href="<?= url('prijava.php') ?>">Prijava</a>
            <a href="<?= url('registracija.php') ?>">Registracija</a>
        <?php endif; ?>
    </div>
    <div class="footer-copy">© <?= date('Y') ?> Kmetija Skledar</div>
</footer>
<script src="<?= url('assets/js/forms.js') ?>"></script>
<script src="<?= url('assets/js/cart.js') ?>"></script>
</body>
</html>
