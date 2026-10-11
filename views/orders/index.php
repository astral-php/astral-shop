<?php
/** @var list<\App\Models\Order> $orders */
/** @var array<string, mixed> $shop */
/** @var bool $confirming */
$symbol = (string) ($shop['currency_symbol'] ?? '€');
$confirming = !empty($confirming);
?>
<section class="admin-panel">
    <div class="admin-panel__head">
        <h1>Mes commandes</h1>
        <a class="btn btn-ghost" href="/boutique">Boutique</a>
    </div>

    <?php if ($confirming): ?>
        <p class="flash-ok" id="orders-confirming">Paiement accepté — actualisation automatique du statut…</p>
        <script>
        (function () {
            var n = 0;
            var t = window.setInterval(function () {
                n += 1;
                if (n >= 6) {
                    window.clearInterval(t);
                    window.location.href = '/orders';
                }
            }, 1500);
        })();
        </script>
    <?php endif ?>

    <?php if ($orders === []): ?>
        <p class="empty-state">Aucune commande pour le moment.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Total TTC</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= (int) $order->id ?></td>
                        <td>
                            <?= htmlspecialchars($order->created_at, ENT_QUOTES, 'UTF-8') ?>
                            <span class="<?= htmlspecialchars($order->paymentStateBadgeClass(), ENT_QUOTES, 'UTF-8') ?>" style="margin-left:0.35rem">
                                <?= htmlspecialchars($order->paymentStateLabel(), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($order->totalLabel($symbol), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="<?= htmlspecialchars($order->fulfillmentBadgeClass(), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($order->fulfillmentLabel(), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td><a href="/orders/<?= (int) $order->id ?>">Détail</a></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</section>
