<?php
/** @var array<string, mixed> $legal */
$company  = htmlspecialchars((string) ($legal['company_name'] ?? ''), ENT_QUOTES, 'UTF-8');
$address  = nl2br(htmlspecialchars((string) ($legal['address'] ?? ''), ENT_QUOTES, 'UTF-8'));
$email    = htmlspecialchars((string) ($legal['email'] ?? ''), ENT_QUOTES, 'UTF-8');
$phone    = htmlspecialchars((string) ($legal['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
$siret    = htmlspecialchars((string) ($legal['siret'] ?? ''), ENT_QUOTES, 'UTF-8');
$rcs      = htmlspecialchars((string) ($legal['rcs'] ?? ''), ENT_QUOTES, 'UTF-8');
$vat      = htmlspecialchars((string) ($legal['vat'] ?? ''), ENT_QUOTES, 'UTF-8');
$director = htmlspecialchars((string) ($legal['director'] ?? ''), ENT_QUOTES, 'UTF-8');
$host     = nl2br(htmlspecialchars((string) ($legal['host'] ?? ''), ENT_QUOTES, 'UTF-8'));
require __DIR__ . '/_layout_start.php';
?>
    <h2 style="color:var(--text);font-size:1.1rem">1. Éditeur du site</h2>
    <p>
        <strong style="color:var(--text)"><?= $company !== '' ? $company : '[Raison sociale — obligatoire]' ?></strong><br>
        <?= $address !== '' ? $address : '[Adresse du siège — obligatoire]' ?><br>
        <strong style="color:var(--text)">SIRET :</strong>
        <?= $siret !== '' && $siret !== '[SIRET à renseigner]'
            ? $siret
            : '<span style="color:var(--muted)">[SIRET obligatoire pour vendre en tant que professionnel — à renseigner dans SHOP_LEGAL_SIRET]</span>' ?><br>
        <?php if ($rcs !== ''): ?>RCS : <?= $rcs ?><br><?php endif ?>
        <?php if ($vat !== ''): ?>N° TVA intracommunautaire : <?= $vat ?><br><?php else: ?>
        <span style="color:var(--muted);font-size:0.9rem">N° TVA : [à renseigner si assujetti — SHOP_LEGAL_VAT]</span><br>
        <?php endif ?>
        <?php if ($email !== ''): ?>E-mail : <a href="mailto:<?= $email ?>"><?= $email ?></a><br><?php endif ?>
        <?php if ($phone !== ''): ?>Téléphone : <?= $phone ?><br><?php endif ?>
    </p>
    <p style="font-size:0.9rem">Pour une activité commerciale, l’identification de l’éditeur (raison sociale, adresse, SIRET)
       doit être renseignée par le marchand avant mise en production
       (<code>SHOP_LEGAL_*</code> dans <code>.env</code>). Voir le guide « Ouvrir une boutique ».</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">2. Directeur de la publication</h2>
    <p><?= $director !== '' ? $director : '[Directeur de la publication]' ?></p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">3. Hébergement</h2>
    <p><?= $host !== '' ? $host : '[Hébergeur — nom, adresse, contact — obligatoire]' ?></p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">4. Propriété intellectuelle</h2>
    <p>L’ensemble des contenus du site (textes, images, logos, marques, structure) est protégé par le droit de la propriété
       intellectuelle. Toute reproduction, représentation ou exploitation non autorisée est interdite.</p>

    <h2 style="color:var(--text);font-size:1.1rem;margin-top:1.25rem">5. Documents connexes</h2>
    <p>
        <a href="/cgv">Conditions générales de vente</a> ·
        <a href="/retours">Politique de retour</a> ·
        <a href="/confidentialite">Confidentialité</a>
    </p>
<?php require __DIR__ . '/_layout_end.php'; ?>
