<!-- Vue : errors/500 -->
<div class="text-center py-16 max-w-3xl mx-auto px-4">
    <p class="text-8xl font-black text-orange-100 mb-4">500</p>
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Erreur serveur</h1>
    <p class="text-gray-400 mb-8">
        <?= htmlspecialchars($message ?? 'Une erreur interne est survenue. Veuillez réessayer.', ENT_QUOTES, 'UTF-8') ?>
    </p>

    <?php if (!empty($debug) && isset($exception) && $exception instanceof \Throwable): ?>
        <div class="text-left bg-gray-900 border border-gray-700 rounded-xl overflow-hidden mb-6 shadow-lg">
            <div class="px-5 py-4 border-b border-gray-800">
                <span class="inline-block bg-purple-900 text-purple-200 text-xs font-mono px-2 py-0.5 rounded mb-2">
                    <?= htmlspecialchars(get_class($exception), ENT_QUOTES, 'UTF-8') ?>
                </span>
                <p class="text-red-300 font-mono text-sm leading-relaxed">
                    <?= htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
            <div class="px-5 py-3 border-b border-gray-800 text-xs text-gray-500 font-mono">
                <span class="text-blue-400"><?= htmlspecialchars($exception->getFile(), ENT_QUOTES, 'UTF-8') ?></span>
                <span>:</span>
                <span class="text-yellow-400"><?= (int) $exception->getLine() ?></span>
            </div>
            <div class="px-5 py-4">
                <p class="text-xs text-gray-500 uppercase tracking-wider mb-3 font-semibold">Stack trace</p>
                <pre class="text-xs font-mono text-gray-400 overflow-auto leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8') ?></pre>
            </div>
            <?php if ($exception->getPrevious() !== null): ?>
                <div class="px-5 py-4 border-t border-gray-800 bg-gray-950">
                    <p class="text-xs text-gray-500 uppercase tracking-wider mb-2 font-semibold">Caused by</p>
                    <span class="text-purple-400 font-mono text-xs">
                        <?= htmlspecialchars(get_class($exception->getPrevious()), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <p class="text-gray-400 font-mono text-xs mt-1">
                        <?= htmlspecialchars($exception->getPrevious()->getMessage(), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
            <?php endif ?>
        </div>
        <p class="text-center text-xs text-gray-400 mb-8">
            Visible uniquement si <code class="bg-gray-100 px-1 rounded">APP_DEBUG=true</code>
        </p>
    <?php endif ?>

    <a href="/" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-3 rounded-xl transition shadow">
        &larr; Retour à l'accueil
    </a>
</div>
