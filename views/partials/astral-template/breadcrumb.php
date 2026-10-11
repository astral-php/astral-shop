<?php
/**
 * Breadcrumb
 * @var list<array{label: string, href?: string}> $items
 */
$items = $items ?? [];
?>
<nav aria-label="Fil d'Ariane" class="text-sm text-slate-500 mb-4">
    <ol class="flex flex-wrap items-center gap-1">
        <?php foreach ($items as $i => $item): ?>
            <?php if ($i > 0): ?>
                <li class="text-slate-300 px-1" aria-hidden="true">/</li>
            <?php endif ?>
            <li>
                <?php if (!empty($item['href'])): ?>
                    <a href="<?= htmlspecialchars((string) $item['href'], ENT_QUOTES, 'UTF-8') ?>"
                       class="hover:text-indigo-600 transition">
                        <?= htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php else: ?>
                    <span class="text-slate-700 font-medium">
                        <?= htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                <?php endif ?>
            </li>
        <?php endforeach ?>
    </ol>
</nav>
