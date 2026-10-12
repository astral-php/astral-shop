<?php
/**
 * Fil d’Ariane tunnel commande.
 *
 * @var string $checkoutStep identification|livraison|validation|confirmation
 * @var bool   $checkoutBusy  spinner sur l’étape active (ex. envoi formulaire Validation)
 */
$checkoutStep = (string) ($checkoutStep ?? 'identification');
$checkoutBusy = !empty($checkoutBusy);

$steps = [
    'identification' => 'Identification',
    'livraison'      => 'Livraison',
    'validation'     => 'Validation',
    'confirmation'   => 'Confirmation',
];

$orderKeys = array_keys($steps);
$currentIndex = array_search($checkoutStep, $orderKeys, true);
if ($currentIndex === false) {
    $currentIndex = 0;
}
// Dernière étape atteinte = tunnel terminé : toutes les étapes en vert (✓).
$tunnelComplete = $currentIndex === count($orderKeys) - 1;
?>
<nav class="checkout-steps<?= $tunnelComplete ? ' is-complete' : '' ?>" aria-label="Étapes de commande">
    <ol class="checkout-steps__list">
        <?php foreach ($steps as $key => $label): ?>
            <?php
            $i = array_search($key, $orderKeys, true);
            $done = $tunnelComplete || ($i !== false && $i < $currentIndex);
            $current = !$tunnelComplete && $i === $currentIndex;
            $stateClass = $done ? 'is-done' : ($current ? 'is-current' : 'is-todo');
            ?>
            <li class="checkout-steps__item <?= $stateClass ?>">
                <span class="checkout-steps__marker" aria-hidden="true">
                    <?php if ($done): ?>
                        ✓
                    <?php elseif ($current): ?>
                        ●
                    <?php else: ?>
                        ○
                    <?php endif ?>
                </span>
                <span class="checkout-steps__label">
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                    <?php if ($current && $checkoutBusy): ?>
                        <span class="checkout-steps__spinner" aria-hidden="true"></span>
                        <span class="visually-hidden">Traitement en cours…</span>
                    <?php endif ?>
                </span>
                <?php if ($i < count($orderKeys) - 1): ?>
                    <span class="checkout-steps__sep" aria-hidden="true">───</span>
                <?php endif ?>
            </li>
        <?php endforeach ?>
    </ol>
</nav>
