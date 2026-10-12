<?php
/** @var \App\Models\Order $order */
/** @var list<\App\Models\OrderItem> $items */
/** @var array<string, mixed> $shop */
$symbol = (string) ($shop['currency_symbol'] ?? '€');
?>
<section class="admin-panel">
    <div class="admin-panel__head">
        <h1>Commande #<?= (int) $order->id ?></h1>
        <a class="btn btn-ghost" href="/admin/orders">← Commandes</a>
    </div>

    <div class="order-detail-grid">
        <div class="order-detail-card">
            <p style="margin:0 0 0.75rem">
                <strong>Date :</strong> <?= htmlspecialchars($order->created_at, ENT_QUOTES, 'UTF-8') ?>
            </p>
            <p style="margin:0 0 0.75rem;display:flex;flex-wrap:wrap;align-items:center;gap:0.5rem">
                <strong>Paiement :</strong>
                <span class="<?= htmlspecialchars($order->paymentStateBadgeClass(), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($order->paymentStateLabel(), ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="badge-muted"><?= htmlspecialchars($order->paymentMethodLabel(), ENT_QUOTES, 'UTF-8') ?></span>
                <?php if ($order->paymentReference() !== null): ?>
                    <span class="badge-muted">Réf. <?= htmlspecialchars($order->paymentReference(), ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif ?>
            </p>
            <p style="margin:0 0 0.75rem;display:flex;flex-wrap:wrap;align-items:center;gap:0.5rem">
                <strong>Statut colis :</strong>
                <span class="<?= htmlspecialchars($order->fulfillmentBadgeClass(), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($order->fulfillmentLabel(), ENT_QUOTES, 'UTF-8') ?>
                </span>
            </p>
            <p style="margin:0 0 0.75rem">
                <strong>Sous-total :</strong> <?= htmlspecialchars($order->subtotalLabel($symbol), ENT_QUOTES, 'UTF-8') ?><br>
                <strong>Livraison :</strong> <?= htmlspecialchars($order->shippingLabel($symbol), ENT_QUOTES, 'UTF-8') ?><br>
                <strong>Total TTC :</strong> <?= htmlspecialchars($order->totalLabel($symbol), ENT_QUOTES, 'UTF-8') ?>
                <?php if ($order->hasInvoice()): ?>
                    <br><a href="/orders/<?= (int) $order->id ?>/invoice">Facture PDF</a>
                <?php endif ?>
            </p>
            <p style="margin:0;color:var(--muted);font-size:0.9rem">
                Stock appliqué : <?= $order->hasStockApplied() ? 'Oui' : 'Non' ?>
                <?php if ($order->payment_due_at): ?>
                    <br>Échéance paiement : <?= htmlspecialchars($order->payment_due_at, ENT_QUOTES, 'UTF-8') ?>
                <?php endif ?>
                <?php if ($order->stripe_intent_id): ?>
                    <br>Stripe : <code style="font-size:0.8rem"><?= htmlspecialchars($order->stripe_intent_id, ENT_QUOTES, 'UTF-8') ?></code>
                <?php endif ?>
            </p>
        </div>

        <div class="order-actions-card">
            <h2 class="order-actions-card__title">Actions</h2>

            <div class="order-actions-card__status">
                <span class="order-actions-card__status-label">Statut colis</span>
                <span class="<?= htmlspecialchars($order->fulfillmentBadgeClass(), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($order->fulfillmentLabel(), ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>

            <?php if ($order->isPending() && in_array($order->payment_method, [\App\Models\Order::PAYMENT_TRANSFER, \App\Models\Order::PAYMENT_CHECK], true)): ?>
                <form method="POST" action="/admin/orders/<?= (int) $order->id ?>/mark-paid">
                    <?= $csrf->field() ?>
                    <button type="submit" class="btn btn-primary order-actions-card__btn">Marquer comme payée</button>
                </form>
            <?php elseif ($order->isPaid() && $order->fulfillment_status === \App\Models\Order::FULFILLMENT_AWAITING): ?>
                <form method="POST" action="/admin/orders/<?= (int) $order->id ?>/mark-preparing">
                    <?= $csrf->field() ?>
                    <button type="submit" class="btn btn-primary order-actions-card__btn">Passer en préparation</button>
                </form>
            <?php elseif ($order->isPaid() && $order->fulfillment_status === \App\Models\Order::FULFILLMENT_PREPARING): ?>
                <form method="POST" action="/admin/orders/<?= (int) $order->id ?>/mark-shipped">
                    <?= $csrf->field() ?>
                    <button type="submit" class="btn btn-primary order-actions-card__btn">Marquer comme envoyée</button>
                </form>
            <?php elseif ($order->isPaid() && $order->fulfillment_status === \App\Models\Order::FULFILLMENT_SHIPPED): ?>
                <p class="order-actions-card__hint">Colis expédié — aucune action restante.</p>
            <?php elseif (!$order->isPaid() && $order->payment_method === \App\Models\Order::PAYMENT_CARD): ?>
                <p class="order-actions-card__hint">Paiement carte : en attente de confirmation Stripe.</p>
            <?php elseif ($order->isCancelled()): ?>
                <form method="POST" action="/admin/orders/<?= (int) $order->id ?>/delete"
                      onsubmit="return confirm('Supprimer définitivement cette commande annulée ?');">
                    <?= $csrf->field() ?>
                    <button type="submit" class="btn btn-ghost order-actions-card__btn link-danger">Supprimer définitivement</button>
                </form>
            <?php else: ?>
                <p class="order-actions-card__hint">Aucune action disponible.</p>
            <?php endif ?>
        </div>
    </div>

    <h2 class="section-title">Client</h2>
    <p style="color:var(--muted);line-height:1.5">
        <?= htmlspecialchars((string) ($order->user_name ?: '—'), ENT_QUOTES, 'UTF-8') ?>
        <?php if ($order->user_email): ?>
            &lt;<?= htmlspecialchars($order->user_email, ENT_QUOTES, 'UTF-8') ?>&gt;
        <?php endif ?>
        <br>User #<?= (int) $order->user_id ?>
    </p>

    <h2 class="section-title">Livraison</h2>
    <p style="color:var(--muted);line-height:1.5">
        <?= htmlspecialchars($order->shipping_name, ENT_QUOTES, 'UTF-8') ?><br>
        <?= htmlspecialchars($order->shipping_line1, ENT_QUOTES, 'UTF-8') ?><br>
        <?= htmlspecialchars($order->shipping_postal . ' ' . $order->shipping_city, ENT_QUOTES, 'UTF-8') ?><br>
        <?= htmlspecialchars($order->shipping_country, ENT_QUOTES, 'UTF-8') ?>
    </p>

    <h2 class="section-title">Articles</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Prix TTC</th>
                    <th>Qté</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($item->product_name, ENT_QUOTES, 'UTF-8') ?>
                        <?php if ($item->product_id): ?>
                            <span class="badge-muted">#<?= (int) $item->product_id ?></span>
                        <?php endif ?>
                    </td>
                    <td><?= htmlspecialchars($item->unitLabel($symbol), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int) $item->quantity ?></td>
                    <td><?= htmlspecialchars($item->lineLabel($symbol), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</section>
