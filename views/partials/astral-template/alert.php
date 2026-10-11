<?php
/**
 * Alert — type: info|success|warning|error
 * @var string $message
 * @var string $type
 */
$type = $type ?? 'info';
$styles = match ($type) {
    'success' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
    'warning' => 'bg-amber-50 text-amber-900 border-amber-200',
    'error'   => 'bg-red-50 text-red-800 border-red-200',
    default   => 'bg-sky-50 text-sky-900 border-sky-200',
};
?>
<div class="rounded-lg border px-4 py-3 text-sm <?= $styles ?>" role="alert">
    <?= htmlspecialchars((string) ($message ?? ''), ENT_QUOTES, 'UTF-8') ?>
</div>
