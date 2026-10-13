<?php
/** @var list<\App\Models\Order> $orders */
/** @var array<string, mixed> $shop */
/** @var string $filter_payment */
/** @var string $filter_fulfillment */
/** @var array<string, int> $payment_counts */
/** @var array<string, int> $fulfillment_counts */
$symbol = (string) ($shop['currency_symbol'] ?? '€');
$filter_payment = (string) ($filter_payment ?? '');
$filter_fulfillment = (string) ($filter_fulfillment ?? '');
$payment_counts = $payment_counts ?? [];
$fulfillment_counts = $fulfillment_counts ?? [];

$paymentFilters = [
    ''          => 'Actives',
    'pending'   => 'À payer',
    'paid'      => 'Payées',
    'failed'    => 'Échouées',
    'refunded'  => 'Remboursées',
    'cancelled' => 'Annulées',
];
$fulfillmentFilters = [
    ''          => 'Tous',
    'awaiting'  => 'En attente',
    'preparing' => 'En préparation',
    'shipped'   => 'Envoyées / retirées',
];

$buildUrl = static function (string $payment, string $fulfillment): string {
    $q = [];
    if ($payment !== '') {
        $q['payment'] = $payment;
    }
    if ($fulfillment !== '') {
        $q['fulfillment'] = $fulfillment;
    }
    return $q === [] ? '/admin/orders' : '/admin/orders?' . http_build_query($q);
};
?>
<section class="admin-panel">
    <div class="admin-panel__head">
        <h1>Commandes</h1>
    </div>

    <div class="admin-filters">
        <p class="admin-filters__label">Paiement</p>
        <p class="cat-pills">
            <?php foreach ($paymentFilters as $key => $label): ?>
                <?php $count = (int) ($payment_counts[$key] ?? 0); ?>
                <a class="cat-pill<?= $filter_payment === $key ? ' is-active' : '' ?>"
                   href="<?= htmlspecialchars($buildUrl($key, $filter_fulfillment), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                    <span class="cat-pill__count"><?= $count ?></span>
                </a>
            <?php endforeach ?>
        </p>
        <p class="admin-filters__label">Statut colis</p>
        <p class="cat-pills">
            <?php foreach ($fulfillmentFilters as $key => $label): ?>
                <?php $count = (int) ($fulfillment_counts[$key] ?? 0); ?>
                <a class="cat-pill<?= $filter_fulfillment === $key ? ' is-active' : '' ?>"
                   href="<?= htmlspecialchars($buildUrl($filter_payment, $key), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                    <span class="cat-pill__count"><?= $count ?></span>
                </a>
            <?php endforeach ?>
        </p>
    </div>

    <?php if ($orders === []): ?>
        <p class="empty-state">Aucune commande pour ce filtre.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Paiement</th>
                        <th>Client</th>
                        <th>Total TTC</th>
                        <th>Statut colis</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= (int) $order->id ?></td>
                        <td><?= htmlspecialchars($order->created_at, ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="<?= htmlspecialchars($order->paymentStateBadgeClass(), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($order->paymentStateLabel(), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td>
                            <?= htmlspecialchars((string) ($order->user_name ?: $order->shipping_name), ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($order->user_email): ?>
                                <br><span style="color:var(--muted);font-size:0.8rem"><?= htmlspecialchars($order->user_email, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif ?>
                        </td>
                        <td><?= htmlspecialchars($order->totalLabel($symbol), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="<?= htmlspecialchars($order->fulfillmentBadgeClass(), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($order->fulfillmentLabel(), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td class="actions">
                            <a class="btn btn-ghost" href="/admin/orders/<?= (int) $order->id ?>" style="padding:0.35rem 0.75rem;font-size:0.8rem">Détail</a>
                            <?php if ($order->isCancelled()): ?>
                                <form method="POST" action="/admin/orders/<?= (int) $order->id ?>/delete"
                                      style="display:inline"
                                      onsubmit="return confirm('Supprimer définitivement la commande #<?= (int) $order->id ?> ?');">
                                    <?= $csrf->field() ?>
                                    <button type="submit" class="link-danger" style="font-size:0.8rem">Supprimer</button>
                                </form>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</section>
