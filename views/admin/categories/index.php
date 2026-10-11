<?php /** @var list<\App\Models\Category> $categories */ ?>
<section class="admin-panel">
    <div class="admin-panel__head">
        <h1>Catégories</h1>
        <a class="btn btn-primary" href="/admin/categories/create">Ajouter</a>
    </div>
    <?php if ($categories === []): ?>
        <p class="empty-state">Aucune catégorie.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Slug</th>
                        <th>Ordre</th>
                        <th>Actif</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?= htmlspecialchars($cat->name, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><code><?= htmlspecialchars($cat->slug, ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td><?= (int) $cat->sort_order ?></td>
                        <td><?= $cat->isActive() ? 'Oui' : 'Non' ?></td>
                        <td class="actions">
                            <a href="/admin/categories/<?= (int) $cat->id ?>/edit">Modifier</a>
                            <form method="POST" action="/admin/categories/<?= (int) $cat->id ?>/delete"
                                  onsubmit="return confirm('Supprimer cette catégorie ?');">
                                <?= $csrf->field() ?>
                                <button type="submit" class="link-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</section>
