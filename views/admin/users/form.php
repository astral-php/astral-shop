<?php
/** @var array<string, list<string>> $errors */
/** @var array<string, mixed> $old */
$role = (string) ($old['role'] ?? 'admin');
?>
<div class="max-w-lg">
    <p class="text-sm text-slate-500 mb-6">
        Créez un <strong>gestionnaire</strong> (accès back-office) ou un <strong>client</strong> boutique.
    </p>

    <form method="POST" action="/admin/users" class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 shadow-sm">
        <?= $csrf->field() ?>
        <?= $viewEngine->partial('partials/validation-errors', ['errors' => $errors ?? []]) ?>

        <div>
            <label for="name" class="block text-sm font-semibold text-slate-700 mb-1">Nom</label>
            <input type="text" id="name" name="name" required
                   value="<?= htmlspecialchars((string) ($old['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200">
            <?= $viewEngine->partial('partials/field-error', ['field' => 'name', 'errors' => $errors ?? []]) ?>
        </div>

        <div>
            <label for="email" class="block text-sm font-semibold text-slate-700 mb-1">E-mail</label>
            <input type="email" id="email" name="email" required
                   value="<?= htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200">
            <?= $viewEngine->partial('partials/field-error', ['field' => 'email', 'errors' => $errors ?? []]) ?>
        </div>

        <div>
            <label for="password" class="block text-sm font-semibold text-slate-700 mb-1">Mot de passe</label>
            <input type="password" id="password" name="password" required minlength="8"
                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200">
            <?= $viewEngine->partial('partials/field-error', ['field' => 'password', 'errors' => $errors ?? []]) ?>
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 mb-1">Confirmation</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8"
                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200">
        </div>

        <div>
            <label for="role" class="block text-sm font-semibold text-slate-700 mb-1">Type de compte</label>
            <select id="role" name="role"
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200">
                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Gestionnaire (admin)</option>
                <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>Client</option>
            </select>
            <?= $viewEngine->partial('partials/field-error', ['field' => 'role', 'errors' => $errors ?? []]) ?>
        </div>

        <div class="flex flex-wrap gap-3 pt-2">
            <button type="submit"
                    class="rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2">
                Créer le compte
            </button>
            <a href="/admin/users" class="rounded-lg border border-slate-200 text-slate-600 text-sm font-medium px-4 py-2 hover:border-indigo-200">
                Annuler
            </a>
        </div>
    </form>
</div>
