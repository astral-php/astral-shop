<?php
/**
 * Consignes + suivi retour colis (G3 / §10.12).
 *
 * @var \App\Models\Order $order
 * @var array<string, mixed> $shop
 */
$legal = is_array($shop['legal'] ?? null) ? $shop['legal'] : [];
$returnDays = (int) ($legal['return_days'] ?? 14);
$storeAddress = trim((string) ($shop['store_address'] ?? ''));
$legalAddress = trim((string) ($legal['address'] ?? ''));
$returnAddress = $storeAddress !== '' ? $storeAddress : $legalAddress;
$company = (string) ($legal['company_name'] ?? ($shop['store_name'] ?? 'la boutique'));
$status = $order->return_status;

if ($status === \App\Models\Order::RETURN_NONE) {
    return;
}
?>
<div class="order-return-panel" style="margin-top:1.5rem;padding:1rem 1.15rem;border:1px solid var(--line,#e2e8f0);border-radius:0.85rem;background:#f8fafc">
    <h2 class="section-title" style="margin-top:0">Retour du colis</h2>

    <?php if ($order->isReturnPending()): ?>
        <p style="margin:0 0 0.65rem">
            <span class="status-badge status-badge--pending">Retour en attente</span>
            <?php if ($order->return_due_at): ?>
                <span class="badge-muted">à renvoyer avant le <?= htmlspecialchars($order->return_due_at, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif ?>
        </p>
        <p style="margin:0 0 0.75rem;color:var(--muted);font-size:0.9rem;line-height:1.5">
            Pour obtenir le remboursement, renvoyez les articles dans un état permettant leur remise en vente
            (emballage d’origine si possible, accessoires). Délai : <?= $returnDays ?> jours après réception / retrait.
            Les frais de renvoi sont à votre charge, sauf défaut imputable à la boutique.
        </p>
        <?php if ($returnAddress !== ''): ?>
            <p style="margin:0 0 0.5rem;font-size:0.9rem">
                <strong>Adresse de retour — <?= htmlspecialchars($company, ENT_QUOTES, 'UTF-8') ?></strong><br>
                <span style="color:var(--muted);white-space:pre-line"><?= htmlspecialchars($returnAddress, ENT_QUOTES, 'UTF-8') ?></span>
            </p>
        <?php endif ?>
        <p style="margin:0;font-size:0.85rem">
            <a href="/retours">Politique de retour</a>
            — indiquez la référence <strong>CMD-<?= (int) $order->id ?></strong> dans le colis.
        </p>

    <?php elseif ($order->isReturnReceived()): ?>
        <p style="margin:0 0 0.5rem">
            <span class="status-badge status-badge--shipped">Retour reçu</span>
            <?php if ($order->returned_at): ?>
                <span class="badge-muted">le <?= htmlspecialchars($order->returned_at, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif ?>
        </p>
        <p style="margin:0;color:var(--muted);font-size:0.9rem">
            Votre colis a été réceptionné. Le remboursement peut être traité.
        </p>

    <?php elseif ($order->isReturnWaived()): ?>
        <p style="margin:0 0 0.5rem">
            <span class="status-badge status-badge--paid">Retour non requis</span>
        </p>
        <p style="margin:0;color:var(--muted);font-size:0.9rem">
            Aucun renvoi de colis n’est demandé pour cette demande (commande non expédiée ou exception acceptée).
        </p>

    <?php elseif ($order->isReturnExpired()): ?>
        <p style="margin:0 0 0.5rem">
            <span class="status-badge status-badge--failed">Délai de retour dépassé</span>
            <?php if ($order->return_due_at): ?>
                <span class="badge-muted">échéance <?= htmlspecialchars($order->return_due_at, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif ?>
        </p>
        <p style="margin:0;color:var(--muted);font-size:0.9rem">
            Le délai de renvoi est dépassé. Sans réception du colis, le remboursement ne peut pas aboutir
            (sauf geste commercial de la boutique).
            <a href="/retours">En savoir plus</a>
        </p>
    <?php endif ?>
</div>
