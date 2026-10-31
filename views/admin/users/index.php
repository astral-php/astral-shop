<?php
/** @var list<\App\Models\User> $users */
/** @var list<string> $roles */
/** @var int $adminCount */
/** @var int $clientCount */
/** @var string $filter_role */

$filters = [
    ''      => 'Tous (' . ((int) $adminCount + (int) $clientCount) . ')',
    'admin' => 'Gestionnaires (' . (int) $adminCount . ')',
    'user'  => 'Clients (' . (int) $clientCount . ')',
];
?>
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <p class="text-sm text-slate-500">Séparez les gestionnaires (back-office) des clients boutique.</p>
    </div>
    <a href="/admin/users/create"
       class="inline-flex items-center rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 transition">
        + Nouveau compte
    </a>
</div>

<div class="flex flex-wrap gap-2 mb-6">
    <?php foreach ($filters as $key => $label): ?>
        <a href="<?= $key === '' ? '/admin/users' : '/admin/users?role=' . rawurlencode($key) ?>"
           class="text-sm rounded-full px-3 py-1.5 border <?= $filter_role === $key
               ? 'bg-indigo-50 border-indigo-200 text-indigo-700 font-semibold'
               : 'bg-white border-slate-200 text-slate-600 hover:border-indigo-200' ?>">
            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
        </a>
    <?php endforeach ?>
</div>

<?php if ($adminCount === 1): ?>
    <div class="mb-6 flex items-start gap-3 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-xl px-4 py-3">
        <span>
            Un seul gestionnaire : il ne peut pas être rétrogradé tant qu'un autre n'a pas été créé.
        </span>
    </div>
<?php endif ?>

<?php if ($users === []): ?>
    <div class="bg-white rounded-2xl border border-dashed border-slate-200 p-12 text-center text-slate-400">
        Aucun compte dans ce filtre.
    </div>
<?php else: ?>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Compte</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">E-mail</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Type</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Changer</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Inscrit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
            <?php foreach ($users as $user): ?>
                <?php
                $isSelf = $user->id === $auth->id();
                $isLastAdmin = $user->role === \Core\Auth\Role::Admin->value && $adminCount <= 1;
                $isDisabled = $isSelf || $isLastAdmin;
                $typeLabel = $user->role === \Core\Auth\Role::Admin->value ? 'Gestionnaire' : 'Client';
                $badgeClass = $user->role === \Core\Auth\Role::Admin->value
                    ? 'bg-amber-100 text-amber-800 border border-amber-200'
                    : 'bg-slate-100 text-slate-600 border border-slate-200';
                ?>
                <tr class="hover:bg-slate-50/80 <?= $isSelf ? 'bg-indigo-50/40' : '' ?>">
                    <td class="px-5 py-4">
                        <p class="font-medium text-slate-800 text-sm">
                            <?= htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($isSelf): ?>
                                <span class="ml-1 text-xs text-indigo-400 font-normal">(vous)</span>
                            <?php endif ?>
                        </p>
                        <p class="text-xs text-slate-400 font-mono">#<?= (int) $user->id ?></p>
                    </td>
                    <td class="px-5 py-4 text-sm text-slate-500">
                        <?= htmlspecialchars($user->email, ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="px-5 py-4">
                        <span class="inline-flex items-center text-xs font-semibold px-2.5 py-0.5 rounded-full <?= $badgeClass ?>">
                            <?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <?php if ($isDisabled): ?>
                            <span class="text-xs text-slate-400 italic">
                                <?= $isSelf ? 'Votre propre rôle' : 'Dernier gestionnaire' ?>
                            </span>
                        <?php else: ?>
                            <form method="POST" action="/admin/users/<?= (int) $user->id ?>/role" class="flex items-center gap-2">
                                <?= $csrf->field() ?>
                                <?php if ($filter_role !== ''): ?>
                                    <input type="hidden" name="filter_role" value="<?= htmlspecialchars($filter_role, ENT_QUOTES, 'UTF-8') ?>">
                                <?php endif ?>
                                <select name="role"
                                        class="text-sm border border-slate-200 rounded-lg px-2 py-1.5 bg-white text-slate-700">
                                    <option value="admin" <?= $user->role === 'admin' ? 'selected' : '' ?>>Gestionnaire</option>
                                    <option value="user" <?= $user->role === 'user' ? 'selected' : '' ?>>Client</option>
                                </select>
                                <button type="submit"
                                        class="text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg">
                                    OK
                                </button>
                            </form>
                        <?php endif ?>
                    </td>
                    <td class="px-5 py-4 text-xs text-slate-400">
                        <?= htmlspecialchars($user->created_at, ENT_QUOTES, 'UTF-8') ?>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php endif ?>
