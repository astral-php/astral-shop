<?php
/** @var \App\Models\Category|null $category */
/** @var array<string, list<string>> $errors */
/** @var array<string, mixed> $old */
$isEdit = $category !== null;
$val = static function (string $key, mixed $fallback = '') use ($old, $category): string {
    if (array_key_exists($key, $old)) {
        return (string) $old[$key];
    }
    if ($category === null) {
        return (string) $fallback;
    }
    return match ($key) {
        'name' => $category->name,
        'slug' => $category->slug,
        'description' => (string) ($category->description ?? ''),
        'sort_order' => (string) $category->sort_order,
        default => (string) $fallback,
    };
};
$activeChecked = array_key_exists('active', $old)
    ? (bool) $old['active']
    : ($category === null || $category->isActive());
?>
<section class="admin-panel auth-wrap" style="max-width:32rem">
    <h1><?= htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="admin-nav"><a href="/admin/categories">← Catégories</a></p>

    <form method="POST"
          action="<?= $isEdit ? '/admin/categories/' . (int) $category->id : '/admin/categories' ?>"
          class="auth-card form-stack">
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
            <label for="description">Description</label>
            <textarea class="input" id="description" name="description" rows="3"><?= htmlspecialchars($val('description'), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="field">
            <label for="sort_order">Ordre</label>
            <input class="input" type="number" id="sort_order" name="sort_order"
                   value="<?= htmlspecialchars($val('sort_order', '0'), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <label style="display:flex;gap:0.5rem;align-items:center;font-size:0.9rem">
            <input type="checkbox" name="active" value="1" <?= $activeChecked ? 'checked' : '' ?>>
            Active
        </label>

        <button type="submit" class="btn-block"><?= $isEdit ? 'Enregistrer' : 'Créer' ?></button>
    </form>
</section>
