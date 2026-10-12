<?php
/** @var list<array{product_id: int, qty: int, product: ?\App\Models\Product, unit_price_cents: int, line_total_cents: int, available: bool, message: ?string}> $lines */
/** @var array<string, mixed> $shop */
/** @var array<string, list<string>> $errors */
/** @var array<string, mixed> $old */
/** @var int $total */
use App\Support\Money;

$symbol = (string) ($shop['currency_symbol'] ?? '€');
$val = static fn (string $key): string => htmlspecialchars((string) ($old[$key] ?? ''), ENT_QUOTES, 'UTF-8');
$billingSame = ((string) ($old['billing_same_as_shipping'] ?? '1')) !== '0';
?>
<section class="admin-panel" style="max-width:40rem">
    <h1>Checkout</h1>
    <?php
    $checkoutStep = 'identification';
    require dirname(__DIR__) . '/partials/checkout-steps.php';
    ?>
    <p class="admin-nav"><a href="/panier">← Panier</a></p>

    <div class="cart-summary" style="margin-bottom:1.25rem">
        <p style="margin:0 0 0.75rem;font-weight:600">Récapitulatif</p>
        <ul style="margin:0;padding-left:1.1rem;color:var(--muted);font-size:0.9rem">
            <?php foreach ($lines as $line): ?>
                <?php if ($line['product'] === null) { continue; } ?>
                <li>
                    <?= htmlspecialchars($line['product']->name, ENT_QUOTES, 'UTF-8') ?>
                    × <?= (int) $line['qty'] ?>
                    — <?= htmlspecialchars(Money::formatCents($line['line_total_cents'], $symbol), ENT_QUOTES, 'UTF-8') ?>
                </li>
            <?php endforeach ?>
        </ul>
        <p class="cart-summary__total" style="margin-top:0.85rem">
            Total TTC :
            <strong><?= htmlspecialchars(Money::formatCents($total, $symbol), ENT_QUOTES, 'UTF-8') ?></strong>
        </p>
    </div>

    <form method="POST" action="/checkout" class="auth-card form-stack" id="checkout-address-form">
        <?= $csrf->field() ?>

        <h2 class="section-title" style="margin-top:0">Adresse de livraison</h2>
        <p class="product-detail__note" style="margin:0 0 1rem">
            Préremplie depuis <a href="/profile">votre profil</a> si renseignée.
        </p>

        <div class="field">
            <label for="shipping_name">Nom complet</label>
            <input class="input<?= isset($errors['shipping_name']) ? ' is-invalid' : '' ?>"
                   id="shipping_name" name="shipping_name" required
                   value="<?= $val('shipping_name') ?>">
            <?php if (isset($errors['shipping_name'])): ?>
                <p class="field-error"><?= htmlspecialchars($errors['shipping_name'][0], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif ?>
        </div>

        <div class="field">
            <label for="shipping_line1">Adresse</label>
            <input class="input<?= isset($errors['shipping_line1']) ? ' is-invalid' : '' ?>"
                   id="shipping_line1" name="shipping_line1" required
                   value="<?= $val('shipping_line1') ?>">
            <?php if (isset($errors['shipping_line1'])): ?>
                <p class="field-error"><?= htmlspecialchars($errors['shipping_line1'][0], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif ?>
        </div>

        <div class="field">
            <label for="shipping_postal">Code postal</label>
            <input class="input<?= isset($errors['shipping_postal']) ? ' is-invalid' : '' ?>"
                   id="shipping_postal" name="shipping_postal" required
                   value="<?= $val('shipping_postal') ?>">
            <?php if (isset($errors['shipping_postal'])): ?>
                <p class="field-error"><?= htmlspecialchars($errors['shipping_postal'][0], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif ?>
        </div>

        <div class="field">
            <label for="shipping_city">Ville</label>
            <input class="input<?= isset($errors['shipping_city']) ? ' is-invalid' : '' ?>"
                   id="shipping_city" name="shipping_city" required
                   value="<?= $val('shipping_city') ?>">
            <?php if (isset($errors['shipping_city'])): ?>
                <p class="field-error"><?= htmlspecialchars($errors['shipping_city'][0], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif ?>
        </div>

        <div class="field">
            <label for="shipping_country">Pays (code ISO)</label>
            <input class="input<?= isset($errors['shipping_country']) ? ' is-invalid' : '' ?>"
                   id="shipping_country" name="shipping_country" maxlength="2" required
                   value="<?= $val('shipping_country') ?>">
            <?php if (isset($errors['shipping_country'])): ?>
                <p class="field-error"><?= htmlspecialchars($errors['shipping_country'][0], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif ?>
        </div>

        <h2 class="section-title">Adresse de facturation</h2>
        <label class="payment-method-option" style="margin-bottom:1rem" for="billing_same_as_shipping">
            <input type="hidden" name="billing_same_as_shipping" value="0">
            <input type="checkbox" id="billing_same_as_shipping" name="billing_same_as_shipping" value="1"
                   <?= $billingSame ? 'checked' : '' ?>>
            <span class="payment-method-option__body">
                <strong>Identique à la livraison</strong>
                <span>Case cochée par défaut — décochez pour saisir une adresse différente.</span>
            </span>
        </label>

        <div id="billing-fields" style="<?= $billingSame ? 'display:none' : '' ?>">
            <div class="field">
                <label for="billing_name">Nom complet</label>
                <input class="input<?= isset($errors['billing_name']) ? ' is-invalid' : '' ?>"
                       id="billing_name" name="billing_name"
                       value="<?= $val('billing_name') ?>">
                <?php if (isset($errors['billing_name'])): ?>
                    <p class="field-error"><?= htmlspecialchars($errors['billing_name'][0], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif ?>
            </div>
            <div class="field">
                <label for="billing_line1">Adresse</label>
                <input class="input<?= isset($errors['billing_line1']) ? ' is-invalid' : '' ?>"
                       id="billing_line1" name="billing_line1"
                       value="<?= $val('billing_line1') ?>">
                <?php if (isset($errors['billing_line1'])): ?>
                    <p class="field-error"><?= htmlspecialchars($errors['billing_line1'][0], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif ?>
            </div>
            <div class="field">
                <label for="billing_postal">Code postal</label>
                <input class="input<?= isset($errors['billing_postal']) ? ' is-invalid' : '' ?>"
                       id="billing_postal" name="billing_postal"
                       value="<?= $val('billing_postal') ?>">
                <?php if (isset($errors['billing_postal'])): ?>
                    <p class="field-error"><?= htmlspecialchars($errors['billing_postal'][0], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif ?>
            </div>
            <div class="field">
                <label for="billing_city">Ville</label>
                <input class="input<?= isset($errors['billing_city']) ? ' is-invalid' : '' ?>"
                       id="billing_city" name="billing_city"
                       value="<?= $val('billing_city') ?>">
                <?php if (isset($errors['billing_city'])): ?>
                    <p class="field-error"><?= htmlspecialchars($errors['billing_city'][0], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif ?>
            </div>
            <div class="field">
                <label for="billing_country">Pays (code ISO)</label>
                <input class="input<?= isset($errors['billing_country']) ? ' is-invalid' : '' ?>"
                       id="billing_country" name="billing_country" maxlength="2"
                       value="<?= $val('billing_country') ?>">
                <?php if (isset($errors['billing_country'])): ?>
                    <p class="field-error"><?= htmlspecialchars($errors['billing_country'][0], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif ?>
            </div>
        </div>

        <label class="payment-method-option" style="margin:0.5rem 0 1rem" for="save_to_profile">
            <input type="checkbox" id="save_to_profile" name="save_to_profile" value="1" checked>
            <span class="payment-method-option__body">
                <strong>Enregistrer dans mon profil</strong>
                <span>Pour les prochaines commandes.</span>
            </span>
        </label>

        <p class="product-detail__note" style="margin:0">
            Étape suivante : choix du mode de livraison (forfait ou retrait).
        </p>

        <button type="submit" class="btn-block">Continuer vers la livraison</button>
    </form>
</section>
<script>
(function () {
    var same = document.getElementById('billing_same_as_shipping');
    var box = document.getElementById('billing-fields');
    if (!same || !box) return;
    function sync() {
        box.style.display = same.checked ? 'none' : '';
        box.querySelectorAll('input').forEach(function (el) {
            el.required = !same.checked;
        });
    }
    same.addEventListener('change', sync);
    sync();
})();
</script>
