<?php

declare(strict_types=1);

namespace App\Services;

use App\Dao\OrderDao;
use App\Events\OrderBecamePaid;
use App\Events\OrderBecameRefunded;
use App\Events\OrderShipped;
use App\Events\RefundRequestAccepted;
use App\Events\RefundRequestRejected;
use App\Events\RefundRequested;
use App\Models\Order;
use AstralPayment\PaymentService;
use Core\Events\EventDispatcher;
use Throwable;

/**
 * Transitions admin : paiement offline + suivi colis + remboursements.
 * Règle : pas de préparation / envoi tant que non payée.
 */
final class OrderFulfillmentService
{
    public function __construct(
        private OrderDao $orderDao,
        private EventDispatcher $dispatcher,
        private PaymentService $paymentService,
    ) {}

    /**
     * @return array{ok: true, order: Order}|array{ok: false, error: string}
     */
    public function markPaid(int $orderId): array
    {
        $order = $this->requireOrder($orderId);
        if ($order === null) {
            return ['ok' => false, 'error' => 'Commande introuvable.'];
        }

        if ($order->isPaid()) {
            return ['ok' => false, 'error' => 'Cette commande est déjà payée.'];
        }

        if (!in_array($order->payment_method, [Order::PAYMENT_TRANSFER, Order::PAYMENT_CHECK], true)) {
            return ['ok' => false, 'error' => 'La validation manuelle ne s’applique qu’au virement ou au chèque.'];
        }

        if ($order->status === 'failed') {
            return ['ok' => false, 'error' => 'Commande en échec — non applicable.'];
        }

        $this->orderDao->update($order->id, ['status' => 'paid']);
        $this->dispatcher->dispatch(new OrderBecamePaid($order->id));

        $fresh = $this->orderDao->findById($order->id);
        /** @var Order $fresh */

        return ['ok' => true, 'order' => $fresh];
    }

    /**
     * @return array{ok: true, order: Order}|array{ok: false, error: string}
     */
    public function markPreparing(int $orderId): array
    {
        $order = $this->requireOrder($orderId);
        if ($order === null) {
            return ['ok' => false, 'error' => 'Commande introuvable.'];
        }

        if (!$order->isPaid()) {
            return ['ok' => false, 'error' => 'La commande doit être payée avant la préparation.'];
        }

        if ($order->fulfillment_status !== Order::FULFILLMENT_AWAITING) {
            return ['ok' => false, 'error' => 'Passage en préparation impossible depuis l’état actuel.'];
        }

        $this->orderDao->update($order->id, [
            'fulfillment_status' => Order::FULFILLMENT_PREPARING,
        ]);

        $fresh = $this->orderDao->findById($order->id);
        /** @var Order $fresh */

        return ['ok' => true, 'order' => $fresh];
    }

    /**
     * @return array{ok: true, order: Order}|array{ok: false, error: string}
     */
    public function markShipped(int $orderId): array
    {
        $order = $this->requireOrder($orderId);
        if ($order === null) {
            return ['ok' => false, 'error' => 'Commande introuvable.'];
        }

        if (!$order->isPaid()) {
            return ['ok' => false, 'error' => 'La commande doit être payée avant l’expédition.'];
        }

        if ($order->fulfillment_status !== Order::FULFILLMENT_PREPARING) {
            return ['ok' => false, 'error' => 'Passez d’abord la commande « En préparation ».'];
        }

        $this->orderDao->update($order->id, [
            'fulfillment_status' => Order::FULFILLMENT_SHIPPED,
        ]);
        $this->dispatcher->dispatch(new OrderShipped($order->id));

        $fresh = $this->orderDao->findById($order->id);
        /** @var Order $fresh */

        return ['ok' => true, 'order' => $fresh];
    }

    /**
     * Demande client de remboursement (9.1d — D1–D5, D4 RIB offline).
     *
     * @param array{iban?: string, bic?: string, holder?: string} $rib
     * @return array{ok: true, order: Order}|array{ok: false, error: string}
     */
    public function requestRefund(int $orderId, int $userId, string $reason, array $rib = []): array
    {
        $order = $this->requireOrder($orderId);
        if ($order === null || (int) $order->user_id !== $userId) {
            return ['ok' => false, 'error' => 'Commande introuvable.'];
        }

        if (!$order->canRequestRefund()) {
            if ($order->hasRefundRequestPending()) {
                return ['ok' => false, 'error' => 'Une demande de remboursement est déjà en cours.'];
            }
            if ($order->hasRefundRequestAccepted()) {
                return ['ok' => false, 'error' => 'Votre demande a déjà été acceptée — traitement en cours.'];
            }

            return ['ok' => false, 'error' => 'Cette commande ne peut pas faire l’objet d’une demande de remboursement.'];
        }

        $reason = trim($reason);
        $reasonLen = mb_strlen($reason);
        if ($reasonLen < 10 || $reasonLen > 500) {
            return ['ok' => false, 'error' => 'Le motif doit contenir entre 10 et 500 caractères.'];
        }

        $data = [
            'refund_request_status' => Order::REFUND_REQUEST_REQUESTED,
            'refund_request_reason' => $reason,
            'refund_request_at'     => date('Y-m-d H:i:s'),
            'refund_reject_reason'  => '',
            'refund_rejected_at'    => null,
        ];

        if ($order->isOfflinePayment()) {
            $iban = $this->normalizeIban((string) ($rib['iban'] ?? ''));
            $bic = strtoupper(preg_replace('/\s+/', '', (string) ($rib['bic'] ?? '')) ?? '');
            $holder = trim((string) ($rib['holder'] ?? ''));

            if ($iban === '' || !$this->isPlausibleIban($iban)) {
                return ['ok' => false, 'error' => 'IBAN requis pour un remboursement par virement (15 à 34 caractères).'];
            }
            if ($holder === '') {
                return ['ok' => false, 'error' => 'Titulaire du compte requis.'];
            }

            $data['refund_iban'] = $iban;
            $data['refund_bic'] = $bic;
            $data['refund_holder'] = $holder;
        }

        $this->orderDao->update($order->id, $data);
        $this->dispatcher->dispatch(new RefundRequested($order->id));

        $fresh = $this->orderDao->findById($order->id);
        /** @var Order $fresh */

        return ['ok' => true, 'order' => $fresh];
    }

    /**
     * Accepte une demande client (D7/D8).
     * Carte : accepte puis rembourse Stripe. Offline : accepte seulement (virement hors app ensuite).
     *
     * @return array{ok: true, order: Order}|array{ok: false, error: string}
     */
    public function acceptRefundRequest(int $orderId): array
    {
        $order = $this->requireOrder($orderId);
        if ($order === null) {
            return ['ok' => false, 'error' => 'Commande introuvable.'];
        }

        if (!$order->hasRefundRequestPending()) {
            return ['ok' => false, 'error' => 'Aucune demande de remboursement en attente.'];
        }

        if (!$order->isPaid()) {
            return ['ok' => false, 'error' => 'La commande n’est plus payée.'];
        }

        $this->orderDao->update($order->id, [
            'refund_request_status' => Order::REFUND_REQUEST_ACCEPTED,
            'refund_accepted_at'    => date('Y-m-d H:i:s'),
        ]);
        $this->dispatcher->dispatch(new RefundRequestAccepted($order->id));

        if ($order->payment_method === Order::PAYMENT_CARD) {
            return $this->refundCard($order->id);
        }

        $fresh = $this->orderDao->findById($order->id);
        /** @var Order $fresh */

        return ['ok' => true, 'order' => $fresh];
    }

    /**
     * Refuse une demande client (D9).
     *
     * @return array{ok: true, order: Order}|array{ok: false, error: string}
     */
    public function rejectRefundRequest(int $orderId, string $rejectReason): array
    {
        $order = $this->requireOrder($orderId);
        if ($order === null) {
            return ['ok' => false, 'error' => 'Commande introuvable.'];
        }

        if (!$order->hasRefundRequestPending()) {
            return ['ok' => false, 'error' => 'Aucune demande de remboursement en attente.'];
        }

        $rejectReason = trim($rejectReason);
        $len = mb_strlen($rejectReason);
        if ($len < 10 || $len > 500) {
            return ['ok' => false, 'error' => 'Le motif de refus doit contenir entre 10 et 500 caractères.'];
        }

        $this->orderDao->update($order->id, [
            'refund_request_status' => Order::REFUND_REQUEST_REJECTED,
            'refund_reject_reason'  => $rejectReason,
            'refund_rejected_at'    => date('Y-m-d H:i:s'),
        ]);
        $this->dispatcher->dispatch(new RefundRequestRejected($order->id));

        $fresh = $this->orderDao->findById($order->id);
        /** @var Order $fresh */

        return ['ok' => true, 'order' => $fresh];
    }

    /**
     * Remboursement total CB via Stripe (MVP).
     *
     * @return array{ok: true, order: Order}|array{ok: false, error: string}
     */
    public function refundCard(int $orderId): array
    {
        $order = $this->requireOrder($orderId);
        if ($order === null) {
            return ['ok' => false, 'error' => 'Commande introuvable.'];
        }

        if ($order->isRefunded()) {
            return ['ok' => false, 'error' => 'Cette commande est déjà remboursée.'];
        }

        if (!$order->isPaid()) {
            return ['ok' => false, 'error' => 'Seule une commande payée peut être remboursée.'];
        }

        if ($order->payment_method !== Order::PAYMENT_CARD) {
            return ['ok' => false, 'error' => 'Utilisez « Marquer remboursée » pour un paiement offline.'];
        }

        $intentId = trim((string) ($order->stripe_intent_id ?? ''));
        if ($intentId === '') {
            return ['ok' => false, 'error' => 'Aucun PaymentIntent Stripe sur cette commande.'];
        }

        try {
            $this->paymentService->refund($intentId);
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Stripe a refusé le remboursement : ' . $e->getMessage()];
        }

        $update = [
            'status'      => 'refunded',
            'refunded_at' => date('Y-m-d H:i:s'),
        ];
        if ($order->hasRefundRequestPending() || $order->hasRefundRequestAccepted()) {
            $update['refund_request_status'] = Order::REFUND_REQUEST_ACCEPTED;
            if ($order->refund_accepted_at === null || trim((string) $order->refund_accepted_at) === '') {
                $update['refund_accepted_at'] = date('Y-m-d H:i:s');
            }
        }

        $this->orderDao->update($order->id, $update);
        $this->dispatcher->dispatch(new OrderBecameRefunded($order->id));

        $fresh = $this->orderDao->findById($order->id);
        /** @var Order $fresh */

        return ['ok' => true, 'order' => $fresh];
    }

    /**
     * Remboursement offline (virement sortant) — RIB obligatoire, pas d’appel Stripe.
     * Réutilise le RIB déjà saisi par le client si le formulaire est vide.
     *
     * @param array{iban?: string, bic?: string, holder?: string, reference?: string} $rib
     * @return array{ok: true, order: Order}|array{ok: false, error: string}
     */
    public function markRefundedOffline(int $orderId, array $rib): array
    {
        $order = $this->requireOrder($orderId);
        if ($order === null) {
            return ['ok' => false, 'error' => 'Commande introuvable.'];
        }

        if ($order->isRefunded()) {
            return ['ok' => false, 'error' => 'Cette commande est déjà remboursée.'];
        }

        if (!$order->isPaid()) {
            return ['ok' => false, 'error' => 'Seule une commande payée peut être remboursée.'];
        }

        if (!$order->isOfflinePayment()) {
            return ['ok' => false, 'error' => 'Utilisez « Rembourser (Stripe) » pour une commande carte.'];
        }

        $iban = $this->normalizeIban((string) ($rib['iban'] ?? ''));
        $bic = strtoupper(preg_replace('/\s+/', '', (string) ($rib['bic'] ?? '')) ?? '');
        $holder = trim((string) ($rib['holder'] ?? ''));
        $reference = trim((string) ($rib['reference'] ?? ''));

        if ($iban === '' && $order->hasRefundRib()) {
            $iban = $this->normalizeIban($order->refund_iban);
            $bic = $bic !== '' ? $bic : strtoupper(preg_replace('/\s+/', '', $order->refund_bic) ?? '');
            $holder = $holder !== '' ? $holder : trim($order->refund_holder);
        }

        if ($iban === '' || !$this->isPlausibleIban($iban)) {
            return ['ok' => false, 'error' => 'IBAN client requis (15 à 34 caractères alphanumériques).'];
        }

        if ($holder === '') {
            return ['ok' => false, 'error' => 'Titulaire du compte requis.'];
        }

        if ($reference === '') {
            $reference = trim($order->refund_reference) !== ''
                ? $order->refund_reference
                : 'REM-' . $order->id;
        }

        $update = [
            'status'           => 'refunded',
            'refund_iban'      => $iban,
            'refund_bic'       => $bic,
            'refund_holder'    => $holder,
            'refund_reference' => $reference,
            'refunded_at'      => date('Y-m-d H:i:s'),
        ];
        if ($order->hasRefundRequestPending() || $order->hasRefundRequestAccepted()) {
            $update['refund_request_status'] = Order::REFUND_REQUEST_ACCEPTED;
            if ($order->refund_accepted_at === null || trim((string) $order->refund_accepted_at) === '') {
                $update['refund_accepted_at'] = date('Y-m-d H:i:s');
            }
        }

        $this->orderDao->update($order->id, $update);
        $this->dispatcher->dispatch(new OrderBecameRefunded($order->id));

        $fresh = $this->orderDao->findById($order->id);
        /** @var Order $fresh */

        return ['ok' => true, 'order' => $fresh];
    }

    private function requireOrder(int $orderId): ?Order
    {
        if ($orderId < 1) {
            return null;
        }

        /** @var Order|null */
        return $this->orderDao->findById($orderId);
    }

    private function normalizeIban(string $iban): string
    {
        return strtoupper(preg_replace('/\s+/', '', $iban) ?? '');
    }

    private function isPlausibleIban(string $iban): bool
    {
        $len = strlen($iban);

        return $len >= 15 && $len <= 34 && (bool) preg_match('/^[A-Z0-9]+$/', $iban);
    }
}
