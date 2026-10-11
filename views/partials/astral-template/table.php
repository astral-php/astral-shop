<?php
/**
 * Table simple
 * @var list<string> $headers
 * @var list<list<string>> $rows
 */
$headers = $headers ?? [];
$rows    = $rows ?? [];
?>
<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full text-sm text-left">
        <thead class="bg-slate-50 text-slate-600">
            <tr>
                <?php foreach ($headers as $header): ?>
                    <th class="px-4 py-3 font-semibold">
                        <?= htmlspecialchars((string) $header, ENT_QUOTES, 'UTF-8') ?>
                    </th>
                <?php endforeach ?>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if ($rows === []): ?>
                <tr>
                    <td colspan="<?= max(1, count($headers)) ?>" class="px-4 py-8 text-center text-slate-400">
                        Aucune donnée
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr class="hover:bg-slate-50/80">
                        <?php foreach ($row as $cell): ?>
                            <td class="px-4 py-3 text-slate-700">
                                <?= htmlspecialchars((string) $cell, ENT_QUOTES, 'UTF-8') ?>
                            </td>
                        <?php endforeach ?>
                    </tr>
                <?php endforeach ?>
            <?php endif ?>
        </tbody>
    </table>
</div>
