<!-- Vue : user/index -->
<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">
            <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
        </h1>
        <?php if ($total > 0): ?>
            <p class="text-sm text-gray-400 mt-1"><?= $total ?> utilisateur<?= $total > 1 ? 's' : '' ?></p>
        <?php endif ?>
    </div>

    <?php if ($auth->is(\Core\Auth\Role::ADMIN)): ?>
        <a href="/users/create"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2 rounded-xl transition shadow text-sm">
            + Nouvel utilisateur
        </a>
    <?php endif ?>
</div>

<?php if (empty($users)): ?>
    <div class="bg-white rounded-2xl border border-dashed border-gray-200 p-16 text-center text-gray-400">
        Aucun utilisateur enregistré.
        <?php if ($auth->is(\Core\Auth\Role::ADMIN)): ?>
            <a href="/users/create" class="text-indigo-500 hover:underline ml-1">Créer le premier</a>.
        <?php endif ?>
    </div>
<?php else: ?>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">E-mail</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Rôle</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Créé le</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($users as $user): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-sm text-gray-400 font-mono">#<?= $user->id ?></td>
                    <td class="px-6 py-4 font-medium text-gray-800">
                        <?= htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8') ?>
                        <?php if ($user->id === $auth->id()): ?>
                            <span class="ml-1 text-xs text-indigo-400">(vous)</span>
                        <?php endif ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        <?= htmlspecialchars($user->email, ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full
                            <?= $user->role === 'admin'
                                ? 'bg-amber-100 text-amber-700'
                                : 'bg-indigo-50 text-indigo-600' ?>">
                            <?= htmlspecialchars(ucfirst($user->role), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-400">
                        <?= htmlspecialchars($user->created_at, ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="/users/<?= $user->id ?>"
                           class="text-indigo-600 hover:text-indigo-800 text-sm font-medium transition">
                            Voir &rarr;
                        </a>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>

    <?= $viewEngine->partial('partials/pagination', ['current' => $current, 'pages' => $pages, 'baseUrl' => '/users']) ?>
<?php endif ?>
