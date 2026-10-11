<?php
/** @var list<array{product_id: int, qty: int, product: ?\App\Models\Product, unit_price_cents: int, line_total_cents: int, available: bool, message: ?string}> $lines */
/** @var array<string, mixed> $shop */
/** @var int $total */
/** @var int $count */
use App\Support\Money;

$symbol = (string) ($shop['currency_symbol'] ?? '€');
?>
<section class="admin-panel">
    <div class="admin-panel__head">
        <h1>Panier</h1>
        <a class="btn btn-ghost" href="/boutique">Continuer vos achats</a>
    </div>

    <?php if ($lines === []): ?>
        <p class="empty-state">Votre panier est vide.</p>
        <a class="btn btn-primary" href="/boutique">Voir la boutique</a>
    <?php else: ?>
        <div class="table-wrap cart-table">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Prix TTC</th>
                        <th>Qté</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lines as $line): ?>
                    <?php $p = $line['product']; ?>
                    <tr>
                        <td>
                            <?php if ($p !== null): ?>
                                <a href="/produit/<?= htmlspecialchars($p->slug, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($p->name, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            <?php else: ?>
                                <em>Produit #<?= (int) $line['product_id'] ?></em>
                            <?php endif ?>
                            <?php if ($line['message']): ?>
                                <p class="field-error" style="margin:0.35rem 0 0"><?= htmlspecialchars($line['message'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif ?>
                        </td>
                        <td>
                            <?= $line['available']
                                ? htmlspecialchars(Money::formatCents($line['unit_price_cents'], $symbol), ENT_QUOTES, 'UTF-8')
                                : '—' ?>
                        </td>
                        <td>
                            <?php if ($p !== null && $p->isActive()): ?>
                                <form method="POST" action="/panier/maj" class="cart-qty-form">
                                    <?= $csrf->field() ?>
                                    <input type="hidden" name="product_id" value="<?= (int) $line['product_id'] ?>">
                                    <input class="input cart-qty" type="number" name="qty" min="1"
                                           max="<?= max(1, (int) $p->stock) ?>"
                                           value="<?= (int) $line['qty'] ?>"
                                           aria-label="Quantité">
                                    <button type="submit" class="btn btn-ghost" style="padding:0.35rem 0.7rem;font-size:0.8rem">OK</button>
                                </form>
                            <?php else: ?>
                                <?= (int) $line['qty'] ?>
                            <?php endif ?>
                        </td>
                        <td>
                            <?= $line['available']
                                ? htmlspecialchars(Money::formatCents($line['line_total_cents'], $symbol), ENT_QUOTES, 'UTF-8')
                                : '—' ?>
                        </td>
                        <td>
                            <form method="POST" action="/panier/retirer">
                                <?= $csrf->field() ?>
                                <input type="hidden" name="product_id" value="<?= (int) $line['product_id'] ?>">
                                <button type="submit" class="link-danger">Retirer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <div class="cart-summary">
            <p class="cart-summary__total">
                Total TTC :
                <strong><?= htmlspecialchars(Money::formatCents($total, $symbol), ENT_QUOTES, 'UTF-8') ?></strong>
                <span class="ttc">(<?= (int) $count ?> article<?= $count > 1 ? 's' : '' ?>)</span>
            </p>
            <?php if ($auth->check()): ?>
                <a class="btn btn-primary" href="/checkout">Passer commande</a>
            <?php else: ?>
                <div class="cart-auth">
                    <p class="cart-auth__note">Connectez-vous pour finaliser votre commande — votre panier est conservé.</p>
                    <div class="cart-auth__actions">
                        <a class="btn btn-primary" href="/login?redirect=/checkout">Se connecter</a>
                        <a class="btn btn-ghost" href="/register?redirect=/checkout">Créer un compte</a>
                    </div>
                </div>
            <?php endif ?>
        </div>
    <?php endif ?>
</section>
