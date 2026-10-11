<?php
/** @var list<\App\Models\Product> $products */
/** @var array<string, mixed> $shop */
$symbol = (string) ($shop['currency_symbol'] ?? '€');
?>
<section class="admin-panel">
    <div class="admin-panel__head">
        <h1>Produits</h1>
        <a class="btn btn-primary" href="/admin/products/create">Ajouter</a>
    </div>
    <?php if ($products === []): ?>
        <p class="empty-state">Aucun produit.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Prix TTC</th>
                        <th>Stock</th>
                        <th>Catégorie</th>
                        <th>Actif</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($p->name, ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($p->imageSrc() === null): ?>
                                <span class="badge-muted">sans photo</span>
                            <?php endif ?>
                        </td>
                        <td><?= htmlspecialchars($p->priceLabel($symbol), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) $p->stock ?></td>
                        <td><?= htmlspecialchars((string) ($p->category_name ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $p->isActive() ? 'Oui' : 'Non' ?></td>
                        <td class="actions">
                            <a href="/admin/products/<?= (int) $p->id ?>/edit">Modifier</a>
                            <form method="POST" action="/admin/products/<?= (int) $p->id ?>/delete"
                                  onsubmit="return confirm('Supprimer ce produit ?');">
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
