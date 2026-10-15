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

/** Accusé de réception client après dépôt d’une demande de remboursement (D11). */
final class SendRefundRequestReceivedEmail implements ListenerInterface
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
        if (!$event instanceof RefundRequested || $event->orderId < 1) {
            return;
        }

        try {
            $order = $this->orderDao->findById($event->orderId);
            /** @var Order|null $order */
            if ($order === null) {
                return;
            }
            // requested (cas normal) ou accepted si un autre listener a déjà avancé.
            if (!in_array($order->refund_request_status, [
                Order::REFUND_REQUEST_REQUESTED,
                Order::REFUND_REQUEST_ACCEPTED,
            ], true)) {
                return;
            }

            $user = $this->userDao->findById($order->user_id);
            if ($user === null || trim($user->email) === '') {
                $this->logger->warning('SendRefundRequestReceivedEmail: client sans e-mail', [
                    'order_id' => $order->id,
                ]);
                return;
            }

            $store = (string) ($this->shop['store_name'] ?? 'Astral Shop');
            $this->mailer->send(
                $user->email,
                $store . ' — Demande de remboursement #' . $order->id,
                $this->buildHtml($order, $user->name),
            );

            $this->logger->info('E-mail demande remboursement (client) envoyé', [
                'order_id' => $order->id,
                'email'    => $user->email,
            ]);
        } catch (Throwable $e) {
            $this->logger->warning('SendRefundRequestReceivedEmail: échec envoi', [
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
        $reason = htmlspecialchars($order->refund_request_reason, ENT_QUOTES, 'UTF-8');
        $base   = rtrim($this->appBaseUrl, '/');
        $link   = htmlspecialchars(
            ($base !== '' ? $base : '') . '/orders/' . $order->id,
            ENT_QUOTES,
            'UTF-8',
        );

        $methodNote = $order->isOfflinePayment()
            ? '<p>Le remboursement, s’il est accepté, sera effectué par virement vers le RIB que vous avez indiqué.</p>'
            : '<p>Le remboursement, s’il est accepté, sera crédité sur la carte utilisée lors du paiement.</p>';

        return <<<HTML
        <div style="font-family:'Segoe UI',sans-serif;max-width:560px;margin:auto;padding:28px;color:#0f172a">
            <h2 style="color:#4f46e5;margin:0 0 12px">Demande bien reçue</h2>
            <p>Bonjour <strong>{$name}</strong>,</p>
            <p>Nous avons bien enregistré votre demande de remboursement pour la commande
               <strong>#{$order->id}</strong> ({$total}) sur <strong>{$store}</strong>.</p>
            <p style="font-size:14px"><strong>Votre motif :</strong> {$reason}</p>
            {$methodNote}
            <p>Nous l’examinerons rapidement et vous tiendrons informé(e).</p>
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
