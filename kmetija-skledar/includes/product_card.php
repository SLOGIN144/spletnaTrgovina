<?php // Kartica izdelka – pričakuje $p (vrstica iz tabele products + category) ?>
<article class="product-card">
    <div class="product-img"><?= product_art((int) $p['id_category']) ?></div>
    <div class="product-body">
        <div class="product-cat"><?= e($p['category']) ?></div>
        <div class="product-name"><?= e($p['name']) ?></div>
        <div class="product-pack"><?= e($p['packaging']) ?></div>
        <div class="product-foot">
            <span class="price"><?= number_format((float) $p['price'], 2, ',', '.') ?> €</span>
            <button type="button" class="btn btn-green btn-sm" disabled title="Košarica pride v naslednji nalogi">V košarico</button>
        </div>
    </div>
</article>
