<!-- Vue : auth/register -->
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-mark" aria-hidden="true">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z"/>
            </svg>
        </div>
        <h1>Créer un compte</h1>
        <p class="auth-lead">Rejoignez la boutique pour commander en quelques clics</p>

        <form method="POST" action="/register" class="form-stack">
            <?= $csrf->field() ?>
            <?php if (!empty($redirect) && $redirect !== '/'): ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars((string) $redirect, ENT_QUOTES, 'UTF-8') ?>">
            <?php endif ?>
            <?= $viewEngine->partial('partials/validation-errors', ['errors' => $errors ?? []]) ?>

            <div class="field">
                <label for="name">Nom complet</label>
                <input type="text" id="name" name="name" autocomplete="name" autofocus
                       value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="input<?= !empty($errors['name']) ? ' is-invalid' : '' ?>"
                       placeholder="Jean Dupont">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'name', 'errors' => $errors ?? []]) ?>
            </div>

            <div class="field">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" autocomplete="email"
                       value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="input<?= !empty($errors['email']) ? ' is-invalid' : '' ?>"
                       placeholder="jean@exemple.com">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'email', 'errors' => $errors ?? []]) ?>
            </div>

            <div class="field">
                <label for="password">Mot de passe <span class="hint">(min. 8 caractères)</span></label>
                <input type="password" id="password" name="password" autocomplete="new-password" minlength="8"
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

            <button type="submit" class="btn-block">Créer le compte</button>
        </form>

        <p class="auth-footer">
            Déjà inscrit ?
            <a href="/login<?= !empty($redirect) && $redirect !== '/' ? '?redirect=' . rawurlencode((string) $redirect) : '' ?>">Se connecter</a>
        </p>
    </div>
</div>
