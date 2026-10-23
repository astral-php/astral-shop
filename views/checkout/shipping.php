<?php
/** @var list<array{product_id: int, qty: int, product: ?\App\Models\Product, unit_price_cents: int, line_total_cents: int, available: bool, message: ?string}> $lines */
/** @var array<string, mixed> $shop */
/** @var list<\AstralShipping\Models\ShippingQuote> $quotes */
/** @var array<string, list<string>> $errors */
/** @var string $selected */
/** @var int $subtotal */
/** @var string $pickup_point_id */
/** @var string $pickup_point_label */
use App\Support\Money;

$symbol = (string) ($shop['currency_symbol'] ?? '€');
$selected = (string) ($selected ?? '');
$storeAddress = trim((string) ($shop['store_address'] ?? ''));
$pickupPointId = (string) ($pickup_point_id ?? '');
$pickupPointLabel = (string) ($pickup_point_label ?? '');
?>
<section class="admin-panel" style="max-width:40rem">
    <h1>Livraison</h1>
    <?php
    $checkoutStep = 'livraison';
    require dirname(__DIR__) . '/partials/checkout-steps.php';
    ?>
    <p class="admin-nav"><a href="/checkout">← Identification</a></p>

    <div class="cart-summary" style="margin-bottom:1.25rem">
        <p style="margin:0 0 0.75rem;font-weight:600">Articles</p>
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
        <p style="margin:0.85rem 0 0;color:var(--muted);font-size:0.9rem">
            Sous-total TTC :
            <strong style="color:var(--text)"><?= htmlspecialchars(Money::formatCents($subtotal, $symbol), ENT_QUOTES, 'UTF-8') ?></strong>
        </p>
    </div>

    <form method="POST" action="/checkout/shipping" class="auth-card form-stack" id="checkout-shipping-form">
        <?= $csrf->field() ?>

        <fieldset class="payment-methods" style="border:0;margin:0;padding:0" id="shipping-options">
            <legend style="font-weight:600;margin-bottom:0.75rem">Mode de livraison</legend>

            <?php if (isset($errors['shipping_method'])): ?>
                <p class="field-error" style="margin-bottom:0.75rem"><?= htmlspecialchars($errors['shipping_method'][0], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif ?>

            <?php foreach ($quotes as $quote): ?>
                <?php
                $inputId = 'ship_' . preg_replace('/[^a-z0-9_]+/i', '_', $quote->code);
                $priceLabel = $quote->isFree()
                    ? 'Gratuit'
                    : Money::formatCents($quote->priceCents, $symbol);
                $isStorePickup = $quote->code === 'store_pickup';
                $needsRelay = $quote->requiresPickupPoint;
                if ($quote->estimatedDays !== null) {
                    $hint = 'Délai estimé : ' . (int) $quote->estimatedDays . ' jour(s)';
                } elseif ($isStorePickup) {
                    $hint = 'Gratuit — à venir chercher en magasin';
                } elseif ($needsRelay) {
                    $hint = 'Choisissez un point relais ci-dessous';
                } else {
                    $hint = 'Livraison à domicile';
                }
                ?>
                <label class="payment-method-option<?= $selected === $quote->code ? ' is-selected' : '' ?>"
                       for="<?= htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="radio"
                           id="<?= htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8') ?>"
                           name="shipping_method"
                           value="<?= htmlspecialchars($quote->code, ENT_QUOTES, 'UTF-8') ?>"
                           data-requires-pickup="<?= $needsRelay ? '1' : '0' ?>"
                           <?= $selected === $quote->code ? 'checked' : '' ?>
                           required>
                    <span class="payment-method-option__body">
                        <strong><?= htmlspecialchars($quote->label, ENT_QUOTES, 'UTF-8') ?>
                            — <?= htmlspecialchars($priceLabel, ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if ($isStorePickup && $storeAddress !== ''): ?>
                            <span class="shipping-pickup-hint">
                                <span class="shipping-pickup-hint__label">Adresse du magasin</span>
                                <?= htmlspecialchars($storeAddress, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif ?>
                    </span>
                </label>
            <?php endforeach ?>
        </fieldset>

        <input type="hidden" name="pickup_point_id" id="pickup_point_id"
               value="<?= htmlspecialchars($pickupPointId, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="pickup_point_label" id="pickup_point_label"
               value="<?= htmlspecialchars($pickupPointLabel, ENT_QUOTES, 'UTF-8') ?>">

        <div id="pickup-widget" class="auth-card" style="display:none;margin-top:1rem;padding:1rem">
            <p style="margin:0 0 0.5rem;font-weight:600">Point relais</p>
            <p class="hint" style="margin:0 0 0.75rem;font-size:0.85rem;color:var(--muted)">
                Branchez ici le widget officiel Colissimo / Mondial Relay
                (voir doc package <code>widgets-pickup.md</code>).
                En démo, saisissez un identifiant de test.
            </p>
            <div class="field">
                <label for="pickup_point_id_demo">Identifiant du point</label>
                <input class="input" type="text" id="pickup_point_id_demo"
                       value="<?= htmlspecialchars($pickupPointId, ENT_QUOTES, 'UTF-8') ?>"
                       autocomplete="off" placeholder="ex. 012345">
            </div>
            <div class="field">
                <label for="pickup_point_label_demo">Libellé affiché</label>
                <input class="input" type="text" id="pickup_point_label_demo"
                       value="<?= htmlspecialchars($pickupPointLabel, ENT_QUOTES, 'UTF-8') ?>"
                       autocomplete="off" placeholder="ex. Tabac — 10 rue…">
            </div>
        </div>

        <button type="submit" class="btn-block">Continuer vers le paiement</button>
        <p class="product-detail__note" style="margin:0.75rem 0 0">
            Le total final (articles + livraison) sera confirmé à l’étape suivante.
        </p>
    </form>
</section>
<script>
(function () {
    var form = document.getElementById('checkout-shipping-form');
    if (!form) return;
    var widget = document.getElementById('pickup-widget');
    var idField = document.getElementById('pickup_point_id');
    var labelField = document.getElementById('pickup_point_label');
    var idDemo = document.getElementById('pickup_point_id_demo');
    var labelDemo = document.getElementById('pickup_point_label_demo');

    function syncPickupVisibility() {
        var selected = form.querySelector('input[name="shipping_method"]:checked');
        var needs = selected && selected.getAttribute('data-requires-pickup') === '1';
        widget.style.display = needs ? 'block' : 'none';
        if (!needs) {
            idField.value = '';
            labelField.value = '';
            if (idDemo) idDemo.value = '';
            if (labelDemo) labelDemo.value = '';
        }
    }

    function syncDemoToHidden() {
        if (idDemo) idField.value = idDemo.value.trim();
        if (labelDemo) labelField.value = labelDemo.value.trim();
    }

    form.addEventListener('change', function (e) {
        if (e.target && e.target.name === 'shipping_method') {
            syncPickupVisibility();
        }
    });
    if (idDemo) idDemo.addEventListener('input', syncDemoToHidden);
    if (labelDemo) labelDemo.addEventListener('input', syncDemoToHidden);
    form.addEventListener('submit', syncDemoToHidden);
    syncPickupVisibility();
})();
</script>
