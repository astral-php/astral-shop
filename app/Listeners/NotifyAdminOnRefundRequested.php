<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Dao\OrderDao;
use App\Dao\UserDao;
use App\Events\RefundRequested;
use App\Models\Order;
use App\Support\Money;
use Core\Events\EventInterface;
use Core\Events\ListenerInterface;
use Core\Logger;
use Core\Mailer\Mailer;
use Throwable;

/**
 * Alerte e-mail gestionnaire lorsqu’un client demande un remboursement (D10).
 */
final class NotifyAdminOnRefundRequested implements ListenerInterface
{
    /** @param array<string, mixed> $shop */
    public function __construct(
        private Mailer $mailer,
        private OrderDao $orderDao,
        private UserDao $userDao,
        private Logger $logger,
        private array $shop = [],
        private string $appBaseUrl = '',
        private string $notifyEmail = '',
    ) {}

    public function handle(EventInterface $event): void
    {
        if (!$event instanceof RefundRequested || $event->orderId < 1) {
            return;
        }

        $to = trim($this->notifyEmail);
        if ($to === '') {
            $this->logger->warning('NotifyAdminOnRefundRequested: SHOP_NOTIFY_EMAIL / MAIL_FROM vide', [
                'order_id' => $event->orderId,
            ]);
            return;
        }

        try {
            $order = $this->orderDao->findById($event->orderId);
            /** @var Order|null $order */
            if ($order === null) {
                return;
            }

            $user = $this->userDao->findById($order->user_id);
            $store = (string) ($this->shop['store_name'] ?? 'Astral Shop');
            $symbol = (string) ($this->shop['currency_symbol'] ?? '€');
            $base = rtrim($this->appBaseUrl, '/');
            $adminLink = ($base !== '' ? $base : '') . '/admin/orders/' . $order->id;

            $clientName = $user?->name ?? $order->shipping_name;
            $clientEmail = $user?->email ?? '';
            $reason = htmlspecialchars($order->refund_request_reason, ENT_QUOTES, 'UTF-8');
            $total = htmlspecialchars(Money::formatCents($order->total_cents, $symbol), ENT_QUOTES, 'UTF-8');
            $method = htmlspecialchars($order->paymentMethodLabel(), ENT_QUOTES, 'UTF-8');
            $safeLink = htmlspecialchars($adminLink, ENT_QUOTES, 'UTF-8');
            $safeClient = htmlspecialchars($clientName, ENT_QUOTES, 'UTF-8');
            $safeEmail = htmlspecialchars($clientEmail, ENT_QUOTES, 'UTF-8');

            $ribBlock = '';
            if ($order->isOfflinePayment() && $order->hasRefundRib()) {
                $ribBlock = '<p style="font-size:14px;margin:12px 0">'
                    . '<strong>RIB client :</strong> '
                    . htmlspecialchars($order->refund_holder, ENT_QUOTES, 'UTF-8')
                    . ' — IBAN ' . htmlspecialchars($order->refund_iban, ENT_QUOTES, 'UTF-8')
                    . (trim($order->refund_bic) !== ''
                        ? ' — BIC ' . htmlspecialchars($order->refund_bic, ENT_QUOTES, 'UTF-8')
                        : '')
                    . '</p>';
            }

            $html = <<<HTML
            <div style="font-family:'Segoe UI',sans-serif;max-width:560px;margin:auto;padding:28px;color:#0f172a">
                <h2 style="color:#4f46e5;margin:0 0 12px">Nouvelle demande de remboursement</h2>
                <p>Commande <strong>#{$order->id}</strong> — {$total} ({$method})</p>
                <p>Client : <strong>{$safeClient}</strong>
                    {$safeEmail}
                </p>
                <p style="font-size:14px"><strong>Motif :</strong> {$reason}</p>
                {$ribBlock}
                <p style="text-align:center;margin:28px 0">
                    <a href="{$safeLink}"
                       style="background:#4f46e5;color:#fff;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:600">
                        Voir la commande
                    </a>
                </p>
            </div>
            HTML;

            $this->mailer->send(
                $to,
                $store . ' — Demande remboursement #' . $order->id,
                $html,
            );

            $this->logger->info('E-mail admin demande remboursement envoyé', [
                'order_id' => $order->id,
                'email'    => $to,
            ]);
        } catch (Throwable $e) {
            $this->logger->warning('NotifyAdminOnRefundRequested: échec envoi', [
                'order_id' => $event->orderId,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
