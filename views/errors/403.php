<!-- Vue : errors/403 -->
<div class="text-center py-24">
    <p class="text-8xl font-black text-red-100 mb-4">403</p>
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Accès refusé</h1>
    <p class="text-gray-400 mb-8">
        <?= htmlspecialchars($message ?? 'Vous n\'êtes pas autorisé à accéder à cette ressource.', ENT_QUOTES, 'UTF-8') ?>
    </p>
    <?php if (!empty($debug) && isset($exception) && $exception instanceof \Throwable): ?>
        <p class="text-xs font-mono text-gray-400 mb-8">
            <?= htmlspecialchars(get_class($exception), ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif ?>
    <a href="/" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-3 rounded-xl transition shadow">
        &larr; Retour à l'accueil
    </a>
</div>
