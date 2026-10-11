<?php
/** @var list<array{product_id: int, qty: int, product: ?\App\Models\Product, unit_price_cents: int, line_total_cents: int, available: bool, message: ?string}> $lines */
/** @var array<string, mixed> $shop */
/** @var list<string> $methods */
/** @var array<string, list<string>> $errors */
/** @var array<string, mixed> $old */
/** @var int $subtotal */
/** @var int $shipping */
/** @var int $total */
/** @var \AstralShipping\Models\ShippingQuote $quote */
use App\Models\Order;
use App\Support\Money;

$symbol = (string) ($shop['currency_symbol'] ?? '€');
$selected = (string) ($old['payment_method'] ?? Order::PAYMENT_CARD);
$shipLabel = $quote->isFree()
    ? 'Gratuit'
    : Money::formatCents($shipping, $symbol);

$labels = [
    Order::PAYMENT_CARD     => ['Carte bancaire', 'Paiement sécurisé via Stripe (immédiat).'],
    Order::PAYMENT_TRANSFER => ['Virement bancaire', 'Vous recevrez l’IBAN et la référence commande.'],
    Order::PAYMENT_CHECK    => ['Chèque', 'Instructions pour l’ordre et l’adresse d’envoi.'],
];
?>
<section class="admin-panel" style="max-width:40rem">
    <h1>Moyen de paiement</h1>
    <p class="admin-nav"><a href="/checkout/shipping">← Livraison</a></p>

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
        <p style="margin:0.85rem 0 0;font-size:0.9rem;color:var(--muted)">
            Sous-total :
            <strong style="color:var(--text)"><?= htmlspecialchars(Money::formatCents($subtotal, $symbol), ENT_QUOTES, 'UTF-8') ?></strong><br>
            Livraison (<?= htmlspecialchars($quote->label, ENT_QUOTES, 'UTF-8') ?>) :
            <strong style="color:var(--text)"><?= htmlspecialchars($shipLabel, ENT_QUOTES, 'UTF-8') ?></strong>
        </p>
        <p class="cart-summary__total" style="margin-top:0.85rem">
            Total TTC :
            <strong><?= htmlspecialchars(Money::formatCents($total, $symbol), ENT_QUOTES, 'UTF-8') ?></strong>
        </p>
    </div>

    <form method="POST" action="/checkout/payment" class="auth-card form-stack">
        <?= $csrf->field() ?>

        <fieldset class="payment-methods" style="border:0;margin:0;padding:0">
            <legend style="font-weight:600;margin-bottom:0.75rem">Choisissez votre mode de paiement</legend>

            <?php if (isset($errors['payment_method'])): ?>
                <p class="field-error" style="margin-bottom:0.75rem"><?= htmlspecialchars($errors['payment_method'][0], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif ?>

            <?php foreach ($methods as $code): ?>
                <?php
                $meta = $labels[$code] ?? [$code, ''];
                $inputId = 'pay_' . $code;
                ?>
                <label class="payment-method-option<?= $selected === $code ? ' is-selected' : '' ?>" for="<?= htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="radio"
                           id="<?= htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8') ?>"
                           name="payment_method"
                           value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"
                           <?= $selected === $code ? 'checked' : '' ?>
                           required>
                    <span class="payment-method-option__body">
                        <strong><?= htmlspecialchars($meta[0], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars($meta[1], ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                </label>
            <?php endforeach ?>
        </fieldset>

        <button type="submit" class="btn-block">Confirmer et payer</button>
        <p class="product-detail__note" style="margin:0.75rem 0 0">
            La commande n’est enregistrée qu’à cette étape. Vous pouvez encore
            <a href="/checkout/shipping">modifier la livraison</a>
            ou <a href="/checkout">l’adresse</a>.
        </p>
    </form>
</section>
