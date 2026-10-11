<?php

declare(strict_types=1);

/**
 * Partial : messages flash (success, error).
 * Utilise $session (partagée globalement). Tailwind CSS.
 */
if ($session->hasFlash('success')): ?>
    <div class="mb-6 flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 text-sm rounded-xl px-4 py-3">
        <svg class="w-4 h-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
        <?= htmlspecialchars((string) $session->getFlash('success'), ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>
<?php if ($session->hasFlash('error')): ?>
    <div class="mb-6 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">
        <svg class="w-4 h-4 shrink-0 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        <?= htmlspecialchars((string) $session->getFlash('error'), ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif;
