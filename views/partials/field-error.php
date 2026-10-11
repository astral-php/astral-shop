<?php

declare(strict_types=1);

$field   = $field ?? '';
$errors  = $errors ?? [];
$message = $errors[$field][0] ?? null;
if ($message === null || $message === '') {
    return;
}
?>
<p class="field-error"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
