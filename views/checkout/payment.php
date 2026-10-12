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
    Order::PAYMENT_CARD     => [
        'Carte bancaire',
        'Paiement immédiat et sécurisé (Stripe). Confirmation dès validation bancaire.',
    ],
    Order::PAYMENT_TRANSFER => [
        'Virement bancaire',
        'Votre commande est enregistrée tout de suite ; vous recevez l’IBAN et la référence. Paiement validé à réception du virement.',
    ],
    Order::PAYMENT_CHECK    => [
        'Chèque',
        'Votre commande est enregistrée tout de suite ; vous recevez l’ordre et l’adresse. Paiement validé à encaissement.',
    ],
];

$submitLabels = [
    Order::PAYMENT_CARD     => 'Payer par carte',
    Order::PAYMENT_TRANSFER => 'Confirmer ma commande',
    Order::PAYMENT_CHECK    => 'Confirmer ma commande',
];
$submitLabel = $submitLabels[$selected] ?? 'Confirmer';
?>
<section class="admin-panel" style="max-width:40rem">
    <h1>Moyen de paiement</h1>
    <?php
    $checkoutStep = 'validation';
    $checkoutBusy = false;
    require dirname(__DIR__) . '/partials/checkout-steps.php';
    ?>
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

    <form method="POST" action="/checkout/payment" class="auth-card form-stack" id="checkout-payment-form">
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
                           data-submit-label="<?= htmlspecialchars($submitLabels[$code] ?? 'Confirmer', ENT_QUOTES, 'UTF-8') ?>"
                           data-is-offline="<?= $code === Order::PAYMENT_CARD ? '0' : '1' ?>"
                           <?= $selected === $code ? 'checked' : '' ?>
                           required>
                    <span class="payment-method-option__body">
                        <strong><?= htmlspecialchars($meta[0], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars($meta[1], ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                </label>
            <?php endforeach ?>
        </fieldset>

        <p class="checkout-commit-note" id="checkout-commit-note" style="margin:1rem 0 0;font-size:0.9rem;color:var(--muted);line-height:1.5">
            <?php if ($selected === Order::PAYMENT_CARD): ?>
                Vous serez redirigé vers le paiement sécurisé. La commande n’est confirmée qu’après validation bancaire.
            <?php else: ?>
                En confirmant, votre commande est <strong style="color:var(--text)">enregistrée définitivement</strong>
                (adresse et livraison figées). Vous ne pourrez plus revenir en arrière dans le tunnel :
                vous recevrez les instructions de paiement, le règlement sera validé à réception.
            <?php endif ?>
        </p>

        <button type="submit" class="btn-block" id="checkout-payment-submit"><?= htmlspecialchars($submitLabel, ENT_QUOTES, 'UTF-8') ?></button>
        <p class="product-detail__note" style="margin:0.75rem 0 0">
            Avant confirmation, vous pouvez encore
            <a href="/checkout/shipping">modifier la livraison</a>
            ou <a href="/checkout">l’adresse</a>.
        </p>
    </form>
</section>
<script>
(function () {
    var form = document.getElementById('checkout-payment-form');
    var btn = document.getElementById('checkout-payment-submit');
    var note = document.getElementById('checkout-commit-note');
    var steps = document.querySelector('.checkout-steps');
    if (!form || !btn) return;

    var noteCard = 'Vous serez redirigé vers le paiement sécurisé. La commande n’est confirmée qu’après validation bancaire.';
    var noteOffline = 'En confirmant, votre commande est <strong style="color:var(--text)">enregistrée définitivement</strong> (adresse et livraison figées). Vous ne pourrez plus revenir en arrière dans le tunnel : vous recevrez les instructions de paiement, le règlement sera validé à réception.';

    function selectedInput() {
        return form.querySelector('input[name="payment_method"]:checked');
    }

    function syncUi() {
        var input = selectedInput();
        if (!input) return;
        btn.textContent = input.getAttribute('data-submit-label') || 'Confirmer';
        if (note) {
            note.innerHTML = input.getAttribute('data-is-offline') === '1' ? noteOffline : noteCard;
        }
        form.querySelectorAll('.payment-method-option').forEach(function (label) {
            label.classList.toggle('is-selected', label.contains(input));
        });
    }

    form.querySelectorAll('input[name="payment_method"]').forEach(function (radio) {
        radio.addEventListener('change', syncUi);
    });
    syncUi();

    form.addEventListener('submit', function () {
        btn.disabled = true;
        btn.setAttribute('aria-busy', 'true');
        btn.textContent = 'Validation en cours…';
        if (steps) {
            var current = steps.querySelector('.checkout-steps__item.is-current .checkout-steps__label');
            if (current && !current.querySelector('.checkout-steps__spinner')) {
                var spin = document.createElement('span');
                spin.className = 'checkout-steps__spinner';
                spin.setAttribute('aria-hidden', 'true');
                current.appendChild(spin);
            }
            steps.classList.add('is-busy');
        }
    });
})();
</script>
