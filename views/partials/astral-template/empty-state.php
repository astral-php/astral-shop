<?php
/**
 * Empty state
 * @var string $title
 * @var string|null $message
 * @var string|null $actionLabel
 * @var string|null $actionHref
 */
?>
<div class="text-center py-16 px-4">
    <p class="text-lg font-semibold text-slate-800 mb-2">
        <?= htmlspecialchars((string) ($title ?? 'Aucun élément'), ENT_QUOTES, 'UTF-8') ?>
    </p>
    <?php if (!empty($message)): ?>
        <p class="text-sm text-slate-500 mb-6 max-w-md mx-auto">
            <?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif ?>
    <?php if (!empty($actionHref) && !empty($actionLabel)): ?>
        <a href="<?= htmlspecialchars((string) $actionHref, ENT_QUOTES, 'UTF-8') ?>"
           class="inline-flex items-center rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 transition">
            <?= htmlspecialchars((string) $actionLabel, ENT_QUOTES, 'UTF-8') ?>
        </a>
    <?php endif ?>
</div>
