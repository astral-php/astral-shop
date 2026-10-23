<?php
/** @var \App\Models\Order $order */
/** @var list<\App\Models\OrderItem> $items */
/** @var array<string, mixed> $shop */
/** @var array<string, mixed>|null $shipment */
$symbol = (string) ($shop['currency_symbol'] ?? '€');
$shipment = is_array($shipment ?? null) ? $shipment : null;
$isPickup = $order->isStorePickup()
    || (($shipment['method_code'] ?? '') === 'store_pickup')
    || (($shipment['carrier'] ?? '') === 'store');
$storeAddress = trim((string) ($shop['store_address'] ?? ''));
$storeName = (string) ($shop['store_name'] ?? 'Boutique');
?>
<section class="admin-panel">
    <div class="admin-panel__head">
        <h1>Commande #<?= (int) $order->id ?></h1>
        <a class="btn btn-ghost" href="/admin/orders">← Commandes</a>
    </div>

    <div class="order-detail-layout">
        <div class="order-detail-layout__main">
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
            <?php
            $showReturnInfo = $order->returnStatusLabel() !== ''
                || $order->hasRefundRequestPending()
                || $order->hasRefundRequestAccepted();
            ?>
            <?php if ($showReturnInfo): ?>
                <p style="margin:0 0 0.75rem;display:flex;flex-wrap:wrap;align-items:center;gap:0.5rem">
                    <strong>Retour colis :</strong>
                    <?php if ($order->returnStatusLabel() !== ''): ?>
                        <span class="status-badge <?= $order->isReturnPending() ? 'status-badge--pending' : ($order->isReturnReceived() || $order->isReturnWaived() ? 'status-badge--shipped' : 'status-badge--failed') ?>">
                            <?= htmlspecialchars($order->returnStatusLabel(), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php else: ?>
                        <span class="badge-muted">à traiter avec la demande</span>
                    <?php endif ?>
                    <?php if ($order->return_due_at): ?>
                        <span class="badge-muted">échéance <?= htmlspecialchars($order->return_due_at, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif ?>
                    <?php if ($order->returned_at): ?>
                        <span class="badge-muted">reçu <?= htmlspecialchars($order->returned_at, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif ?>
                </p>
            <?php endif ?>
            <?php if ($order->refundRequestLabel() !== ''): ?>
                <p style="margin:0 0 0.75rem;display:flex;flex-wrap:wrap;align-items:center;gap:0.5rem">
                    <strong>Demande client :</strong>
                    <span class="status-badge status-badge--pending">
                        <?= htmlspecialchars($order->refundRequestLabel(), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <?php if ($order->refund_request_at): ?>
                        <span class="badge-muted"><?= htmlspecialchars($order->refund_request_at, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif ?>
                </p>
                <?php if (trim($order->refund_request_reason) !== ''): ?>
                    <p style="margin:0 0 0.75rem;font-size:0.9rem">
                        <strong>Motif client :</strong>
                        <?= htmlspecialchars($order->refund_request_reason, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif ?>
                <?php if ($order->hasRefundRequestRejected() && trim($order->refund_reject_reason) !== ''): ?>
                    <p style="margin:0 0 0.75rem;font-size:0.9rem;color:var(--muted)">
                        <strong>Motif refus :</strong>
                        <?= htmlspecialchars($order->refund_reject_reason, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif ?>
                <?php if ($order->isOfflinePayment() && $order->hasRefundRib() && !$order->isRefunded()): ?>
                    <p style="margin:0 0 0.75rem;font-size:0.9rem;color:var(--muted)">
                        <strong>RIB client :</strong>
                        <?= htmlspecialchars($order->refund_holder, ENT_QUOTES, 'UTF-8') ?>
                        — IBAN <?= htmlspecialchars($order->refund_iban, ENT_QUOTES, 'UTF-8') ?>
                        <?php if (trim($order->refund_bic) !== ''): ?>
                            — BIC <?= htmlspecialchars($order->refund_bic, ENT_QUOTES, 'UTF-8') ?>
                        <?php endif ?>
                    </p>
                <?php endif ?>
            <?php endif ?>
            <p style="margin:0 0 0.75rem">
                <strong>Total TTC :</strong> <?= htmlspecialchars($order->totalLabel($symbol), ENT_QUOTES, 'UTF-8') ?>
                <?php if ($order->hasInvoice()): ?>
                    — <a href="/orders/<?= (int) $order->id ?>/invoice">Facture PDF</a>
                <?php endif ?>
            </p>
            <p style="margin:0;color:var(--muted);font-size:0.85rem;line-height:1.5">
                Stock appliqué : <?= $order->hasStockApplied() ? 'Oui' : 'Non' ?>
                <?php if ($order->payment_due_at): ?>
                    · Échéance : <?= htmlspecialchars($order->payment_due_at, ENT_QUOTES, 'UTF-8') ?>
                <?php endif ?>
                <?php if ($order->stripe_intent_id): ?>
                    <br>Stripe : <code style="font-size:0.78rem"><?= htmlspecialchars($order->stripe_intent_id, ENT_QUOTES, 'UTF-8') ?></code>
                <?php endif ?>
                <?php if ($order->isRefunded() && $order->refunded_at): ?>
                    <br>Remboursée le <?= htmlspecialchars($order->refunded_at, ENT_QUOTES, 'UTF-8') ?>
                <?php endif ?>
                <?php if ($order->isRefunded() && $order->hasRefundRib()): ?>
                    <br>RIB :
                    <?= htmlspecialchars($order->refund_holder, ENT_QUOTES, 'UTF-8') ?>
                    — <?= htmlspecialchars($order->refund_iban, ENT_QUOTES, 'UTF-8') ?>
                    <?php if (trim($order->refund_reference) !== ''): ?>
                        — <?= htmlspecialchars($order->refund_reference, ENT_QUOTES, 'UTF-8') ?>
                    <?php endif ?>
                <?php endif ?>
            </p>
        </div>

    <article class="order-invoice-sheet">
        <header class="order-invoice-sheet__header">
            <div>
                <p class="order-invoice-sheet__eyebrow">Document commande</p>
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

        <div class="order-invoice-sheet__parties">
            <div>
                <h3 class="order-invoice-sheet__label">Client</h3>
                <p class="order-invoice-sheet__text">
                    <?= htmlspecialchars((string) ($order->user_name ?: '—'), ENT_QUOTES, 'UTF-8') ?><br>
                    <?php if ($order->user_email): ?>
                        <?= htmlspecialchars($order->user_email, ENT_QUOTES, 'UTF-8') ?><br>
                    <?php endif ?>
                    <span class="order-invoice-sheet__muted">Compte #<?= (int) $order->user_id ?></span>
                </p>
            </div>
            <div>
                <h3 class="order-invoice-sheet__label"><?= $isPickup ? 'Retrait' : 'Livraison' ?></h3>
                <p class="order-invoice-sheet__text">
                    <?php if ($shipment !== null): ?>
                        <strong><?= htmlspecialchars((string) ($shipment['label'] ?? 'Livraison'), ENT_QUOTES, 'UTF-8') ?></strong><br>
                        <?php
                        $relayLabel = trim((string) ($shipment['pickup_point_label'] ?? ''));
                        $relayId = trim((string) ($shipment['pickup_point_id'] ?? ''));
                        ?>
                        <?php if ($relayLabel !== '' || $relayId !== ''): ?>
                            <span class="order-invoice-sheet__muted">
                                Point relais :
                                <?= htmlspecialchars($relayLabel !== '' ? $relayLabel : $relayId, ENT_QUOTES, 'UTF-8') ?>
                                <?php if ($relayLabel !== '' && $relayId !== ''): ?>
                                    (<?= htmlspecialchars($relayId, ENT_QUOTES, 'UTF-8') ?>)
                                <?php endif ?>
                            </span><br>
                        <?php endif ?>
                    <?php endif ?>
                    <?php if ($isPickup && $storeAddress !== ''): ?>
                        <span class="order-invoice-sheet__muted">Magasin : <?= htmlspecialchars($storeAddress, ENT_QUOTES, 'UTF-8') ?></span><br>
                    <?php endif ?>
                    <?= htmlspecialchars($order->shipping_name, ENT_QUOTES, 'UTF-8') ?><br>
                    <?= htmlspecialchars($order->shipping_line1, ENT_QUOTES, 'UTF-8') ?><br>
                    <?= htmlspecialchars($order->shipping_postal . ' ' . $order->shipping_city, ENT_QUOTES, 'UTF-8') ?><br>
                    <?= htmlspecialchars($order->shipping_country, ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
            <div>
                <h3 class="order-invoice-sheet__label">Facturation</h3>
                <p class="order-invoice-sheet__text">
                    <?php if ($order->billingSameAsShippingSnapshot()): ?>
                        <span class="order-invoice-sheet__muted">Identique à la livraison</span>
                    <?php else: ?>
                        <?= htmlspecialchars($order->billingName(), ENT_QUOTES, 'UTF-8') ?><br>
                        <?= htmlspecialchars($order->billingLine1(), ENT_QUOTES, 'UTF-8') ?><br>
                        <?= htmlspecialchars($order->billingPostal() . ' ' . $order->billingCity(), ENT_QUOTES, 'UTF-8') ?><br>
                        <?= htmlspecialchars($order->billingCountry(), ENT_QUOTES, 'UTF-8') ?>
                    <?php endif ?>
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
                        <td>
                            <?= htmlspecialchars($item->product_name, ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($item->product_id): ?>
                                <span class="order-invoice-sheet__muted">#<?= (int) $item->product_id ?></span>
                            <?php endif ?>
                        </td>
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
        </div><!-- /.order-detail-layout__main -->

        <aside class="order-detail-layout__aside">
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
                    <button type="submit" class="btn btn-primary order-actions-card__btn">
                        <?= $isPickup ? 'Marquer comme retiré en boutique' : 'Marquer comme envoyée' ?>
                    </button>
                </form>
            <?php elseif ($order->isPaid() && $order->fulfillment_status === \App\Models\Order::FULFILLMENT_SHIPPED): ?>
                <p class="order-actions-card__hint">
                    <?= $isPickup ? 'Retrait effectué — aucune action colis restante.' : 'Colis expédié — aucune action colis restante.' ?>
                </p>
            <?php elseif ($order->isRefunded()): ?>
                <p class="order-actions-card__hint">Commande remboursée — aucune action colis restante.</p>
            <?php elseif ($order->isCancelled()): ?>
                <form method="POST" action="/admin/orders/<?= (int) $order->id ?>/delete"
                      onsubmit="return confirm('Supprimer définitivement cette commande annulée ?');">
                    <?= $csrf->field() ?>
                    <button type="submit" class="btn btn-ghost order-actions-card__btn link-danger">Supprimer définitivement</button>
                </form>
            <?php elseif ($order->isPending() && $order->payment_method === \App\Models\Order::PAYMENT_CARD): ?>
                <p class="order-actions-card__hint">Paiement carte : en attente de confirmation Stripe.</p>
            <?php else: ?>
                <p class="order-actions-card__hint">Aucune action disponible.</p>
            <?php endif ?>

            <?php if ($order->isPaid()): ?>
                <?php
                $returnSatisfied = $order->isReturnSatisfiedForRefund();
                $inRefundRequestFlow = $order->hasRefundRequestPending() || $order->hasRefundRequestAccepted();
                $returnProcessActive = $order->isReturnPending()
                    || $order->isReturnExpired()
                    || $order->isReturnReceived()
                    || $order->isReturnWaived();
                $showReturnActions = $inRefundRequestFlow || $order->isReturnPending() || $order->isReturnExpired();
                $canMarkReturn = $order->isReturnPending()
                    || $order->isReturnExpired()
                    || ($inRefundRequestFlow
                        && $order->return_status === \App\Models\Order::RETURN_NONE
                        && $order->wasShippedOrPickedUp());
                $canWaiveReturn = $showReturnActions
                    && !$order->isReturnReceived()
                    && !$order->isReturnWaived();
                $canExpireReturn = $order->isReturnPending()
                    && $order->return_due_at
                    && $order->return_due_at < date('Y-m-d H:i:s');
                $lockRefundForReturn = ($inRefundRequestFlow || $returnProcessActive) && !$returnSatisfied;
                ?>
                <?php if ($showReturnActions): ?>
                    <hr class="order-actions-card__divider" style="margin:1rem 0;border:0;border-top:1px solid var(--border, #e2e8f0)">
                    <h3 class="order-actions-card__title" style="font-size:1rem;margin:0 0 0.5rem">Retour colis</h3>
                    <?php if ($order->isReturnPending()): ?>
                        <p class="order-actions-card__hint" style="margin-bottom:0.75rem">
                            Colis en attente de réception
                            <?php if ($order->return_due_at): ?>
                                (échéance <?= htmlspecialchars($order->return_due_at, ENT_QUOTES, 'UTF-8') ?>)
                            <?php endif ?>.
                            Marquez « Retour reçu » avant d’accepter / rembourser.
                        </p>
                    <?php elseif ($order->isReturnExpired()): ?>
                        <p class="order-actions-card__hint" style="margin-bottom:0.75rem">
                            Délai dépassé — pas de remboursement sauf réception tardive ou dispense.
                        </p>
                    <?php elseif ($inRefundRequestFlow && !$returnSatisfied): ?>
                        <p class="order-actions-card__hint" style="margin-bottom:0.75rem">
                            Validez le retour (ou dispense) avant d’accepter la demande.
                        </p>
                    <?php elseif ($order->isReturnReceived() || $order->isReturnWaived()): ?>
                        <p class="order-actions-card__hint" style="margin-bottom:0.75rem">
                            <?= $order->isReturnWaived()
                                ? 'Retour non requis — vous pouvez traiter le remboursement.'
                                : 'Retour reçu — vous pouvez traiter le remboursement.' ?>
                        </p>
                    <?php endif ?>
                    <?php if ($canMarkReturn): ?>
                        <form method="POST" action="/admin/orders/<?= (int) $order->id ?>/mark-return-received"
                              onsubmit="return confirm('Confirmer la réception du colis retour ?');">
                            <?= $csrf->field() ?>
                            <button type="submit" class="btn btn-primary order-actions-card__btn">Retour reçu</button>
                        </form>
                    <?php endif ?>
                    <?php if ($canWaiveReturn && !$returnSatisfied): ?>
                        <form method="POST" action="/admin/orders/<?= (int) $order->id ?>/waive-return" style="margin-top:0.5rem"
                              onsubmit="return confirm('Dispenser le client de renvoyer le colis ?');">
                            <?= $csrf->field() ?>
                            <button type="submit" class="btn btn-ghost order-actions-card__btn">Dispenser du retour</button>
                        </form>
                    <?php endif ?>
                    <?php if ($canExpireReturn): ?>
                        <form method="POST" action="/admin/orders/<?= (int) $order->id ?>/expire-return" style="margin-top:0.5rem"
                              onsubmit="return confirm('Clôturer le délai de retour sans remboursement ?');">
                            <?= $csrf->field() ?>
                            <button type="submit" class="btn btn-ghost order-actions-card__btn link-danger">Clôturer délai dépassé</button>
                        </form>
                    <?php endif ?>
                <?php endif ?>

                <hr class="order-actions-card__divider" style="margin:1rem 0;border:0;border-top:1px solid var(--border, #e2e8f0)">
                <h3 class="order-actions-card__title" style="font-size:1rem;margin:0 0 0.5rem">Remboursement</h3>

                <?php if ($lockRefundForReturn): ?>
                    <p class="order-actions-card__hint" style="margin-bottom:0.75rem">
                        Actions de remboursement verrouillées tant que le retour n’est pas reçu ou dispensé.
                    </p>
                    <?php if ($order->hasRefundRequestPending()): ?>
                        <form method="POST" action="/admin/orders/<?= (int) $order->id ?>/reject-refund-request"
                              class="form-stack"
                              onsubmit="return confirm('Refuser cette demande ?');">
                            <?= $csrf->field() ?>
                            <div class="field">
                                <label for="refund_reject_reason">Motif de refus <span class="hint">(10–500 car.)</span></label>
                                <textarea class="input" id="refund_reject_reason" name="refund_reject_reason"
                                          rows="3" required minlength="10" maxlength="500"></textarea>
                            </div>
                            <button type="submit" class="btn btn-ghost order-actions-card__btn link-danger">Refuser la demande</button>
                        </form>
                    <?php endif ?>

                <?php elseif ($order->hasRefundRequestPending()): ?>
                    <p class="order-actions-card__hint" style="margin-bottom:0.75rem">
                        Demande client en attente —
                        <?= $order->payment_method === \App\Models\Order::PAYMENT_CARD
                            ? 'accepter déclenche le remboursement Stripe.'
                            : 'accepter puis effectuer le virement sortant.' ?>
                    </p>
                    <form method="POST" action="/admin/orders/<?= (int) $order->id ?>/accept-refund-request"
                          onsubmit="return confirm('Accepter cette demande de remboursement ?');">
                        <?= $csrf->field() ?>
                        <button type="submit" class="btn btn-primary order-actions-card__btn">
                            <?= $order->payment_method === \App\Models\Order::PAYMENT_CARD
                                ? 'Accepter et rembourser (Stripe)'
                                : 'Accepter la demande' ?>
                        </button>
                    </form>
                    <form method="POST" action="/admin/orders/<?= (int) $order->id ?>/reject-refund-request"
                          class="form-stack" style="margin-top:1rem"
                          onsubmit="return confirm('Refuser cette demande ?');">
                        <?= $csrf->field() ?>
                        <div class="field">
                            <label for="refund_reject_reason">Motif de refus <span class="hint">(10–500 car.)</span></label>
                            <textarea class="input" id="refund_reject_reason" name="refund_reject_reason"
                                      rows="3" required minlength="10" maxlength="500"></textarea>
                        </div>
                        <button type="submit" class="btn btn-ghost order-actions-card__btn link-danger">Refuser</button>
                    </form>

                <?php elseif ($order->hasRefundRequestAccepted() && $order->isOfflinePayment()): ?>
                    <p class="order-actions-card__hint" style="margin-bottom:0.75rem">
                        Demande acceptée — après le virement sortant vers le RIB ci-dessous,
                        confirmez pour clôturer la commande (stock + e-mail client).
                    </p>
                    <form method="POST" action="/admin/orders/<?= (int) $order->id ?>/mark-refunded" class="form-stack order-refund-form"
                          onsubmit="return confirm('Virement sortant effectué ? La commande sera marquée remboursée.');">
                        <?= $csrf->field() ?>
                        <div class="field">
                            <label for="refund_holder">Titulaire</label>
                            <input class="input" type="text" id="refund_holder" name="refund_holder" required maxlength="255" autocomplete="off"
                                   value="<?= htmlspecialchars($order->refund_holder, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="field">
                            <label for="refund_iban">IBAN</label>
                            <input class="input" type="text" id="refund_iban" name="refund_iban" required maxlength="42" autocomplete="off" placeholder="FR76…"
                                   value="<?= htmlspecialchars($order->refund_iban, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="field">
                            <label for="refund_bic">BIC <span class="hint">(optionnel)</span></label>
                            <input class="input" type="text" id="refund_bic" name="refund_bic" maxlength="16" autocomplete="off"
                                   value="<?= htmlspecialchars($order->refund_bic, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="field">
                            <label for="refund_reference">Réf. virement <span class="hint">(optionnel)</span></label>
                            <input class="input" type="text" id="refund_reference" name="refund_reference" maxlength="64" autocomplete="off"
                                   placeholder="REM-<?= (int) $order->id ?>"
                                   value="<?= htmlspecialchars($order->refund_reference, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <button type="submit" class="btn btn-primary order-actions-card__btn">
                            Confirmer le remboursement
                        </button>
                        <p class="order-actions-card__hint" style="margin:0.5rem 0 0;font-size:0.8rem">
                            À cliquer uniquement une fois le virement réellement envoyé.
                        </p>
                    </form>

                <?php elseif ($order->payment_method === \App\Models\Order::PAYMENT_CARD): ?>
                    <form method="POST" action="/admin/orders/<?= (int) $order->id ?>/refund"
                          onsubmit="return confirm('Rembourser intégralement cette commande via Stripe ?');">
                        <?= $csrf->field() ?>
                        <button type="submit" class="btn btn-primary order-actions-card__btn">
                            Rembourser via Stripe
                        </button>
                    </form>
                    <p class="order-actions-card__hint" style="margin-top:0.5rem">
                        Remboursement total sans demande client (geste commercial).
                    </p>

                <?php else: ?>
                    <p class="order-actions-card__hint" style="margin-bottom:0.75rem">
                        Remboursement offline sans demande client — saisissez le RIB après le virement sortant,
                        puis confirmez pour clôturer (stock + e-mail).
                    </p>
                    <form method="POST" action="/admin/orders/<?= (int) $order->id ?>/mark-refunded" class="form-stack order-refund-form"
                          onsubmit="return confirm('Virement sortant effectué ? La commande sera marquée remboursée.');">
                        <?= $csrf->field() ?>
                        <div class="field">
                            <label for="refund_holder">Titulaire</label>
                            <input class="input" type="text" id="refund_holder" name="refund_holder" required maxlength="255" autocomplete="off"
                                   value="<?= htmlspecialchars($order->refund_holder, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="field">
                            <label for="refund_iban">IBAN</label>
                            <input class="input" type="text" id="refund_iban" name="refund_iban" required maxlength="42" autocomplete="off" placeholder="FR76…"
                                   value="<?= htmlspecialchars($order->refund_iban, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="field">
                            <label for="refund_bic">BIC <span class="hint">(optionnel)</span></label>
                            <input class="input" type="text" id="refund_bic" name="refund_bic" maxlength="16" autocomplete="off"
                                   value="<?= htmlspecialchars($order->refund_bic, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="field">
                            <label for="refund_reference">Réf. virement <span class="hint">(optionnel)</span></label>
                            <input class="input" type="text" id="refund_reference" name="refund_reference" maxlength="64" autocomplete="off" placeholder="REM-<?= (int) $order->id ?>">
                        </div>
                        <button type="submit" class="btn btn-primary order-actions-card__btn">
                            Confirmer le remboursement
                        </button>
                        <p class="order-actions-card__hint" style="margin:0.5rem 0 0;font-size:0.8rem">
                            À cliquer uniquement une fois le virement réellement envoyé.
                        </p>
                    </form>
                <?php endif ?>
            <?php endif ?>
        </div>
        </aside>
    </div>
</section>
