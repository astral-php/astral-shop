<!-- Vue : auth/reset-password -->
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-mark" aria-hidden="true">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
            </svg>
        </div>
        <h1>Nouveau mot de passe</h1>
        <p class="auth-lead">Choisissez un mot de passe d’au moins 8 caractères</p>

        <form method="POST" action="/reset-password" class="form-stack">
            <?= $csrf->field() ?>
            <?= $viewEngine->partial('partials/validation-errors', ['errors' => $errors ?? []]) ?>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

            <div class="field">
                <label for="password">Nouveau mot de passe <span class="hint">(min. 8)</span></label>
                <input type="password" id="password" name="password" autocomplete="new-password" minlength="8" autofocus
                       class="input<?= !empty($errors['password']) ? ' is-invalid' : '' ?>"
                       placeholder="••••••••">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'password', 'errors' => $errors ?? []]) ?>
            </div>

            <div class="field">
                <label for="password_confirmation">Confirmer le mot de passe</label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                       class="input<?= !empty($errors['password_confirmation']) ? ' is-invalid' : '' ?>"
                       placeholder="••••••••">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'password_confirmation', 'errors' => $errors ?? []]) ?>
            </div>

            <button type="submit" class="btn-block">Enregistrer</button>
        </form>
    </div>
</div>
