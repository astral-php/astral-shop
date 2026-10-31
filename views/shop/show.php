<?php
/** @var \App\Models\Product $product */
/** @var array<string, mixed> $shop */
$symbol = (string) ($shop['currency_symbol'] ?? '€');
$src    = $product->imageSrc();
?>
<article class="product-detail">
    <div class="product-detail__media">
        <?php if ($src !== null): ?>
            <img src="<?= htmlspecialchars($src, ENT_QUOTES, 'UTF-8') ?>"
                 alt="<?= htmlspecialchars($product->name, ENT_QUOTES, 'UTF-8') ?>">
        <?php else: ?>
            <div class="product-card__placeholder product-card__placeholder--lg" aria-hidden="true">
                <span><?= htmlspecialchars(mb_strtoupper(mb_substr($product->name, 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        <?php endif ?>
    </div>
    <div class="product-detail__info">
        <p class="eyebrow">
            <a href="/boutique">Boutique</a>
            <?php if (!empty($product->category_name)): ?>
                · <?= htmlspecialchars((string) $product->category_name, ENT_QUOTES, 'UTF-8') ?>
            <?php endif ?>
        </p>
        <h1><?= htmlspecialchars($product->name, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="product-detail__price">
            <?= htmlspecialchars($product->priceLabel($symbol), ENT_QUOTES, 'UTF-8') ?>
            <span class="ttc">TTC</span>
        </p>
        <?php if ($product->description): ?>
            <div class="product-detail__desc">
                <?= nl2br(htmlspecialchars($product->description, ENT_QUOTES, 'UTF-8')) ?>
            </div>
        <?php endif ?>
        <p class="product-detail__meta">
            <span class="<?= htmlspecialchars($product->stockBadgeClass(), ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($product->stockLabel(), ENT_QUOTES, 'UTF-8') ?>
            </span>
            <?php if ($product->stockHint() !== null): ?>
                <span class="product-detail__stock-hint"><?= htmlspecialchars($product->stockHint(), ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif ?>
            <?php if ($product->sku): ?>
                <span class="badge-muted">SKU <?= htmlspecialchars($product->sku, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif ?>
        </p>

        <?php $isAdmin = $auth->check() && $auth->is(\Core\Auth\Role::Admin); ?>
        <?php if (!$isAdmin): ?>
            <?php if ($product->inStock()): ?>
                <form method="POST" action="/panier/ajouter" class="form-stack" style="max-width:16rem;margin-bottom:1.25rem">
                    <?= $csrf->field() ?>
                    <input type="hidden" name="product_id" value="<?= (int) $product->id ?>">
                    <input type="hidden" name="redirect" value="/panier">
                    <div class="field">
                        <label for="qty">Quantité</label>
                        <input class="input" type="number" id="qty" name="qty" min="1"
                               max="<?= (int) $product->stock ?>" value="1">
                    </div>
                    <button type="submit" class="btn-block">Ajouter au panier</button>
                </form>
            <?php else: ?>
                <p class="product-detail__stock-hint" style="margin-bottom:1rem">
                    Produit indisponible pour le moment.
                </p>
            <?php endif ?>
        <?php endif ?>

        <div style="display:flex;flex-wrap:wrap;gap:0.75rem">
            <a class="btn btn-ghost" href="/boutique">← Retour boutique</a>
            <?php if (!$isAdmin): ?>
                <a class="btn btn-ghost" href="/panier">Voir le panier</a>
            <?php else: ?>
                <a class="btn btn-ghost" href="/admin/products">Gérer le catalogue</a>
            <?php endif ?>
        </div>
    </div>
</article>
