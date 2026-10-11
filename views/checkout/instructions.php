<?php
/** @var \App\Models\Order $order */
/** @var array<string, mixed> $shop */
use App\Models\Order;
use App\Support\Money;

$symbol = (string) ($shop['currency_symbol'] ?? '€');
$payment = is_array($shop['payment'] ?? null) ? $shop['payment'] : [];
$transfer = is_array($payment['transfer'] ?? null) ? $payment['transfer'] : [];
$check = is_array($payment['check'] ?? null) ? $payment['check'] : [];
$ref = $order->paymentReference() ?? ('CMD-' . $order->id);
?>
<section class="admin-panel" style="max-width:40rem">
    <div class="admin-panel__head">
        <h1>Paiement commande #<?= (int) $order->id ?></h1>
        <a class="btn btn-ghost" href="/boutique">Boutique</a>
    </div>

    <p style="margin:0 0 1rem;display:flex;flex-wrap:wrap;gap:0.5rem;align-items:center">
        <span class="<?= htmlspecialchars($order->paymentStateBadgeClass(), ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($order->paymentStateLabel(), ENT_QUOTES, 'UTF-8') ?>
        </span>
        <span class="badge-muted"><?= htmlspecialchars($order->paymentMethodLabel(), ENT_QUOTES, 'UTF-8') ?></span>
    </p>

    <p class="cart-summary__total" style="margin-bottom:1.25rem">
        Montant à régler :
        <strong><?= htmlspecialchars($order->totalLabel($symbol), ENT_QUOTES, 'UTF-8') ?></strong>
    </p>

    <?php if ($order->payment_method === Order::PAYMENT_TRANSFER): ?>
        <div class="order-detail-card" style="margin-bottom:1.25rem">
            <h2 class="section-title" style="margin-top:0">Virement bancaire</h2>
            <p style="color:var(--muted);line-height:1.55;margin:0 0 0.85rem">
                Effectuez un virement du montant exact en indiquant la <strong>référence</strong> ci-dessous.
                Dès réception, nous validerons le paiement et préparerons votre colis.
            </p>
            <dl class="pay-dl">
                <dt>Bénéficiaire</dt>
                <dd><?= htmlspecialchars((string) ($transfer['beneficiary'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd>
                <dt>IBAN</dt>
                <dd><code><?= htmlspecialchars((string) ($transfer['iban'] ?: 'À renseigner (SHOP_TRANSFER_IBAN)'), ENT_QUOTES, 'UTF-8') ?></code></dd>
                <dt>BIC</dt>
                <dd><code><?= htmlspecialchars((string) ($transfer['bic'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></code></dd>
                <dt>Référence</dt>
                <dd><code><?= htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') ?></code></dd>
                <dt>Montant</dt>
                <dd><?= htmlspecialchars(Money::formatCents($order->total_cents, $symbol), ENT_QUOTES, 'UTF-8') ?></dd>
            </dl>
        </div>
    <?php elseif ($order->payment_method === Order::PAYMENT_CHECK): ?>
        <div class="order-detail-card" style="margin-bottom:1.25rem">
            <h2 class="section-title" style="margin-top:0">Paiement par chèque</h2>
            <p style="color:var(--muted);line-height:1.55;margin:0 0 0.85rem">
                Établissez un chèque du montant exact, à l’ordre indiqué, et joignez la référence commande.
                Dès encaissement, nous validerons le paiement.
            </p>
            <dl class="pay-dl">
                <dt>Ordre</dt>
                <dd><?= htmlspecialchars((string) ($check['payable_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd>
                <dt>Adresse d’envoi</dt>
                <dd style="white-space:pre-line"><?= htmlspecialchars((string) ($check['mail_to'] ?: 'À renseigner (SHOP_CHECK_MAIL_TO)'), ENT_QUOTES, 'UTF-8') ?></dd>
                <dt>Référence à indiquer</dt>
                <dd><code><?= htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') ?></code></dd>
                <dt>Montant</dt>
                <dd><?= htmlspecialchars(Money::formatCents($order->total_cents, $symbol), ENT_QUOTES, 'UTF-8') ?></dd>
            </dl>
        </div>
    <?php endif ?>

    <div style="display:flex;flex-wrap:wrap;gap:0.65rem;align-items:center;margin-top:1rem">
        <a class="btn btn-primary" href="/orders/<?= (int) $order->id ?>">Voir ma commande</a>
        <?php if ($order->isOpen()): ?>
            <a class="btn btn-ghost" href="/checkout/change-payment/<?= (int) $order->id ?>">Changer de moyen de paiement</a>
        <?php endif ?>
    </div>
</section>
