<?php // Kartica izdelka – pričakuje $p (vrstica iz tabele products + category, z image in stock) ?>
<article class="product-card">
    <a href="<?= url('izdelek.php?id=' . $p['id_product']) ?>" class="product-img" tabindex="-1" aria-hidden="true"><?= product_image($p) ?></a>
    <div class="product-body">
        <div class="product-cat"><?= e($p['category']) ?></div>
        <a href="<?= url('izdelek.php?id=' . $p['id_product']) ?>" class="product-name"><?= e($p['name']) ?></a>
        <div class="product-pack"><?= e($p['packaging']) ?></div>
        <div class="product-foot">
            <span class="price"><?= money($p['price']) ?></span>
            <?= add_to_cart_form($p) ?>
        </div>
    </div>
</article>
