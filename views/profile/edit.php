<?php
/** @var \App\Models\User|null $user */
/** @var array<string, list<string>> $errors */
/** @var array<string, mixed> $old */
$errors = $errors ?? [];
$old = $old ?? [];
$addressTab = !empty($address_tab);
$passwordTab = !empty($password_tab);
$billingSame = ((string) ($old['billing_same_as_shipping'] ?? (($user?->billingSameAsShipping() ?? true) ? '1' : '0'))) !== '0';

$shipVal = static function (string $key) use ($old, $user): string {
    $fallback = match ($key) {
        'shipping_name'    => (string) ($user->shipping_name ?: $user->name ?? ''),
        'shipping_line1'   => (string) ($user->shipping_line1 ?? ''),
        'shipping_postal'    => (string) ($user->shipping_postal ?? ''),
        'shipping_city'    => (string) ($user->shipping_city ?? ''),
        'shipping_country' => (string) ($user->shipping_country ?: 'FR'),
        'billing_name'     => (string) ($user->billing_name ?: $user->name ?? ''),
        'billing_line1'    => (string) ($user->billing_line1 ?? ''),
        'billing_postal'     => (string) ($user->billing_postal ?? ''),
        'billing_city'     => (string) ($user->billing_city ?? ''),
        'billing_country'  => (string) ($user->billing_country ?: 'FR'),
        default            => '',
    };

    return htmlspecialchars((string) ($old[$key] ?? $fallback), ENT_QUOTES, 'UTF-8');
};
?>
<!-- Vue : profile/edit -->
<div class="max-w-2xl mx-auto">

    <h1 class="text-3xl font-bold text-gray-900 mb-8">Mon profil</h1>

    <?php $tab = $passwordTab ? 'password' : ($addressTab ? 'addresses' : 'profile'); ?>
    <div class="flex flex-wrap gap-1 mb-8 bg-gray-100 p-1 rounded-xl w-fit">
        <a href="/profile"
           class="px-4 py-2 rounded-lg text-sm font-medium transition
                  <?= $tab === 'profile' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
            Informations
        </a>
        <a href="/profile#addresses"
           onclick="document.getElementById('addresses-section')?.scrollIntoView({behavior:'smooth'});return false;"
           class="px-4 py-2 rounded-lg text-sm font-medium transition
                  <?= $tab === 'addresses' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
            Adresses
        </a>
        <a href="/profile#password"
           onclick="document.getElementById('password-section')?.scrollIntoView({behavior:'smooth'});return false;"
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
            <?php if (!$addressTab && !$passwordTab): ?>
                <?= $viewEngine->partial('partials/validation-errors', ['errors' => $errors]) ?>
            <?php endif ?>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom complet</label>
                <input type="text" id="name" name="name"
                       value="<?= htmlspecialchars($old['name'] ?? $user->name ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition
                              <?= !empty($errors['name']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?>">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'name', 'errors' => $errors]) ?>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Adresse e-mail</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($old['email'] ?? $user->email ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition
                              <?= !empty($errors['email']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?>">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'email', 'errors' => $errors]) ?>
            </div>

            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2.5 rounded-xl transition shadow text-sm">
                Enregistrer les modifications
            </button>
        </form>
    </div>

    <!-- ─── Adresses ─── -->
    <div id="addresses-section" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-2">Adresses</h2>
        <p class="text-sm text-gray-500 mb-6">
            Utilisées pour préremplir le checkout (livraison domicile) et la facture.
        </p>

        <form method="POST" action="/profile/addresses" class="space-y-5" id="profile-addresses-form">
            <?= $csrf->field() ?>
            <?php if ($addressTab): ?>
                <?= $viewEngine->partial('partials/validation-errors', ['errors' => $errors]) ?>
            <?php endif ?>

            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Livraison</h3>

            <div>
                <label for="shipping_name" class="block text-sm font-medium text-gray-700 mb-1">Nom complet</label>
                <input type="text" id="shipping_name" name="shipping_name" required
                       value="<?= $shipVal('shipping_name') ?>"
                       class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition
                              <?= !empty($errors['shipping_name']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?>">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'shipping_name', 'errors' => $errors]) ?>
            </div>
            <div>
                <label for="shipping_line1" class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                <input type="text" id="shipping_line1" name="shipping_line1" required
                       value="<?= $shipVal('shipping_line1') ?>"
                       class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition
                              <?= !empty($errors['shipping_line1']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?>">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'shipping_line1', 'errors' => $errors]) ?>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="shipping_postal" class="block text-sm font-medium text-gray-700 mb-1">Code postal</label>
                    <input type="text" id="shipping_postal" name="shipping_postal" required
                           value="<?= $shipVal('shipping_postal') ?>"
                           class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition
                                  <?= !empty($errors['shipping_postal']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?>">
                    <?= $viewEngine->partial('partials/field-error', ['field' => 'shipping_postal', 'errors' => $errors]) ?>
                </div>
                <div>
                    <label for="shipping_city" class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
                    <input type="text" id="shipping_city" name="shipping_city" required
                           value="<?= $shipVal('shipping_city') ?>"
                           class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition
                                  <?= !empty($errors['shipping_city']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?>">
                    <?= $viewEngine->partial('partials/field-error', ['field' => 'shipping_city', 'errors' => $errors]) ?>
                </div>
            </div>
            <div>
                <label for="shipping_country" class="block text-sm font-medium text-gray-700 mb-1">Pays (code ISO)</label>
                <input type="text" id="shipping_country" name="shipping_country" maxlength="2" required
                       value="<?= $shipVal('shipping_country') ?>"
                       class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition
                              <?= !empty($errors['shipping_country']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?>">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'shipping_country', 'errors' => $errors]) ?>
            </div>

            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide pt-2">Facturation</h3>

            <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-100 bg-gray-50 cursor-pointer">
                <input type="hidden" name="billing_same_as_shipping" value="0">
                <input type="checkbox" id="profile_billing_same" name="billing_same_as_shipping" value="1"
                       class="mt-1" <?= $billingSame ? 'checked' : '' ?>>
                <span>
                    <span class="block text-sm font-medium text-gray-800">Identique à la livraison</span>
                    <span class="block text-xs text-gray-500">Recommandé — décochez pour une adresse de facturation différente.</span>
                </span>
            </label>

            <div id="profile-billing-fields" class="space-y-5" style="<?= $billingSame ? 'display:none' : '' ?>">
                <div>
                    <label for="billing_name" class="block text-sm font-medium text-gray-700 mb-1">Nom complet</label>
                    <input type="text" id="billing_name" name="billing_name"
                           value="<?= $shipVal('billing_name') ?>"
                           class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition
                                  <?= !empty($errors['billing_name']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?>">
                    <?= $viewEngine->partial('partials/field-error', ['field' => 'billing_name', 'errors' => $errors]) ?>
                </div>
                <div>
                    <label for="billing_line1" class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                    <input type="text" id="billing_line1" name="billing_line1"
                           value="<?= $shipVal('billing_line1') ?>"
                           class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition
                                  <?= !empty($errors['billing_line1']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?>">
                    <?= $viewEngine->partial('partials/field-error', ['field' => 'billing_line1', 'errors' => $errors]) ?>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="billing_postal" class="block text-sm font-medium text-gray-700 mb-1">Code postal</label>
                        <input type="text" id="billing_postal" name="billing_postal"
                               value="<?= $shipVal('billing_postal') ?>"
                               class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition
                                      <?= !empty($errors['billing_postal']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?>">
                        <?= $viewEngine->partial('partials/field-error', ['field' => 'billing_postal', 'errors' => $errors]) ?>
                    </div>
                    <div>
                        <label for="billing_city" class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
                        <input type="text" id="billing_city" name="billing_city"
                               value="<?= $shipVal('billing_city') ?>"
                               class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition
                                      <?= !empty($errors['billing_city']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?>">
                        <?= $viewEngine->partial('partials/field-error', ['field' => 'billing_city', 'errors' => $errors]) ?>
                    </div>
                </div>
                <div>
                    <label for="billing_country" class="block text-sm font-medium text-gray-700 mb-1">Pays (code ISO)</label>
                    <input type="text" id="billing_country" name="billing_country" maxlength="2"
                           value="<?= $shipVal('billing_country') ?>"
                           class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition
                                  <?= !empty($errors['billing_country']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?>">
                    <?= $viewEngine->partial('partials/field-error', ['field' => 'billing_country', 'errors' => $errors]) ?>
                </div>
            </div>

            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2.5 rounded-xl transition shadow text-sm">
                Enregistrer les adresses
            </button>
        </form>
    </div>

    <!-- ─── Changement de mot de passe ─── -->
    <div id="password-section" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">

        <h2 class="text-lg font-semibold text-gray-800 mb-6">Changer le mot de passe</h2>

        <form method="POST" action="/profile/password" class="space-y-5">
            <?= $csrf->field() ?>
            <?php if ($passwordTab): ?>
                <?= $viewEngine->partial('partials/validation-errors', ['errors' => $errors]) ?>
            <?php endif ?>

            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe actuel</label>
                <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                       class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition
                              <?= !empty($errors['current_password']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?>"
                       placeholder="••••••••">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'current_password', 'errors' => $errors]) ?>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                    Nouveau mot de passe <span class="text-gray-400 font-normal">(min. 8 caractères)</span>
                </label>
                <input type="password" id="password" name="password" autocomplete="new-password" minlength="8"
                       class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition
                              <?= !empty($errors['password']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?>"
                       placeholder="••••••••">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'password', 'errors' => $errors]) ?>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmer le nouveau mot de passe</label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                       class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition
                              <?= !empty($errors['password_confirmation']) ? 'border-red-400 bg-red-50' : 'border-gray-200' ?>"
                       placeholder="••••••••">
                <?= $viewEngine->partial('partials/field-error', ['field' => 'password_confirmation', 'errors' => $errors]) ?>
            </div>

            <button type="submit"
                    class="bg-gray-800 hover:bg-gray-900 text-white font-semibold px-6 py-2.5 rounded-xl transition shadow text-sm">
                Modifier le mot de passe
            </button>
        </form>
    </div>

</div>
<script>
(function () {
    var same = document.getElementById('profile_billing_same');
    var box = document.getElementById('profile-billing-fields');
    if (!same || !box) return;
    function sync() {
        box.style.display = same.checked ? 'none' : '';
        box.querySelectorAll('input').forEach(function (el) {
            el.required = !same.checked;
        });
    }
    same.addEventListener('change', sync);
    sync();
    if (window.location.hash === '#addresses') {
        document.getElementById('addresses-section')?.scrollIntoView({behavior: 'smooth'});
    }
})();
</script>
