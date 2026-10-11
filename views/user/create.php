<!-- Vue : user/create -->
<div class="max-w-md mx-auto">
    <a href="/users" class="inline-flex items-center gap-1 text-sm text-gray-400 hover:text-gray-600 mb-6 transition">
        &larr; Retour à la liste
    </a>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">
            <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
        </h1>

        <form method="POST" action="/users" class="space-y-5">

            <?= $csrf->field() ?>
            <?= $viewEngine->partial('partials/validation-errors', ['errors' => $errors ?? []]) ?>

            <!-- Nom -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom complet</label>
                <input type="text" id="name" name="name"
                       value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition
                              <?= !empty($errors['name']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?>"
                       placeholder="Jean Dupont">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'name', 'errors' => $errors ?? []]) ?>
            </div>

            <!-- E-mail -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Adresse e-mail</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition
                              <?= !empty($errors['email']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?>"
                       placeholder="jean@example.com">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'email', 'errors' => $errors ?? []]) ?>
            </div>

            <!-- Mot de passe -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                <input type="password" id="password" name="password" minlength="8"
                       class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition
                              <?= !empty($errors['password']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?>"
                       placeholder="••••••••">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'password', 'errors' => $errors ?? []]) ?>
            </div>

            <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-xl transition shadow text-sm">
                Créer le compte
            </button>

        </form>
    </div>
</div>
