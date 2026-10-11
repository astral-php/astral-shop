<!-- Vue : auth/forgot-password -->
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-mark" aria-hidden="true">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
            </svg>
        </div>
        <h1>Mot de passe oublié</h1>
        <p class="auth-lead">Recevez un lien de réinitialisation par e-mail</p>

        <?php if ($sent): ?>
            <div class="notice-ok">
                <span aria-hidden="true">✓</span>
                <p style="margin:0">
                    Si cette adresse e-mail est associée à un compte, vous recevrez
                    un lien de réinitialisation dans quelques instants.
                </p>
            </div>
        <?php else: ?>
            <form method="POST" action="/forgot-password" class="form-stack">
                <?= $csrf->field() ?>
                <?= $viewEngine->partial('partials/validation-errors', ['errors' => $errors ?? []]) ?>

                <div class="field">
                    <label for="email">Adresse e-mail</label>
                    <input type="email" id="email" name="email" autocomplete="email" autofocus
                           value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           class="input<?= !empty($errors['email']) ? ' is-invalid' : '' ?>"
                           placeholder="vous@exemple.com">
                    <?= $viewEngine->partial('partials/field-error', ['field' => 'email', 'errors' => $errors ?? []]) ?>
                </div>

                <button type="submit" class="btn-block">Envoyer le lien</button>
            </form>
        <?php endif ?>

        <p class="auth-footer">
            <a href="/login">← Retour à la connexion</a>
        </p>
    </div>
</div>
