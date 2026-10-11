<?php

declare(strict_types=1);

/**
 * Partial : liens de pagination.
 * Variables : $current (int), $pages (int), $baseUrl (string), $mode (string, optionnel).
 * Modes : 'simple' (Précédent / Suivant), 'numbers' (Précédent | 1 2 3 | Suivant), 'elastic' (1 … 5 6 7 … 42).
 * Défaut : 'numbers'. Tailwind CSS.
 */
$current = (int) ($current ?? 1);
$pages   = (int) ($pages ?? 1);
$baseUrl = $baseUrl ?? '?';
$mode    = $mode ?? 'numbers';
$query   = str_contains($baseUrl, '?') ? '&' : '?';

if ($pages <= 1) {
    return;
}

$url = fn(int $page) => $baseUrl . $query . 'page=' . $page;
$linkClass = 'px-3 py-1.5 rounded-lg text-sm font-medium transition';
$activeClass = 'bg-indigo-600 text-white shadow';
$inactiveClass = 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200';
$arrowClass = 'px-3 py-1.5 rounded-lg bg-white border border-gray-200 hover:bg-gray-50 transition font-medium';
?>
<div class="mt-6 flex items-center justify-between text-sm text-gray-500">
    <span>Page <?= $current ?> sur <?= $pages ?></span>
    <div class="flex gap-1">
        <?php if ($current > 1): ?>
            <a href="<?= htmlspecialchars($url($current - 1), ENT_QUOTES, 'UTF-8') ?>"
               class="<?= $arrowClass ?>">&larr;</a>
        <?php endif; ?>

        <?php if ($mode === 'simple'): ?>
            <?php /* Mode simple : uniquement Précédent / Suivant (déjà affiché au-dessus) */ ?>
        <?php elseif ($mode === 'numbers'): ?>
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a href="<?= htmlspecialchars($url($i), ENT_QUOTES, 'UTF-8') ?>"
                   class="<?= $linkClass ?> <?= $i === $current ? $activeClass : $inactiveClass ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        <?php elseif ($mode === 'elastic'): ?>
            <?php
            $items = [];
            if ($pages <= 7) {
                $items = range(1, $pages);
            } else {
                $items[] = 1;
                $windowStart = max(2, $current - 2);
                $windowEnd   = min($pages - 1, $current + 2);
                if ($windowStart > 2) {
                    $items[] = '…';
                }
                for ($i = $windowStart; $i <= $windowEnd; $i++) {
                    $items[] = $i;
                }
                if ($windowEnd < $pages - 1) {
                    $items[] = '…';
                }
                if ($pages > 1) {
                    $items[] = $pages;
                }
            }
            foreach ($items as $item):
                if ($item === '…'): ?>
                    <span class="px-2 py-1.5 text-gray-400">…</span>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($url($item), ENT_QUOTES, 'UTF-8') ?>"
                       class="<?= $linkClass ?> <?= $item === $current ? $activeClass : $inactiveClass ?>">
                        <?= $item ?>
                    </a>
                <?php endif;
            endforeach; ?>
        <?php endif; ?>

        <?php if ($current < $pages): ?>
            <a href="<?= htmlspecialchars($url($current + 1), ENT_QUOTES, 'UTF-8') ?>"
               class="<?= $arrowClass ?>">&rarr;</a>
        <?php endif; ?>
    </div>
</div>
