<?php
/** @var \App\Models\Order $order */
/** @var array<string, mixed> $shop */
/** @var string $stripePublicKey */
/** @var string $clientSecret */
/** @var string $returnUrl */
/** @var string $amountFormatted */
?>
<section class="admin-panel" style="max-width:28rem">
    <h1>Paiement</h1>
    <p class="admin-nav">
        Commande #<?= (int) $order->id ?> —
        <strong><?= htmlspecialchars($amountFormatted, ENT_QUOTES, 'UTF-8') ?></strong> TTC
    </p>

    <div class="cart-summary" style="margin-bottom:1.25rem">
        <div style="display:flex;justify-content:space-between;gap:1rem">
            <span>Total à régler</span>
            <strong><?= htmlspecialchars($amountFormatted, ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
    </div>

    <div id="stripe-checkout"
         class="auth-card"
         data-public-key="<?= htmlspecialchars($stripePublicKey, ENT_QUOTES, 'UTF-8') ?>"
         data-client-secret="<?= htmlspecialchars($clientSecret, ENT_QUOTES, 'UTF-8') ?>"
         data-return-url="<?= htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8') ?>">

        <div id="payment-element" style="margin-bottom:1rem"></div>
        <div id="payment-message" class="field-error" style="display:none;margin-bottom:1rem"></div>

        <button type="button" id="submit-payment" class="btn-block">
            <span id="btn-text">Payer <?= htmlspecialchars($amountFormatted, ENT_QUOTES, 'UTF-8') ?></span>
            <span id="btn-spinner" style="display:none">Traitement…</span>
        </button>
    </div>

    <p class="product-detail__note" style="margin-top:1rem">
        Paiement sécurisé via Stripe. Les données carte ne transitent pas par notre serveur.
    </p>
    <p style="margin-top:1rem">
        <a class="btn btn-ghost" href="/checkout/change-payment/<?= (int) $order->id ?>">
            ← Changer de moyen de paiement
        </a>
    </p>
    <p><a href="/orders/<?= (int) $order->id ?>">← Retour à la commande</a></p>
</section>

<script src="https://js.stripe.com/v3/"></script>
<script>
(async () => {
    const el = document.getElementById('stripe-checkout');
    if (!el) return;

    const publicKey = el.dataset.publicKey;
    const clientSecret = el.dataset.clientSecret;
    const returnUrl = el.dataset.returnUrl;

    if (!publicKey || !clientSecret) {
        const msg = document.getElementById('payment-message');
        msg.textContent = 'Configuration Stripe incomplète.';
        msg.style.display = 'block';
        return;
    }

    const stripe = Stripe(publicKey);
    const elements = stripe.elements({ clientSecret });
    const paymentElement = elements.create('payment', { layout: 'tabs' });
    paymentElement.mount('#payment-element');

    const form = document.getElementById('submit-payment');
    const btnText = document.getElementById('btn-text');
    const btnSpinner = document.getElementById('btn-spinner');
    const msgDiv = document.getElementById('payment-message');

    form.addEventListener('click', async (e) => {
        e.preventDefault();
        form.disabled = true;
        btnText.style.display = 'none';
        btnSpinner.style.display = 'inline';
        msgDiv.style.display = 'none';

        const { error } = await stripe.confirmPayment({
            elements,
            confirmParams: { return_url: returnUrl },
        });

        if (error) {
            msgDiv.textContent = error.message || 'Erreur de paiement.';
            msgDiv.style.display = 'block';
            form.disabled = false;
            btnText.style.display = 'inline';
            btnSpinner.style.display = 'none';
        }
    });
})();
</script>
