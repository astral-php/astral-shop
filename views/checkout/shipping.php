<?php
/** @var list<array{product_id: int, qty: int, product: ?\App\Models\Product, unit_price_cents: int, line_total_cents: int, available: bool, message: ?string}> $lines */
/** @var array<string, mixed> $shop */
/** @var list<\AstralShipping\Models\ShippingQuote> $quotes */
/** @var array<string, list<string>> $errors */
/** @var string $selected */
/** @var int $subtotal */
use App\Support\Money;

$symbol = (string) ($shop['currency_symbol'] ?? '€');
$selected = (string) ($selected ?? '');
?>
<section class="admin-panel" style="max-width:40rem">
    <h1>Livraison</h1>
    <p class="admin-nav"><a href="/checkout">← Adresse</a></p>

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

    <form method="POST" action="/checkout/shipping" class="auth-card form-stack">
        <?= $csrf->field() ?>

        <fieldset class="payment-methods" style="border:0;margin:0;padding:0">
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
                $hint = $quote->estimatedDays !== null
                    ? 'Délai estimé : ' . (int) $quote->estimatedDays . ' jour(s)'
                    : ($quote->code === 'store_pickup' ? 'À retirer en magasin' : 'Livraison à domicile');
                ?>
                <label class="payment-method-option<?= $selected === $quote->code ? ' is-selected' : '' ?>"
                       for="<?= htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="radio"
                           id="<?= htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8') ?>"
                           name="shipping_method"
                           value="<?= htmlspecialchars($quote->code, ENT_QUOTES, 'UTF-8') ?>"
                           <?= $selected === $quote->code ? 'checked' : '' ?>
                           required>
                    <span class="payment-method-option__body">
                        <strong><?= htmlspecialchars($quote->label, ENT_QUOTES, 'UTF-8') ?>
                            — <?= htmlspecialchars($priceLabel, ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                </label>
            <?php endforeach ?>
        </fieldset>

        <button type="submit" class="btn-block">Continuer vers le paiement</button>
        <p class="product-detail__note" style="margin:0.75rem 0 0">
            Le total final (articles + livraison) sera confirmé à l’étape suivante.
        </p>
    </form>
</section>
