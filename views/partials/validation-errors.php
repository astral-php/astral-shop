<?php

declare(strict_types=1);

$errors = $errors ?? [];
if (empty($errors)) {
    return;
}
$flat = [];
foreach ($errors as $messages) {
    foreach ((array) $messages as $msg) {
        $flat[] = $msg;
    }
}
if (empty($flat)) {
    return;
}
?>
<div class="form-errors">
    <p style="margin:0;font-weight:600">Veuillez corriger les erreurs suivantes :</p>
    <ul>
        <?php foreach ($flat as $msg): ?>
            <li><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ul>
</div>
