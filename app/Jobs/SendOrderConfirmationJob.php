<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Dao\OrderDao;
use App\Dao\OrderItemDao;
use App\Dao\UserDao;
use App\Models\Order;
use App\Support\Money;
use Astral\Worker\Job\JobInterface;
use Core\Logger;
use Core\Mailer\Mailer;

/**
 * E-mail de confirmation commande — payload : ['order_id' => int].
 */
final class SendOrderConfirmationJob implements JobInterface
{
    public const TRIES = 5;
    public const BACKOFF = 60;

    /** @param array<string, mixed> $shop */
    public function __construct(
        private Mailer $mailer,
        private OrderDao $orderDao,
        private OrderItemDao $orderItemDao,
        private UserDao $userDao,
        private Logger $logger,
        private array $shop = [],
        private string $appBaseUrl = '',
    ) {}

    public function handle(array $payload): void
    {
        $orderId = (int) ($payload['order_id'] ?? 0);
        if ($orderId < 1) {
            return;
        }

        $order = $this->orderDao->findById($orderId);
        /** @var Order|null $order */
        if ($order === null) {
            return;
        }

        $user = $this->userDao->findById($order->user_id);
        if ($user === null || trim($user->email) === '') {
            $this->logger->warning('SendOrderConfirmationJob: client sans e-mail', [
                'order_id' => $order->id,
                'user_id'  => $order->user_id,
            ]);

            return;
        }

        $items = $this->orderItemDao->findByOrder($order->id);
        $html  = $this->buildHtml($order, $user->name, $items);

        $store = (string) ($this->shop['store_name'] ?? 'Astral Shop');
        $this->mailer->send(
            $user->email,
            $store . ' — Confirmation de commande #' . $order->id,
            $html,
        );

        $this->logger->info('E-mail confirmation commande envoyé (job)', [
            'order_id' => $order->id,
            'email'    => $user->email,
        ]);
    }

    /**
     * @param list<\App\Models\OrderItem> $items
     */
    private function buildHtml(Order $order, string $customerName, array $items): string
    {
        $store  = htmlspecialchars((string) ($this->shop['store_name'] ?? 'Astral Shop'), ENT_QUOTES, 'UTF-8');
        $symbol = (string) ($this->shop['currency_symbol'] ?? '€');
        $name   = htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8');
        $total  = htmlspecialchars($order->totalLabel($symbol), ENT_QUOTES, 'UTF-8');
        $sub    = htmlspecialchars($order->subtotalLabel($symbol), ENT_QUOTES, 'UTF-8');
        $shipAmt = htmlspecialchars($order->shippingLabel($symbol), ENT_QUOTES, 'UTF-8');
        $base   = rtrim($this->appBaseUrl, '/');
        $link   = $base !== '' ? $base . '/orders/' . $order->id : '/orders/' . $order->id;
        $invoiceNote = $order->hasInvoice()
            ? '<p style="margin:16px 0;padding:12px 14px;background:#eef2ff;border-radius:8px;font-size:14px">'
              . 'Votre facture est disponible dans votre espace client '
              . '(détail de la commande → télécharger la facture PDF).</p>'
            : '';

        $rows = '';
        foreach ($items as $item) {
            $rows .= '<tr>'
                . '<td style="padding:8px 0;border-bottom:1px solid #e2e8f0">'
                . htmlspecialchars($item->product_name, ENT_QUOTES, 'UTF-8')
                . '</td>'
                . '<td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:center">'
                . (int) $item->quantity
                . '</td>'
                . '<td style="padding:8px 0;border-bottom:1px solid #e2e8f0;text-align:right">'
                . htmlspecialchars(Money::formatCents($item->line_total_cents, $symbol), ENT_QUOTES, 'UTF-8')
                . '</td>'
                . '</tr>';
        }

        $ship = htmlspecialchars(
            trim($order->shipping_name . "\n" . $order->shipping_line1 . "\n"
                . $order->shipping_postal . ' ' . $order->shipping_city . "\n"
                . $order->shipping_country),
            ENT_QUOTES,
            'UTF-8',
        );
        $ship = nl2br($ship);

        return <<<HTML
        <div style="font-family:'Segoe UI',sans-serif;max-width:560px;margin:auto;padding:28px;color:#0f172a">
            <h2 style="color:#4f46e5;margin:0 0 12px">Commande confirmée</h2>
            <p>Bonjour <strong>{$name}</strong>,</p>
            <p>Merci pour votre achat sur <strong>{$store}</strong>. Votre paiement a bien été reçu.</p>
            <p style="margin:18px 0 8px"><strong>Commande #{$order->id}</strong></p>
            <table style="width:100%;border-collapse:collapse;font-size:14px;margin:12px 0 12px">
                <thead>
                    <tr style="text-align:left;color:#64748b;font-size:12px">
                        <th style="padding:0 0 8px">Article</th>
                        <th style="padding:0 0 8px;text-align:center">Qté</th>
                        <th style="padding:0 0 8px;text-align:right">Total</th>
                    </tr>
                </thead>
                <tbody>{$rows}</tbody>
            </table>
            <p style="font-size:14px;margin:0 0 4px">Sous-total TTC : {$sub}</p>
            <p style="font-size:14px;margin:0 0 4px">Livraison : {$shipAmt}</p>
            <p style="font-size:14px;margin:0 0 16px"><strong>Total TTC : {$total}</strong></p>
            {$invoiceNote}
            <p style="font-size:14px;color:#64748b;margin:0 0 6px">Adresse</p>
            <p style="margin:0 0 20px;line-height:1.5">{$ship}</p>
            <p style="text-align:center;margin:28px 0">
                <a href="{$link}"
                   style="background:#4f46e5;color:#fff;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:600">
                    Voir ma commande
                </a>
            </p>
            <p style="color:#94a3b8;font-size:12px">Cet e-mail a été envoyé automatiquement. Votre facture est disponible dans votre espace client.</p>
        </div>
        HTML;
    }
}
