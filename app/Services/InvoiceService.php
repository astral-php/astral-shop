<?php

declare(strict_types=1);

namespace App\Services;

use App\Dao\OrderDao;
use App\Dao\OrderItemDao;
use App\Models\Order;
use App\Support\Money;
use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;

/** Génère et stocke une facture PDF pour une commande payée. */
final class InvoiceService
{
    public function __construct(
        private OrderDao $orderDao,
        private OrderItemDao $orderItemDao,
        /** @var array<string, mixed> */
        private array $shop = [],
        private string $storagePath = '',
    ) {
        if ($this->storagePath === '') {
            $this->storagePath = dirname(__DIR__, 2) . '/storage/invoices';
        }
    }

    /**
     * Crée la facture si absente. Retourne le chemin relatif (sous storage/) ou null.
     */
    public function generateForOrder(Order $order): ?string
    {
        if (!$order->isPaid()) {
            return null;
        }

        if ($order->hasInvoice()) {
            return $order->invoice_path;
        }

        if (!is_dir($this->storagePath) && !mkdir($this->storagePath, 0775, true) && !is_dir($this->storagePath)) {
            return null;
        }

        $relative = 'invoices/facture-' . $order->id . '.pdf';
        $absolute = dirname($this->storagePath) . '/' . $relative;

        try {
            $html = $this->buildHtml($order);
            $options = new Options();
            $options->set('isRemoteEnabled', false);
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4');
            $dompdf->render();
            file_put_contents($absolute, $dompdf->output());

            $this->orderDao->update($order->id, ['invoice_path' => $relative]);
            $order->invoice_path = $relative;

            return $relative;
        } catch (Throwable) {
            return null;
        }
    }

    public function absolutePath(Order $order): ?string
    {
        if (!$order->hasInvoice()) {
            return null;
        }

        $path = dirname($this->storagePath) . '/' . ltrim((string) $order->invoice_path, '/');
        return is_file($path) ? $path : null;
    }

    private function buildHtml(Order $order): string
    {
        $store  = htmlspecialchars((string) ($this->shop['store_name'] ?? 'Astral Shop'), ENT_QUOTES, 'UTF-8');
        $symbol = (string) ($this->shop['currency_symbol'] ?? '€');
        $items  = $this->orderItemDao->findByOrder($order->id);
        $ref    = htmlspecialchars($order->paymentReference() ?? ('CMD-' . $order->id), ENT_QUOTES, 'UTF-8');

        $rows = '';
        foreach ($items as $item) {
            $rows .= '<tr>'
                . '<td>' . htmlspecialchars($item->product_name, ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="text-align:center">' . (int) $item->quantity . '</td>'
                . '<td style="text-align:right">' . htmlspecialchars(Money::formatCents($item->unit_price_cents, $symbol), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="text-align:right">' . htmlspecialchars(Money::formatCents($item->line_total_cents, $symbol), ENT_QUOTES, 'UTF-8') . '</td>'
                . '</tr>';
        }

        $addr = htmlspecialchars(
            trim($order->shipping_name . "\n" . $order->shipping_line1 . "\n"
                . $order->shipping_postal . ' ' . $order->shipping_city . "\n"
                . $order->shipping_country),
            ENT_QUOTES,
            'UTF-8',
        );
        $addr = nl2br($addr);

        $subtotal = htmlspecialchars($order->subtotalLabel($symbol), ENT_QUOTES, 'UTF-8');
        $shipping = htmlspecialchars($order->shippingLabel($symbol), ENT_QUOTES, 'UTF-8');
        $total    = htmlspecialchars($order->totalLabel($symbol), ENT_QUOTES, 'UTF-8');
        $date     = htmlspecialchars($order->created_at, ENT_QUOTES, 'UTF-8');
        $method   = htmlspecialchars($order->paymentMethodLabel(), ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr"><head><meta charset="UTF-8"><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; }
            h1 { color: #4f46e5; font-size: 22px; margin: 0 0 8px; }
            table { width: 100%; border-collapse: collapse; margin: 16px 0; }
            th, td { padding: 8px 6px; border-bottom: 1px solid #e2e8f0; }
            th { text-align: left; color: #64748b; font-size: 11px; }
            .muted { color: #64748b; }
            .totals { width: 280px; margin-left: auto; }
            .totals td { border: 0; padding: 4px 0; }
            .totals .grand { font-weight: bold; font-size: 14px; padding-top: 8px; }
        </style></head><body>
            <h1>Facture</h1>
            <p class="muted">{$store}</p>
            <p><strong>Commande #{$order->id}</strong> · Réf. {$ref}<br>
            Date : {$date}<br>
            Paiement : {$method}</p>
            <p><strong>Livraison / facturation</strong><br>{$addr}</p>
            <table>
                <thead><tr><th>Article</th><th style="text-align:center">Qté</th><th style="text-align:right">P.U. TTC</th><th style="text-align:right">Total</th></tr></thead>
                <tbody>{$rows}</tbody>
            </table>
            <table class="totals">
                <tr><td>Sous-total TTC</td><td style="text-align:right">{$subtotal}</td></tr>
                <tr><td>Livraison</td><td style="text-align:right">{$shipping}</td></tr>
                <tr class="grand"><td>Total TTC</td><td style="text-align:right">{$total}</td></tr>
            </table>
            <p class="muted" style="margin-top:32px;font-size:10px">Document généré automatiquement — {$store}</p>
        </body></html>
        HTML;
    }
}
