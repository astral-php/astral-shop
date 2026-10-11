<!-- Vue : user/show -->
<div class="max-w-xl mx-auto">
    <a href="/users" class="inline-flex items-center gap-1 text-sm text-gray-400 hover:text-gray-600 mb-6 transition">
        &larr; Retour à la liste
    </a>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <div class="flex items-center gap-4 mb-6">
            <!-- Avatar initiales -->
            <div class="w-14 h-14 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xl">
                <?= mb_strtoupper(mb_substr($user->name, 0, 1)) ?>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    <?= htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8') ?>
                </h1>
                <div class="flex items-center gap-2 mt-1">
                    <p class="text-gray-400 text-sm">Utilisateur #<?= $user->id ?></p>
                    <!-- Badge rôle -->
                    <span class="inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full
                        <?= $user->role === 'admin'
                            ? 'bg-amber-100 text-amber-700 border border-amber-200'
                            : 'bg-indigo-50 text-indigo-600 border border-indigo-100' ?>">
                        <?= htmlspecialchars(ucfirst($user->role), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <!-- Badge vérification e-mail -->
                    <?php if ($user->isVerified()): ?>
                        <span class="inline-flex items-center gap-1 text-xs text-green-700 bg-green-50 border border-green-200 px-2 py-0.5 rounded-full">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                            Vérifié
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center text-xs text-gray-400 bg-gray-50 border border-gray-200 px-2 py-0.5 rounded-full">
                            Non vérifié
                        </span>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <dl class="space-y-4 text-sm">
            <div class="flex justify-between py-3 border-b border-gray-50">
                <dt class="text-gray-500 font-medium">E-mail</dt>
                <dd class="text-gray-800"><?= htmlspecialchars($user->email, ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div class="flex justify-between py-3 border-b border-gray-50">
                <dt class="text-gray-500 font-medium">Rôle</dt>
                <dd class="text-gray-800"><?= htmlspecialchars(ucfirst($user->role), ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div class="flex justify-between py-3 border-b border-gray-50">
                <dt class="text-gray-500 font-medium">Membre depuis</dt>
                <dd class="text-gray-800"><?= htmlspecialchars($user->created_at, ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
        </dl>

        <!-- Actions admin uniquement -->
        <?php if ($auth->is(\Core\Auth\Role::ADMIN) && $user->id !== $auth->id()): ?>
            <form method="POST" action="/users/<?= $user->id ?>/delete" class="mt-8 text-right"
                  onsubmit="return confirm('Supprimer définitivement cet utilisateur ?')">
                <?= $csrf->field() ?>
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-red-50 hover:bg-red-100 text-red-600 font-semibold px-4 py-2 rounded-xl transition text-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Supprimer le compte
                </button>
            </form>
        <?php endif ?>

    </div>
</div>
