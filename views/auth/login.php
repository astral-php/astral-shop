<!-- Vue : auth/login -->
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-mark" aria-hidden="true">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
            </svg>
        </div>
        <h1>Connexion</h1>
        <p class="auth-lead">Accédez à votre espace Astral Starter</p>

        <form method="POST" action="/login" class="form-stack">
            <?= $csrf->field() ?>
            <?php if (!empty($redirect) && $redirect !== '/'): ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars((string) $redirect, ENT_QUOTES, 'UTF-8') ?>">
            <?php endif ?>

            <div class="field">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" autocomplete="email" autofocus
                       value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="input<?= !empty($errors['email']) ? ' is-invalid' : '' ?>"
                       placeholder="vous@exemple.com">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'email', 'errors' => $errors ?? []]) ?>
            </div>

            <div class="field">
                <div class="field-row">
                    <label for="password">Mot de passe</label>
                    <a class="link-quiet" href="/forgot-password">Mot de passe oublié ?</a>
                </div>
                <input type="password" id="password" name="password" autocomplete="current-password"
                       class="input<?= !empty($errors['password']) ? ' is-invalid' : '' ?>"
                       placeholder="••••••••">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'password', 'errors' => $errors ?? []]) ?>
            </div>

            <button type="submit" class="btn-block">Se connecter</button>
        </form>

        <p class="auth-footer">
            Pas encore de compte ?
            <a href="/register<?= !empty($redirect) && $redirect !== '/' ? '?redirect=' . rawurlencode((string) $redirect) : '' ?>">Créer un compte</a>
        </p>
    </div>
</div>
