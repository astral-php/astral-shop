<?php
/** @var \App\Models\Order $order */
/** @var list<\App\Models\OrderItem> $items */
/** @var array<string, mixed> $shop */
/** @var array<string, mixed>|null $shipment */
/** @var bool $confirming */
$symbol = (string) ($shop['currency_symbol'] ?? '€');
$confirming = !empty($confirming);
$shipment = is_array($shipment ?? null) ? $shipment : null;
$storeName = (string) ($shop['store_name'] ?? 'Boutique');
$isPickup = $order->isStorePickup()
    || (($shipment['method_code'] ?? '') === 'store_pickup')
    || (($shipment['carrier'] ?? '') === 'store');
if ($isPickup && ($order->shipping_method_code === null || $order->shipping_method_code === '')) {
    $order->shipping_method_code = 'store_pickup';
}
$storeAddress = trim((string) ($shop['store_address'] ?? ''));
?>
<section class="admin-panel">
    <div class="admin-panel__head">
        <h1>Commande #<?= (int) $order->id ?></h1>
        <div style="display:flex;flex-wrap:wrap;gap:0.5rem">
            <?php if ($order->isPaid()): ?>
                <a class="btn btn-primary" href="/orders/<?= (int) $order->id ?>/invoice">Télécharger la facture</a>
            <?php endif ?>
            <a class="btn btn-ghost" href="/orders">← Mes commandes</a>
        </div>
    </div>

    <?php if ($confirming && $order->isPending()): ?>
        <p id="order-confirming" class="flash-ok" style="margin-bottom:1rem">
            Paiement accepté — confirmation automatique en cours…
        </p>
    <?php endif ?>

    <p style="margin:0 0 1rem">
        <strong>Date :</strong>
        <?= htmlspecialchars($order->created_at, ENT_QUOTES, 'UTF-8') ?><br>
        <strong>Paiement :</strong>
        <span id="order-payment-badge" class="<?= htmlspecialchars($order->paymentStateBadgeClass(), ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($order->paymentStateLabel(), ENT_QUOTES, 'UTF-8') ?>
        </span>
        <span class="badge-muted"><?= htmlspecialchars($order->paymentMethodLabel(), ENT_QUOTES, 'UTF-8') ?></span>
        <?php if ($order->paymentReference() !== null): ?>
            <span class="badge-muted">Réf. <?= htmlspecialchars($order->paymentReference(), ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif ?>
        <?php if ($order->refundRequestLabel() !== ''): ?>
            <br>
            <strong>Remboursement :</strong>
            <span class="status-badge status-badge--pending">
                <?= htmlspecialchars($order->refundRequestLabel(), ENT_QUOTES, 'UTF-8') ?>
            </span>
        <?php endif ?>
        <?php if ($order->returnStatusLabel() !== ''): ?>
            <br>
            <strong>Retour :</strong>
            <span class="status-badge <?= $order->isReturnPending() ? 'status-badge--pending' : ($order->isReturnReceived() || $order->isReturnWaived() ? 'status-badge--shipped' : 'status-badge--failed') ?>">
                <?= htmlspecialchars($order->returnStatusLabel(), ENT_QUOTES, 'UTF-8') ?>
            </span>
        <?php endif ?>
        <br>
        <strong>Statut :</strong>
        <span id="order-status-badge" class="<?= htmlspecialchars($order->fulfillmentBadgeClass(), ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($order->fulfillmentLabel(), ENT_QUOTES, 'UTF-8') ?>
        </span>
    </p>

    <?php if ($order->isPaid()): ?>
        <p style="margin:0 0 1rem;padding:0.75rem 1rem;background:#eef2ff;border-radius:8px;font-size:0.9rem">
            Votre facture PDF est disponible —
            <a href="/orders/<?= (int) $order->id ?>/invoice">télécharger</a>.
        </p>
    <?php endif ?>

    <article class="order-invoice-sheet order-invoice-sheet--client">
        <header class="order-invoice-sheet__header">
            <div>
                <p class="order-invoice-sheet__eyebrow">Votre commande</p>
                <h2 class="order-invoice-sheet__title"><?= htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8') ?></h2>
            </div>
            <div class="order-invoice-sheet__meta">
                <strong>CMD-<?= (int) $order->id ?></strong>
                <span><?= htmlspecialchars($order->created_at, ENT_QUOTES, 'UTF-8') ?></span>
                <?php if ($order->hasInvoice()): ?>
                    <a href="/orders/<?= (int) $order->id ?>/invoice">Télécharger le PDF</a>
                <?php endif ?>
            </div>
        </header>

        <div class="order-invoice-sheet__parties order-invoice-sheet__parties--client">
            <div>
                <h3 class="order-invoice-sheet__label">Facturation</h3>
                <p class="order-invoice-sheet__text">
                    <?php if ($order->billingSameAsShippingSnapshot()): ?>
                        <?= htmlspecialchars($order->billingName(), ENT_QUOTES, 'UTF-8') ?><br>
                        <?= htmlspecialchars($order->billingLine1(), ENT_QUOTES, 'UTF-8') ?><br>
                        <?= htmlspecialchars($order->billingPostal() . ' ' . $order->billingCity(), ENT_QUOTES, 'UTF-8') ?><br>
                        <?= htmlspecialchars($order->billingCountry(), ENT_QUOTES, 'UTF-8') ?>
                        <br><span class="order-invoice-sheet__muted">Identique à la livraison</span>
                    <?php else: ?>
                        <?= htmlspecialchars($order->billingName(), ENT_QUOTES, 'UTF-8') ?><br>
                        <?= htmlspecialchars($order->billingLine1(), ENT_QUOTES, 'UTF-8') ?><br>
                        <?= htmlspecialchars($order->billingPostal() . ' ' . $order->billingCity(), ENT_QUOTES, 'UTF-8') ?><br>
                        <?= htmlspecialchars($order->billingCountry(), ENT_QUOTES, 'UTF-8') ?>
                    <?php endif ?>
                </p>
            </div>
            <div class="order-invoice-sheet__ship">
                <h3 class="order-invoice-sheet__label"><?= $isPickup ? 'Retrait' : 'Livraison' ?></h3>
                <p class="order-invoice-sheet__text">
                    <?php if ($shipment !== null): ?>
                        <strong><?= htmlspecialchars((string) ($shipment['label'] ?? 'Livraison'), ENT_QUOTES, 'UTF-8') ?></strong><br>
                        <?php
                        $relayLabel = trim((string) ($shipment['pickup_point_label'] ?? ''));
                        $relayId = trim((string) ($shipment['pickup_point_id'] ?? ''));
                        ?>
                        <?php if ($relayLabel !== '' || $relayId !== ''): ?>
                            <span class="shipping-pickup-hint" style="margin:0.35rem 0 0.55rem;display:inline-block;max-width:100%">
                                <span class="shipping-pickup-hint__label">Point relais</span>
                                <?= htmlspecialchars($relayLabel !== '' ? $relayLabel : $relayId, ENT_QUOTES, 'UTF-8') ?>
                                <?php if ($relayLabel !== '' && $relayId !== ''): ?>
                                    <span class="order-invoice-sheet__muted">(<?= htmlspecialchars($relayId, ENT_QUOTES, 'UTF-8') ?>)</span>
                                <?php endif ?>
                            </span><br>
                        <?php endif ?>
                    <?php endif ?>
                    <?php if ($isPickup && $storeAddress !== ''): ?>
                        <span class="shipping-pickup-hint" style="margin:0.35rem 0 0.55rem;display:inline-block;max-width:100%">
                            <span class="shipping-pickup-hint__label">Adresse du magasin</span>
                            <?= htmlspecialchars($storeAddress, ENT_QUOTES, 'UTF-8') ?>
                        </span><br>
                    <?php endif ?>
                    <?= htmlspecialchars($order->shipping_name, ENT_QUOTES, 'UTF-8') ?><br>
                    <?= htmlspecialchars($order->shipping_line1, ENT_QUOTES, 'UTF-8') ?><br>
                    <?= htmlspecialchars($order->shipping_postal . ' ' . $order->shipping_city, ENT_QUOTES, 'UTF-8') ?><br>
                    <?= htmlspecialchars($order->shipping_country, ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
        </div>

        <div class="order-invoice-sheet__lines">
            <table class="order-invoice-sheet__table">
                <thead>
                    <tr>
                        <th>Désignation</th>
                        <th class="order-invoice-sheet__num">Prix TTC</th>
                        <th class="order-invoice-sheet__num">Qté</th>
                        <th class="order-invoice-sheet__num">Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item->product_name, ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="order-invoice-sheet__num"><?= htmlspecialchars($item->unitLabel($symbol), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="order-invoice-sheet__num"><?= (int) $item->quantity ?></td>
                        <td class="order-invoice-sheet__num"><?= htmlspecialchars($item->lineLabel($symbol), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <footer class="order-invoice-sheet__totals">
            <div class="order-invoice-sheet__totals-rows">
                <div>
                    <span>Sous-total TTC</span>
                    <strong><?= htmlspecialchars($order->subtotalLabel($symbol), ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div>
                    <span>Livraison</span>
                    <strong><?= htmlspecialchars($order->shippingLabel($symbol), ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div class="order-invoice-sheet__totals-grand">
                    <span>Total TTC</span>
                    <strong><?= htmlspecialchars($order->totalLabel($symbol), ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            </div>
        </footer>
    </article>

    <?php if ($order->isOpen()): ?>
        <div id="order-pay-cta" style="margin-top:1.25rem<?= $confirming && $order->isPending() ? ';display:none' : '' ?>">
            <?php if ($order->payment_method === \App\Models\Order::PAYMENT_CARD): ?>
                <p style="color:var(--muted);font-size:0.9rem;margin:0 0 0.75rem">
                    Finalisez le paiement dans la boutique, ou changez de moyen.
                </p>
                <div style="display:flex;flex-wrap:wrap;gap:0.65rem">
                    <a class="btn btn-primary" href="/payment/checkout?order_id=<?= (int) $order->id ?>">
                        Continuer le paiement carte
                    </a>
                    <a class="btn btn-ghost" href="/checkout/change-payment/<?= (int) $order->id ?>">
                        Changer de moyen
                    </a>
                </div>
            <?php else: ?>
                <a class="btn btn-primary" href="/checkout/instructions/<?= (int) $order->id ?>">
                    Voir les instructions de paiement
                </a>
            <?php endif ?>
        </div>
    <?php endif ?>

    <?php if ($order->hasRefundRequestPending() || $order->isReturnPending() || $order->isReturnReceived() || $order->isReturnWaived() || $order->isReturnExpired()): ?>
        <?php if ($order->hasRefundRequestPending()): ?>
        <div class="order-refund-request" style="margin-top:1.5rem;padding:1rem 1.15rem;border:1px solid var(--line,#e2e8f0);border-radius:0.85rem">
            <h2 class="section-title" style="margin-top:0">Demande de remboursement</h2>
            <p style="margin:0 0 0.5rem;color:var(--muted);font-size:0.9rem">
                Votre demande est en cours d’examen
                <?php if ($order->refund_request_at): ?>
                    (déposée le <?= htmlspecialchars($order->refund_request_at, ENT_QUOTES, 'UTF-8') ?>)
                <?php endif ?>.
                <?php if ($order->isReturnPending()): ?>
                    Renvoyez le colis selon les consignes ci-dessous — le remboursement intervient après réception.
                <?php endif ?>
            </p>
            <p style="margin:0;font-size:0.9rem">
                <strong>Motif :</strong>
                <?= htmlspecialchars($order->refund_request_reason, ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>
        <?php endif ?>
        <?php require __DIR__ . '/_return_panel.php'; ?>
    <?php endif ?>

    <?php if ($order->hasRefundRequestAccepted() && !$order->isRefunded()): ?>
        <div class="order-refund-request" style="margin-top:1.5rem;padding:1rem 1.15rem;border:1px solid var(--line,#e2e8f0);border-radius:0.85rem">
            <h2 class="section-title" style="margin-top:0">Demande acceptée</h2>
            <p style="margin:0;color:var(--muted);font-size:0.9rem">
                Votre demande a été acceptée. Le remboursement est en cours de traitement.
            </p>
        </div>
    <?php elseif ($order->hasRefundRequestRejected() && $order->canRequestRefund()): ?>
        <div class="order-refund-request" style="margin-top:1.5rem;padding:1rem 1.15rem;border:1px solid var(--line,#e2e8f0);border-radius:0.85rem">
            <h2 class="section-title" style="margin-top:0">Demande refusée</h2>
            <?php if (trim($order->refund_reject_reason) !== ''): ?>
                <p style="margin:0 0 0.75rem;font-size:0.9rem">
                    <strong>Motif :</strong>
                    <?= htmlspecialchars($order->refund_reject_reason, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif ?>
            <p style="margin:0 0 1rem;color:var(--muted);font-size:0.9rem">
                Vous pouvez déposer une nouvelle demande si besoin.
            </p>
            <?php require __DIR__ . '/_refund_request_form.php'; ?>
        </div>
    <?php elseif ($order->canRequestRefund()): ?>
        <div class="order-refund-request" style="margin-top:1.5rem;padding:1rem 1.15rem;border:1px solid var(--line,#e2e8f0);border-radius:0.85rem">
            <h2 class="section-title" style="margin-top:0">Demander un remboursement</h2>
            <p style="margin:0 0 1rem;color:var(--muted);font-size:0.9rem">
                Remboursement du montant total de la commande.
                <?php if ($order->wasShippedOrPickedUp()): ?>
                    Si le colis a été reçu, un retour sera demandé avant remboursement
                    (<a href="/retours">politique de retour</a>).
                <?php endif ?>
                <?php if ($order->isOfflinePayment()): ?>
                    Indiquez votre RIB pour recevoir le virement.
                <?php else: ?>
                    Le crédit sera effectué sur la carte utilisée au paiement.
                <?php endif ?>
            </p>
            <?php require __DIR__ . '/_refund_request_form.php'; ?>
        </div>
    <?php endif ?>
</section>

<?php if ($confirming && $order->isPending()): ?>
<script>
(function () {
    var orderId = <?= (int) $order->id ?>;
    var tries = 0;
    var maxTries = 20;
    var badge = document.getElementById('order-status-badge');
    var banner = document.getElementById('order-confirming');

    function poll() {
        tries += 1;
        fetch('/orders/' + orderId + '/status', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.paid) {
                    var payBadge = document.getElementById('order-payment-badge');
                    if (payBadge) {
                        payBadge.className = data.payment_badge || 'status-badge status-badge--paid';
                        payBadge.textContent = data.payment_label || 'Payée';
                    }
                    if (badge) {
                        badge.className = data.fulfillment_badge || 'status-badge status-badge--awaiting';
                        badge.textContent = data.fulfillment_label || 'En attente';
                    }
                    if (banner) {
                        banner.textContent = 'Paiement confirmé. Merci pour votre commande !';
                    }
                    window.setTimeout(function () {
                        window.location.href = '/orders/' + orderId;
                    }, 600);
                    return;
                }
                if (tries < maxTries) {
                    window.setTimeout(poll, 1000);
                } else if (banner) {
                    banner.textContent = 'Confirmation un peu longue — rechargez la page dans un instant.';
                }
            })
            .catch(function () {
                if (tries < maxTries) {
                    window.setTimeout(poll, 1500);
                }
            });
    }

    window.setTimeout(poll, 500);
})();
</script>
<?php endif ?>
