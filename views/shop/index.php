<?php
/** @var list<\App\Models\Category> $categories */
/** @var list<\App\Models\Product> $products */
/** @var \App\Models\Category|null $category */
/** @var array<string, mixed> $shop */
$store = (string) ($shop['store_name'] ?? 'Astral Shop');
?>
<section class="shop-hero shop-hero--nova" style="padding:1.75rem 1.5rem">
    <div class="shop-hero__copy">
        <p class="eyebrow"><?= htmlspecialchars($store, ENT_QUOTES, 'UTF-8') ?></p>
        <h1 style="font-size:clamp(1.55rem,3vw,2.1rem)"><?= htmlspecialchars(
            $category?->name ?? 'Boutique',
            ENT_QUOTES,
            'UTF-8',
        ) ?></h1>
        <p class="lead">
            <?= htmlspecialchars(
                $category?->description
                    ?? (string) ($shop['hero_lead'] ?? ''),
                ENT_QUOTES,
                'UTF-8',
            ) ?>
        </p>
    </div>
</section>

<?php if ($categories !== []): ?>
<nav class="cat-pills" aria-label="Catégories">
    <a class="cat-pill<?= $category === null ? ' is-active' : '' ?>" href="/boutique">Tout</a>
    <?php foreach ($categories as $cat): ?>
        <a class="cat-pill<?= ($category !== null && $category->id === $cat->id) ? ' is-active' : '' ?>"
           href="/boutique/categorie/<?= htmlspecialchars($cat->slug, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($cat->name, ENT_QUOTES, 'UTF-8') ?>
        </a>
    <?php endforeach ?>
</nav>
<?php endif ?>

<?php if ($products === []): ?>
    <p class="empty-state">Aucun produit pour le moment.</p>
<?php else: ?>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <?= $viewEngine->partial('partials/product-card', [
                'product'  => $product,
                'shop'     => $shop,
                'redirect' => $category !== null
                    ? '/boutique/categorie/' . $category->slug
                    : '/boutique',
            ]) ?>
        <?php endforeach ?>
    </div>
<?php endif ?>
