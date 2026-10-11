<?php
/** @var array<string, list<string>> $errors */
/** @var array<string, mixed> $old */
?>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-mark" aria-hidden="true">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17l-5.18 2.59a.75.75 0 01-1.08-.67V6.91a.75.75 0 01.45-.68l5.18-2.32a.75.75 0 01.61 0l5.18 2.32a.75.75 0 01.45.68v10.18a.75.75 0 01-1.08.67l-5.18-2.59a.75.75 0 00-.67 0z"/>
            </svg>
        </div>
        <h1>Installation</h1>
        <p class="auth-lead">Créez le compte administrateur de la boutique. Cette étape n’apparaît qu’une seule fois.</p>

        <form method="POST" action="/install" class="form-stack">
            <?= $csrf->field() ?>
            <?= $viewEngine->partial('partials/validation-errors', ['errors' => $errors ?? []]) ?>

            <div class="field">
                <label for="name">Nom complet</label>
                <input type="text" id="name" name="name" autocomplete="name" autofocus
                       value="<?= htmlspecialchars((string) ($old['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                       class="input<?= !empty($errors['name']) ? ' is-invalid' : '' ?>"
                       placeholder="Alex Martin">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'name', 'errors' => $errors ?? []]) ?>
            </div>

            <div class="field">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" autocomplete="email"
                       value="<?= htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                       class="input<?= !empty($errors['email']) ? ' is-invalid' : '' ?>"
                       placeholder="admin@boutique.local">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'email', 'errors' => $errors ?? []]) ?>
            </div>

            <div class="field">
                <label for="password">Mot de passe <span class="hint">(min. 8 caractères)</span></label>
                <input type="password" id="password" name="password" autocomplete="new-password" minlength="8"
                       class="input<?= !empty($errors['password']) ? ' is-invalid' : '' ?>">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'password', 'errors' => $errors ?? []]) ?>
            </div>

            <div class="field">
                <label for="password_confirmation">Confirmer le mot de passe</label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                       class="input">
            </div>

            <button type="submit" class="btn-block">Créer l’administrateur</button>
        </form>
    </div>
</div>
