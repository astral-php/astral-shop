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
                <?php if ($order->isRefunded() && $order->refunded_at): ?>
                    <br>Remboursée le : <?= htmlspecialchars($order->refunded_at, ENT_QUOTES, 'UTF-8') ?>
                <?php endif ?>
                <?php if ($order->isRefunded() && $order->hasRefundRib()): ?>
                    <br>RIB remboursement :
                    <?= htmlspecialchars($order->refund_holder, ENT_QUOTES, 'UTF-8') ?>
                    — IBAN <?= htmlspecialchars($order->refund_iban, ENT_QUOTES, 'UTF-8') ?>
                    <?php if (trim($order->refund_bic) !== ''): ?>
                        — BIC <?= htmlspecialchars($order->refund_bic, ENT_QUOTES, 'UTF-8') ?>
                    <?php endif ?>
                    <?php if (trim($order->refund_reference) !== ''): ?>
                        — réf. <?= htmlspecialchars($order->refund_reference, ENT_QUOTES, 'UTF-8') ?>
                    <?php endif ?>
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
                <hr class="order-actions-card__divider" style="margin:1rem 0;border:0;border-top:1px solid var(--border, #e2e8f0)">
                <h3 class="order-actions-card__title" style="font-size:1rem;margin:0 0 0.5rem">Remboursement</h3>

                <?php if ($order->hasRefundRequestPending()): ?>
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
                        Demande acceptée — effectuez le virement sortant puis marquez remboursée.
                        Le RIB client est prérempli.
                    </p>
                    <form method="POST" action="/admin/orders/<?= (int) $order->id ?>/mark-refunded" class="form-stack order-refund-form"
                          onsubmit="return confirm('Confirmer le remboursement offline et marquer la commande remboursée ?');">
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
                        <button type="submit" class="btn btn-ghost order-actions-card__btn link-danger">Marquer remboursée</button>
                    </form>

                <?php elseif ($order->payment_method === \App\Models\Order::PAYMENT_CARD): ?>
                    <form method="POST" action="/admin/orders/<?= (int) $order->id ?>/refund"
                          onsubmit="return confirm('Rembourser intégralement cette commande via Stripe ?');">
                        <?= $csrf->field() ?>
                        <button type="submit" class="btn btn-ghost order-actions-card__btn link-danger">Rembourser (Stripe)</button>
                    </form>
                    <p class="order-actions-card__hint">Remboursement total sans demande client (geste commercial).</p>

                <?php else: ?>
                    <p class="order-actions-card__hint" style="margin-bottom:0.75rem">
                        Saisissez le RIB client après virement sortant (hors Stripe).
                    </p>
                    <form method="POST" action="/admin/orders/<?= (int) $order->id ?>/mark-refunded" class="form-stack order-refund-form"
                          onsubmit="return confirm('Confirmer le remboursement offline et marquer la commande remboursée ?');">
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
                        <button type="submit" class="btn btn-ghost order-actions-card__btn link-danger">Marquer remboursée</button>
                    </form>
                <?php endif ?>
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

    <h2 class="section-title"><?= $isPickup ? 'Retrait' : 'Livraison' ?></h2>
    <p style="color:var(--muted);line-height:1.5">
        <?php if ($shipment !== null): ?>
            <strong style="color:var(--text)"><?= htmlspecialchars((string) ($shipment['label'] ?? 'Livraison'), ENT_QUOTES, 'UTF-8') ?></strong><br>
        <?php endif ?>
        <?php if ($isPickup && $storeAddress !== ''): ?>
            <span class="shipping-pickup-hint" style="margin:0.5rem 0 0.75rem;display:inline-block;max-width:28rem">
                <span class="shipping-pickup-hint__label">Adresse du magasin</span>
                <?= htmlspecialchars($storeAddress, ENT_QUOTES, 'UTF-8') ?>
            </span><br>
        <?php endif ?>
        <?= htmlspecialchars($order->shipping_name, ENT_QUOTES, 'UTF-8') ?><br>
        <?= htmlspecialchars($order->shipping_line1, ENT_QUOTES, 'UTF-8') ?><br>
        <?= htmlspecialchars($order->shipping_postal . ' ' . $order->shipping_city, ENT_QUOTES, 'UTF-8') ?><br>
        <?= htmlspecialchars($order->shipping_country, ENT_QUOTES, 'UTF-8') ?>
    </p>

    <h2 class="section-title">Facturation</h2>
    <p style="color:var(--muted);line-height:1.5">
        <?php if ($order->billingSameAsShippingSnapshot()): ?>
            Identique à la livraison
        <?php else: ?>
            <?= htmlspecialchars($order->billingName(), ENT_QUOTES, 'UTF-8') ?><br>
            <?= htmlspecialchars($order->billingLine1(), ENT_QUOTES, 'UTF-8') ?><br>
            <?= htmlspecialchars($order->billingPostal() . ' ' . $order->billingCity(), ENT_QUOTES, 'UTF-8') ?><br>
            <?= htmlspecialchars($order->billingCountry(), ENT_QUOTES, 'UTF-8') ?>
        <?php endif ?>
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
