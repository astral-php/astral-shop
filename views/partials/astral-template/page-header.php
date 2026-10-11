<?php
/**
 * Page header
 * @var string $heading
 * @var string|null $subtitle
 * @var string|null $actionLabel
 * @var string|null $actionHref
 */
?>
<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">
            <?= htmlspecialchars((string) ($heading ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </h2>
        <?php if (!empty($subtitle)): ?>
            <p class="mt-1 text-sm text-slate-500">
                <?= htmlspecialchars((string) $subtitle, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif ?>
    </div>
    <?php if (!empty($actionHref) && !empty($actionLabel)): ?>
        <a href="<?= htmlspecialchars((string) $actionHref, ENT_QUOTES, 'UTF-8') ?>"
           class="inline-flex shrink-0 items-center rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 transition">
            <?= htmlspecialchars((string) $actionLabel, ENT_QUOTES, 'UTF-8') ?>
        </a>
    <?php endif ?>
</div>
