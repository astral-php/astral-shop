<?php
/** @var \App\Models\Product|null $product */
/** @var list<\App\Models\Category> $categories */
/** @var array<string, list<string>> $errors */
/** @var array<string, mixed> $old */
/** @var array<string, mixed> $shop */
$isEdit = $product !== null;
$symbol = (string) ($shop['currency_symbol'] ?? '€');

$val = static function (string $key, mixed $fallback = '') use ($old, $product): string {
    if (array_key_exists($key, $old)) {
        return (string) $old[$key];
    }
    if ($product === null) {
        return (string) $fallback;
    }
    return match ($key) {
        'name' => $product->name,
        'slug' => $product->slug,
        'description' => (string) ($product->description ?? ''),
        'price' => number_format($product->price_cents / 100, 2, ',', ''),
        'category_id' => (string) ($product->category_id ?? ''),
        'stock' => (string) $product->stock,
        'weight_g' => (string) $product->weight_g,
        'sku' => (string) ($product->sku ?? ''),
        'image_path' => (string) ($product->image_path ?? ''),
        'image_url' => (string) ($product->image_url ?? ''),
        default => (string) $fallback,
    };
};

$activeChecked = array_key_exists('active', $old)
    ? (bool) $old['active']
    : ($product === null || $product->isActive());
?>
<section class="admin-panel auth-wrap" style="max-width:36rem">
    <h1><?= htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="admin-nav"><a href="/admin/products">← Produits</a></p>

    <form method="POST"
          action="<?= $isEdit ? '/admin/products/' . (int) $product->id : '/admin/products' ?>"
          class="auth-card form-stack"
          enctype="multipart/form-data">
        <?= $csrf->field() ?>

        <div class="field">
            <label for="name">Nom</label>
            <input class="input<?= isset($errors['name']) ? ' is-invalid' : '' ?>"
                   id="name" name="name" required
                   value="<?= htmlspecialchars($val('name'), ENT_QUOTES, 'UTF-8') ?>">
            <?php if (isset($errors['name'])): ?>
                <p class="field-error"><?= htmlspecialchars($errors['name'][0], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif ?>
        </div>

        <div class="field">
            <label for="slug">Slug <span class="hint">(vide = auto)</span></label>
            <input class="input" id="slug" name="slug"
                   value="<?= htmlspecialchars($val('slug'), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="field">
            <label for="category_id">Catégorie</label>
            <select class="input" id="category_id" name="category_id">
                <option value="">— Aucune —</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat->id ?>"
                        <?= $val('category_id') === (string) $cat->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat->name, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach ?>
            </select>
        </div>

        <div class="field">
            <label for="price">Prix TTC (<?= htmlspecialchars($symbol, ENT_QUOTES, 'UTF-8') ?>)</label>
            <input class="input<?= isset($errors['price']) ? ' is-invalid' : '' ?>"
                   id="price" name="price" required placeholder="24,90"
                   value="<?= htmlspecialchars($val('price'), ENT_QUOTES, 'UTF-8') ?>">
            <p class="hint" style="margin:0.35rem 0 0;font-size:0.8rem;color:var(--muted)">
                Saisie du montant TTC — pas de calcul TVA au MVP.
            </p>
            <?php if (isset($errors['price'])): ?>
                <p class="field-error"><?= htmlspecialchars($errors['price'][0], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif ?>
        </div>

        <div class="field">
            <label for="stock">Stock</label>
            <input class="input" type="number" min="0" id="stock" name="stock"
                   value="<?= htmlspecialchars($val('stock', '0'), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="field">
            <label for="weight_g">Poids (grammes)</label>
            <input class="input" type="number" min="0" id="weight_g" name="weight_g"
                   value="<?= htmlspecialchars($val('weight_g', '0'), ENT_QUOTES, 'UTF-8') ?>">
            <p class="hint" style="margin:0.35rem 0 0;font-size:0.8rem;color:var(--muted)">
                Utilisé pour les barèmes Colissimo / Mondial Relay (0 = tranche la plus légère).
            </p>
        </div>

        <div class="field">
            <label for="sku">SKU</label>
            <input class="input" id="sku" name="sku"
                   value="<?= htmlspecialchars($val('sku'), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="field">
            <label for="description">Description</label>
            <textarea class="input" id="description" name="description" rows="4"><?= htmlspecialchars($val('description'), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="field">
            <label for="image_file">Image produit <span class="hint">(JPEG, PNG, WebP, GIF — max 2 Mo)</span></label>
            <input class="input<?= isset($errors['image_file']) ? ' is-invalid' : '' ?>"
                   type="file" id="image_file" name="image_file"
                   accept="image/jpeg,image/png,image/webp,image/gif">
            <?php if (isset($errors['image_file'])): ?>
                <p class="field-error"><?= htmlspecialchars($errors['image_file'][0], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif ?>
            <?php if ($isEdit && $product?->imageSrc()): ?>
                <p class="hint" style="margin:0.5rem 0 0;font-size:0.8rem">
                    Actuelle :
                    <a href="<?= htmlspecialchars($product->imageSrc(), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">voir</a>
                </p>
            <?php endif ?>
        </div>

        <div class="field">
            <label for="image_url">Image URL <span class="hint">(optionnel, prioritaire si renseignée)</span></label>
            <input class="input" id="image_url" name="image_url" placeholder="https://…"
                   value="<?= htmlspecialchars($val('image_url'), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <input type="hidden" name="image_path" value="<?= htmlspecialchars($val('image_path'), ENT_QUOTES, 'UTF-8') ?>">

        <label style="display:flex;gap:0.5rem;align-items:center;font-size:0.9rem">
            <input type="checkbox" name="active" value="1" <?= $activeChecked ? 'checked' : '' ?>>
            Actif
        </label>

        <button type="submit" class="btn-block"><?= $isEdit ? 'Enregistrer' : 'Créer' ?></button>
    </form>
</section>
