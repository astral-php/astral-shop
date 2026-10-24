<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Dao\OrderDao;
use App\Dao\UserDao;
use App\Events\OrderBecameRefunded;
use App\Models\Order;
use App\Support\Money;
use AstralPayment\Events\PaymentRefunded;
use Core\Events\EventInterface;
use Core\Events\ListenerInterface;
use Core\Logger;
use Core\Mailer\Mailer;
use Throwable;

/**
 * E-mail client après remboursement (CB Stripe ou offline).
 */
final class SendRefundConfirmationEmail implements ListenerInterface
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
        $orderId = match (true) {
            $event instanceof OrderBecameRefunded => $event->orderId,
            $event instanceof PaymentRefunded     => $event->orderId,
            default                               => 0,
        };

        if ($orderId < 1) {
            return;
        }

        try {
            $order = $this->orderDao->findById($orderId);
            /** @var Order|null $order */
            if ($order === null || !$order->isRefunded()) {
                return;
            }

            $user = $this->userDao->findById($order->user_id);
            if ($user === null || trim($user->email) === '') {
                $this->logger->warning('SendRefundConfirmationEmail: client sans e-mail', [
                    'order_id' => $order->id,
                    'user_id'  => $order->user_id,
                ]);
                return;
            }

            $store = (string) ($this->shop['store_name'] ?? 'Astral Shop');
            $this->mailer->send(
                $user->email,
                $store . ' — Remboursement commande #' . $order->id,
                $this->buildHtml($order, $user->name),
            );

            $this->logger->info('E-mail remboursement envoyé', [
                'order_id' => $order->id,
                'email'    => $user->email,
            ]);
        } catch (Throwable $e) {
            $this->logger->warning('SendRefundConfirmationEmail: échec envoi', [
                'order_id' => $orderId,
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
        $base   = rtrim($this->appBaseUrl, '/');
        $link   = $base !== '' ? $base . '/orders/' . $order->id : '/orders/' . $order->id;

        $methodNote = match ($order->payment_method) {
            Order::PAYMENT_CARD => '<p>Le remboursement a été initié sur votre carte bancaire. Selon votre banque, le crédit peut prendre quelques jours ouvrés.</p>',
            Order::PAYMENT_PAYPAL => '<p>Le remboursement a été initié sur votre compte PayPal. Selon PayPal, le crédit peut prendre quelques jours ouvrés.</p>',
            default => '<p>Le remboursement sera effectué par virement vers le RIB communiqué'
                . ($order->hasRefundRib()
                    ? ' (IBAN se terminant par <strong>'
                      . htmlspecialchars(substr($order->refund_iban, -4), ENT_QUOTES, 'UTF-8')
                      . '</strong>)'
                    : '')
                . '.</p>',
        };

        $ref = trim($order->refund_reference) !== ''
            ? '<p style="font-size:14px">Référence : <strong>'
              . htmlspecialchars($order->refund_reference, ENT_QUOTES, 'UTF-8')
              . '</strong></p>'
            : '';

        return <<<HTML
        <div style="font-family:'Segoe UI',sans-serif;max-width:560px;margin:auto;padding:28px;color:#0f172a">
            <h2 style="color:#4f46e5;margin:0 0 12px">Remboursement confirmé</h2>
            <p>Bonjour <strong>{$name}</strong>,</p>
            <p>Votre commande <strong>#{$order->id}</strong> sur <strong>{$store}</strong> a été remboursée.</p>
            <p style="font-size:14px;margin:12px 0"><strong>Montant : {$total}</strong></p>
            {$methodNote}
            {$ref}
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
