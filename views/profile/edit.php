<!-- Vue : profile/edit -->
<div class="max-w-2xl mx-auto">

    <h1 class="text-3xl font-bold text-gray-900 mb-8">Mon profil</h1>

    <!-- Onglets -->
    <?php $tab = ($password_tab ?? false) ? 'password' : 'profile'; ?>
    <div class="flex gap-1 mb-8 bg-gray-100 p-1 rounded-xl w-fit">
        <a href="/profile"
           class="px-4 py-2 rounded-lg text-sm font-medium transition
                  <?= $tab === 'profile' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
            Informations
        </a>
        <a href="/profile#password"
           onclick="document.getElementById('password-section').scrollIntoView({behavior:'smooth'});return false;"
           class="px-4 py-2 rounded-lg text-sm font-medium transition
                  <?= $tab === 'password' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
            Mot de passe
        </a>
    </div>

    <!-- ─── Informations personnelles ─── -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mb-6">

        <div class="flex items-center gap-4 mb-6">
            <div class="w-14 h-14 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xl shrink-0">
                <?= mb_strtoupper(mb_substr($user->name ?? '', 0, 1)) ?>
            </div>
            <div>
                <p class="font-semibold text-gray-800"><?= htmlspecialchars($user->name ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-sm text-gray-400"><?= htmlspecialchars($user->email ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                <span class="inline-flex items-center mt-1 text-xs font-medium px-2 py-0.5 rounded-full
                    <?= ($user->role ?? '') === 'admin'
                        ? 'bg-amber-100 text-amber-700 border border-amber-200'
                        : 'bg-indigo-50 text-indigo-600 border border-indigo-100' ?>">
                    <?= htmlspecialchars(ucfirst($user->role ?? 'user'), ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
        </div>

        <form method="POST" action="/profile" class="space-y-5">
            <?= $csrf->field() ?>
            <?= $viewEngine->partial('partials/validation-errors', ['errors' => $errors ?? []]) ?>

            <!-- Nom -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom complet</label>
                <input type="text" id="name" name="name"
                       value="<?= htmlspecialchars($old['name'] ?? $user->name ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition
                              <?= !empty($errors['name']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?>">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'name', 'errors' => $errors ?? []]) ?>
            </div>

            <!-- E-mail -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Adresse e-mail</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($old['email'] ?? $user->email ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition
                              <?= !empty($errors['email']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?>">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'email', 'errors' => $errors ?? []]) ?>
            </div>

            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2.5 rounded-xl transition shadow text-sm">
                Enregistrer les modifications
            </button>
        </form>
    </div>

    <!-- ─── Changement de mot de passe ─── -->
    <div id="password-section" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">

        <h2 class="text-lg font-semibold text-gray-800 mb-6">Changer le mot de passe</h2>

        <form method="POST" action="/profile/password" class="space-y-5">
            <?= $csrf->field() ?>
            <?= $viewEngine->partial('partials/validation-errors', ['errors' => $errors ?? []]) ?>

            <!-- Mot de passe actuel -->
            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe actuel</label>
                <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                       class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition
                              <?= !empty($errors['current_password']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?>"
                       placeholder="••••••••">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'current_password', 'errors' => $errors ?? []]) ?>
            </div>

            <!-- Nouveau mot de passe -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                    Nouveau mot de passe <span class="text-gray-400 font-normal">(min. 8 caractères)</span>
                </label>
                <input type="password" id="password" name="password" autocomplete="new-password" minlength="8"
                       class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition
                              <?= !empty($errors['password']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?>"
                       placeholder="••••••••">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'password', 'errors' => $errors ?? []]) ?>
            </div>

            <!-- Confirmation -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmer le nouveau mot de passe</label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                       class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition
                              <?= !empty($errors['password_confirmation']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?>"
                       placeholder="••••••••">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'password_confirmation', 'errors' => $errors ?? []]) ?>
            </div>

            <button type="submit"
                    class="bg-gray-800 hover:bg-gray-900 text-white font-semibold px-6 py-2.5 rounded-xl transition shadow text-sm">
                Modifier le mot de passe
            </button>
        </form>
    </div>

</div>
