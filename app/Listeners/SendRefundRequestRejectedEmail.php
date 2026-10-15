<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Dao\OrderDao;
use App\Dao\UserDao;
use App\Events\RefundRequestRejected;
use App\Models\Order;
use App\Support\Money;
use Core\Events\EventInterface;
use Core\Events\ListenerInterface;
use Core\Logger;
use Core\Mailer\Mailer;
use Throwable;

/** E-mail client lorsqu’une demande de remboursement est refusée (D9). */
final class SendRefundRequestRejectedEmail implements ListenerInterface
{
    /** @param array<string, mixed> $shop */
    public function __construct(
        private Mailer $mailer,
        private OrderDao $orderDao,
        private UserDao $userDao,
        private Logger $logger,
        private array $shop = [],
        private string $appBaseUrl = '',
    ) {}

    public function handle(EventInterface $event): void
    {
        if (!$event instanceof RefundRequestRejected || $event->orderId < 1) {
            return;
        }

        try {
            $order = $this->orderDao->findById($event->orderId);
            /** @var Order|null $order */
            if ($order === null || !$order->hasRefundRequestRejected()) {
                return;
            }

            $user = $this->userDao->findById($order->user_id);
            if ($user === null || trim($user->email) === '') {
                $this->logger->warning('SendRefundRequestRejectedEmail: client sans e-mail', [
                    'order_id' => $order->id,
                ]);
                return;
            }

            $store = (string) ($this->shop['store_name'] ?? 'Astral Shop');
            $this->mailer->send(
                $user->email,
                $store . ' — Demande de remboursement refusée #' . $order->id,
                $this->buildHtml($order, $user->name),
            );

            $this->logger->info('E-mail refus remboursement envoyé', [
                'order_id' => $order->id,
                'email'    => $user->email,
            ]);
        } catch (Throwable $e) {
            $this->logger->warning('SendRefundRequestRejectedEmail: échec envoi', [
                'order_id' => $event->orderId,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    private function buildHtml(Order $order, string $customerName): string
    {
        $store  = htmlspecialchars((string) ($this->shop['store_name'] ?? 'Astral Shop'), ENT_QUOTES, 'UTF-8');
        $symbol = (string) ($this->shop['currency_symbol'] ?? '€');
        $name   = htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8');
        $total  = htmlspecialchars(Money::formatCents($order->total_cents, $symbol), ENT_QUOTES, 'UTF-8');
        $reason = htmlspecialchars($order->refund_reject_reason, ENT_QUOTES, 'UTF-8');
        $base   = rtrim($this->appBaseUrl, '/');
        $link   = htmlspecialchars(
            ($base !== '' ? $base : '') . '/orders/' . $order->id,
            ENT_QUOTES,
            'UTF-8',
        );

        return <<<HTML
        <div style="font-family:'Segoe UI',sans-serif;max-width:560px;margin:auto;padding:28px;color:#0f172a">
            <h2 style="color:#4f46e5;margin:0 0 12px">Demande non acceptée</h2>
            <p>Bonjour <strong>{$name}</strong>,</p>
            <p>Votre demande de remboursement pour la commande
               <strong>#{$order->id}</strong> ({$total}) sur <strong>{$store}</strong>
               n’a pas pu être acceptée.</p>
            <p style="font-size:14px"><strong>Motif :</strong> {$reason}</p>
            <p>Votre commande reste payée. Vous pouvez déposer une nouvelle demande depuis votre espace client si besoin.</p>
            <p style="text-align:center;margin:28px 0">
                <a href="{$link}"
                   style="background:#4f46e5;color:#fff;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:600">
                    Voir ma commande
                </a>
            </p>
            <p style="color:#94a3b8;font-size:12px">Cet e-mail a été envoyé automatiquement.</p>
        </div>
        HTML;
    }
}
