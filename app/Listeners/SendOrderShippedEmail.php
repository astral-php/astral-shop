<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Dao\OrderDao;
use App\Dao\UserDao;
use App\Events\OrderShipped;
use App\Models\Order;
use AstralShipping\Dao\OrderShipmentDao;
use Core\Events\EventInterface;
use Core\Events\ListenerInterface;
use Core\Logger;
use Core\Mailer\Mailer;
use Throwable;

/** E-mail client : colis expédié ou retrait boutique. */
final class SendOrderShippedEmail implements ListenerInterface
{
    /** @param array<string, mixed> $shop */
    public function __construct(
        private Mailer $mailer,
        private OrderDao $orderDao,
        private UserDao $userDao,
        private OrderShipmentDao $shipmentDao,
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

            $shipment = $this->shipmentDao->findByOrder($order->id);
            $isPickup = is_array($shipment)
                && (
                    ($shipment['method_code'] ?? '') === 'store_pickup'
                    || ($shipment['carrier'] ?? '') === 'store'
                );

            $store  = htmlspecialchars((string) ($this->shop['store_name'] ?? 'Astral Shop'), ENT_QUOTES, 'UTF-8');
            $name   = htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8');
            $base   = rtrim($this->appBaseUrl, '/');
            $link   = ($base !== '' ? $base : '') . '/orders/' . $order->id;
            $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
            $storeAddress = trim((string) ($this->shop['store_address'] ?? ''));
            $addressHtml = '';
            if ($isPickup && $storeAddress !== '') {
                $safeAddr = nl2br(htmlspecialchars($storeAddress, ENT_QUOTES, 'UTF-8'));
                $addressHtml = "<p><strong>Adresse du magasin :</strong><br>{$safeAddr}</p>";
            }

            if ($isPickup) {
                $title = 'Votre commande est prête / retirée';
                $body  = "Bonne nouvelle : votre commande <strong>#{$order->id}</strong> sur <strong>{$store}</strong> est marquée comme <strong>retirée en boutique</strong>.";
                $subject = (string) ($this->shop['store_name'] ?? 'Astral Shop') . ' — Retrait boutique #' . $order->id;
            } else {
                $title = 'Votre colis a été envoyé';
                $body  = "Bonne nouvelle : votre commande <strong>#{$order->id}</strong> sur <strong>{$store}</strong> a bien été expédiée.";
                $subject = (string) ($this->shop['store_name'] ?? 'Astral Shop') . ' — Colis expédié #' . $order->id;
            }

            $html = <<<HTML
            <div style="font-family:'Segoe UI',sans-serif;max-width:560px;margin:auto;padding:28px;color:#0f172a">
                <h2 style="color:#4f46e5;margin:0 0 12px">{$title}</h2>
                <p>Bonjour <strong>{$name}</strong>,</p>
                <p>{$body}</p>
                {$addressHtml}
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

            $this->mailer->send($user->email, $subject, $html);

            $this->logger->info('E-mail expédition / retrait envoyé', [
                'order_id' => $order->id,
                'email'    => $user->email,
                'pickup'   => $isPickup,
            ]);
        } catch (Throwable $e) {
            $this->logger->warning('SendOrderShippedEmail: échec envoi', [
                'order_id' => $event->orderId,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
