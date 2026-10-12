<?php
/** @var \App\Models\Product $product */
/** @var array<string, mixed> $shop */
$symbol = (string) ($shop['currency_symbol'] ?? '€');
$src    = $product->imageSrc();
$redirect = $redirect ?? '/boutique';
?>
<article class="product-card">
    <a class="product-card__link" href="/produit/<?= htmlspecialchars($product->slug, ENT_QUOTES, 'UTF-8') ?>">
        <div class="product-card__media">
            <?php if ($src !== null): ?>
                <img src="<?= htmlspecialchars($src, ENT_QUOTES, 'UTF-8') ?>"
                     alt="<?= htmlspecialchars($product->name, ENT_QUOTES, 'UTF-8') ?>"
                     loading="lazy">
            <?php else: ?>
                <div class="product-card__placeholder" aria-hidden="true">
                    <span><?= htmlspecialchars(mb_strtoupper(mb_substr($product->name, 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif ?>
        </div>
        <div class="product-card__body">
            <?php if (!empty($product->category_name)): ?>
                <p class="product-card__cat"><?= htmlspecialchars((string) $product->category_name, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif ?>
            <h3 class="product-card__title"><?= htmlspecialchars($product->name, ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="product-card__price"><?= htmlspecialchars($product->priceLabel($symbol), ENT_QUOTES, 'UTF-8') ?> <span class="ttc">TTC</span></p>
            <span class="<?= htmlspecialchars($product->stockBadgeClass(), ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($product->stockLabel(), ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>
    </a>
    <?php if (!$auth->check() || !$auth->is(\Core\Auth\Role::ADMIN)): ?>
    <div class="product-card__actions">
        <?php if ($product->inStock()): ?>
            <form method="POST" action="/panier/ajouter">
                <?= $csrf->field() ?>
                <input type="hidden" name="product_id" value="<?= (int) $product->id ?>">
                <input type="hidden" name="qty" value="1">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars((string) $redirect, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-primary product-card__btn">Ajouter au panier</button>
            </form>
        <?php else: ?>
            <button type="button" class="btn btn-ghost product-card__btn" disabled>Indisponible</button>
        <?php endif ?>
    </div>
    <?php endif ?>
</article>
