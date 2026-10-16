<?php
/** @var string $title */
/** @var array<string, mixed> $shop */
/** @var array<string, mixed> $legal */
$store = (string) ($shop['store_name'] ?? 'Astral Shop');
?>
<section class="admin-panel legal-page" style="max-width:46rem">
    <div class="admin-panel__head">
        <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
    </div>
    <p class="legal-page__disclaimer" style="font-size:0.85rem;color:var(--muted);margin:0 0 1.25rem;line-height:1.5;padding:0.75rem 0.9rem;border-left:3px solid var(--border, #cbd5e1);background:var(--surface-muted, rgba(15,23,42,0.03))">
        <strong style="color:var(--text)">Cadre légal — responsabilité du marchand.</strong>
        Ce document est un <strong style="color:var(--text)">modèle</strong> fourni avec Astral Shop :
        il doit être personnalisé (identité via <code>SHOP_LEGAL_*</code>, rédaction adaptée à l’activité)
        avant toute ouverture au public. Ce n’est <strong style="color:var(--text)">pas</strong> un conseil juridique.
        Boutique affichée : <strong style="color:var(--text)"><?= htmlspecialchars($store, ENT_QUOTES, 'UTF-8') ?></strong>.
    </p>
    <div class="legal-page__body" style="line-height:1.65;color:var(--muted);font-size:0.95rem">
