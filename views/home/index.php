<?php
/** @var array<string, mixed> $shop */
/** @var list<\App\Models\Product> $featured */
/** @var bool $hasUsers */
$store = (string) ($shop['store_name'] ?? 'Astral Shop');
?>
<section class="shop-hero shop-hero--nova">
    <div class="shop-hero__copy">
        <p class="eyebrow"><?= htmlspecialchars((string) ($shop['tagline'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        <h1><?= htmlspecialchars((string) ($shop['hero_title'] ?? $store), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="lead">
            <?= htmlspecialchars((string) ($shop['hero_lead'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </p>
        <div class="shop-hero__actions">
            <a class="btn btn-primary" href="/boutique">Explorer la boutique</a>
            <?php if (!$auth->check()): ?>
                <a class="btn btn-ghost" href="/login">Connexion</a>
            <?php elseif ($auth->is(\Core\Auth\Role::Admin)): ?>
                <a class="btn btn-ghost" href="/admin/orders">Espace admin</a>
            <?php else: ?>
                <a class="btn btn-ghost" href="/orders">Mes commandes</a>
            <?php endif ?>
        </div>
    </div>
</section>

<?php if ($featured !== []): ?>
    <h2 class="section-title">Sélection</h2>
    <div class="product-grid">
        <?php foreach ($featured as $product): ?>
            <?= $viewEngine->partial('partials/product-card', [
                'product'  => $product,
                'shop'     => $shop,
                'redirect' => '/',
            ]) ?>
        <?php endforeach ?>
    </div>
    <p class="section-more">
        <a href="/boutique">Voir tous les produits →</a>
    </p>
<?php else: ?>
    <p class="empty-state">
        Catalogue vide — ajoutez des produits depuis l’espace admin.
    </p>
<?php endif ?>
