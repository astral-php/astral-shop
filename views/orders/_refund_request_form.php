<?php
/** @var \App\Models\Order $order */
/** @var \Core\CsrfGuard $csrf */
?>
<form method="POST" action="/orders/<?= (int) $order->id ?>/request-refund" class="form-stack"
      onsubmit="return confirm('Envoyer cette demande de remboursement ?');">
    <?= $csrf->field() ?>
    <div class="field">
        <label for="refund_reason">Motif <span class="hint">(10 à 500 caractères)</span></label>
        <textarea class="input" id="refund_reason" name="refund_reason" rows="3" required minlength="10" maxlength="500"></textarea>
    </div>
    <?php if ($order->isOfflinePayment()): ?>
        <div class="field">
            <label for="refund_holder">Titulaire du compte</label>
            <input class="input" type="text" id="refund_holder" name="refund_holder" required maxlength="255" autocomplete="off"
                   value="<?= htmlspecialchars($order->refund_holder, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label for="refund_iban">IBAN</label>
            <input class="input" type="text" id="refund_iban" name="refund_iban" required maxlength="42" autocomplete="off"
                   placeholder="FR76…"
                   value="<?= htmlspecialchars($order->refund_iban, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="field">
            <label for="refund_bic">BIC <span class="hint">(optionnel)</span></label>
            <input class="input" type="text" id="refund_bic" name="refund_bic" maxlength="16" autocomplete="off"
                   value="<?= htmlspecialchars($order->refund_bic, ENT_QUOTES, 'UTF-8') ?>">
        </div>
    <?php endif ?>
    <button type="submit" class="btn btn-primary">Envoyer la demande</button>
</form>
