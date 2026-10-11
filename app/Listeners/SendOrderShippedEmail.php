<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Dao\OrderDao;
use App\Dao\UserDao;
use App\Events\OrderShipped;
use App\Models\Order;
use Core\Events\EventInterface;
use Core\Events\ListenerInterface;
use Core\Logger;
use Core\Mailer\Mailer;
use Throwable;

/** E-mail client : colis expédié. */
final class SendOrderShippedEmail implements ListenerInterface
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
        if (!$event instanceof OrderShipped || $event->orderId < 1) {
            return;
        }

        try {
            $order = $this->orderDao->findById($event->orderId);
            /** @var Order|null $order */
            if ($order === null) {
                return;
            }

            $user = $this->userDao->findById($order->user_id);
            if ($user === null || trim($user->email) === '') {
                $this->logger->warning('SendOrderShippedEmail: client sans e-mail', [
                    'order_id' => $order->id,
                ]);
                return;
            }

            $store  = htmlspecialchars((string) ($this->shop['store_name'] ?? 'Astral Shop'), ENT_QUOTES, 'UTF-8');
            $name   = htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8');
            $base   = rtrim($this->appBaseUrl, '/');
            $link   = ($base !== '' ? $base : '') . '/orders/' . $order->id;
            $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

            $html = <<<HTML
            <div style="font-family:'Segoe UI',sans-serif;max-width:560px;margin:auto;padding:28px;color:#0f172a">
                <h2 style="color:#4f46e5;margin:0 0 12px">Votre colis a été envoyé</h2>
                <p>Bonjour <strong>{$name}</strong>,</p>
                <p>Bonne nouvelle : votre commande <strong>#{$order->id}</strong> sur <strong>{$store}</strong> a bien été expédiée.</p>
                <p>Vous pouvez suivre le statut depuis votre espace client.</p>
                <p style="text-align:center;margin:28px 0">
                    <a href="{$safeLink}"
                       style="background:#4f46e5;color:#fff;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:600">
                        Voir ma commande
                    </a>
                </p>
                <p style="color:#94a3b8;font-size:12px">Cet e-mail a été envoyé automatiquement.</p>
            </div>
            HTML;

            $this->mailer->send(
                $user->email,
                (string) ($this->shop['store_name'] ?? 'Astral Shop') . ' — Colis expédié #' . $order->id,
                $html,
            );

            $this->logger->info('E-mail expédition envoyé', [
                'order_id' => $order->id,
                'email'    => $user->email,
            ]);
        } catch (Throwable $e) {
            $this->logger->warning('SendOrderShippedEmail: échec envoi', [
                'order_id' => $event->orderId,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
